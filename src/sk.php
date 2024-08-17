<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Buenos_Aires');

function checkStripeKey($key) {
    $endpoints = [
        'account' => 'https://api.stripe.com/v1/account',
        'balance' => 'https://api.stripe.com/v1/balance',
        'customers' => 'https://api.stripe.com/v1/customers?limit=1',
        'charges' => 'https://api.stripe.com/v1/charges?limit=1'
    ];

    $results = [];
    foreach ($endpoints as $type => $url) {
        $results[$type] = makeStripeRequest($url, $key);
    }

    if ($results['account']['status'] === 'success') {
        $testCharge = createTestCharge($key);
        return createSuccessResponse($results, $testCharge, $key);
    } else {
        return createErrorResponse($results['account']);
    }
}

function makeStripeRequest($url, $key, $method = 'GET', $data = null) {
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key],
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
        logError($error);
        return ['status' => 'error', 'message' => 'cURL error: ' . $error];
    }

    curl_close($ch);
    $data = json_decode($response, true);

    return [
        'status' => ($httpCode == 200) ? 'success' : 'error',
        'http_code' => $httpCode,
        'data' => $data
    ];
}

function createTestCharge($key) {
    $token = createTestToken($key);
    if ($token['status'] === 'error') {
        return $token;
    }

    $chargeData = [
        'amount' => 100, // $1.00
        'currency' => 'usd',
        'source' => $token['data']['id'],
        'description' => 'Test charge for Stripe key validation'
    ];

    return makeStripeRequest('https://api.stripe.com/v1/charges', $key, 'POST', $chargeData);
}

function createTestToken($key) {
    $tokenData = [
        'card[number]' => '4154644405363426',
        'card[exp_month]' => '07',
        'card[exp_year]' => '2025',
        'card[cvc]' => '847'
    ];

    return makeStripeRequest('https://api.stripe.com/v1/tokens', $key, 'POST', $tokenData);
}

function createSuccessResponse($results, $testCharge, $key) {
    $accountData = $results['account']['data'];
    $balanceData = $results['balance']['data'];

    return [
        'status' => 'success',
        'message' => 'Stripe key is valid.',
        'timestamp' => date('c'),
        'stripe_key' => $key,
        'account_info' => [
            'id' => $accountData['id'],
            'business_name' => $accountData['business_profile']['name'] ?? 'N/A',
            'country' => $accountData['country'],
            'default_currency' => $accountData['default_currency'],
            'payouts_enabled' => $accountData['payouts_enabled'],
            'charges_enabled' => $accountData['charges_enabled']
        ],
        'balance_info' => [
            'available' => formatCurrencyAmounts($balanceData['available']),
            'pending' => formatCurrencyAmounts($balanceData['pending'])
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

function createErrorResponse($result) {
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

function formatCurrencyAmounts($amounts) {
    return array_map(function($amount) {
        return [
            'amount' => $amount['amount'],
            'currency' => $amount['currency'],
            'formatted' => formatMoney($amount['amount'], $amount['currency'])
        ];
    }, $amounts);
}

function formatMoney($amount, $currency) {
    return number_format($amount / 100, 2) . ' ' . strtoupper($currency);
}

function logError($message) {
    $logFile = 'error_log.txt';
    $timestamp = date('Y-m-d\TH:i:s\Z');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

$sk = $_GET['sk'] ?? '';

if (empty($sk)) {
    $result = [
        'status' => 'error',
        'message' => 'No Stripe key provided.',
        'timestamp' => date('c')
    ];
} else {
    $result = checkStripeKey($sk);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
