<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Donation Successful</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px 30px;
            color: white;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: bounce 1s ease;
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translateY(0);
            }
            40%, 43% {
                transform: translateY(-10px);
            }
            70% {
                transform: translateY(-5px);
            }
            90% {
                transform: translateY(-2px);
            }
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .success-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .success-body {
            padding: 40px 30px;
        }

        .amount-display {
            background: #f0f9ff;
            border: 2px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .amount-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }

        .success-message {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .success-features {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .features-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            text-align: center;
        }

        .features-list {
            list-style: none;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .features-list li:last-child {
            margin-bottom: 0;
        }

        .features-list li i {
            color: #10b981;
            margin-right: 10px;
            width: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f9fafb;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .social-share {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f3f4f6;
        }

        .share-title {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .share-btn:hover {
            transform: translateY(-2px);
        }

        .share-btn.twitter {
            background: #1da1f2;
            color: white;
        }

        .share-btn.facebook {
            background: #4267b2;
            color: white;
        }

        .share-btn.linkedin {
            background: #0077b5;
            color: white;
        }

        .share-btn.whatsapp {
            background: #25d366;
            color: white;
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .success-container {
                border-radius: 16px;
            }

            .success-header {
                padding: 30px 20px;
            }

            .success-title {
                font-size: 1.5rem;
            }

            .success-body {
                padding: 30px 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .share-buttons {
                gap: 8px;
            }

            .share-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h1 class="success-title">Thank You!</h1>
            <p class="success-subtitle">Your donation has been received</p>
        </div>

        <div class="success-body">
            <?php if (isset($_GET['amount'])): ?>
            <div class="amount-display">
                <div class="amount-label">Donation Amount</div>
                <div class="amount-value">₵<?php echo number_format(floatval($_GET['amount']), 2); ?></div>
            </div>
            <?php endif; ?>

            <p class="success-message">
                Your generous contribution helps us keep Budgetly free and accessible to everyone. 
                We truly appreciate your support in making financial management easier for all our users.
            </p>

            <div class="success-features">
                <h3 class="features-title">Your donation helps us:</h3>
                <ul class="features-list">
                    <li><i class="fas fa-server"></i>Maintain reliable servers and infrastructure</li>
                    <li><i class="fas fa-code"></i>Develop new features and improvements</li>
                    <li><i class="fas fa-shield-alt"></i>Ensure data security and privacy</li>
                    <li><i class="fas fa-headset"></i>Provide customer support</li>
                    <li><i class="fas fa-mobile-alt"></i>Keep the app free for everyone</li>
                </ul>
            </div>

            <div class="action-buttons">
                <a href="../dashboard.php" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i>
                    Go to Dashboard
                </a>
                <a href="donate" class="btn btn-secondary">
                    <i class="fas fa-heart"></i>
                    Donate Again
                </a>
            </div>

            <div class="social-share">
                <p class="share-title">Help spread the word about Budgetly!</p>
                <div class="share-buttons">
                    <a href="https://twitter.com/intent/tweet?text=I%20just%20supported%20@Budgetly%20-%20a%20free%20budgeting%20app%20that%20helps%20manage%20finances%20better!%20Check%20it%20out%20%F0%9F%92%B0&url=<?php echo urlencode('https://budgetly.app'); ?>" 
                       class="share-btn twitter" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://budgetly.app'); ?>" 
                       class="share-btn facebook" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://budgetly.app'); ?>" 
                       class="share-btn linkedin" target="_blank">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="https://wa.me/?text=Check%20out%20Budgetly%20-%20a%20free%20budgeting%20app%20that%20helps%20you%20manage%20your%20finances%20better!%20<?php echo urlencode('https://budgetly.app'); ?>" 
                       class="share-btn whatsapp" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some celebratory effects
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti effect (optional)
            createConfetti();
        });

        function createConfetti() {
            const colors = ['#10b981', '#2563eb', '#ef4444', '#f59e0b', '#8b5cf6'];
            const confettiContainer = document.createElement('div');
            confettiContainer.style.position = 'fixed';
            confettiContainer.style.top = '0';
            confettiContainer.style.left = '0';
            confettiContainer.style.width = '100%';
            confettiContainer.style.height = '100%';
            confettiContainer.style.pointerEvents = 'none';
            confettiContainer.style.zIndex = '9999';
            document.body.appendChild(confettiContainer);

            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'absolute';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-10px';
                    confetti.style.borderRadius = '50%';
                    confetti.style.animation = 'fall 3s linear forwards';
                    
                    confettiContainer.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 3000);
                }, i * 100);
            }

            // Add CSS animation for falling confetti
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fall {
                    to {
                        transform: translateY(100vh) rotate(360deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Remove confetti container after animation
            setTimeout(() => {
                confettiContainer.remove();
                style.remove();
            }, 8000);
        }
    </script>
</body>
</html>
