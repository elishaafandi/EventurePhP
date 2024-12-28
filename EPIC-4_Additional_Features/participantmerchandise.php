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


// Fetch student details to autofill form
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

// Get total orders
$totalOrdersQuery = "SELECT COUNT(*) as total FROM merch_participant WHERE user_id = ?";
$stmt = $conn->prepare($totalOrdersQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalOrders = $stmt->get_result()->fetch_assoc()['total'];

// Get delivered orders
$deliveredQuery = "SELECT COUNT(*) as delivered FROM merch_participant WHERE user_id = ? AND delivery_status = 'Delivered'";
$stmt = $conn->prepare($deliveredQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$deliveredOrders = $stmt->get_result()->fetch_assoc()['delivered'];

// Get pending orders
$pendingQuery = "SELECT COUNT(*) as pending FROM merch_participant WHERE user_id = ? AND delivery_status != 'Delivered'";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendingOrders = $stmt->get_result()->fetch_assoc()['pending'];

// Get total spent
$totalSpentQuery = "SELECT SUM(total_price) as total FROM merch_participant WHERE user_id = ? AND payment_status = 'Success'";
$stmt = $conn->prepare($totalSpentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalSpent = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$recentPurchasesQuery = "SELECT mp.*, mo.item_name, mo.item_image 
                        FROM merch_participant mp 
                        JOIN merch_organiser mo ON mp.merch_org_id = mo.merch_org_id 
                        WHERE mp.user_id = ? 
                        ORDER BY mp.order_date DESC 
                        LIMIT 3";
$stmt = $conn->prepare($recentPurchasesQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentPurchases = $stmt->get_result();

// Close the database connection
mysqli_close($conn);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="participantmerchandise.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <!-- HEADER NAVIGATION BAR  -->   
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a>
                <a href="participantmerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantmerchandise.php' ? 'active' : 'active'; ?>"></i>Merchandise</a>
            </nav>
        </div>
        <div class="nav-right">
            <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
            <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
            <div class="profile-menu">
                <!-- Ensure the profile image is fetched and rendered properly -->
                <?php if (!empty($student['student_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($student['student_photo']); ?>" alt="Student Photo" class="profile-icon">
                <?php else: ?>
                    <img src="default-profile.png" alt="Default Profile" class="profile-icon">
                <?php endif; ?>

                <!-- Dropdown menu -->
                <div class="dropdown-menu">
                    <a href="profilepage.php">Profile</a>
                    <hr>
                    <a href="logout.php" class="sign-out">Sign Out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
    <div class="merchandise-content">
        <div class="filters">
        <input type="text" class="search-bar" placeholder="Search merchandise...">
        <select class="filter-select" id="type-filter">
            <option value="">All Types</option>
            <option value="T-shirt">T-shirt</option>
            <option value="Keychain">Keychain</option>
            <option value="Cap">Cap</option>
            <option value="Hoodie">Hoodie</option>
            <option value="Mugs">Mugs</option>
        </select>
        <select class="filter-select" id="size-filter">
            <option value="">All Sizes</option>
            <option value="XS">XS</option>
            <option value="S">S</option>
            <option value="M">M</option>
            <option value="L">L</option>
            <option value="XL">XL</option>
            <option value="XXL">XXL</option>
        </select>
    </div>

        <div class="merchandise-grid">
            <?php
            // Reestablish database connection
            include 'config.php';
            
            $query = "SELECT * FROM merch_organiser";
            $result = $conn->query($query);
            
            while($row = $result->fetch_assoc()) {
                ?>
                <div class="merchandise-card">
                    <?php if ($row['item_image']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['item_image']); ?>" 
                            alt="<?php echo htmlspecialchars($row['item_name']); ?>" 
                            class="merchandise-image">
                    <?php endif; ?>
                    
                    <div class="merchandise-info">
                        <h3 class="merchandise-name"><?php echo htmlspecialchars($row['item_name']); ?></h3>
                        <p class="merchandise-type"><?php echo htmlspecialchars($row['item_type']); ?></p>
                        <p class="merchandise-description"><?php echo htmlspecialchars($row['item_description']); ?></p>
                        <div class="merchandise-details">
                            <p class="merchandise-price">RM <?php echo number_format($row['price'], 2); ?></p>
                            <?php if ($row['item_size'] != 'Not Applicable'): ?>
                                <p class="merchandise-size">Size: <?php echo htmlspecialchars($row['item_size']); ?></p>
                            <?php endif; ?>
                            <p class="merchandise-stock">Stock: <?php echo $row['stock_quantity']; ?> units</p>
                            <p class="merchandise-pickup">Pickup: <?php echo htmlspecialchars($row['pickup_address']); ?></p>
                        </div>
                        <a href="viewmerch.php?id=<?php echo $row['merch_org_id']; ?>" class="buy-button">Purchase</a>
                    </div>

                    
                </div>
                <?php
            }
            $conn->close();
            ?>
        </div>
        </div> 

        <div class="side-panel">
        <div class="stats-container">
            <h2 class="stats-title">
                <i class="fas fa-shopping-bag"></i>
                My Orders Overview
            </h2>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Orders</span>
                    <span class="stat-value"><?php echo $totalOrders; ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon delivered">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Delivered</span>
                    <span class="stat-value delivered-text"><?php echo $deliveredOrders; ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value pending-text"><?php echo $pendingOrders; ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Total Spent</span>
                    <span class="stat-value">RM <?php echo number_format($totalSpent, 2); ?></span>
                </div>
            </div>

            <a href="vieworders.php" class="view-orders-btn">
                <i class="fas fa-scroll"></i>
                View All Orders
            </a>
        </div>
        
    <div class="stats-container mt-4">
        <h2 class="stats-title">
            <i class="fas fa-clock-rotate-left"></i>
            Recent Purchases
        </h2>
        
        <div class="recent-purchases">
            <?php if ($recentPurchases->num_rows > 0): ?>
                <?php while($purchase = $recentPurchases->fetch_assoc()): ?>
                    <div class="recent-purchase-card">
                        <div class="purchase-image">
                            <?php if ($purchase['item_image']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($purchase['item_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($purchase['item_name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="purchase-info">
                            <h3 class="purchase-name"><?php echo htmlspecialchars($purchase['item_name']); ?></h3>
                            <p class="purchase-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M d, Y', strtotime($purchase['order_date'])); ?>
                            </p>
                            <p class="purchase-price">RM <?php echo number_format($purchase['total_price'], 2); ?></p>
                            <span class="purchase-status <?php echo strtolower($purchase['delivery_status']); ?>">
                                <?php echo $purchase['delivery_status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-purchases">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No recent purchases</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchBar = document.querySelector('.search-bar');
            const typeFilter = document.querySelector('#type-filter');
            const sizeFilter = document.querySelector('#size-filter');
            const merchandiseCards = document.querySelectorAll('.merchandise-card');

            // Function to filter merchandise
            function filterMerchandise() {
                const searchTerm = searchBar.value.toLowerCase();
                const selectedType = typeFilter.value.toLowerCase();
                const selectedSize = sizeFilter.value.toLowerCase();

                merchandiseCards.forEach(card => {
                    const name = card.querySelector('.merchandise-name').textContent.toLowerCase();
                    const type = card.querySelector('.merchandise-type').textContent.toLowerCase();
                    const sizeElement = card.querySelector('.merchandise-size');
                    const size = sizeElement ? sizeElement.textContent.toLowerCase() : '';

                    // Check if card matches all filters
                    const matchesSearch = name.includes(searchTerm);
                    const matchesType = selectedType === '' || type.includes(selectedType);
                    const matchesSize = selectedSize === '' || size.includes(selectedSize);

                    // Show/hide card based on filters
                    if (matchesSearch && matchesType && matchesSize) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });

                // Check if no results found
                const visibleCards = document.querySelectorAll('.merchandise-card:not(.hidden)');
                const noResultsMessage = document.querySelector('.no-results');
                
                if (visibleCards.length === 0) {
                    if (!noResultsMessage) {
                        const message = document.createElement('div');
                        message.className = 'no-results';
                        message.style.textAlign = 'center';
                        message.style.padding = '2rem';
                        message.style.color = '#4a5568';
                        message.style.fontSize = '1.1rem';
                        message.textContent = 'No merchandise found matching your criteria.';
                        document.querySelector('.merchandise-grid').appendChild(message);
                    }
                } else if (noResultsMessage) {
                    noResultsMessage.remove();
                }
            }

            // Add event listeners
            searchBar.addEventListener('input', filterMerchandise);
            typeFilter.addEventListener('change', filterMerchandise);
            sizeFilter.addEventListener('change', filterMerchandise);

            // Add debounce to search for better performance
            let searchTimeout;
            searchBar.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterMerchandise, 300);
            });
        });
        </script>
</body>
</html>
