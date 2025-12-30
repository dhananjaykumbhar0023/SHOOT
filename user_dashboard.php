<?php
session_start();
include 'db.php';
include 'auth_helper.php';

// Require authentication
requireAuthentication();

// Get user info
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';

// Get user's bookings - flexible approach to handle different database structures
$bookings_result = null;
$bookings = [];

// Check what columns exist in bookings table
$columns_result = $conn->query("SHOW COLUMNS FROM bookings");
$available_columns = [];
if ($columns_result) {
    while ($col = $columns_result->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
}

// Try different approaches based on available columns
if (in_array('user_id', $available_columns) && $user_id > 0) {
    // Use user_id if available
    $bookings_query = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
    $bookings_query->bind_param("i", $user_id);
    $bookings_query->execute();
    $bookings_result = $bookings_query->get_result();
} elseif (in_array('email', $available_columns) && !empty($user_email)) {
    // Use email if user_id not available
    $bookings_query = $conn->prepare("SELECT * FROM bookings WHERE email = ? ORDER BY created_at DESC");
    $bookings_query->bind_param("s", $user_email);
    $bookings_query->execute();
    $bookings_result = $bookings_query->get_result();
} else {
    // Fallback: get all bookings (for testing)
    $bookings_result = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 10");
}

// Convert result to array for easier handling
if ($bookings_result) {
    while ($row = $bookings_result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Function to format event type for display
function formatEventType($event_type) {
    return ucwords(str_replace('-', ' ', $event_type ?? 'Unknown'));
}

// Function to get status badge class
function getStatusClass($status) {
    switch(strtolower($status ?? 'pending')) {
        case 'confirmed': return 'status-confirmed';
        case 'pending': return 'status-pending';
        case 'cancelled': return 'status-cancelled';
        case 'completed': return 'status-completed';
        default: return 'status-pending';
    }
}

// Function to get package price based on event type
function getPackagePrice($event_type) {
    $prices = [
        'pre-wedding' => ['original' => '₹25,000', 'offer' => '₹14,999'],
        'engagement' => ['original' => '₹35,000', 'offer' => '₹19,999'],
        'wedding' => ['original' => '₹75,000', 'offer' => '₹49,999'],
        'reception' => ['original' => '₹40,000', 'offer' => '₹24,999'],
        'maternity' => ['original' => '₹20,000', 'offer' => '₹12,999'],
        'baby' => ['original' => '₹18,000', 'offer' => '₹11,999'],
        'family' => ['original' => '₹22,000', 'offer' => '₹14,999'],
        'corporate' => ['original' => '₹30,000', 'offer' => '₹19,999'],
        'birthday' => ['original' => '₹25,000', 'offer' => '₹16,999'],
        'anniversary' => ['original' => '₹28,000', 'offer' => '₹18,999']
    ];
    
    return $prices[$event_type ?? 'pre-wedding'] ?? ['original' => '₹25,000', 'offer' => '₹15,999'];
}

// Function to safely get field value
function getField($booking, $field, $default = '') {
    return $booking[$field] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Dashboard - RohDip Photography</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-content">
                <div>
                    <h1>BOO<span style="color: #6a5af9;">K</span>ING</h1>
                    <small>Customer Portal</small>
                </div>
                <div class="user-section">
                    <span class="welcome-text">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                    <a href="user_logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Welcome Banner -->
        <div class="welcome-box">
            <h2>Welcome to Your BOOKING-ORDER</h2>
            <p>You have successfully signed in with email: <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
        </div>

        <!-- Main Dashboard Content -->
        <div class="dashboard-main">
            <!-- Bookings Section -->
            <div class="bookings-section">
                <div class="section-header">
                    <h3>Your Bookings</h3>
                </div>

                <?php if (count($bookings) > 0): ?>
                    <div class="bookings-table-container">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>BOOKING ID</th>
                                    <th>EVENT TYPE</th>
                                    <th>EVENT DATE</th>
                                    <th>TIME</th>
                                    <th>STATUS</th>
                                    <th>PAYMENT</th>
                                    <th>PACKAGE PRICE</th>
                                    <th>CREATED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): 
                                    $price_info = getPackagePrice(getField($booking, 'event_type'));
                                    $event_type = getField($booking, 'event_type', 'unknown');
                                    $event_date = getField($booking, 'event_date', date('Y-m-d'));
                                    $start_time = getField($booking, 'start_time', getField($booking, 'event_time_start', '10:00:00'));
                                    $end_time = getField($booking, 'end_time', getField($booking, 'event_time_end', '14:00:00'));
                                    $status = getField($booking, 'status', getField($booking, 'booking_status', 'pending'));
                                    $payment_status = getField($booking, 'payment_status', 'pending');
                                    $created_at = getField($booking, 'created_at', date('Y-m-d H:i:s'));
                                ?>
                                <tr class="booking-row" onclick="toggleBookingDetails(<?php echo getField($booking, 'id', '1'); ?>)">
                                    <td class="booking-id">#<?php echo getField($booking, 'id', '1'); ?></td>
                                    <td>
                                        <span class="event-type-badge event-<?php echo $event_type; ?>">
                                            <?php echo formatEventType($event_type); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($event_date)); ?></td>
                                    <td>
                                        <?php 
                                        echo date('g:i A', strtotime($start_time)) . ' - ' . 
                                             date('g:i A', strtotime($end_time)); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusClass($status); ?>">
                                            <?php echo strtoupper($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="payment-badge payment-<?php echo $payment_status; ?>">
                                            <?php echo strtoupper($payment_status); ?>
                                        </span>
                                    </td>
                                    <td class="price-cell">
                                        <div class="price-info">
                                            <span class="offer-price"><?php echo $price_info['offer']; ?></span>
                                            <span class="original-price"><?php echo $price_info['original']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <div class="no-bookings-content">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No Bookings Yet</h4>
                            <p>You haven't made any bookings yet. Start by creating your first booking!</p>
                            <a href="components/blog.html#booking" class="create-booking-btn">
                                <i class="fas fa-plus"></i> Create Your First Booking
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions Section -->
            <div class="quick-actions-section">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <a href="components/blog.html#booking" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Booking</span>
                    </a>
                    <a href="#" class="action-card" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        <span>Print Bookings</span>
                    </a>
                    <a href="mailto:rohdip@colorlib.com" class="action-card">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Support</span>
                    </a>
                </div>
            </div>

            <!-- Login Success Section -->
            <div class="login-success-section">
                <div class="success-banner">
                    <h3><i class="fas fa-check-circle"></i> Login Successful!</h3>
                    <p><strong>Validation System Working:</strong> You have successfully signed in because your data exists in our database.</p>
                </div>

                <div class="validation-status">
                    <h4><i class="fas fa-shield-alt"></i> Database Validation Status</h4>
                    <div class="status-grid">
                        <div class="status-item">
                            <i class="fas fa-check-circle status-icon"></i>
                            <div class="status-text">
                                <h5>User Registered</h5>
                                <p>Your account exists in database</p>
                            </div>
                        </div>
                        <div class="status-item">
                            <i class="fas fa-check-circle status-icon"></i>
                            <div class="status-text">
                                <h5>Password Verified</h5>
                                <p>Credentials matched successfully</p>
                            </div>
                        </div>
                        <div class="status-item">
                            <i class="fas fa-check-circle status-icon"></i>
                            <div class="status-text">
                                <h5>Session Created</h5>
                                <p>Secure login session established</p>
                            </div>
                        </div>
                        <div class="status-item">
                            <i class="fas fa-check-circle status-icon"></i>
                            <div class="status-text">
                                <h5>Access Granted</h5>
                                <p>Dashboard access authorized</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="system-info">
                    <h4>System Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Database:</strong> rohan
                        </div>
                        <div class="info-item">
                            <strong>Table:</strong> users
                        </div>
                        <div class="info-item">
                            <strong>Validation:</strong> Active
                        </div>
                        <div class="info-item">
                            <strong>Security:</strong> Password Hashed
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Interactive booking row functionality
        function toggleBookingDetails(bookingId) {
            const row = event.currentTarget;
            row.classList.toggle('selected');
            
            // Add visual feedback
            if (row.classList.contains('selected')) {
                row.style.backgroundColor = '#e3f2fd';
                row.style.transform = 'scale(1.02)';
            } else {
                row.style.backgroundColor = '';
                row.style.transform = '';
            }
        }

        // Add hover effects and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.action-card, .status-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add interactive hover effects to table rows
            const bookingRows = document.querySelectorAll('.booking-row');
            bookingRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.cursor = 'pointer';
                });
                
                row.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.backgroundColor = '';
                    }
                });
            });

            // Add click animation to action cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });

        // Print functionality
        function printBookings() {
            window.print();
        }

        // Smooth scroll to sections
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>