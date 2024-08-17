<?php

namespace App;

use Exception;

class StripeValidator
{
    private $apiKey;
    
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function validate()
    {
        try {
            $results = $this->checkStripeKey();
            return $results;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    private function checkStripeKey()
    {
        $endpoints = [
            'account' => 'https://api.stripe.com/v1/account',
            'balance' => 'https://api.stripe.com/v1/balance',
            'customers' => 'https://api.stripe.com/v1/customers?limit=1',
            'charges' => 'https://api.stripe.com/v1/charges?limit=1'
        ];

        $results = [];
        foreach ($endpoints as $type => $url) {
            $results[$type] = $this->makeStripeRequest($url);
        }

        if ($results['account']['status'] === 'success') {
            $testCharge = $this->createTestCharge();
            return $this->createSuccessResponse($results, $testCharge);
        } else {
            return $this->createErrorResponse($results['account']);
        }
    }

    private function makeStripeRequest($url, $method = 'GET', $data = null)
    {
        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_TIMEOUT => 15
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);
        $data = json_decode($response, true);

        return [
            'status' => ($httpCode == 200) ? 'success' : 'error',
            'http_code' => $httpCode,
            'data' => $data
        ];
    }

    private function createTestCharge()
    {
        $token = $this->createTestToken();
        if ($token['status'] === 'error') {
            return $token;
        }

        $chargeData = [
            'amount' => 100, // $1.00
            'currency' => 'usd',
            'source' => $token['data']['id'],
            'description' => 'Test charge for Stripe key validation'
        ];

        return $this->makeStripeRequest('https://api.stripe.com/v1/charges', 'POST', $chargeData);
    }

    private function createTestToken()
    {
        $tokenData = [
            'card[number]' => '4154644405363426',
            'card[exp_month]' => '07',
            'card[exp_year]' => '2025',
            'card[cvc]' => '847'
        ];

        return $this->makeStripeRequest('https://api.stripe.com/v1/tokens', 'POST', $tokenData);
    }

    private function createSuccessResponse($results, $testCharge)
    {
        $accountData = $results['account']['data'];
        $balanceData = $results['balance']['data'];

        return [
            'status' => 'success',
            'message' => 'Stripe key is valid.',
            'timestamp' => date('c'),
            'stripe_key' => $this->apiKey,
            'account_info' => [
                'id' => $accountData['id'],
                'business_name' => $accountData['business_profile']['name'] ?? 'N/A',
                'country' => $accountData['country'],
                'default_currency' => $accountData['default_currency'],
                'payouts_enabled' => $accountData['payouts_enabled'],
                'charges_enabled' => $accountData['charges_enabled']
            ],
            'balance_info' => [
                'available' => $this->formatCurrencyAmounts($balanceData['available']),
                'pending' => $this->formatCurrencyAmounts($balanceData['pending'])
            ],
            'customer_count' => $results['customers']['data']['total_count'] ?? 0,
            'charge_count' => $results['charges']['data']['total_count'] ?? 0,
            'test_charge_result' => [
                'success' => $testCharge['status'] === 'success',
                'charge_id' => $testCharge['data']['id'] ?? null,
                'amount' => $testCharge['data']['amount'] ?? null,
                'currency' => $testCharge['data']['currency'] ?? null,
                'status' => $testCharge['data']['status'] ?? null,
                'error' => $testCharge['data']['error']['message'] ?? null
            ]
        ];
    }

    private function createErrorResponse($result)
    {
        return [
            'status' => 'error',
            'message' => 'The Stripe key is invalid.',
            'timestamp' => date('c'),
            'error_details' => [
                'http_code' => $result['http_code'],
                'error_type' => $result['data']['error']['type'] ?? 'unknown',
                'error_message' => $result['data']['error']['message'] ?? 'No additional error information provided.'
            ]
        ];
    }

    private function formatCurrencyAmounts($amounts)
    {
        return array_map(function($amount) {
            return [
                'amount' => $amount['amount'],
                'currency' => $amount['currency'],
                'formatted' => $this->formatMoney($amount['amount'], $amount['currency'])
            ];
        }, $amounts);
    }

    private function formatMoney($amount, $currency)
    {
        return number_format($amount / 100, 2) . ' ' . strtoupper($currency);
    }
}
?>
