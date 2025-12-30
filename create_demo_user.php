<?php
/*
 * ========================================
 * CREATE DEMO USER - create_demo_user.php
 * ========================================
 * Creates a demo user for testing sign-in functionality
 */

include 'db.php';
include 'auth_helper.php';

echo "<h2>🔧 Demo User Creation</h2>";

// Demo user data
$demo_name = "Rohan Kumbhar";
$demo_email = "rohankumbhar2105@gmail.com";
$demo_phone = "9307919706";
$demo_password = "123456";

echo "<p><strong>Creating demo user:</strong></p>";
echo "<ul>";
echo "<li><strong>Name:</strong> $demo_name</li>";
echo "<li><strong>Email:</strong> $demo_email</li>";
echo "<li><strong>Phone:</strong> $demo_phone</li>";
echo "<li><strong>Password:</strong> $demo_password</li>";
echo "</ul>";

// Try to register the demo user
$result = registerUser($conn, $demo_name, $demo_email, $demo_password, $demo_phone);

if ($result['success']) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Success!</h3>";
    echo "<p>" . $result['message'] . "</p>";
    echo "<p><strong>User ID:</strong> " . $result['user_id'] . "</p>";
    echo "</div>";
    
    echo "<h3>🧪 Test Sign-In Now</h3>";
    echo "<p>You can now test the sign-in functionality:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='sign.php'>sign.php</a></li>";
    echo "<li>Use email: <code>$demo_email</code></li>";
    echo "<li>Use password: <code>$demo_password</code></li>";
    echo "<li>Click Sign In</li>";
    echo "</ol>";
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $result['message'] . "</p>";
    
    if (strpos($result['message'], 'already registered') !== false) {
        echo "<p><strong>Good news:</strong> The demo user already exists! You can use it to test sign-in.</p>";
        echo "<h3>🧪 Test Sign-In Now</h3>";
        echo "<p>Go to <a href='sign.php'>sign.php</a> and use:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> <code>$demo_email</code></li>";
        echo "<li><strong>Password:</strong> <code>$demo_password</code></li>";
        echo "</ul>";
    }
    echo "</div>";
}

// Show database status
echo "<h3>📊 Database Status</h3>";

try {
    $users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    echo "<p><strong>Total users in database:</strong> $users_count</p>";
    
    if ($users_count > 0) {
        echo "<h4>👥 Existing Users:</h4>";
        $users = $conn->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Created</th></tr>";
        while ($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking database: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>🔗 Quick Links</h3>";
echo "<ul>";
echo "<li><a href='sign.php'>🔐 Test Sign In</a></li>";
echo "<li><a href='register_simple.php'>📝 Register New User</a></li>";
echo "<li><a href='user_dashboard.php'>📊 User Dashboard</a></li>";
echo "<li><a href='shoot.html'>🏠 Main Page</a></li>";
echo "</ul>";

$conn->close();
?>