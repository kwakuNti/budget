<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Budgetly - Donate</title>
    <?php include '../includes/favicon.php'; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .donate-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
            display: flex;
            min-height: 600px;
        }

        /* Left Side - Hero */
        .donate-hero {
            flex: 1;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .hero-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            backdrop-filter: blur(10px);
        }

        .hero-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-features {
            text-align: left;
            margin-top: 20px;
        }

        .hero-features li {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .hero-features li i {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.7rem;
        }

        /* Right Side - Donation Form */
        .donate-form-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Amount Selection */
        .amount-selection {
            margin-bottom: 24px;
        }

        .amount-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .amount-options {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .amount-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 600;
            color: #374151;
        }

        .amount-option:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .amount-option.selected {
            border-color: #2563eb;
            background: #2563eb;
            color: white;
        }

        .custom-amount {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-weight: 600;
        }

        .custom-amount input {
            width: 100%;
            padding: 12px 12px 12px 30px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .custom-amount input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Payment Methods */
        .payment-methods {
            margin-bottom: 24px;
        }

        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #2563eb;
        }

        .payment-method.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .payment-header {
            padding: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payment-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .payment-details h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1f2937;
        }

        .payment-details p {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .payment-content {
            padding: 0 16px 16px;
            display: none;
            background: #f9fafb;
        }

        .payment-method.selected .payment-content {
            display: block;
        }

        .bank-details {
            background: white;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }

        .bank-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .bank-detail:last-child {
            border-bottom: none;
        }

        .bank-detail .label {
            font-weight: 500;
            color: #6b7280;
        }

        .bank-detail .value {
            font-weight: 600;
            color: #1f2937;
            font-family: 'Courier New', monospace;
        }

        .copy-btn {
            background: #f3f4f6;
            border: none;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            color: #6b7280;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }

        /* Mobile Money Section */
        .momo-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .momo-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .momo-option:hover {
            border-color: #2563eb;
        }

        .momo-option.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .momo-option img {
            width: 30px;
            height: 30px;
            margin-bottom: 8px;
        }

        .momo-option .name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
        }

        /* Buttons */
        .donate-btn {
            width: 100%;
            padding: 16px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .donate-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .donate-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        /* Loading State */
        .btn-loader {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        .donate-btn.loading .btn-text {
            display: none;
        }

        .donate-btn.loading .btn-loader {
            display: block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Snackbar */
        .snackbar {
            position: fixed;
            bottom: -100px;
            left: 50%;
            transform: translateX(-50%);
            background: #323232;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            transition: all 0.3s ease;
            max-width: 400px;
            min-width: 300px;
        }

        .snackbar.show {
            bottom: 30px;
        }

        .snackbar.success {
            background: #059669;
        }

        .snackbar.error {
            background: #dc2626;
        }

        .snackbar.info {
            background: #2563eb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .donate-container {
                flex-direction: column;
                margin: 10px;
                border-radius: 16px;
            }

            .donate-hero {
                padding: 30px 20px;
            }

            .hero-title {
                font-size: 1.5rem;
            }

            .donate-form-section {
                padding: 30px 20px;
            }

            .amount-options {
                grid-template-columns: 1fr 1fr;
            }

            .momo-options {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .donate-container {
                margin: 0;
                border-radius: 12px;
            }

            .donate-hero {
                padding: 20px 15px;
            }

            .donate-form-section {
                padding: 20px 15px;
            }

            .amount-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="donate-container">
        <!-- Left Side - Hero -->
        <div class="donate-hero">
            <div class="hero-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h1 class="hero-title">Support Budgetly</h1>
            <p class="hero-subtitle">Help us keep this free budgeting tool running and continuously improving for everyone.</p>
            
            <ul class="hero-features">
                <li><i class="fas fa-check"></i>Keep the app free for all users</li>
                <li><i class="fas fa-check"></i>Add new features and improvements</li>
                <li><i class="fas fa-check"></i>Maintain secure and reliable servers</li>
                <li><i class="fas fa-check"></i>Provide ongoing support</li>
            </ul>
        </div>

        <!-- Right Side - Donation Form -->
        <div class="donate-form-section">
            <div class="form-header">
                <h2 class="form-title">Make a Donation</h2>
                <p class="form-subtitle">Every contribution helps us improve Budgetly</p>
            </div>

            <!-- Amount Selection -->
            <div class="amount-selection">
                <label class="amount-label">Select Amount (GHS)</label>
                <div class="amount-options">
                    <div class="amount-option" data-amount="10">₵10</div>
                    <div class="amount-option" data-amount="25">₵25</div>
                    <div class="amount-option" data-amount="50">₵50</div>
                </div>
                <div class="custom-amount">
                    <span class="currency-symbol">₵</span>
                    <input type="number" id="customAmount" placeholder="Enter custom amount" min="1" step="0.01">
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods">
                <label class="amount-label">Choose Payment Method</label>
                
                <!-- Bank Transfer -->
                <div class="payment-method" data-method="bank">
                    <div class="payment-header">
                        <div class="payment-info">
                            <div class="payment-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="payment-details">
                                <h4>Bank Transfer</h4>
                                <p>Direct bank transfer (recommended)</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="payment-content">
                        <div class="bank-details">
                            <div class="bank-detail">
                                <span class="label">Bank:</span>
                                <div>
                                    <span class="value">MTN Mobile Money</span>
                                    <button class="copy-btn" onclick="copyToClipboard('MTN Mobile Money')">Copy</button>
                                </div>
                            </div>
                            <div class="bank-detail">
                                <span class="label">Account Name:</span>
                                <div>
                                    <span class="value">Clifford Ntinkansah</span>
                                    <button class="copy-btn" onclick="copyToClipboard('Clifford Ntinkansah')">Copy</button>
                                </div>
                            </div>
                            <div class="bank-detail">
                                <span class="label">Mobile Money Number:</span>
                                <div>
                                    <span class="value">+233 24 123 4567</span>
                                    <button class="copy-btn" onclick="copyToClipboard('+233241234567')">Copy</button>
                                </div>
                            </div>
                            <div style="margin-top: 12px; padding: 12px; background: #fef3cd; border-radius: 6px; font-size: 0.85rem; color: #92400e;">
                                <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                                Please include your email in the transaction reference for confirmation.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Money -->
                <div class="payment-method" data-method="momo">
                    <div class="payment-header">
                        <div class="payment-info">
                            <div class="payment-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-details">
                                <h4>Mobile Money</h4>
                                <p>Pay with MoMo (via Paystack)</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="payment-content">
                        <div class="momo-options">
                            <div class="momo-option" data-network="mtn">
                                <div style="color: #ffcc00; font-size: 24px;">📱</div>
                                <div class="name">MTN MoMo</div>
                            </div>
                            <div class="momo-option" data-network="vodafone">
                                <div style="color: #e60000; font-size: 24px;">📱</div>
                                <div class="name">Vodafone Cash</div>
                            </div>
                        </div>
                        <div style="margin-top: 12px; padding: 12px; background: #dbeafe; border-radius: 6px; font-size: 0.85rem; color: #1e40af;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            You'll be redirected to Paystack for secure payment processing.
                        </div>
                    </div>
                </div>

                <!-- PayPal (International) -->
                <div class="payment-method" data-method="paypal">
                    <div class="payment-header">
                        <div class="payment-info">
                            <div class="payment-icon">
                                <i class="fab fa-paypal"></i>
                            </div>
                            <div class="payment-details">
                                <h4>PayPal</h4>
                                <p>International donations</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="payment-content">
                        <div style="padding: 12px; background: #f0f9ff; border-radius: 6px; font-size: 0.85rem; color: #0369a1;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            PayPal integration coming soon. For now, please use bank transfer or mobile money.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donate Button -->
            <button class="donate-btn" id="donateBtn" onclick="processDonation()">
                <span class="btn-text">
                    <i class="fas fa-heart"></i>
                    Donate Now
                </span>
                <span class="btn-loader"></span>
            </button>

            <!-- Back Button -->
            <button class="back-btn" onclick="goBack()">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                Back to Budgetly
            </button>
        </div>
    </div>

    <!-- Snackbar -->
    <div id="snackbar" class="snackbar">
        <span class="snackbar-icon"></span>
        <span class="snackbar-message"></span>
    </div>

    <script>
        let selectedAmount = 0;
        let selectedPaymentMethod = '';
        let selectedNetwork = '';

        // Amount selection
        document.querySelectorAll('.amount-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selection from all options
                document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked option
                this.classList.add('selected');
                
                // Set amount
                selectedAmount = parseFloat(this.dataset.amount);
                
                // Clear custom amount
                document.getElementById('customAmount').value = '';
                
                updateDonateButton();
            });
        });

        // Custom amount input
        document.getElementById('customAmount').addEventListener('input', function() {
            const customAmount = parseFloat(this.value) || 0;
            if (customAmount > 0) {
                // Remove selection from preset amounts
                document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));
                
                selectedAmount = customAmount;
            } else {
                selectedAmount = 0;
            }
            
            updateDonateButton();
        });

        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            const header = method.querySelector('.payment-header');
            header.addEventListener('click', function() {
                // Remove selection from all methods
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                
                // Add selection to clicked method
                method.classList.add('selected');
                
                // Set payment method
                selectedPaymentMethod = method.dataset.method;
                
                updateDonateButton();
            });
        });

        // Mobile money network selection
        document.querySelectorAll('.momo-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selection from all networks
                document.querySelectorAll('.momo-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked network
                this.classList.add('selected');
                
                // Set network
                selectedNetwork = this.dataset.network;
            });
        });

        function updateDonateButton() {
            const donateBtn = document.getElementById('donateBtn');
            const btnText = donateBtn.querySelector('.btn-text');
            
            if (selectedAmount > 0 && selectedPaymentMethod) {
                donateBtn.disabled = false;
                btnText.innerHTML = `<i class="fas fa-heart"></i> Donate ₵${selectedAmount.toFixed(2)}`;
            } else {
                donateBtn.disabled = true;
                btnText.innerHTML = `<i class="fas fa-heart"></i> Donate Now`;
            }
        }

        function processDonation() {
            if (selectedAmount <= 0) {
                showSnackbar('Please select or enter a donation amount', 'error');
                return;
            }

            if (!selectedPaymentMethod) {
                showSnackbar('Please select a payment method', 'error');
                return;
            }

            const donateBtn = document.getElementById('donateBtn');
            donateBtn.classList.add('loading');

            switch (selectedPaymentMethod) {
                case 'bank':
                    handleBankTransfer();
                    break;
                case 'momo':
                    handleMobileMoneyPayment();
                    break;
                case 'paypal':
                    handlePayPalPayment();
                    break;
                default:
                    showSnackbar('Payment method not available yet', 'error');
                    donateBtn.classList.remove('loading');
            }
        }

        function handleBankTransfer() {
            // Simulate processing
            setTimeout(() => {
                document.getElementById('donateBtn').classList.remove('loading');
                showSnackbar('Bank transfer details displayed. Please complete the transfer manually.', 'info');
                
                // You could also open a modal with detailed instructions
                showBankTransferInstructions();
            }, 1000);
        }

        function handleMobileMoneyPayment() {
            if (!selectedNetwork) {
                document.getElementById('donateBtn').classList.remove('loading');
                showSnackbar('Please select your mobile money network', 'error');
                return;
            }

            // Collect user email for payment processing
            const email = prompt('Please enter your email address for payment confirmation:');
            if (!email || !email.includes('@')) {
                document.getElementById('donateBtn').classList.remove('loading');
                showSnackbar('Valid email address is required', 'error');
                return;
            }

            // Create payment data
            const paymentData = {
                email: email,
                amount: selectedAmount,
                payment_method: selectedPaymentMethod,
                network: selectedNetwork
            };

            // Send to payment processor (you can integrate with Paystack here)
            fetch('paystack-integration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('donateBtn').classList.remove('loading');
                
                if (data.success) {
                    // In a real implementation, redirect to Paystack checkout
                    showSnackbar('Redirecting to payment gateway...', 'success');
                    
                    // For demo purposes, simulate success after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'donate-success.php?amount=' + selectedAmount;
                    }, 2000);
                } else {
                    showSnackbar(data.message || 'Payment initialization failed', 'error');
                }
            })
            .catch(error => {
                document.getElementById('donateBtn').classList.remove('loading');
                showSnackbar('Network error. Please try again.', 'error');
                console.error('Payment error:', error);
            });
        }

        function handlePayPalPayment() {
            setTimeout(() => {
                document.getElementById('donateBtn').classList.remove('loading');
                showSnackbar('PayPal integration coming soon! Please use bank transfer for now.', 'info');
            }, 1000);
        }

        function showBankTransferInstructions() {
            // Create a modal or alert with detailed instructions
            const instructions = `
Bank Transfer Instructions:

1. Use your bank app or visit a branch
2. Transfer ₵${selectedAmount.toFixed(2)} to:
   Account Name: Clifford Nti Nkansah
   Mobile Money: +233 XX XXX XXXX (MTN)

3. Include your email in the reference/description
4. Send us a screenshot at support@budgetly.com

Thank you for your support!
            `;
            
            alert(instructions);
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showSnackbar('Copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                showSnackbar('Failed to copy to clipboard', 'error');
            });
        }

        function goBack() {
            // Go back to previous page or default to login
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = 'login.php';
            }
        }

        // Snackbar functionality
        function showSnackbar(message, type = 'info') {
            // Remove existing snackbar if any
            const existingSnackbar = document.querySelector('.snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }

            // Create new snackbar
            const snackbar = document.createElement('div');
            snackbar.className = `snackbar ${type}`;
            
            const icons = {
                success: '<i class="fas fa-check-circle"></i>',
                error: '<i class="fas fa-times-circle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            snackbar.innerHTML = `
                <span class="snackbar-icon">${icons[type] || icons.info}</span>
                <span class="snackbar-message">${message}</span>
            `;
            
            document.body.appendChild(snackbar);
            
            // Show snackbar
            setTimeout(() => snackbar.classList.add('show'), 100);
            
            // Hide snackbar after 4 seconds
            setTimeout(() => {
                snackbar.classList.remove('show');
                setTimeout(() => snackbar.remove(), 300);
            }, 4000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Select default amount
            document.querySelector('[data-amount="25"]').click();
            
            // Select default payment method
            document.querySelector('[data-method="bank"]').click();
        });
    </script>
</body>
</html>
