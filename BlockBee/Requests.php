<?php

namespace BlockBee;

use Exception;
use BlockBee\Exceptions\ApiException;

class Requests {
    private static $base_url = 'https://api.blockbee.io';

    /**
     * @throws ApiException
     */
    public static function _request_get($coin, $endpoint, $params = [], $assoc = false)
    {
        $base_url = Requests::$base_url;
        $coin     = str_replace('_', '/', (string) $coin);

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

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_object = json_decode($response, $assoc);

        if (isset($response_object->status) && $response_object->status === 'error') {
            $statusCode = $http_code;
            $apiError = $response_object->error ?? null;

            throw ApiException::withStatus($statusCode, $apiError);
        }

        return $response_object;
    }

    /**
     * @throws ApiException
     */
    public static function _request_post($coin, $endpoint, $api_key, $body = [], $isJson = false, $assoc = false )
    {
        $base_url = Requests::$base_url;
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

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_object = json_decode($response, $assoc);

        if (isset($response_object->status) && $response_object->status === 'error') {
            $statusCode = $http_code;
            $apiError = $response_object->error ?? null;

            throw ApiException::withStatus($statusCode, $apiError);
        }

        return $response_object;
    }
}
