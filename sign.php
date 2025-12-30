<?php
/*
 * ========================================
 * SIGN IN PAGE - sign.php
 * ========================================
 * User authentication page with database validation
 */

session_start();
include 'db.php';
include 'auth_helper.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all required fields (Name and Email).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = authenticateUser($conn, $email, $password);
        
        if ($result['success']) {
            createUserSession($result['user']);
            
            // Add success message to session for dashboard
            $_SESSION['login_success'] = "Welcome back, " . $result['user']['name'] . "!";
            
            header('Location: user_dashboard.php');
            exit;
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
    <title>Sign In - RohDip Photography</title>
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

        .validation-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .validation-info h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .validation-info p {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.5;
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .remember-me input {
            width: auto;
            margin: 0;
        }

        .forgot-password {
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
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

        .submit-btn:active {
            transform: translateY(0);
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

        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .register-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .status-list {
            list-style: none;
            padding: 0;
        }

        .status-list li {
            padding: 5px 0;
            font-size: 0.9rem;
        }

        .status-list .success {
            color: #28a745;
        }

        .status-list .error {
            color: #dc3545;
        }

        .demo-credentials {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .demo-credentials h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .demo-credentials p {
            font-size: 13px;
            color: #1565c0;
            margin: 5px 0;
        }

        .demo-credentials code {
            background: #bbdefb;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2>🔐 Sign In</h2>
            <p>Access your photography dashboard</p>
        </div>

        <div class="validation-info">
            <h3>📋 How Sign-In Works</h3>
            <p><strong>Database Authentication:</strong> You can only sign in if you're registered in our system.</p>
            
            <ul class="status-list">
                <li class="success">✔ Registered user → Login allowed</li>
                <li class="error">❌ Not registered → Please register first</li>
                <li class="error">❌ Wrong password → Access denied</li>
                <li class="success">✔ Correct credentials → Dashboard access</li>
            </ul>
        </div>

        <div class="demo-credentials">
            <h4>🧪 Demo Account (For Testing)</h4>
            <p><strong>Email:</strong> <code>rohankumbhar2105@gmail.com</code></p>
            <p><strong>Password:</strong> <code>123456</code></p>
            <p><em>Note: Register first if this demo account doesn't exist</em></p>
        </div>

        <?php if ($error): ?>
            <div class="message error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">📧 Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your registered email">
            </div>

            <div class="form-group">
                <label for="password">🔒 Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember_me">
                    Remember me
                </label>
                <a href="#" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here first</a></p>
            <p><a href="shoot.html">← Back to main page</a></p>
        </div>
    </div>

    <script>
        // Auto-fill demo credentials for testing
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Add click handler for demo credentials
            document.querySelector('.demo-credentials').addEventListener('click', function() {
                emailInput.value = 'rohankumbhar2105@gmail.com';
                passwordInput.value = '123456';
            });
        });
    </script>
</body>
</html>