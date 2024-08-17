<?php
require 'vendor/autoload.php'; // Include Composer autoload if you're using Composer
require 'src/StripeValidator.php'; // For manual inclusion

use App\StripeValidator;

header('Content-Type: application/json');
date_default_timezone_set('America/Buenos_Aires');

// Retrieve the API key from the query parameter
$apiKey = $_GET['sk'] ?? '';

// Validate the presence of the API key
if (empty($apiKey)) {
    $response = [
        'status' => 'error',
        'message' => 'No Stripe key provided.',
        'timestamp' => date('c')
    ];
} else {
    // Initialize the StripeValidator with the provided API key
    $validator = new StripeValidator($apiKey);

    // Validate the Stripe API key and get the response
    $response = $validator->validate();
}

// Output the JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
