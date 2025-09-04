<?php
// Email Configuration for PHPMailer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        try {
            // Use Gmail SMTP for reliable delivery, but with custom domain appearance
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'noreplybudgetly@gmail.com';     // Your actual Gmail
            $this->mailer->Password = 'icfp uwqk iynj cuga';  // Gmail App Password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            
            // Set custom domain in From field for professional appearance
            $this->mailer->setFrom('noreply@budgetly.online', 'Budgetly App');
            $this->mailer->addReplyTo('noreply@budgetly.online', 'Budgetly App');
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw new Exception("Email service configuration failed");
        }
    }
    
    public function sendVerificationEmail($toEmail, $firstName, $verificationToken) {
        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            
            // Recipients
            $this->mailer->addAddress($toEmail, $firstName);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verify Your Email - Budget Manager';
            
            // Create verification URL
            $baseUrl = $this->getBaseUrl();
            $verificationUrl = $baseUrl . "/verify?token=" . urlencode($verificationToken);
            
            // HTML email template
            $htmlBody = $this->getVerificationEmailTemplate($firstName, $verificationUrl, $verificationToken);
            $this->mailer->Body = $htmlBody;
            
            // Alternative plain text body
            $this->mailer->AltBody = $this->getPlainTextTemplate($firstName, $verificationUrl, $verificationToken);
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Verification email sent successfully'
                ];
            } else {
                throw new Exception('Failed to send email');
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send verification email: ' . $e->getMessage()
            ];
        }
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // For local development
        if (strpos($host, 'localhost') !== false) {
            return $protocol . '://' . $host . '/budget';
        }
        
        // For production - use the actual domain
        return 'https://budgetly.online';
    }
    
    private function getVerificationEmailTemplate($firstName, $verificationUrl, $verificationCode) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Your Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .code-box { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; letter-spacing: 2px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>&#127919; Budget Manager</h1>
                    <p>Welcome to Your Personal Finance Journey!</p>
                </div>
                <div class="content">
                    <h2>Hi ' . htmlspecialchars($firstName) . '! &#128075;</h2>
                    
                    <p>Thank you for registering with Budget Manager! We\'re excited to help you take control of your finances.</p>
                    
                    <p>To complete your registration and start managing your budget, please verify your email address using the verification code below:</p>
                    
                    <p><strong>Use this verification code:</strong></p>
                    <div class="code-box">' . strtoupper(substr($verificationCode, 0, 8)) . '</div>
                    
                    <p>This verification code will expire in <strong>24 hours</strong> for security reasons.</p>
                    
                    <p>If you didn\'t create an account with Budget Manager, please ignore this email.</p>
                    
                    <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h3>&#128640; What\'s Next?</h3>
                        <p>Once verified, you can:</p>
                        <ul>
                            <li>Set up your income and salary information</li>
                            <li>Create budget categories and track expenses</li>
                            <li>Set savings goals and track progress</li>
                            <li>Get insights and analytics on your spending</li>
                        </ul>
                    </div>
                </div>
                <div class="footer">
                    <p>Budget Manager - Your Personal Finance Companion</p>
                    <p>If you have any questions, feel free to contact our support team.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function getPlainTextTemplate($firstName, $verificationUrl, $verificationCode) {
        return "Hi $firstName!

Thank you for registering with Budget Manager!

To complete your registration, please verify your email address by visiting:
$verificationUrl

Or use this verification code: " . strtoupper(substr($verificationCode, 0, 8)) . "

This verification link will expire in 24 hours.

If you didn't create an account with Budget Manager, please ignore this email.

Best regards,
Budget Manager Team";
    }
}

// Helper function to send verification email
function sendVerificationEmail($email, $firstName, $token) {
    try {
        $emailService = new EmailService();
        return $emailService->sendVerificationEmail($email, $firstName, $token);
    } catch (Exception $e) {
        error_log("Error sending verification email: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
