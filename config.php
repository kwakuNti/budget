<?php
// config/email_config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // or manual includes

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP() {
        // Use Gmail SMTP for reliable delivery, but with custom domain appearance
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'nkansahclifford@gmail.com';     // Your actual Gmail
        $this->mail->Password = 'oydy xmge amjc zgrp';  // Gmail App Password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        
        // Set custom domain in From field for professional appearance
        $this->mail->setFrom('noreply@budgetly.online', 'Budgetly App');
        $this->mail->addReplyTo('noreply@budgetly.online', 'Budgetly App');
    }
    
    public function sendVerificationEmail($userEmail, $userName, $verificationToken) {
        try {
            $this->mail->addAddress($userEmail, $userName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Budgetly Account';
            
            $verificationLink = "https://budgetly.online/verify?token=" . $verificationToken;
            
            $this->mail->Body = "
                <h2>Welcome to Budgetly!</h2>
                <p>Hi $userName,</p>
                <p>Please click the link below to verify your email address:</p>
                <p><a href='$verificationLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
                <p>Or copy this link: $verificationLink</p>
                <p>This link will expire in 24 hours.</p>
            ";
            
            $this->mail->AltBody = "Welcome to Budgetly! Please verify your email by visiting: $verificationLink";
            
            $this->mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
?>