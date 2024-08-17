<?php
ini_set('memory_limit', '256M');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Key Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
        }
        .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .input-group button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .input-group button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fafafa;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .details {
            margin-top: 10px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 4px;
        }
        .details p {
            margin: 0 0 5px;
        }
        .details span {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Stripe Key Checker</h1>
        <div class="input-group">
            <label for="stripeKey">Enter Stripe Secret Key:</label>
            <input type="text" id="stripeKey" placeholder="sk_test_...">
            <button onclick="checkKey()">Check Key</button>
        </div>
        <div class="result" id="result"></div>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        function checkKey() {
            const key = document.getElementById('stripeKey').value;
            const resultDiv = document.getElementById('result');
            require 'vendor/autoload.php';
            fetch('/validate_key.php', {  // Ensure your PHP endpoint is correct
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ key: key })
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                if (data.status === 'success') {
                    html = `<p class="success">Stripe key is valid.</p>`;
                    html += `<div class="details">`;
                    html += `<p><span>Stripe Key:</span> ${data.stripe_key}</p>`;
                    html += `<p><span>Available Balance:</span> ${data.balance_info.available[0].formatted}</p>`;
                    html += `<p><span>Country:</span> ${data.account_info.country}</p>`;
                    html += `<p><span>Currency:</span> ${data.account_info.default_currency.toUpperCase()}</p>`;
                    html += `<p><span>Pending Balance:</span> ${data.balance_info.pending[0].formatted}</p>`;
                    html += `<p><span>Customer Count:</span> ${data.customer_count}</p>`;
                    html += `<p><span>Charge Count:</span> ${data.charge_count}</p>`;
                    if (data.test_charge_result.success) {
                        html += `<p><span>Test Charge ID:</span> ${data.test_charge_result.charge_id}</p>`;
                    } else {
                        html += `<p><span>Test Charge Error:</span> ${data.test_charge_result.error}</p>`;
                    }
                    html += `</div>`;
                } else {
                    html = `<p class="error">Invalid Stripe key.</p>`;
                    html += `<div class="details">`;
                    html += `<p><span>Error Details:</span> ${data.error_details.error_message}</p>`;
                    html += `</div>`;
                }
                resultDiv.innerHTML = html;
            })
            .catch(error => {
                resultDiv.innerHTML = `<p class="error">An error occurred: ${error.message}</p>`;
            });
        }
    </script>
</body>
</html>
