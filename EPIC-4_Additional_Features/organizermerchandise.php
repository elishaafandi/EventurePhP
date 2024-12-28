<?php
session_start();
require 'config.php';

if (!isset($_SESSION["ID"])) {
    header("Location: organizerhome.php");
    exit;
}

if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
}

$selected_club_id = isset($_SESSION["SELECTEDID"]) ? $_SESSION["SELECTEDID"] : '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $_POST['name'];
    $item_description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock'];
    $item_type = $_POST['item_type'];
    
    // Handle multiple size selection
    $item_size = isset($_POST['item_size']) ? implode(',', $_POST['item_size']) : NULL;
    
    $pickup_address = $_POST['pickup_address'];
    
    // Handle file uploads
    if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] === 0) {
        $item_image = file_get_contents($_FILES['item_image']['tmp_name']);
    }
    
    if(isset($_FILES['promotional_image']) && $_FILES['promotional_image']['error'] === 0) {
        $promotional_image = file_get_contents($_FILES['promotional_image']['tmp_name']);
    }
    
    $stmt = $conn->prepare("INSERT INTO merch_organiser (club_id, item_image, promotional_image, 
        item_name, item_description, item_type, item_size, price, stock_quantity, pickup_address, 
        promotion_start, promotion_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    
    $promotion_end = date('Y-m-d H:i:s', strtotime($_POST['promotion_end']));
    
    $stmt->bind_param("issssssdiss", 
        $selected_club_id,
        $item_image,
        $promotional_image,
        $item_name,
        $item_description,
        $item_type,
        $item_size,
        $price,
        $stock_quantity,
        $pickup_address,
        $promotion_end
    );
    
    if($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to get merchandise items for the selected club
function getMerchandise($conn, $club_id) {
    $merchandise = array();
    $stmt = $conn->prepare("SELECT * FROM merch_organiser WHERE club_id = ?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        // Convert BLOB data to base64 for image display
        $row['item_image'] = base64_encode($row['item_image']);
        $row['promotional_image'] = base64_encode($row['promotional_image']);
        $merchandise[] = $row;
    }
    
    $stmt->close();
    return $merchandise;
}

function getMerchPurchases($conn, $club_id) {
    $purchases = array();
    $query = "SELECT mo.item_name, 
              COUNT(mp.order_id) as total_orders,
              SUM(CASE WHEN mp.delivery_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_orders,
              mo.merch_org_id
              FROM merch_organiser mo
              LEFT JOIN merch_participant mp ON mo.merch_org_id = mp.merch_org_id
              WHERE mo.club_id = ? AND mp.payment_status = 'Success'
              GROUP BY mo.merch_org_id, mo.item_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $purchases[] = $row;
    }
    
    $stmt->close();
    return $purchases;
}

$purchases = getMerchPurchases($conn, $selected_club_id);
// Get merchandise for the current club
$merchandise = getMerchandise($conn, $selected_club_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Site</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizermerchandise.css">
    <style>
        /* Additional styles for multiple select */
        .size-select-container {
            position: relative;
        }
        .size-select-container select {
            min-height: 100px;
            padding: 8px;
        }
        .size-select-container select option {
            padding: 8px;
        }
    </style>
</head>
<body>

<header>
        <h1>Merchandise</h1>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <a href="profilepage.php" class="profile-icon"><i class="fas fa-user-circle"></i></a>
            </div>
        </div>
    </header>


    <main>

    <aside class="sidebar">
        <div class="logo-container">
            <a href="organizerhome.php" class="logo">EVENTURE</a>
        </div>
        <ul>
            <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
            <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
            <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
            <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Crew Listing</a></li>
            <li><a href="organizerclubmembership.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerclub membership.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Club Membership</a></li>
            <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
            <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
            <li><a href="organizermerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizermerchandise.php' ? 'active' : 'active'; ?>"><i class="fas fa-tshirt"></i>Merchandise</a></li>
        </ul>
        <ul style="margin-top: 60px;">
            <li><a href="organizerrevenue.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerrevenue.php' ? '' : ''; ?>"><i class="fas fa-hand-holding-usd"></i>Revenue</a></li>
            <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    
    <div class="main-content">

    <!--Purchases Table -->
    <h2 class="section-title">Purchases</h2>
        <div class = "purchases">
        <table class="purchase-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Total Orders</th>
                    <th>Delivered Orders</th>
                    <th>Pending Delivery</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($purchase['item_name']); ?></td>
                        <td><?php echo $purchase['total_orders']; ?></td>
                        <td><?php echo $purchase['delivered_orders']; ?></td>
                        <td><?php echo $purchase['total_orders'] - $purchase['delivered_orders']; ?></td>
                        <td>
                            <a href="view_orders.php?merch_id=<?php echo $purchase['merch_org_id']; ?>" 
                               class="view-btn">View Orders</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>  
        </div>

        
        <h2 class="section-title">Add New Merchandise</h2>
        <div class="merchandise-form">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Item Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price (RM)</label>
                        <input type="number" id="price" name="price" step="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="item_type">Item Type</label>
                        <select id="item_type" name="item_type" required>
                            <option value="T-shirt">T-shirt</option>
                            <option value="Keychain">Keychain</option>
                            <option value="Cap">Cap</option>
                            <option value="Hoodie">Hoodie</option>
                            <option value="Mugs">Mugs</option>
                        </select>
                    </div>
                    <div class="form-group size-select-container">
                        <label for="item_size">Sizes Available (Hold Ctrl/Cmd to select multiple)</label>
                        <select id="item_size" name="item_size[]" multiple size="7">
                          <option value="Not Applicable">Not Applicable</option> 
                          <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock" required>
                    </div>
                    <div class="form-group">
                        <label for="pickup_address">Pickup Address</label>
                        <input type="text" id="pickup_address" name="pickup_address" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="item_image">Product Image</label>
                        <input type="file" id="item_image" name="item_image" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="promotional_image">Promotional Image</label>
                        <input type="file" id="promotional_image" name="promotional_image" accept="image/*" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="promotion_end">Promotion End Date</label>
                    <input type="datetime-local" id="promotion_end" name="promotion_end" required>
                </div>
                <button type="submit" class="submit-btn">Add Merchandise</button>
            </form>
        </div>

        
        <h2 class="section-title">Current Merchandise</h2>
        <div class="merchandise-grid">
            <?php foreach ($merchandise as $item): ?>
                <a href="edit_merchandise.php?id=<?php echo $item['merch_org_id']; ?>" class="merchandise-link"> 
                <div class="merchandise-card">
                    <img src="data:image/jpeg;base64,<?php echo $item['item_image']; ?>" 
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                         class="merchandise-image">
                    <div class="merchandise-details">
                        <h3 class="merchandise-name"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                        <p class="merchandise-description"><?php echo htmlspecialchars($item['item_description']); ?></p>
                        <p class="merchandise-type">Type: <?php echo htmlspecialchars($item['item_type']); ?></p>
                        <?php if ($item['item_size']): ?>
                            <p class="merchandise-size">Sizes Available: <?php echo htmlspecialchars($item['item_size']); ?></p>
                        <?php endif; ?>
                        <div class="merchandise-price">RM<?php echo number_format($item['price'], 2); ?></div>
                        <div class="merchandise-stock">In Stock: <?php echo htmlspecialchars($item['stock_quantity']); ?></div>
                        <p class="merchandise-pickup">Pickup: <?php echo htmlspecialchars($item['pickup_address']); ?></p>
                        <p class="merchandise-promotion">Promotion ends: <?php echo date('Y-m-d H:i', strtotime($item['promotion_end'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>