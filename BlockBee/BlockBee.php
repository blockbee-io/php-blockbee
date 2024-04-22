<?php

namespace BlockBee;

use BlockBee\Exceptions\ApiException;
use Exception;

class BlockBee
{
    private $valid_coins     = [];
    private $own_address     = null;
    private $payment_address = null;
    private $callback_url    = null;
    private $coin            = null;
    private $bb_params       = [];
    private $parameters      = [];
    private $api_key         = null;

    /**
     * @throws Exception
     */
    public function __construct($coin, $own_address, $callback_url, $parameters = [], $bb_params = [], $api_key = '')
    {
        if (empty($coin)) {
            throw new Exception('Please provide a valid coin/ticker.');
        }

        if (empty($callback_url)) {
            throw new Exception('Please provide a valid callback url.');
        }

        $this->valid_coins = BlockBee::get_supported_coins($api_key);

        if (!in_array($coin, $this->valid_coins)) {
            $vc = print_r($this->valid_coins, true);
            throw new Exception("Unsupported Coin: {$coin}, Valid options are: {$vc}");
        }

        $this->own_address  = $own_address;
        $this->callback_url = $callback_url;
        $this->coin         = $coin;
        $this->bb_params    = $bb_params;
        $this->parameters   = $parameters;
        $this->api_key      = $api_key;
    }

    /**
     * @throws ApiException
     */
    public static function get_supported_coins($api_key = '')
    {
        $info = BlockBee::get_info(null, true);

        unset($info['fee_tiers']);

        $coins = [];

        foreach ($info as $chain => $data) {
            $is_base_coin = in_array('ticker', array_keys($data));
            if ($is_base_coin) {
                $coins[] = $chain;
                continue;
            }

            $base_ticker = "{$chain}_";
            foreach ($data as $token => $subdata) {
                $coins[] = $base_ticker . $token;
            }
        }

        return $coins;
    }

    /**
     * @throws Exception
     */
    public function get_address()
    {
        $callback_url = $this->callback_url;

        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'callback' => $callback_url,
            'address'  => $this->own_address,
            'apikey'   => $this->api_key,
        ], $this->bb_params);

        if (empty($this->own_address)) {
            unset($bb_params['address']);
        }

        $response = Requests::_request_get($this->coin, 'create', $bb_params);

        $this->payment_address = $response->address_in;

        return $response->address_in;
    }

    public function check_logs()
    {
        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $params = [
            'callback' => $callback_url,
            'apikey'   => $this->api_key,
        ];

        $response = Requests::_request_get($this->coin, 'logs', $params);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    /**
     * @throws ApiException
     */
    public function get_qrcode($value = false, $size = false)
    {
        $address = $this->payment_address;

        if (empty($address)) {
            $address = $this->get_address();
        }

        $params = [
            'address' => $address
        ];

        if ($value) {
            $params['value'] = $value;
        }

        if ($size) {
            $params['size'] = $size;
        }

        $response = Requests::_request_get($this->coin, 'qrcode', $params);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    /**
     * @throws ApiException
     */
    public static function get_info($coin = null, $assoc = false, $api_key = '')
    {
        $params = [];

        if (empty($coin)) {
            $params['prices'] = '0';
        }

        return Requests::_request_get($coin, 'info', $params, $assoc);
    }

    /**
     * @throws ApiException
     */
    public static function get_estimate($coin, $addresses = 1, $priority = 'default', $api_key = '')
    {
        $params = [
            'addresses' => $addresses,
            'priority'  => $priority
        ];

        return Requests::_request_get($coin, 'estimate', $params);
    }

    /**
     * @throws ApiException
     */
    public static function get_convert($coin, $value, $from, $api_key = '')
    {
        return Requests::_request_get($coin,'convert', [
            'value' => $value,
            'from'  => $from
        ]);
    }

    /**
     * @throws ApiException
     * @throws Exception
     */
    public static function create_payout($coin, $requests, $api_key, $process = false)  {
        if (empty($requests)) {
            throw new Exception('No requests provided');
        }

        $body['outputs'] = $requests;

        $endpoint = 'payout/request/bulk';

        if ($process) {
            $endpoint .= '/process';
        }

        return Requests::_request_post($coin, $endpoint, $api_key, $body, true);
    }

    /**
     * @throws ApiException
     */
    public static function list_payouts ($coin, $status, $page, $api_key, $requests = false) {
        $params = [
            'apikey' => $api_key,
        ];

        if ($status) {
            $params['status'] = $status;
        }

        if ($page) {
            $params['p'] = $page;
        }

        $endpoint = 'payout/list';

        if ($requests) {
            $endpoint = 'payout/request/list';
        }

        return Requests::_request_get($coin, $endpoint, $params);
    }

    /**
     * @throws ApiException
     */
    public static function get_payout_wallet($coin, $api_key, $balance = false) {
        $params = [
            'apikey' => $api_key,
        ];

        $wallet = Requests::_request_get($coin, 'payout/address', $params);

        $output = [];

        if ($wallet->status === 'success') {
            $output['address'] = $wallet->address;

            if ($balance) {
                $wallet = Requests::_request_get($coin, 'payout/balance', $params);

                if ($wallet->status === 'success') {
                    $output['balance'] = $wallet->balance;
                }
            }
        }

        return (object) $output;
    }

    /**
     * @throws ApiException
     */
    public static function create_payout_by_ids($api_key, $ids = []) {
        if (empty($ids)) {
            throw new Exception('Please provide the Payout Request(s) ID(s)');
        }

        return Requests::_request_post('', 'payout/create', $api_key, [
            'request_ids' => implode(',', $ids)
        ]);
    }

    /**
     * @throws ApiException
     */
    public static function process_payout($api_key, $id = '') {
        if (empty($id)) {
            throw new Exception('Please provide the Payout ID');
        }

        return Requests::_request_post('', 'payout/process', $api_key, [
            'payout_id' => $id
        ]);
    }

    /**
     * @throws ApiException
     */
    public static function check_payout_status($api_key, $id) {
        if (empty($id)) {
            throw new Exception('Please provide the Payout ID');
        }

        return Requests::_request_post('', 'payout/status', $api_key, [
            'payout_id' => $id
        ]);
    }

    public static function process_callback($_get)
    {
        $params = [
            'address_in'           => $_get['address_in'],
            'address_out'          => $_get['address_out'],
            'txid_in'              => $_get['txid_in'],
            'txid_out'             => isset($_get['txid_out']) ? $_get['txid_out'] : null,
            'confirmations'        => $_get['confirmations'],
            'value'                => $_get['value'],
            'value_coin'           => $_get['value_coin'],
            'value_forwarded'      => isset($_get['value_forwarded']) ? $_get['value_forwarded'] : null,
            'value_forwarded_coin' => isset($_get['value_forwarded_coin']) ? $_get['value_forwarded_coin'] : null,
            'coin'                 => $_get['coin'],
            'pending'              => isset($_get['pending']) ? $_get['pending'] : false,
        ];

        foreach ($_get as $k => $v) {
            if (isset($params[$k])) {
                continue;
            }
            $params[$k] = $_get[$k];
        }

        return $params;
    }
}
