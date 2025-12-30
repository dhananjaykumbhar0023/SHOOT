<?php
/*
 * ========================================
 * CONTACT FORM HANDLER - submit.php
 * ========================================
 * This file handles contact form submissions from the photography website
 * and sends emails using PHPMailer with Gmail SMTP
 */

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer files
require 'PHPmailer/Exception.php';
require 'PHPmailer/PHPMailer.php';
require 'PHPmailer/SMTP.php';

// Get and sanitize form data
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Basic validation
if (empty($full_name) || empty($email) || empty($message)) {
    echo "error: Missing required fields";
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "error: Invalid email format";
    exit;
}

// Sanitize data to prevent XSS
$full_name = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Create an instance of PHPMailer
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 0;                                       // Disable debug output for production
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'dhananjaykumbhar2105@gmail.com';       // SMTP username
    $mail->Password   = 'zrgkknimofpndvxx';                     // SMTP password (App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
    $mail->Port       = 465;                                    // TCP port to connect to

    // Recipients
    $mail->setFrom('dhananjaykumbhar2105@gmail.com', 'Photography Website');
    $mail->addAddress('dhananjaykumbhar2105@gmail.com', 'RohDip Photography');     // Add recipient
    $mail->addReplyTo($email, $full_name);                      // Set reply-to to customer's email

    // Content
    $mail->isHTML(true);                                        // Set email format to HTML
    $mail->Subject = 'New Contact Form Submission: ' . $subject;
    
    // Create HTML email body
    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
            .content { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #555; }
            .value { margin-top: 5px; padding: 10px; background: #f9f9f9; border-radius: 3px; }
            .message-box { background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
                <p>You have received a new message from your photography website.</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <div class='label'>Full Name:</div>
                    <div class='value'>{$full_name}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Email Address:</div>
                    <div class='value'>{$email}</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Phone Number:</div>
                    <div class='value'>" . (!empty($phone) ? $phone : 'Not provided') . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div class='value'>" . (!empty($subject) ? $subject : 'No subject') . "</div>
                </div>
                
                <div class='message-box'>
                    <div class='label'>Message:</div>
                    <div style='margin-top: 10px; white-space: pre-wrap;'>{$message}</div>
                </div>
            </div>
            
            <div style='margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px; font-size: 12px; color: #666;'>
                <p><strong>Submission Details:</strong></p>
                <p>Date: " . date('Y-m-d H:i:s') . "</p>
                <p>IP Address: " . $_SERVER['REMOTE_ADDR'] . "</p>
                <p>User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "</p>
            </div>
        </div>
    </body>
    </html>";

    // Plain text version for email clients that don't support HTML
    $mail->AltBody = "New Contact Form Submission\n\n" .
                     "Name: {$full_name}\n" .
                     "Email: {$email}\n" .
                     "Phone: " . (!empty($phone) ? $phone : 'Not provided') . "\n" .
                     "Subject: " . (!empty($subject) ? $subject : 'No subject') . "\n\n" .
                     "Message:\n{$message}\n\n" .
                     "Submitted on: " . date('Y-m-d H:i:s');

    // Send the email
    $mail->send();
    
    // Log successful submission (optional)
    $log_entry = date('Y-m-d H:i:s') . " - Contact form submitted by: {$full_name} ({$email})\n";
    file_put_contents('contact_submissions.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => "Thank you, {$full_name}! Your message has been sent successfully. We'll get back to you soon."
    ]);
    
} catch (Exception $e) {
    // Log error (optional)
    $error_log = date('Y-m-d H:i:s') . " - Email Error: {$mail->ErrorInfo}\n";
    file_put_contents('contact_errors.txt', $error_log, FILE_APPEND | LOCK_EX);
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Sorry, there was an error sending your message. Please try again or contact us directly.'
    ]);
}
?>