<?php
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form type
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'admin') {
        // Handle Admin Registration + Login
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $number = trim($_POST['number'] ?? '');

        if (empty($name) || empty($email) || empty($password) || empty($number)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
            exit;
        }

        // First, register the data in database
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM registration WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            // Email doesn't exist, register the user
            $isAdmin = 1; // Mark as admin in database
            $insertStmt = $conn->prepare("INSERT INTO registration (Name, email, password, number, is_admin) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssi", $name, $email, $password, $number, $isAdmin);
            
            if (!$insertStmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $insertStmt->error]);
                exit;
            }
            $insertStmt->close();
        }
        $checkStmt->close();

        // Check if these are valid admin credentials (multiple admins allowed)
        $isValidAdmin = false;
        
        // Admin 1: Rohan
        if ($name === 'rohan' && $email === 'rohankumbhar2105@gmail.com' && $password === 'rohan23' && $number === '9307919706') {
            $isValidAdmin = true;
        }
        
        // Admin 2: Jaydip
        if ($name === 'jaydip' && $email === 'jaydip2425@gmail.com') {
            $isValidAdmin = true;
        }

        if ($isValidAdmin) {
            // Start session and store admin data
            session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = 'Rohan';
            $_SESSION['admin_id'] = 1;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Admin registration successful! Redirecting to dashboard...',
                'redirect' => 'admin_dashboard.php'
            ]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Registration successful! Data saved to database.']);
        }
        
    } elseif ($formType === 'user') {
        // Handle User Sign In (from Registration tab - User sub-tab)
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $number = trim($_POST['number'] ?? '');
        $password = $_POST['password'] ?? '';

        // Check if this is from Sign In tab (only email and password)
        $isSignInTab = empty($name) && empty($number);

        if ($isSignInTab) {
            // Sign In tab - only email and password required
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
                exit;
            }

            // Check if user exists in database
            $stmt = $conn->prepare("SELECT id, Name, email, password FROM registration WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if ($password === $user['password']) {
                    // Start session and store user data
                    session_start();
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['Name'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Sign in successful! Redirecting to dashboard...',
                        'redirect' => 'user_dashboard.php'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid password.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found. Please register first.']);
            }
            $stmt->close();
        } else {
            // Registration tab - User form (all 4 fields required)
            if (empty($name) || empty($email) || empty($number) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
                exit;
            }

            // Check if user exists in database with all matching credentials
            $stmt = $conn->prepare("SELECT id, Name, email, number, password FROM registration WHERE email = ? AND Name = ? AND number = ?");
            $stmt->bind_param("sss", $email, $name, $number);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if ($password === $user['password']) {
                    // Start session and store user data
                    session_start();
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['Name'];
                    $_SESSION['user_id'] = $user['id'];
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Sign in successful! Redirecting to dashboard...',
                        'redirect' => 'user_dashboard.php'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid password.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found. Please check your credentials.']);
            }
            $stmt->close();
        }
        
    } elseif ($formType === 'registration') {
        // Handle Registration
        $firstName = trim($_POST['firstName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $number = trim($_POST['number'] ?? '');

        // Validate inputs
        if (empty($firstName) || empty($email) || empty($password) || empty($number)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
            exit;
        }

        // Check for capital letters in email
        if ($email !== strtolower($email)) {
            echo json_encode(['success' => false, 'message' => 'Email must be in lowercase only. No capital letters allowed.']);
            exit;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }

        // Additional email validation - check for proper domain
        $emailParts = explode('@', $email);
        if (count($emailParts) != 2) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }

        $domain = $emailParts[1];
        $username = $emailParts[0];

        // Check if domain has proper format (contains dot and valid characters)
        if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address with proper domain.']);
            exit;
        }

        // Check username part (should not be too short or contain invalid patterns)
        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
            exit;
        }



        // Validate phone number (10 digits)
        if (!preg_match('/^[0-9]{10}$/', $number)) {
            echo json_encode(['success' => false, 'message' => 'Mobile number must be 10 digits.']);
            exit;
        }

        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM registration WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered. Please sign in.']);
            $checkStmt->close();
        } else {
            $checkStmt->close();
            
            // Insert data (is_admin default is 0 for regular users)
            $stmt = $conn->prepare("INSERT INTO registration (Name, email, password, number) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $firstName, $email, $password, $number);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Registration successful! Data saved to database.'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Registration failed: ' . $stmt->error
                ]);
            }
            $stmt->close();
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid form type.']);
    }

    $conn->close();
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
