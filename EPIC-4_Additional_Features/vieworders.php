<?php
// Include the config file to establish the database connection
session_start();

include 'config.php';

if (!isset($_SESSION["ID"])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Fetch student details
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build the query
$query = "SELECT mp.*, mo.item_name, mo.item_image, c.club_name 
          FROM merch_participant mp 
          JOIN merch_organiser mo ON mp.merch_org_id = mo.merch_org_id 
          JOIN clubs c ON mp.club_id = c.club_id 
          WHERE mp.user_id = ?";

if ($status_filter) {
    $query .= " AND mp.delivery_status = ?";
}
if ($date_filter) {
    $query .= " AND DATE(mp.order_date) = ?";
}
$query .= " ORDER BY mp.order_date DESC";

$stmt = $conn->prepare($query);

// Bind parameters based on filters
if ($status_filter && $date_filter) {
    $stmt->bind_param("iss", $user_id, $status_filter, $date_filter);
} elseif ($status_filter) {
    $stmt->bind_param("is", $user_id, $status_filter);
} elseif ($date_filter) {
    $stmt->bind_param("is", $user_id, $date_filter);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Eventure</title>
    <link rel="stylesheet" href="participantmerchandise.css">
    <link rel="stylesheet" href="vieworders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- HEADER NAVIGATION BAR  -->   
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php">Home</a>
                <a href="participantdashboard.php">Dashboard</a>
                <a href="participantcalendar.php">Calendar</a>
                <a href="participantmerchandise.php">Merchandise</a>
                <a href="profilepage.php">User Profile</a>
            </nav>
        </div>
        <div class="nav-right">
            <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
            <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
            <div class="profile-menu">
                <?php if (!empty($student['student_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($student['student_photo']); ?>" alt="Student Photo" class="profile-icon">
                <?php else: ?>
                    <img src="default-profile.png" alt="Default Profile" class="profile-icon">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <a href="profilepage.php">Profile</a>
                    <hr>
                    <a href="logout.php" class="sign-out">Sign Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="orders-main">
        
    <a href="participantmerchandise.php" class="back-button">
    <i class="fas fa-arrow-left"></i> Back to Merchandise
    </a>


        <div class="orders-container">
            <div class="orders-header">
                <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
                
                <!-- Filters -->
                <div class="filters">
                    <form action="" method="GET" class="filter-form">
                        <select name="status" class="filter-select">
                            <option value="">All Statuses</option>
                            <option value="Not Delivered" <?php echo $status_filter == 'Not Delivered' ? 'selected' : ''; ?>>Not Delivered</option>
                            <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo $status_filter == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                        </select>
                        <input type="date" name="date" class="filter-date" value="<?php echo $date_filter; ?>">
                        <button type="submit" class="filter-button">Apply Filters</button>
                        <a href="vieworders.php" class="clear-filters">Clear Filters</a>
                    </form>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="orders-grid">
                    <?php while($order = $result->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-title">
                                    <h3>Order #<?php echo $order['order_id']; ?></h3>
                                    <span class="order-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                    </span>
                                </div>
                                <span class="status-badge <?php echo strtolower($order['delivery_status']); ?>">
                                    <?php echo $order['delivery_status']; ?>
                                </span>
                            </div>
                            
                            <div class="order-content">
                                <div class="product-image">
                                    <?php if ($order['item_image']): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($order['item_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['item_name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="order-details">
                                    <h4><?php echo htmlspecialchars($order['item_name']); ?></h4>
                                    <p class="club-name">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($order['club_name']); ?>
                                    </p>
                                    <div class="order-specifics">
                                        <span>
                                            <i class="fas fa-box"></i>
                                            Quantity: <?php echo $order['quantity']; ?>
                                        </span>
                                        <?php if ($order['size'] != 'Not Applicable'): ?>
                                            <span>
                                                <i class="fas fa-tshirt"></i>
                                                Size: <?php echo $order['size']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span>
                                            <i class="fas fa-wallet"></i>
                                            RM <?php echo number_format($order['total_price'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="order-footer">
                                <?php if ($order['tracking_no']): ?>
                                    <div class="tracking-info">
                                        <i class="fas fa-truck"></i>
                                        Tracking: #<?php echo $order['tracking_no']; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="delivery-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo $order['distribution_method']; ?>: 
                                    <?php echo htmlspecialchars($order['address']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>No orders found</h2>
                    <p>Looks like you haven't made any purchases yet.</p>
                    <a href="participantmerchandise.php" class="shop-now-btn">Shop Now</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>