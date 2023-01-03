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

        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
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
        if (empty($this->own_address) || empty($this->coin) || empty($this->callback_url)) {
            return null;
        }

        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'callback' => $callback_url,
            'address'  => $this->own_address,
            'apikey' => $this->api_key
        ], $this->bb_params);

        $response = BlockBee::_request($this->coin, 'create', $bb_params);

        if ($response->status == 'success') {
            // assert($response->address_out == $this->own_address, 'Output address mismatch');
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
            'callback' => $callback_url,
            'apikey' => $this->api_key
        ];

        $response = BlockBee::_request($this->coin, 'logs', $params);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public function get_qrcode($value = false, $size = false)
    {
        if (empty($this->coin)) {
            return null;
        }

        $params = [
            'address' => $this->payment_address,
            'apikey' => $this->api_key
        ];

        if ($value) {
            $params['value'] = $value;
        }
        if ($size) {
            $params['size'] = $size;
        }

        $response = BlockBee::_request($this->coin, 'qrcode', $params);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function get_info($coin = null, $assoc = false, $api_key = '')
    {
        $params = [];

        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $params['apikey'] = $api_key;

        if (empty($coin)) {
            $params['prices'] = '0';
        }

        $response = BlockBee::_request($coin, 'info', $params, $assoc);

        if (empty($coin) || $response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function get_estimate($coin, $addresses = 1, $priority = 'default', $api_key = '')
    {
        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $response = BlockBee::_request($coin, 'estimate', [
            'addresses' => $addresses,
            'priority'  => $priority,
            'apikey' => $api_key
        ]);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function get_convert($coin, $value, $from, $api_key)
    {
        $response = BlockBee::_request($coin, 'convert', [
            'value' => $value,
            'from'  => $from,
            'apikey' => $api_key
        ]);

        if ($response->status == 'success') {
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

    private static function _request($coin, $endpoint, $params = [], $assoc = false)
    {
        $base_url = BlockBee::$base_url;
        $coin     = str_replace('_', '/', (string) $coin);

        if (!empty($params)) {
            $data = http_build_query($params);
        }

        if (!empty($coin)) {
            $url = "{$base_url}/{$coin}/{$endpoint}/";
        } else {
            $url = "{$base_url}/{$endpoint}/";
        }

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
}
