<?php

namespace App\Service;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;

class ApiClient
{
    /**
     * @throws BillingUnavailableException
     */
    public function post(
        string $url,
        $params = '',
        $httpHeader = ['Accept: application/json', 'Content-Type: application/json'],
        $exceptionMessage = 'Сервис временно недоступен'
    ) {
        $ch = curl_init($_ENV['BILLING_ADDRESS'] . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new BillingUnavailableException($exceptionMessage);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function get(
        string $url,
        $httpHeader = ['Content-Type:application/json'],
        $exceptionMessage = 'Сервис временно недоступен'
    ) {
        $ch = curl_init($_ENV['BILLING_ADDRESS'] . $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new BillingUnavailableException($exceptionMessage);
        }
        curl_close($ch);
        return $response;
    }
}
