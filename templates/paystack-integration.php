<?php
// Paystack configuration
define('PAYSTACK_SECRET_KEY', 'your_paystack_secret_key_here');
define('PAYSTACK_PUBLIC_KEY', 'your_paystack_public_key_here');

/**
 * Initialize Paystack payment
 */
function initializePaystackPayment($email, $amount, $currency = 'GHS', $callback_url = '') {
    $url = 'https://api.paystack.co/transaction/initialize';
    
    $fields = [
        'email' => $email,
        'amount' => $amount * 100, // Convert to kobo/pesewas
        'currency' => $currency,
        'callback_url' => $callback_url,
        'metadata' => [
            'donation_type' => 'budgetly_support',
            'source' => 'donation_page'
        ]
    ];
    
    $fields_string = http_build_query($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

/**
 * Verify Paystack payment
 */
function verifyPaystackPayment($reference) {
    $url = 'https://api.paystack.co/transaction/verify/' . $reference;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Handle donation request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
        $amount = floatval($input['amount']);
        $payment_method = $input['payment_method'];
        
        if (!$email || $amount <= 0) {
            throw new Exception('Invalid email or amount');
        }
        
        // For now, we'll just simulate the payment initialization
        // In production, you'd use your actual Paystack keys
        $callback_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                       '://' . $_SERVER['HTTP_HOST'] . '/templates/donate-success.php';
        
        // Simulate Paystack response
        $response = [
            'status' => true,
            'message' => 'Authorization URL created',
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/simulated_checkout',
                'access_code' => 'access_code_' . time(),
                'reference' => 'ref_' . time() . '_' . rand(1000, 9999)
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $response,
            'message' => 'Payment initialized successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    
    // In production, verify the payment with Paystack
    // $verification = verifyPaystackPayment($reference);
    
    // For now, simulate a successful verification
    $verification = [
        'status' => true,
        'data' => [
            'status' => 'success',
            'amount' => 2500, // Amount in kobo/pesewas
            'currency' => 'GHS'
        ]
    ];
    
    if ($verification['status'] && $verification['data']['status'] === 'success') {
        // Payment was successful
        header('Location: donate-success.php?amount=' . ($verification['data']['amount'] / 100));
        exit;
    } else {
        // Payment failed
        header('Location: donate.php?status=failed');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paystack Integration - Budgetly</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            padding: 40px 20px;
            text-align: center;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .info {
            background: #dbeafe;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #1e40af;
        }
        
        .back-btn {
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Paystack Integration</h1>
        <div class="info">
            <h3>Setup Required</h3>
            <p>To enable mobile money payments, you need to:</p>
            <ol style="text-align: left; margin-top: 15px;">
                <li>Create a Paystack account at <a href="https://paystack.com" target="_blank">paystack.com</a></li>
                <li>Get your API keys from the Paystack dashboard</li>
                <li>Replace the placeholder keys in this file</li>
                <li>Test with Paystack's test mode first</li>
            </ol>
            <p style="margin-top: 15px;">
                <strong>Note:</strong> Paystack supports MTN Mobile Money and Vodafone Cash in Ghana.
            </p>
        </div>
        <a href="donate.php" class="back-btn">Back to Donations</a>
    </div>
</body>
</html>
