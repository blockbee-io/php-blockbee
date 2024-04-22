<?php

namespace BlockBee;

use BlockBee\Exceptions\ApiException;
use Exception;

class Checkout
{
    private $bb_params = [];
    private $parameters = [];
    private $api_key = null;

    /**
     * @throws Exception
     */
    public function __construct($api_key, $parameters = [], $bb_params = [])
    {
        if (empty($api_key)) {
            throw new Exception('API Key is Empty');
        }

        $this->bb_params = $bb_params;
        $this->parameters = $parameters;
        $this->api_key = $api_key;
    }

    /**
     * Handles request to payments.
     * @return array
     * @throws ApiException
     */
    public function payment_request($redirect_url, $notify_url, $value)
    {
        if (empty($redirect_url) || empty($value)) {
            return null;
        }

        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $redirect_url   = "{$redirect_url}?{$req_parameters}";
            $notify_url   = "{$notify_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'redirect_url' => $redirect_url,
            'notify_url' => $notify_url,
            'apikey' => $this->api_key,
            'value' => $value
        ], $this->bb_params);

        return Requests::_request_get(null, 'checkout/request', $bb_params);
    }

    /**
     * Fetch payment logs
     * @param $token
     * @param $api_key
     * @throws ApiException
     */
    public static function payment_logs($token, $api_key){
        if (empty($token)) {
            throw new Exception('Token is Empty');
        }

        $params = [
            'token' => $token,
            'apikey' => $api_key
        ];

        return Requests::_request_get(null, 'checkout/logs', $params);
    }

    /**
     * Handles deposit requests
     * @return array
     * @throws ApiException
     */
    public function deposit_request($notify_url) {
        if (empty($notify_url)) {
            return null;
        }

        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $notify_url   = "{$notify_url}?{$req_parameters}";
        }

        $bb_params = array_merge([
            'notify_url' => $notify_url,
            'apikey' => $this->api_key,
        ], $this->bb_params);

        return Requests::_request_get(null, 'deposit/request', $bb_params);
    }

    /**
     * Fetch payment logs
     * @param $token
     * @param $api_key
     * @throws ApiException
     */
    public static function deposit_logs($token, $api_key){
        if (empty($token)) {
            throw new Exception('Token is Empty');
        }

        $params = [
            'token' => $token,
            'apikey' => $api_key
        ];

        return Requests::_request_get(null, 'deposit/logs', $params);
    }
}
