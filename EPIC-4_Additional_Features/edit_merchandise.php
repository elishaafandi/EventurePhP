<?php
session_start();
require 'config.php';

if (!isset($_SESSION["ID"])) {
    header("Location: organizerhome.php");
    exit;
}

// Get merchandise details
if (isset($_GET['id'])) {
    $merch_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM merch_organiser WHERE merch_org_id = ?");
    $stmt->bind_param("i", $merch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $merchandise = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission for updating
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $item_name = $_POST['name'];
    $item_description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock'];
    $item_type = $_POST['item_type'];
    $item_size = isset($_POST['item_size']) ? implode(',', $_POST['item_size']) : NULL;
    $pickup_address = $_POST['pickup_address'];
    $promotion_end = date('Y-m-d H:i:s', strtotime($_POST['promotion_end']));
    
    $update_query = "UPDATE merch_organiser SET 
        item_name = ?, 
        item_description = ?, 
        item_type = ?, 
        item_size = ?, 
        price = ?, 
        stock_quantity = ?, 
        pickup_address = ?, 
        promotion_end = ?";
    
    $params = [
        $item_name, 
        $item_description, 
        $item_type, 
        $item_size, 
        $price, 
        $stock_quantity, 
        $pickup_address, 
        $promotion_end
    ];
    $types = "sssssdss";
    
    // Handle image updates if new images are uploaded
    if(isset($_FILES['item_image']) && $_FILES['item_image']['error'] === 0) {
        $item_image = file_get_contents($_FILES['item_image']['tmp_name']);
        $update_query .= ", item_image = ?";
        $params[] = $item_image;
        $types .= "s";
    }
    
    if(isset($_FILES['promotional_image']) && $_FILES['promotional_image']['error'] === 0) {
        $promotional_image = file_get_contents($_FILES['promotional_image']['tmp_name']);
        $update_query .= ", promotional_image = ?";
        $params[] = $promotional_image;
        $types .= "s";
    }
    
    $update_query .= " WHERE merch_org_id = ?";
    $params[] = $merch_id;
    $types .= "i";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param($types, ...$params);
    
    if($stmt->execute()) {
        header("Location: organizermerchandise.php");
        exit();
    } else {
        echo "Error updating merchandise: " . $stmt->error;
    }
    $stmt->close();
}

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM merch_organiser WHERE merch_org_id = ?");
    $stmt->bind_param("i", $merch_id);
    
    if($stmt->execute()) {
        header("Location: organizermerchandise.php");
        exit();
    } else {
        echo "Error deleting merchandise: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Merchandise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizermerchandise.css">
    <style>
        /* Enhanced styling for edit merchandise page */
        .edit-merchandise-container {
            padding: 30px;
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .edit-merchandise-container h2 {
            color: #891a1a;
            font-size: 28px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #891a1a;
        }

        .current-images {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 8px;
        }

        .current-images > div {
            flex: 1;
            text-align: center;
        }

        .current-images h4 {
            color: #555;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .current-images img {
            max-width: 300px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        /* Form styling */
        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #444;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 15px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="datetime-local"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: #f9f9f9;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #891a1a;
            box-shadow: 0 0 0 3px rgba(137, 26, 26, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* File input styling */
        .form-group input[type="file"] {
            padding: 10px;
            background-color: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }

        .form-group input[type="file"]:hover {
            border-color: #891a1a;
        }

        /* Size select container */
        .size-select-container select {
            min-height: 150px;
            background-color: #f9f9f9;
        }

        .size-select-container select option {
            padding: 10px;
            font-size: 14px;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .action-buttons button,
        .action-buttons a {
            flex: 1;
            padding: 14px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .update-btn {
            background-color: #891a1a;
            color: white;
        }

        .update-btn:hover {
            background-color: #6d1515;
            transform: translateY(-2px);
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .current-images {
                flex-direction: column;
            }
            
            .current-images img {
                max-width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Add subtle animations */
        .edit-merchandise-container {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? '' : ''; ?>"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
            <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Crew Listing</a></li>
            <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
            <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
            <li><a href="organizermerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizermerchandise.php' ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i>Merchandise</a></li>
        </ul>
        <ul style="margin-top: 60px;">
            <li><a href="organizerrevenue.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerrevenue.php' ? '' : ''; ?>"><i class="fas fa-hand-holding-usd"></i>Revenue</a></li>
            <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    
    <div class="edit-merchandise-container">
        <h2>Edit Merchandise</h2>
        
        <div class="current-images">
            <div>
                <h4>Current Product Image</h4>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($merchandise['item_image']); ?>" 
                     alt="Current Product Image">
            </div>
            <div>
                <h4>Current Promotional Image</h4>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($merchandise['promotional_image']); ?>" 
                     alt="Current Promotional Image">
            </div>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Item Name</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($merchandise['item_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Price (RM)</label>
                    <input type="number" id="price" name="price" step="0.01" 
                           value="<?php echo $merchandise['price']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($merchandise['item_description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="item_type">Item Type</label>
                    <select id="item_type" name="item_type" required>
                        <option value="T-shirt" <?php echo $merchandise['item_type'] == 'T-shirt' ? 'selected' : ''; ?>>T-shirt</option>
                        <option value="Keychain" <?php echo $merchandise['item_type'] == 'Keychain' ? 'selected' : ''; ?>>Keychain</option>
                        <option value="Cap" <?php echo $merchandise['item_type'] == 'Cap' ? 'selected' : ''; ?>>Cap</option>
                        <option value="Hoodie" <?php echo $merchandise['item_type'] == 'Hoodie' ? 'selected' : ''; ?>>Hoodie</option>
                        <option value="Mugs" <?php echo $merchandise['item_type'] == 'Mugs' ? 'selected' : ''; ?>>Mugs</option>
                    </select>
                </div>
                
                <div class="form-group size-select-container">
                    <label for="item_size">Sizes Available (Hold Ctrl/Cmd to select multiple)</label>
                    <select id="item_size" name="item_size[]" multiple size="7">
                        <?php 
                        $sizes = explode(',', $merchandise['item_size']);
                        $available_sizes = ['Not Applicable', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
                        foreach($available_sizes as $size): 
                        ?>
                            <option value="<?php echo $size; ?>" 
                                <?php echo in_array($size, $sizes) ? 'selected' : ''; ?>>
                                <?php echo $size; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="stock">Stock Quantity</label>
                    <input type="number" id="stock" name="stock" 
                           value="<?php echo $merchandise['stock_quantity']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="pickup_address">Pickup Address</label>
                    <input type="text" id="pickup_address" name="pickup_address" 
                           value="<?php echo htmlspecialchars($merchandise['pickup_address']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="item_image">New Product Image (optional)</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="promotional_image">New Promotional Image (optional)</label>
                    <input type="file" id="promotional_image" name="promotional_image" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label for="promotion_end">Promotion End Date</label>
                <input type="datetime-local" id="promotion_end" name="promotion_end" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($merchandise['promotion_end'])); ?>" required>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="update" class="update-btn">Update Merchandise</button>
                <button type="submit" name="delete" class="delete-btn" 
                        onclick="return confirm('Are you sure you want to delete this merchandise?')">
                    Delete Merchandise
                </button>
                <a href="organizermerchandise.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>