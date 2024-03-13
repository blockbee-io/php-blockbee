<?php

namespace BlockBee;

use Exception;

class BlockBee
{
    private static $base_url = 'https://api.blockbee.io';
    private $valid_coins     = [];
    private $own_address     = null;
    private $payment_address = null;
    private $callback_url    = null;
    private $coin            = null;
    private $bb_params       = [];
    private $parameters      = [];
    private $api_key         = null;

    public function __construct($coin, $own_address, $callback_url, $parameters = [], $bb_params = [], $api_key = '')
    {
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

    public static function get_supported_coins($api_key)
    {
        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $info = BlockBee::get_info(null, true, $api_key);

        if (empty($info)) {
            return null;
        }

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

    public function get_address()
    {
        if (empty($this->coin) || empty($this->callback_url)) {
            return null;
        }

        $callback_url = $this->callback_url;

        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'callback' => $callback_url,
            'address'  => $this->own_address
        ], $this->bb_params);

        if (empty($this->own_address)) {
            unset($bb_params['address']);
        }

        $response = BlockBee::_request_get($this->coin, 'create', $this->api_key, $bb_params);

        if ($response->status === 'success') {
            $this->payment_address = $response->address_in;
            return $response->address_in;
        }

        return null;
    }

    public function check_logs()
    {
        if (empty($this->coin) || empty($this->callback_url)) {
            return null;
        }

        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $params = [
            'callback' => $callback_url
        ];

        $response = BlockBee::_request_get($this->coin, 'logs', $this->api_key, $params);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public function get_qrcode($value = false, $size = false)
    {
        if (empty($this->coin)) {
            return null;
        }

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

        $response = BlockBee::_request_get($this->coin, 'qrcode', $this->api_key, $params);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function get_info($coin = null, $assoc = false, $api_key = '')
    {
        $params = [];

        if (empty($coin)) {
            $params['prices'] = '0';
        }

        $response = BlockBee::_request_get($coin, 'info',  $api_key, $params, $assoc);

        if (empty($coin) || $response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function get_estimate($coin, $addresses = 1, $priority = 'default', $api_key = '')
    {
        $response = BlockBee::_request_get($coin, 'estimate', $api_key, [
            'addresses' => $addresses,
            'priority'  => $priority,
        ]);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function get_convert($coin, $value, $from, $api_key)
    {
        $response = BlockBee::_request_get($coin,'convert', $api_key, [
            'value' => $value,
            'from'  => $from
        ]);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function create_payout($coin, $requests, $api_key, $process = false)  {
        if (empty($requests)) {
            throw new Exception('No requests provided');
        }

        $body['outputs'] = $requests;

        $endpoint = 'payout/request/bulk';

        if ($process) {
            $endpoint .= '/process';
        }

        $response = BlockBee::_request_post($coin, $endpoint, $api_key, $body, true);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function list_payouts ($coin, $status, $page, $api_key, $requests = false) {
        $params = [];

        if ($status) {
            $params['status'] = $status;
        }

        if ($page) {
            $params['page'] = $page;
        }

        $endpoint = 'payout/list';

        if ($requests) {
            $endpoint = 'payout/request/list';
        }

        $response = BlockBee::_request_get($coin, $endpoint, $api_key, $params);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function get_payout_wallet($coin, $api_key, $balance = false) {
        $wallet = BlockBee::_request_get($coin, 'payout/address', $api_key);

        $output = [];

        if ($wallet->status === 'success') {
            $output['address'] = $wallet->address;

            if ($balance) {
                $wallet = BlockBee::_request_get($coin, 'payout/balance', $api_key);

                if ($wallet->status === 'success') {
                    $output['balance'] = $wallet->balance;
                }
            }

            return (object) $output;
        }

        return null;
    }

    public static function create_payout_by_ids($api_key, $ids = []) {
        if (empty($ids)) {
            throw new Exception('Please provide the Payout Request(s) ID(s)');
        }

        $response = BlockBee::_request_post('', 'payout/create', $api_key, [
            'request_ids' => implode(',', $ids)
        ]);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function process_payout($api_key, $id = '') {
        if (empty($id)) {
            throw new Exception('Please provide the Payout ID');
        }

        $response = BlockBee::_request_post('', 'payout/process', $api_key, [
            'payout_id' => $id
        ]);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
    }

    public static function check_payout_status($api_key, $id) {
        if (empty($id)) {
            throw new Exception('Please provide the Payout ID');
        }

        $response = BlockBee::_request_post('', 'payout/status', $api_key, [
            'payout_id' => $id
        ]);

        if ($response->status === 'success') {
            return $response;
        }

        return null;
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

    private static function _request_get($coin, $endpoint, $api_key, $params = [], $assoc = false)
    {
        $base_url = BlockBee::$base_url;
        $coin     = str_replace('_', '/', (string) $coin);

        if (empty($api_key) && $endpoint !== 'info' && !$coin) {
            throw new Exception('API Key is Empty');
        }

        $params['apikey'] = $api_key;

        if (!empty($params)) {
            $data = http_build_query($params);
        }

        $url = !empty($coin) ? "{$base_url}/{$coin}/{$endpoint}/" : "{$base_url}/{$endpoint}/";

        if (!empty($data)) {
            $url .= "?{$data}";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, $assoc);
    }

    private static function _request_post($coin, $endpoint, $api_key, $body = [], $isJson = false, $assoc = false )
    {
        $base_url = BlockBee::$base_url;
        $coin = str_replace('_', '/', (string)$coin);
        $url = !empty($coin) ? "{$base_url}/{$coin}/{$endpoint}/" : "{$base_url}/{$endpoint}/";

        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $url .= '?apikey=' . $api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($isJson) {
            $data = json_encode($body);
            $headers[] = 'Content-Type: application/json';
        } else {
            $data = http_build_query($body);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, $assoc);
    }
}
