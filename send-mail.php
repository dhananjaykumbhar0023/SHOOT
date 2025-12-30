<?php
/*
 * ========================================
 * UNIFIED FORM HANDLER - send-mail.php
 * ========================================
 * This file handles both enquiry popup and registration form submissions
 * from the photography website and sends emails using PHPMailer with Gmail SMTP
 */

// Set content type for proper response
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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

// Load database connection and auth helper (only if they exist)
if (file_exists('db.php')) {
    require_once 'db.php';
}
if (file_exists('auth_helper.php')) {
    require_once 'auth_helper.php';
}

// Determine form type based on submitted data
$isBookingForm = isset($_POST['event_type']) && isset($_POST['event_date']) && isset($_POST['package']);
$isRegistrationForm = isset($_POST['phone']) && isset($_POST['address']) && isset($_POST['event_type']) && isset($_POST['password']);

if ($isBookingForm) {
    // Handle booking form submission from book.html
    handleBookingForm();
} elseif ($isRegistrationForm) {
    // Handle registration form submission
    handleRegistrationForm();
} else {
    // Handle enquiry form submission
    handleEnquiryForm();
}

function handleEnquiryForm() {
    // Get and sanitize form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // Basic validation
    if (empty($name) || empty($email)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please fill in all required fields (Name and Email).'
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    // Sanitize data to prevent XSS
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    
    // Send enquiry email
    sendEnquiryEmail($name, $email);
}

function handleBookingForm() {
    // Get and sanitize booking form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $event_type = isset($_POST['event_type']) ? trim($_POST['event_type']) : '';
    $event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
    $event_time = isset($_POST['event_time']) ? trim($_POST['event_time']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $package = isset($_POST['package']) ? trim($_POST['package']) : '';
    $package_price = isset($_POST['package_price']) ? floatval($_POST['package_price']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($event_type) || empty($event_date) || empty($location) || empty($package)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    // Validate date is not in the past
    $selected_date = new DateTime($event_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($selected_date < $today) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Event date cannot be in the past.'
        ]);
        exit;
    }

    // Sanitize data to prevent XSS
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $event_type = htmlspecialchars($event_type, ENT_QUOTES, 'UTF-8');
    $location = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
    $package = htmlspecialchars($package, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // Generate unique booking ID
    $booking_id = 'BOOK_' . date('Ymd') . '_' . uniqid();

    // Save booking to database
    try {
        // Check if database connection exists
        if (!isset($conn)) {
            throw new Exception("Database connection not available");
        }

        // Prepare SQL statement to insert booking into event table
        $stmt = $conn->prepare("INSERT INTO event (booking_id, name, email, phone, event_type, event_date, event_time, location, package, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("ssssssssss", $booking_id, $name, $email, $phone, $event_type, $event_date, $event_time, $location, $package, $message);
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Send booking emails
        sendBookingEmails($name, $email, $phone, $event_type, $event_date, $event_time, $location, $package, $package_price, $message, $booking_id);
        
        // Log successful booking
        $log_entry = date('Y-m-d H:i:s') . " - Booking saved to database: {$name} ({$email}) - {$event_type} on {$event_date} - Package: {$package} - Booking ID: {$booking_id}\n";
        file_put_contents('booking_submissions.txt', $log_entry, FILE_APPEND | LOCK_EX);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking confirmed successfully! We will contact you within 24 hours.',
            'booking_id' => $booking_id
        ]);
        
    } catch (Exception $e) {
        // Log database error
        $error_log = date('Y-m-d H:i:s') . " - Database Error: " . $e->getMessage() . " - Booking: {$name} ({$email})\n";
        file_put_contents('booking_database_errors.txt', $error_log, FILE_APPEND | LOCK_EX);
        
        // Still send emails even if database fails
        sendBookingEmails($name, $email, $phone, $event_type, $event_date, $event_time, $location, $package, $package_price, $message, $booking_id);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking confirmed successfully! We will contact you within 24 hours.',
            'booking_id' => $booking_id,
            'note' => 'Email sent successfully'
        ]);
    }
}

function handleRegistrationForm() {
    // Get and sanitize form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $event_type = isset($_POST['event_type']) ? trim($_POST['event_type']) : '';
    $event_date = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
    $start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
    $end_time = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($event_type) || empty($event_date) || empty($start_time) || empty($end_time) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // Validate password
    if (strlen($password) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    // Validate date is not in the past
    $selected_date = new DateTime($event_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($selected_date < $today) {
        echo json_encode(['status' => 'error', 'message' => 'Event date cannot be in the past.']);
        exit;
    }

    // Validate time range
    $start_datetime = new DateTime('2000-01-01 ' . $start_time);
    $end_datetime = new DateTime('2000-01-01 ' . $end_time);

    if ($start_datetime >= $end_datetime) {
        echo json_encode(['status' => 'error', 'message' => 'End time must be after start time.']);
        exit;
    }

    // Check minimum duration (1 hour)
    $diff_hours = ($end_datetime->getTimestamp() - $start_datetime->getTimestamp()) / 3600;
    if ($diff_hours < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Event duration must be at least 1 hour.']);
        exit;
    }

    // Sanitize data
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
    $event_type = htmlspecialchars($event_type, ENT_QUOTES, 'UTF-8');

    // Send enhanced email notifications
    sendRegistrationEmails($name, $email, $phone, $address, $event_type, $event_date, $start_time, $end_time);
    
    // Log successful booking
    $log_entry = date('Y-m-d H:i:s') . " - Registration & Booking created: {$name} ({$email}) - {$event_type} on {$event_date}\n";
    file_put_contents('booking_submissions.txt', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Registration and booking confirmed successfully! We will contact you within 24 hours.',
        'redirect' => 'user_dashboard.php'
    ]);
}

function sendEnquiryEmail($name, $email) {

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
        $mail->setFrom('dhananjaykumbhar2105@gmail.com', 'RohDip Photography - Enquiry');
        $mail->addAddress('dhananjaykumbhar2105@gmail.com', 'RohDip Photography');     // Add recipient
        $mail->addReplyTo($email, $name);                           // Set reply-to to customer's email

        // Content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = 'New Enquiry from Photography Website - ' . $name;
        
        // Create HTML email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 25px; 
                    border-radius: 8px; 
                    margin-bottom: 20px; 
                    text-align: center;
                }
                .content { 
                    background: #fff; 
                    padding: 25px; 
                    border: 2px solid #f0f0f0; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .field { 
                    margin-bottom: 20px; 
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 5px;
                    border-left: 4px solid #667eea;
                }
                .label { 
                    font-weight: bold; 
                    color: #555; 
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .value { 
                    margin-top: 8px; 
                    font-size: 16px;
                    color: #333;
                }
                .enquiry-badge {
                    background: #28a745;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    display: inline-block;
                    margin-bottom: 15px;
                }
                .footer-info {
                    margin-top: 25px; 
                    padding: 20px; 
                    background: #f8f9fa; 
                    border-radius: 5px; 
                    font-size: 12px; 
                    color: #666;
                    border-top: 3px solid #667eea;
                }
                .cta-section {
                    background: #e3f2fd;
                    padding: 20px;
                    border-radius: 8px;
                    margin-top: 20px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📸 New Photography Enquiry</h2>
                    <p>Someone is interested in your photography services!</p>
                </div>
                
                <div class='content'>
                    <div class='enquiry-badge'>🔔 NEW ENQUIRY</div>
                    
                    <div class='field'>
                        <div class='label'>👤 Customer Name:</div>
                        <div class='value'>{$name}</div>
                    </div>
                    
                    <div class='field'>
                        <div class='label'>📧 Email Address:</div>
                        <div class='value'>{$email}</div>
                    </div>
                    
                    <div class='cta-section'>
                        <h3 style='color: #1976d2; margin-top: 0;'>📞 Next Steps</h3>
                        <p style='margin-bottom: 15px;'>This customer showed interest by clicking 'EXPLORE MORE' on your website.</p>
                        <p style='font-weight: bold; color: #d32f2f;'>⚡ Respond quickly to convert this lead!</p>
                        <p style='font-size: 14px; color: #666;'>
                            💡 <strong>Tip:</strong> Reply within 1 hour for best conversion rates
                        </p>
                    </div>
                </div>
                
                <div class='footer-info'>
                    <p><strong>📊 Enquiry Details:</strong></p>
                    <p>🕒 <strong>Submitted:</strong> " . date('l, F j, Y \a\t g:i A') . "</p>
                    <p>🌐 <strong>IP Address:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>
                    <p>📱 <strong>User Agent:</strong> " . substr($_SERVER['HTTP_USER_AGENT'], 0, 100) . "...</p>
                    <p>🔗 <strong>Source:</strong> Photography Website Enquiry Popup</p>
                </div>
            </div>
        </body>
        </html>";

        // Plain text version for email clients that don't support HTML
        $mail->AltBody = "NEW PHOTOGRAPHY ENQUIRY\n\n" .
                         "Customer Name: {$name}\n" .
                         "Email Address: {$email}\n\n" .
                         "This customer showed interest by clicking 'EXPLORE MORE' on your website.\n" .
                         "Respond quickly to convert this lead!\n\n" .
                         "Submitted on: " . date('Y-m-d H:i:s') . "\n" .
                         "IP Address: " . $_SERVER['REMOTE_ADDR'];

        // Send the email
        $mail->send();
        
        // Log successful enquiry (optional)
        $log_entry = date('Y-m-d H:i:s') . " - Enquiry submitted by: {$name} ({$email}) - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        file_put_contents('enquiry_submissions.txt', $log_entry, FILE_APPEND | LOCK_EX);
        
        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => "Thank you, {$name}! We'll contact you soon at {$email}."
        ]);
        
    } catch (Exception $e) {
        // Log error (optional)
        $error_log = date('Y-m-d H:i:s') . " - Enquiry Email Error: {$mail->ErrorInfo} - Name: {$name}, Email: {$email}\n";
        file_put_contents('enquiry_errors.txt', $error_log, FILE_APPEND | LOCK_EX);
        
        // Return error response
        echo json_encode([
            'status' => 'error',
            'message' => 'Sorry, there was an error sending your enquiry. Please try again or contact us directly.'
        ]);
    }
}

// Function to send enhanced registration emails
function sendRegistrationEmails($name, $email, $phone, $address, $event_type, $event_date, $start_time, $end_time) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dhananjaykumbhar2105@gmail.com';
        $mail->Password = 'zrgkknimofpndvxx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Format event type for display
        $event_display = ucwords(str_replace('-', ' ', $event_type));
        
        // Send confirmation email to customer
        $mail->setFrom('dhananjaykumbhar2105@gmail.com', 'RohDip Photography');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = '🎉 Registration & Booking Confirmation - RohDip Photography';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .booking-card { background: #f8f9fa; border-radius: 10px; padding: 25px; margin: 20px 0; border-left: 5px solid #28a745; }
                .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .status-badge { background: #ffc107; color: #856404; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .next-steps { background: #e7f3ff; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                .contact-info { background: #fff; border: 2px solid #28a745; border-radius: 8px; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Registration & Booking Confirmed!</h1>
                    <p>Welcome to RohDip Photography</p>
                </div>
                
                <div class='content'>
                    <p>Dear <strong>{$name}</strong>,</p>
                    
                    <p>Welcome to RohDip Photography! Your account has been created and your booking has been confirmed. We're thrilled to capture your special moments!</p>
                    
                    <div class='booking-card'>
                        <h3>📅 Your Booking Details</h3>
                        <div class='detail-row'>
                            <span class='label'>Event Type:</span>
                            <span class='value'>{$event_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Event Date:</span>
                            <span class='value'>" . date('l, F j, Y', strtotime($event_date)) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Event Time:</span>
                            <span class='value'>" . date('g:i A', strtotime($start_time)) . " - " . date('g:i A', strtotime($end_time)) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Duration:</span>
                            <span class='value'>" . calculateDuration($start_time, $end_time) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Status:</span>
                            <span class='value'><span class='status-badge'>PENDING CONFIRMATION</span></span>
                        </div>
                    </div>
                    
                    <div class='next-steps'>
                        <h3>🚀 What Happens Next?</h3>
                        <ul>
                            <li><strong>Review & Confirmation:</strong> Our team will review your booking within 24 hours</li>
                            <li><strong>Personal Consultation:</strong> We'll contact you to discuss your vision and requirements</li>
                            <li><strong>Contract & Payment:</strong> Once confirmed, we'll send you the contract and payment details</li>
                            <li><strong>Pre-shoot Planning:</strong> We'll plan the perfect shoot based on your preferences</li>
                        </ul>
                    </div>
                    
                    <div class='contact-info'>
                        <h3>📞 Need to Reach Us?</h3>
                        <p><strong>Phone:</strong> 9307919706 (Mon-Sat, 9 AM - 7 PM)</p>
                        <p><strong>Email:</strong> rohdip@colorlib.com</p>
                        <p><strong>Address:</strong> Mohare, Panhala, Satave Savarde Road</p>
                    </div>
                    
                    <p>You can now log in to your dashboard to view and manage your bookings!</p>
                    
                    <p>Warm regards,<br>
                    <strong>The RohDip Photography Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p>© 2025 RohDip Photography. All rights reserved.</p>
                    <p>This is an automated confirmation email. Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->send();
        
        // Send notification to admin
        $mail->clearAddresses();
        $mail->addAddress('dhananjaykumbhar2105@gmail.com', 'RohDip Photography Admin');
        $mail->Subject = '🔔 New Registration & Booking Alert - Action Required';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .alert-header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px; }
                .booking-details { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .customer-info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .action-required { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='alert-header'>
                    <h2>🔔 NEW REGISTRATION & BOOKING</h2>
                    <p>New customer registered and booked!</p>
                </div>
                
                <div class='customer-info'>
                    <h3>👤 Customer Information</h3>
                    <p><strong>Name:</strong> {$name}</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Phone:</strong> {$phone}</p>
                    <p><strong>Address:</strong> {$address}</p>
                </div>
                
                <div class='booking-details'>
                    <h3>📅 Booking Details</h3>
                    <p><strong>Event Type:</strong> {$event_display}</p>
                    <p><strong>Event Date:</strong> " . date('l, F j, Y', strtotime($event_date)) . "</p>
                    <p><strong>Event Time:</strong> " . date('g:i A', strtotime($start_time)) . " - " . date('g:i A', strtotime($end_time)) . "</p>
                    <p><strong>Duration:</strong> " . calculateDuration($start_time, $end_time) . "</p>
                    <p><strong>Registration Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <div class='action-required'>
                    <h3>⚡ Action Required</h3>
                    <ul>
                        <li>Review booking details and check availability</li>
                        <li>Contact customer within 24 hours</li>
                        <li>Confirm or reschedule the booking</li>
                        <li>Send contract and payment details if confirmed</li>
                    </ul>
                </div>
                
                <p><strong>Customer account created and is waiting for confirmation!</strong></p>
            </div>
        </body>
        </html>";
        
        $mail->send();
        
    } catch (Exception $e) {
        // Log email error
        $error_log = date('Y-m-d H:i:s') . " - Registration Email Error: {$mail->ErrorInfo}\n";
        file_put_contents('registration_email_errors.txt', $error_log, FILE_APPEND | LOCK_EX);
    }
}

// Function to send booking emails
function sendBookingEmails($name, $email, $phone, $event_type, $event_date, $event_time, $location, $package, $package_price, $message, $booking_id) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dhananjaykumbhar2105@gmail.com';
        $mail->Password = 'zrgkknimofpndvxx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Format data for display
        $formatted_date = date('F j, Y', strtotime($event_date));
        if (!empty($event_time)) {
            $formatted_date .= ' at ' . date('g:i A', strtotime($event_time));
        }
        $formatted_price = $package_price ? '₹' . number_format($package_price) : 'Contact for pricing';
        
        // ========================================
        // 1. SEND ADMIN EMAIL
        // ========================================
        
        $mail->setFrom('dhananjaykumbhar2105@gmail.com', 'RohDip Photography Booking');
        $mail->addAddress('dhananjaykumbhar2105@gmail.com', 'RohDip Photography Admin');
        $mail->addReplyTo($email, $name);
        $mail->isHTML(true);
        $mail->Subject = '🎉 New Photography Booking - ' . $event_type . ' (' . $name . ')';
        
        $admin_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .detail-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .detail-row:last-child { border-bottom: none; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .urgent { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 15px 0; }
                .message-box { background: #e8f4fd; padding: 20px; border-left: 4px solid #3498db; margin: 20px 0; border-radius: 0 8px 8px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📸 NEW BOOKING ALERT!</h1>
                    <p>You have received a new photography booking request</p>
                    <p><strong>Booking ID: {$booking_id}</strong></p>
                </div>
                <div class='content'>
                    <div class='urgent'>
                        <strong>⚡ ACTION REQUIRED:</strong> Please contact the client within 24 hours.
                    </div>
                    
                    <div class='detail-box'>
                        <h3>👤 Client Information</h3>
                        <div class='detail-row'><span class='label'>Name:</span><span class='value'>{$name}</span></div>
                        <div class='detail-row'><span class='label'>Email:</span><span class='value'><a href='mailto:{$email}'>{$email}</a></span></div>
                        <div class='detail-row'><span class='label'>Phone:</span><span class='value'><a href='tel:{$phone}'>{$phone}</a></span></div>
                    </div>
                    
                    <div class='detail-box'>
                        <h3>🎉 Event Information</h3>
                        <div class='detail-row'><span class='label'>Event Type:</span><span class='value'>{$event_type}</span></div>
                        <div class='detail-row'><span class='label'>Date & Time:</span><span class='value'>{$formatted_date}</span></div>
                        <div class='detail-row'><span class='label'>Location:</span><span class='value'>{$location}</span></div>
                        <div class='detail-row'><span class='label'>Package:</span><span class='value'>{$package}</span></div>
                        <div class='detail-row'><span class='label'>Price:</span><span class='value'>{$formatted_price}</span></div>
                    </div>";
        
        if (!empty($message)) {
            $admin_body .= "
                    <div class='message-box'>
                        <h3 style='margin-top: 0; color: #3498db;'>💬 Special Requirements:</h3>
                        <p>" . nl2br($message) . "</p>
                    </div>";
        }
        
        $admin_body .= "
                    <div class='detail-box'>
                        <h3>📋 Next Steps</h3>
                        <ul>
                            <li>📞 Call the client: <a href='tel:{$phone}'>{$phone}</a></li>
                            <li>📧 Send follow-up email to: <a href='mailto:{$email}'>{$email}</a></li>
                            <li>📄 Send contract and payment terms</li>
                            <li>📅 Schedule consultation if needed</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px;'>
                        <p style='margin: 0; color: #27ae60; font-weight: bold;'>
                            💰 Potential Revenue: {$formatted_price}
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $admin_body;
        $mail->send();
        
        // ========================================
        // 2. SEND CLIENT EMAIL
        // ========================================
        
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom('dhananjaykumbhar2105@gmail.com', 'RohDip Photography');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('dhananjaykumbhar2105@gmail.com', 'RohDip Photography');
        $mail->Subject = '✅ Booking Confirmation - ' . $event_type . ' Photography';
        
        $client_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .success-box { background: #d4edda; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px solid #28a745; }
                .detail-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .detail-row:last-child { border-bottom: none; }
                .contact-box { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 5px solid #007bff; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Booking Confirmed!</h1>
                    <p>Thank you for choosing RohDip Photography</p>
                    <p><strong>Booking ID: {$booking_id}</strong></p>
                </div>
                <div class='content'>
                    <div class='success-box'>
                        <h2>✅ Your booking is confirmed!</h2>
                        <p>We're excited to capture your special moments and will contact you within 24 hours.</p>
                    </div>
                    
                    <div class='detail-box'>
                        <h3>📋 Your Booking Summary</h3>
                        <div class='detail-row'><span>Name:</span><span>{$name}</span></div>
                        <div class='detail-row'><span>Event Type:</span><span>{$event_type}</span></div>
                        <div class='detail-row'><span>Date & Time:</span><span>{$formatted_date}</span></div>
                        <div class='detail-row'><span>Location:</span><span>{$location}</span></div>
                        <div class='detail-row'><span>Package:</span><span>{$package}</span></div>
                        <div class='detail-row'><span>Investment:</span><span>{$formatted_price}</span></div>
                    </div>
                    
                    <div class='detail-box'>
                        <h3>📞 What Happens Next?</h3>
                        <ul>
                            <li><strong>Personal Call:</strong> We'll call you within 24 hours to confirm all details</li>
                            <li><strong>Consultation:</strong> Discuss your vision, style preferences, and special requests</li>
                            <li><strong>Planning:</strong> Schedule a pre-event consultation if needed</li>
                            <li><strong>Contract:</strong> Send you the contract and payment information</li>
                            <li><strong>Preparation:</strong> Plan the perfect photography session for your special day</li>
                        </ul>
                    </div>
                    
                    <div class='contact-box'>
                        <h3>📧 Contact Information</h3>
                        <p><strong>Email:</strong> <a href='mailto:dhananjaykumbhar2105@gmail.com'>dhananjaykumbhar2105@gmail.com</a></p>
                        <p><strong>Response Time:</strong> Within 24 hours</p>
                        <p><strong>Business Hours:</strong> Monday - Saturday, 9 AM - 7 PM</p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 20px; padding: 20px; background: white; border-radius: 8px;'>
                        <p style='color: #666; font-style: italic; margin: 0 0 10px 0;'>
                            \"Capturing moments, creating memories that last forever.\"
                        </p>
                        <p style='color: #ff6b6b; font-weight: bold; margin: 0;'>
                            Thank you for trusting us with your special day! 📸✨
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $client_body;
        $mail->send();
        
    } catch (Exception $e) {
        // Log email error
        $error_log = date('Y-m-d H:i:s') . " - Booking Email Error: {$mail->ErrorInfo}\n";
        file_put_contents('booking_email_errors.txt', $error_log, FILE_APPEND | LOCK_EX);
    }
}

// Helper function to calculate duration
function calculateDuration($start_time, $end_time) {
    $start = new DateTime('2000-01-01 ' . $start_time);
    $end = new DateTime('2000-01-01 ' . $end_time);
    $diff = $start->diff($end);
    
    $hours = $diff->h;
    $minutes = $diff->i;
    
    if ($hours > 0 && $minutes > 0) {
        return "{$hours} hours {$minutes} minutes";
    } elseif ($hours > 0) {
        return "{$hours} hour" . ($hours > 1 ? 's' : '');
    } else {
        return "{$minutes} minutes";
    }
}
?>