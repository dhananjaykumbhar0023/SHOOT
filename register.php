<?php
/*
 * ========================================
 * REGISTRATION PAGE - register.php
 * ========================================
 * User registration page
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
    $address = trim($_POST['address'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($event_type) || empty($event_date) || empty($start_time) || empty($end_time) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUserWithBooking($conn, $name, $email, $phone, $address, $event_type, $event_date, $start_time, $end_time, $password);
        
        if ($result['success']) {
            $message = $result['message'];
            // Clear form data
            $_POST = array();
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
    <title>Register - Photography Booking</title>
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
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background-color: white;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4facfe;
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
            transition: transform 0.2s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
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

        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Register to access photography services</p>
        </div>

        <?php if ($error): ?>
            <div class="message error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message success-message">
                <?php echo htmlspecialchars($message); ?>
                <br><br>
                <a href="sign.php" style="color: #363; font-weight: bold;">Sign in now</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email address">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" required 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                       placeholder="Enter your phone number">
            </div>

            <div class="form-group">
                <label for="address">Address <span class="required">*</span></label>
                <input type="text" id="address" name="address" required 
                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                       placeholder="Enter your address">
            </div>

            <div class="form-group">
                <label for="event_type">Event Type <span class="required">*</span></label>
                <select id="event_type" name="event_type" required>
                    <option value="">Select Event Type</option>
                    <option value="pre-wedding" <?php echo ($_POST['event_type'] ?? '') === 'pre-wedding' ? 'selected' : ''; ?>>Pre-Wedding Photoshoot</option>
                    <option value="engagement" <?php echo ($_POST['event_type'] ?? '') === 'engagement' ? 'selected' : ''; ?>>Engagement Photography</option>
                    <option value="wedding" <?php echo ($_POST['event_type'] ?? '') === 'wedding' ? 'selected' : ''; ?>>Wedding Photography</option>
                    <option value="reception" <?php echo ($_POST['event_type'] ?? '') === 'reception' ? 'selected' : ''; ?>>Reception Photography</option>
                    <option value="maternity" <?php echo ($_POST['event_type'] ?? '') === 'maternity' ? 'selected' : ''; ?>>Maternity Shoot</option>
                    <option value="baby" <?php echo ($_POST['event_type'] ?? '') === 'baby' ? 'selected' : ''; ?>>Baby Photography</option>
                    <option value="family" <?php echo ($_POST['event_type'] ?? '') === 'family' ? 'selected' : ''; ?>>Family Portrait</option>
                    <option value="corporate" <?php echo ($_POST['event_type'] ?? '') === 'corporate' ? 'selected' : ''; ?>>Corporate Event</option>
                    <option value="birthday" <?php echo ($_POST['event_type'] ?? '') === 'birthday' ? 'selected' : ''; ?>>Birthday Party</option>
                    <option value="anniversary" <?php echo ($_POST['event_type'] ?? '') === 'anniversary' ? 'selected' : ''; ?>>Anniversary Celebration</option>
                </select>
            </div>

            <div class="form-group">
                <label for="event_date">Event Date <span class="required">*</span></label>
                <input type="date" id="event_date" name="event_date" required 
                       value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Event Time <span class="required">*</span></label>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                    <input type="time" id="start_time" name="start_time" required 
                           value="<?php echo htmlspecialchars($_POST['start_time'] ?? '10:00'); ?>"
                           style="flex: 1;">
                    <span style="color: #666;">to</span>
                    <input type="time" id="end_time" name="end_time" required 
                           value="<?php echo htmlspecialchars($_POST['end_time'] ?? '14:00'); ?>"
                           style="flex: 1;">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required 
                       placeholder="At least 6 characters">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Re-enter your password">
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="sign.php">Sign in here</a></p>
            <p><a href="shoot.html">Back to main page</a></p>
        </div>
    </div>
</body>
</html>