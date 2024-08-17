<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Key Checker</title>
    
    <!-- OG Metadata -->
    <meta property="og:title" content="Stripe Key Checker">
    <meta property="og:description" content="A tool to check the validity of your Stripe secret keys and retrieve account details.">
    <meta property="og:image" content="https://yourwebsite.com/path-to-image.jpg">
    <meta property="og:url" content="https://yourwebsite.com">
    <meta property="og:site_name" content="Your Site Name">
    
    <!-- Twitter Card Metadata -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Stripe Key Checker">
    <meta property="twitter:description" content="A tool to check the validity of your Stripe secret keys and retrieve account details.">
    <meta property="twitter:image" content="https://yourwebsite.com/path-to-image.jpg">

    <!-- SEO Metadata -->
    <meta name="description" content="A tool to check the validity of your Stripe secret keys and retrieve account details.">
    
    <style>
        /* Your CSS styles here */
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

            fetch('/validate_key.php', {
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
