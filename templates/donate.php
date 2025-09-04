<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Budgetly - Donate</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .donate-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            display: flex;
            min-height: 700px;
        }

        /* Left Side - Hero */
        .donate-hero {
            flex: 1.2;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .donate-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hero-icon i {
            font-size: 3rem;
            color: white;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
            background: linear-gradient(45deg, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-features {
            text-align: left;
            margin-top: 30px;
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-features li {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            font-size: 1rem;
            opacity: 0.95;
            padding: 8px 0;
        }

        .hero-features li i {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 0.8rem;
            animation: none;
        }

        /* Right Side - Donation Form */
        .donate-form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 12px;
            background: linear-gradient(45deg, #1f2937, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Amount Selection */
        .amount-selection {
            margin-bottom: 32px;
        }

        .amount-label {
            display: block;
            font-weight: 700;
            color: #374151;
            margin-bottom: 16px;
            font-size: 1.1rem;
        }

        .amount-options {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .amount-option {
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 700;
            color: #374151;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .amount-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .amount-option:hover::before {
            left: 100%;
        }

        .amount-option:hover {
            border-color: #2563eb;
            background: #eff6ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
        }

        .amount-option.selected {
            border-color: #2563eb;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .custom-amount {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-weight: 700;
            font-size: 1.1rem;
            z-index: 2;
        }

        .custom-amount input {
            width: 100%;
            padding: 16px 16px 16px 40px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            background: white;
        }

        .custom-amount input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            transform: translateY(-1px);
        }

        /* Payment Methods */
        .payment-methods {
            margin-bottom: 32px;
        }

        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-method:hover {
            border-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.1);
        }

        .payment-method.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .payment-header {
            padding: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payment-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .payment-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
            font-size: 1.2rem;
        }

        .payment-method.selected .payment-icon {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
        }

        .payment-details h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .payment-details p {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .chevron-icon {
            color: #9ca3af;
            transition: transform 0.3s ease;
        }

        .payment-method.selected .chevron-icon {
            transform: rotate(180deg);
            color: #2563eb;
        }

        .payment-content {
            padding: 0 20px 20px;
            display: none;
            background: rgba(249, 250, 251, 0.5);
        }

        .payment-method.selected .payment-content {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Buttons */
        .donate-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .donate-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .donate-btn:hover::before {
            left: 100%;
        }

        .donate-btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
        }

        .donate-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .donate-btn:disabled::before {
            display: none;
        }

        .back-btn {
            width: 100%;
            padding: 16px;
            background: transparent;
            color: #6b7280;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
            transform: translateY(-1px);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transform: scale(0.8) translateY(20px);
            transition: all 0.3s ease;
        }

        .modal-overlay.show .modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 30px 30px 0;
            text-align: center;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .modal-subtitle {
            color: #6b7280;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .modal-body {
            padding: 0 30px 30px;
        }

        .bank-details {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .bank-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .bank-detail:last-child {
            border-bottom: none;
        }

        .bank-detail .label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .bank-detail .value-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bank-detail .value {
            font-weight: 700;
            color: #1f2937;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
        }

        .copy-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        .copy-btn.copied {
            background: #059669;
        }

        .modal-instructions {
            background: #fef3cd;
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .modal-instructions .instruction-title {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-instructions ol {
            color: #92400e;
            padding-left: 20px;
        }

        .modal-instructions li {
            margin-bottom: 4px;
            font-size: 0.9rem;
        }

        .modal-close {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .modal-close:hover {
            background: #4b5563;
        }

        /* Loading State */
        .btn-loader {
            width: 24px;
            height: 24px;
            border: 3px solid transparent;
            border-top: 3px solid white;
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
            padding: 18px 28px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            z-index: 10001;
            transition: all 0.3s ease;
            max-width: 400px;
            min-width: 320px;
        }

        .snackbar.show {
            bottom: 30px;
        }

        .snackbar.success {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .snackbar.error {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .snackbar.info {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .donate-container {
                max-width: 900px;
            }
            
            .donate-hero {
                padding: 40px 30px;
            }
            
            .donate-form-section {
                padding: 40px 30px;
            }
        }

        @media (max-width: 768px) {
            .donate-container {
                flex-direction: column;
                margin: 10px;
                border-radius: 20px;
                max-width: none;
            }

            .donate-hero {
                padding: 40px 30px;
                flex: none;
            }

            .hero-title {
                font-size: 2rem;
            }

            .donate-form-section {
                padding: 40px 30px;
                flex: none;
            }

            .form-title {
                font-size: 1.8rem;
            }

            .amount-options {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .donate-container {
                margin: 0;
                border-radius: 16px;
            }

            .donate-hero {
                padding: 30px 20px;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .donate-form-section {
                padding: 30px 20px;
            }

            .form-title {
                font-size: 1.6rem;
            }

            .amount-options {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .modal {
                width: 95%;
                margin: 10px;
            }

            .modal-header,
            .modal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="donate-container">
        <!-- Left Side - Hero -->
        <div class="donate-hero">
            <div class="hero-content">
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
                    <div class="amount-option" data-amount="1">₵1</div>
                    <div class="amount-option" data-amount="5">₵5</div>
                    <div class="amount-option" data-amount="10">₵10</div>
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
                        <i class="fas fa-chevron-down chevron-icon"></i>
                    </div>
                    <div class="payment-content">
                        <div style="padding: 12px; background: #dbeafe; border-radius: 8px; font-size: 0.9rem; color: #1e40af;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            Click "Donate Now" to view complete bank transfer details in a secure popup.
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
                                <p>MTN MoMo direct transfer</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down chevron-icon"></i>
                    </div>
                    <div class="payment-content">
                        <div style="padding: 12px; background: #fef3cd; border-radius: 8px; font-size: 0.9rem; color: #92400e;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            Transfer directly to our MTN MoMo number. Details will be shown after clicking "Donate Now".
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
                        <i class="fas fa-chevron-down chevron-icon"></i>
                    </div>
                    <div class="payment-content">
                        <div style="padding: 12px; background: #f0f9ff; border-radius: 8px; font-size: 0.9rem; color: #0369a1;">
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

    <!-- Bank Transfer Modal -->
    <div id="bankModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-university"></i>
                </div>
                <h3 class="modal-title">Bank Transfer Details</h3>
                <p class="modal-subtitle">Complete your donation of <span id="modalAmount">₵0</span></p>
            </div>
            <div class="modal-body">
                <div class="bank-details">
                    <div class="bank-detail">
                        <span class="label">Bank:</span>
                        <div class="value-container">
                            <span class="value">MTN Mobile Money</span>
                            <button class="copy-btn" onclick="copyToClipboard('MTN Mobile Money', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Account Name:</span>
                        <div class="value-container">
                            <span class="value">Clifford Ntinkansah</span>
                            <button class="copy-btn" onclick="copyToClipboard('Clifford Ntinkansah', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Mobile Money:</span>
                        <div class="value-container">
                            <span class="value">0558579224</span>
                            <button class="copy-btn" onclick="copyToClipboard('0558579224', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Account Number:</span>
                        <div class="value-container">
                            <span class="value">1441002596723</span>
                            <button class="copy-btn" onclick="copyToClipboard('1441002596723', this)">Copy</button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-instructions">
                    <div class="instruction-title">
                        <i class="fas fa-info-circle"></i>
                        Transfer Instructions:
                    </div>
                    <ol>
                        <li>Open your mobile money app or dial *170#</li>
                        <li>Select "Send Money" or "Transfer"</li>
                        <li>Enter the mobile money number: <strong>0558579224</strong></li>
                        <li>Enter amount: <strong>₵<span class="modal-amount-text">0</span></strong></li>
                        <li>Add reference: <strong>"Budgetly Donation"</strong></li>
                        <li>Complete the transaction</li>
                        <li><em>Optional:</em> Send confirmation to: <strong>noreplybudgetly@gmail.com</strong></li>
                    </ol>
                </div>
                
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-check"></i>
                    Got it, Thanks!
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Money Modal -->
    <div id="momoModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="modal-title">Mobile Money Transfer</h3>
                <p class="modal-subtitle">Send <span id="momoModalAmount">₵0</span> via MTN Mobile Money</p>
            </div>
            <div class="modal-body">
                <div class="bank-details">
                    <div class="bank-detail">
                        <span class="label">Network:</span>
                        <div class="value-container">
                            <span class="value">MTN Mobile Money</span>
                            <button class="copy-btn" onclick="copyToClipboard('MTN Mobile Money', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Recipient Name:</span>
                        <div class="value-container">
                            <span class="value">Clifford Ntinkansah</span>
                            <button class="copy-btn" onclick="copyToClipboard('Clifford Ntinkansah', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Phone Number:</span>
                        <div class="value-container">
                            <span class="value">0558579224</span>
                            <button class="copy-btn" onclick="copyToClipboard('0558579224', this)">Copy</button>
                        </div>
                    </div>
                    <div class="bank-detail">
                        <span class="label">Amount:</span>
                        <div class="value-container">
                            <span class="value">₵<span class="momo-amount-text">0</span></span>
                            <button class="copy-btn" onclick="copyToClipboard(document.querySelector('.momo-amount-text').textContent, this)">Copy</button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-instructions">
                    <div class="instruction-title">
                        <i class="fas fa-mobile-alt"></i>
                        How to Send:
                    </div>
                    <ol>
                        <li>Dial <strong>*170#</strong> from your MTN line</li>
                        <li>Select <strong>"Send Money"</strong></li>
                        <li>Select <strong>"To Mobile Money User"</strong></li>
                        <li>Enter recipient number: <strong>0558579224</strong></li>
                        <li>Enter amount: <strong>₵<span class="momo-amount-text">0</span></strong></li>
                        <li>Enter reference: <strong>"Budgetly Support"</strong></li>
                        <li>Confirm with your Mobile Money PIN</li>
                    </ol>
                </div>
                
                <div style="background: #dcfce7; border: 1px solid #16a34a; border-radius: 12px; padding: 16px; margin: 16px 0; color: #15803d;">
                    <div style="font-weight: 700; margin-bottom: 8px;">
                        <i class="fas fa-shield-alt"></i>
                        Safe & Secure
                    </div>
                    <p style="font-size: 0.9rem; margin: 0;">Your donation goes directly to our official MTN Mobile Money account. No third-party payment processors involved.</p>
                </div>
                
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-check"></i>
                    Transfer Complete
                </button>
            </div>
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

            // Simulate processing time
            setTimeout(() => {
                donateBtn.classList.remove('loading');
                
                switch (selectedPaymentMethod) {
                    case 'bank':
                        showBankTransferModal();
                        break;
                    case 'momo':
                        showMobileMoneyModal();
                        break;
                    case 'paypal':
                        handlePayPalPayment();
                        break;
                    default:
                        showSnackbar('Payment method not available yet', 'error');
                }
            }, 1500);
        }

        function showBankTransferModal() {
            document.getElementById('modalAmount').textContent = `₵${selectedAmount.toFixed(2)}`;
            document.querySelector('.modal-amount-text').textContent = selectedAmount.toFixed(2);
            document.getElementById('bankModal').classList.add('show');
        }

        function showMobileMoneyModal() {
            document.getElementById('momoModalAmount').textContent = `₵${selectedAmount.toFixed(2)}`;
            document.querySelectorAll('.momo-amount-text').forEach(el => {
                el.textContent = selectedAmount.toFixed(2);
            });
            document.getElementById('momoModal').classList.add('show');
        }

        function handlePayPalPayment() {
            showSnackbar('PayPal integration coming soon! Please use bank transfer or mobile money for now.', 'info');
        }

        function closeModal() {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.classList.remove('show');
            });
            
            // Show success message
            showSnackbar('Thank you for your support! 🎉', 'success');
            
            // Reset form after modal closes
            setTimeout(() => {
                resetForm();
            }, 1000);
        }

        function resetForm() {
            selectedAmount = 0;
            selectedPaymentMethod = '';
            
            // Clear selections
            document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));
            document.querySelectorAll('.payment-method').forEach(method => method.classList.remove('selected'));
            document.getElementById('customAmount').value = '';
            
            // Select defaults
            document.querySelector('[data-amount="5"]').click();
            document.querySelector('[data-method="bank"]').click();
        }

        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
                
                showSnackbar(`Copied: ${text}`, 'success');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                showSnackbar('Failed to copy to clipboard', 'error');
                
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showSnackbar(`Copied: ${text}`, 'success');
                } catch (err) {
                    console.error('Fallback copy failed:', err);
                }
                document.body.removeChild(textArea);
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

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Snackbar functionality
        function showSnackbar(message, type = 'info') {
            // Remove existing snackbar if any
            const existingSnackbar = document.querySelector('.snackbar.show');
            if (existingSnackbar) {
                existingSnackbar.classList.remove('show');
                setTimeout(() => existingSnackbar.remove(), 300);
            }

            // Create new snackbar
            const snackbar = document.getElementById('snackbar');
            snackbar.className = `snackbar ${type}`;
            
            const icons = {
                success: '<i class="fas fa-check-circle"></i>',
                error: '<i class="fas fa-times-circle"></i>',
                info: '<i class="fas fa-info-circle"></i>'
            };
            
            snackbar.querySelector('.snackbar-icon').innerHTML = icons[type] || icons.info;
            snackbar.querySelector('.snackbar-message').textContent = message;
            
            // Show snackbar
            setTimeout(() => snackbar.classList.add('show'), 100);
            
            // Hide snackbar after 4 seconds
            setTimeout(() => {
                snackbar.classList.remove('show');
            }, 4000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Select default amount
            document.querySelector('[data-amount="5"]').click();
            
            // Select default payment method
            document.querySelector('[data-method="bank"]').click();
        });
    </script>
</body>
</html>