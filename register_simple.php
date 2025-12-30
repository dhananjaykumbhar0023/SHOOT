<?php
/*
 * ========================================
 * SIMPLE REGISTRATION - register_simple.php
 * ========================================
 * Simple user registration for testing sign-in
 */

session_start();
include 'db.php';
include 'auth_helper.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($conn, $name, $email, $password, $phone);
        
        if ($result['success']) {
            $message = $result['message'] . ' You can now sign in.';
            
            // Clear form data on success
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RohDip Photography</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .auth-header p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .error-message {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .success-message {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .login-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .demo-info {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .demo-info h4 {
            color: #155724;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .demo-info p {
            font-size: 13px;
            color: #155724;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2>📝 Register</h2>
            <p>Create your photography account</p>
        </div>

        <div class="demo-info" onclick="fillDemoData()">
            <h4>🎯 Click here for Quick Demo Registration</h4>
            <p><strong>Name:</strong> Rohan Kumbhar</p>
            <p><strong>Email:</strong> rohankumbhar2105@gmail.com</p>
            <p><strong>Password:</strong> 123456</p>
            <p><em>Click this box to auto-fill the form</em></p>
        </div>

        <?php if ($error): ?>
            <div class="message error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message success-message">
                <?php echo htmlspecialchars($message); ?>
                <br><a href="sign.php" style="color: #155724; font-weight: bold;">→ Sign In Now</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">👤 Full Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">📧 Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email address">
            </div>

            <div class="form-group">
                <label for="phone">📱 Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                       placeholder="Enter your phone number (optional)">
            </div>

            <div class="form-group">
                <label for="password">🔒 Password *</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter password (min 6 characters)">
            </div>

            <div class="form-group">
                <label for="confirm_password">🔒 Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Confirm your password">
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="sign.php">Sign in here</a></p>
            <p><a href="shoot.html">← Back to main page</a></p>
        </div>
    </div>

    <script>
        function fillDemoData() {
            document.getElementById('name').value = 'Rohan Kumbhar';
            document.getElementById('email').value = 'rohankumbhar2105@gmail.com';
            document.getElementById('phone').value = '9307919706';
            document.getElementById('password').value = '123456';
            document.getElementById('confirm_password').value = '123456';
        }
    </script>
</body>
</html>