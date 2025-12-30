<?php
/*
 * ========================================
 * AUTHENTICATION HELPER - auth_helper.php
 * ========================================
 * Helper functions for user authentication
 */

// Function to check if user is authenticated
function isUserAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to require authentication (redirect if not authenticated)
function requireAuthentication($redirect_to = 'sign.php') {
    if (!isUserAuthenticated()) {
        header("Location: $redirect_to");
        exit;
    }
}

// Function to authenticate user credentials
function authenticateUser($conn, $email, $password) {
    try {
        // First check if users table exists, if not create it
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows == 0) {
            createUsersTable($conn);
        }
        
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $conn->error
            ];
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Login successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid password. Please check your password and try again.'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Email not found. Please register first or check your email address.'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Authentication error: ' . $e->getMessage()
        ];
    }
}

// Function to register new user
function registerUser($conn, $name, $email, $password, $phone = '') {
    try {
        // First check if users table exists, if not create it
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows == 0) {
            createUsersTable($conn);
        }
        
        // Check if already registered
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if (!$check) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $conn->error
            ];
        }
        
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Email already registered. Please use a different email or sign in.'
            ];
        }
        
        // Hash password and insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $conn->error
            ];
        }
        
        $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Registration successful! You can now sign in.',
                'user_id' => $conn->insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $stmt->error
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Registration error: ' . $e->getMessage()
        ];
    }
}

// Function to create users table
function createUsersTable($conn) {
    $createUsersTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_email (email),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($createUsersTable)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }
}

// Function to get user bookings from event table
function getUserBookings($conn, $user_email) {
    try {
        $stmt = $conn->prepare("SELECT * FROM event WHERE email = ? ORDER BY created_at DESC");
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    } catch (Exception $e) {
        error_log("Error getting user bookings: " . $e->getMessage());
        return [];
    }
}

// Function to create user session
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_authenticated'] = true;
    $_SESSION['login_time'] = time();
}

// Function to destroy user session
function destroyUserSession() {
    session_unset();
    session_destroy();
}

// Function to check if email exists in event table (for existing customers)
function checkExistingCustomer($conn, $email) {
    try {
        $stmt = $conn->prepare("SELECT name, email, phone FROM event WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error checking existing customer: " . $e->getMessage());
        return null;
    }
}
?>