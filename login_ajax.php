<?php
/*
 * ========================================
 * AJAX LOGIN HANDLER - login_ajax.php
 * ========================================
 * Handles login requests via AJAX and returns JSON response
 */

session_start();
include 'db.php';
include 'auth_helper.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get form data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter both email and password.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

// Authenticate user
$result = authenticateUser($conn, $email, $password);

if ($result['success']) {
    // Create session
    createUserSession($result['user']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Redirecting...',
        'redirect' => 'user_dashboard.php'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
?>