<?php
session_start();
include 'config.php';

if (!isset($_SESSION["ID"])) {
    header("Location: login.php");
    exit;
}

// Get the merchandise ID from URL
if (!isset($_GET['id'])) {
    header("Location: participantmerchandise.php");
    exit;
}

$merch_id = $_GET['id'];
$user_id = $_SESSION['ID'];

// Updated query to fetch both merchandise and club details
$query = "SELECT m.*, c.club_id, c.club_name, c.club_photo 
          FROM merch_organiser m 
          LEFT JOIN clubs c ON m.club_id = c.club_id 
          WHERE m.merch_org_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $merch_id);
$stmt->execute();
$result = $stmt->get_result();
$merch = $result->fetch_assoc();

$available_sizes = $merch['item_size'] ? explode(',', $merch['item_size']) : [];

// If merchandise doesn't exist, redirect back
if (!$merch) {
    header("Location: participantmerchandise.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Merchandise</title>
    <link rel="stylesheet" href="participantmerchandise.css">
    <style>
         .product-details {
        padding: 2rem;
        margin-bottom: 2rem;
        background: #f7f7f7;
        border-radius: 8px;
    }
    
    .product-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .product-images {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .product-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .club-info {
        margin-top: 2rem;
        padding: 1rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .club-photo {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        margin-right: 1rem;
    }
    
    .club-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .price-tag {
        font-size: 1.5rem;
        color: #800c12;
        font-weight: bold;
        margin: 1rem 0;
    }
    
    .stock-info {
        color: #718096;
        padding: 0.5rem;
        background: #f1f1f1;
        border-radius: 4px;
        display: inline-block;
    }

    .product-info {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .item-details {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .item-property {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .item-property-label {
        font-weight: bold;
        color: #4a5568;
    }
    .pickup-address-display {
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-top: 5px;
    }

        .purchase-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .purchase-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .step {
            text-align: center;
            color: #718096;
        }

        .step.active {
            color: #800c12;
            font-weight: bold;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .payment-options {
            display: grid;
            gap: 1rem;
            margin: 1rem 0;
        }

        .payment-option {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-option:hover {
            border-color: #800c12;
        }

        .payment-option.selected {
            border-color: #800c12;
            background-color: #fff5f5;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .button {
            background: #800c12;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .button:hover {
            background: #9b0f16;
        }

        .button.secondary {
            background: #718096;
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
<header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a>
                <a href="participantmerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantmerchandise.php' ? 'active' : ''; ?>"></i>Merchandise</a>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a>
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

    <div class="purchase-container">
    <div class="product-details">
            <div class="product-layout">
                <div class="product-images">
                    <?php if($merch['item_image']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($merch['item_image']); ?>" 
                             alt="Product Image" class="product-image">
                    <?php endif; ?>
                    
                    <?php if($merch['promotional_image']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($merch['promotional_image']); ?>" 
                             alt="Promotional Image" class="product-image">
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <h2><?php echo htmlspecialchars($merch['item_name']); ?></h2>
                    
                    <div class="item-details">
                        <div class="item-property">
                            <span class="item-property-label">Description:</span>
                            <span><?php echo htmlspecialchars($merch['item_description']); ?></span>
                        </div>
                        <div class="item-property">
                            <span class="item-property-label">Type:</span>
                            <span><?php echo htmlspecialchars($merch['item_type']); ?></span>
                        </div>

                        <?php if ($merch['item_size'] != 'Not Applicable' && !empty($available_sizes)): ?>
                        <div class="item-property">
                            <span class="item-property-label">Available Sizes:</span>
                            <span><?php echo str_replace(',', ', ', $merch['item_size']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="price-tag">RM <?php echo number_format($merch['price'], 2); ?></div>
                    <div class="stock-info">Stock Available: <?php echo $merch['stock_quantity']; ?> units</div>

                    <div class="club-info">
                        <div class="club-header">
                            <?php if($merch['club_photo']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($merch['club_photo']); ?>" 
                                     alt="Club Photo" class="club-photo">
                            <?php endif; ?>
                            <div>
                                <h3>Sold by</h3>
                                <strong><?php echo htmlspecialchars($merch['club_name']); ?></strong>
                            </div>
                        </div>
                        <div class="pickup-address">
                            <strong>Pickup Location:</strong><br>
                            <?php echo htmlspecialchars($merch['pickup_address']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="purchase-steps">
            <div class="step active" id="step1">1. Order Details</div>
            <div class="step" id="step2">2. Payment</div>
            <div class="step" id="step3">3. Confirmation</div>
        </div>

        <!-- Step 1: Order Details -->
        <div class="step-content active" id="step1-content">
            <form id="orderForm">
                <input type="hidden" name="merch_org_id" value="<?php echo $merch_id; ?>">
                <input type="hidden" name="club_id" value="<?php echo $merch['club_id']; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" max="<?php echo $merch['stock_quantity']; ?>" required>
                </div>

                <?php if ($merch['item_size'] != 'Not Applicable'): ?>
                    <div class="form-group">
                        <label>Size</label>
                        <select name="size" required>
                            <?php foreach($available_sizes as $size): ?>
                                <option value="<?php echo trim($size); ?>"><?php echo trim($size); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Distribution Method</label>
                    <select name="distribution_method" id="distributionMethod" onchange="toggleAddress()" required>
                        <option value="Pickup">Pickup</option>
                        <option value="Delivery">Delivery</option>
                    </select>
                </div>
               
                <div class="form-group" id="addressGroup">
                    <label>Address</label>
                    <input type="text" name="address" id="addressInput">
                </div>

                <div class="form-group" id="pickupAddressGroup" style="display: none;">
                    <label>Pickup Address</label>
                    <div class="pickup-address-display">
                        <?php echo htmlspecialchars($merch['pickup_address']); ?>
                    </div>
                    <input type="hidden" name="pickup_address" value="<?php echo htmlspecialchars($merch['pickup_address']); ?>">
                </div>


                <div class="navigation-buttons">
                    <button type="button" class="button" onclick="nextStep(1)">Continue to Payment</button>
                </div>
            </form>
        </div>

       <!-- Step 2: Payment -->
        <div class="step-content" id="step2-content">
            <div class="payment-options">
                <div class="payment-option" onclick="selectPayment('credit_card')">
                    <h3>Credit Card</h3>
                    <p>Pay securely with your credit card</p>
                </div>
                <div class="payment-option" onclick="selectPayment('online_banking')">
                    <h3>Online Banking</h3>
                    <p>Direct transfer from your bank account</p>
                </div>
            </div>

            <div id="payment-form" style="display: none;">
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" id="card_number" pattern="[0-9]{16}" placeholder="1234 5678 9012 3456">
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" id="expiry" pattern="[0-9]{2}/[0-9]{2}" placeholder="MM/YY">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" id="cvv" pattern="[0-9]{3}" placeholder="123">
                </div>
            </div>
            
            <!-- Update the bank-list div in Step 2: Payment section -->
            <div id="bank-list" style="display: none;">
                <h3>Select Your Bank</h3>
                <div class="bank-options">
                    <button class="bank-option" onclick="selectBank('Bank A')">Bank A</button>
                    <button class="bank-option" onclick="selectBank('Bank B')">Bank B</button>
                    <button class="bank-option" onclick="selectBank('Bank C')">Bank C</button>
                </div>
            </div>

            <div class="navigation-buttons">
                <button type="button" class="button secondary" onclick="prevStep(2)">Back</button>
                <button type="button" class="button" onclick="processPayment()">Complete Payment</button>
            </div>
        </div>

        <!-- Step 3: Confirmation -->
        <div class="step-content" id="step3-content">
            <h2>Order Confirmation</h2>
            <div id="confirmation-details">
                <!-- Order details will be dynamically inserted here after payment -->
            </div>
            <div class="navigation-buttons">
                <button type="button" class="button" onclick="window.location.href='participantmerchandise.php'">Back to Merchandise</button>
            </div>
        </div>


    <script>
       function toggleAddress() {
        const distributionMethod = document.getElementById('distributionMethod').value;
        const addressGroup = document.getElementById('addressGroup');
        const pickupAddressGroup = document.getElementById('pickupAddressGroup');
        const addressInput = document.getElementById('addressInput');

        if (distributionMethod === 'Pickup') {
            addressGroup.style.display = 'none';
            pickupAddressGroup.style.display = 'block';
            addressInput.value = document.querySelector('input[name="pickup_address"]').value;
            addressInput.required = false;
        } else {
            addressGroup.style.display = 'block';
            pickupAddressGroup.style.display = 'none';
            addressInput.value = '';
            addressInput.required = true;
        }
    }


    document.addEventListener('DOMContentLoaded', toggleAddress);




       let currentStep = 1;
        let selectedPayment = null;
        let selectedBank = null;
        let orderData = null;

        function nextStep(step) {
            if (step === 1) {
                // Collect form data
                const formData = new FormData(document.getElementById('orderForm'));
                orderData = Object.fromEntries(formData.entries());

                // Ensure PHP variable is available and numeric
                const price = parseFloat(<?php echo json_encode($merch['price']); ?>);
                if (isNaN(price)) {
                    alert('Invalid product price.');
                    return;
                }
                orderData.total_price = price * parseInt(orderData.quantity);
            }

            document.querySelector(`#step${step}-content`).classList.remove('active');
            document.querySelector(`#step${step + 1}-content`).classList.add('active');
            document.querySelector(`#step${step}`).classList.remove('active');
            document.querySelector(`#step${step + 1}`).classList.add('active');
            currentStep = step + 1;
        }

        function prevStep(step) {
            document.querySelector(`#step${step}-content`).classList.remove('active');
            document.querySelector(`#step${step - 1}-content`).classList.add('active');
            document.querySelector(`#step${step}`).classList.remove('active');
            document.querySelector(`#step${step - 1}`).classList.add('active');
            currentStep = step - 1;
        }

        function selectPayment(method) {
            selectedPayment = method;

            // Reset bank selection
            selectedBank = null;

            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            const paymentForm = document.getElementById('payment-form');
            const bankList = document.getElementById('bank-list');

            if (method === 'credit_card') {
                paymentForm.style.display = 'block';
                bankList.style.display = 'none';
            } else if (method === 'online_banking') {
                paymentForm.style.display = 'none';
                bankList.style.display = 'block';
            }
        }

        function selectBank(bank) {
                selectedBank = bank;
                
                document.querySelectorAll('.bank-option').forEach(option => {
                    option.classList.remove('selected');
                });
                event.currentTarget.classList.add('selected');
                // Removed the proceed button logic from here
            }

            function processPayment() {
                if (!selectedPayment) {
                    alert('Please select a payment method');
                    return;
                }

                if (selectedPayment === 'online_banking' && !selectedBank) {
                    alert('Please select a bank');
                    return;
                }

                // Get the form data
                const formData = new FormData(document.getElementById('orderForm'));
                const orderData = Object.fromEntries(formData.entries());

                // Add payment information
                const paymentData = {
                    ...orderData,
                    payment_method: selectedPayment,
                    bank: selectedBank || 'N/A'
                };

                // Debug log
                console.log('Sending payment data:', paymentData);

                fetch('process_purchase.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(paymentData)
                })
                .then(response => {
                    // Debug log
                    console.log('Raw response:', response);
                    return response.json();
                })
                .then(data => {
                    // Debug log
                    console.log('Processed response:', data);
                    
                    if (data.success) {
                        const confirmationContent = `
                            <div class="confirmation-box">
                                <h3>Order Successfully Placed!</h3>
                                <div class="order-details">
                                    <p><strong>Order ID:</strong> ${data.order_id}</p>
                                    <p><strong>Total Amount:</strong> RM ${data.total_price}</p>
                                    <p><strong>Tracking Number:</strong> ${data.tracking_no}</p>
                                    <p><strong>Quantity:</strong> ${data.quantity}</p>
                                    <p><strong>Delivery Method:</strong> ${data.distribution_method}</p>
                                    <p><strong>Delivery Address:</strong> ${data.address}</p>
                                    <p><strong>Status:</strong> Success</p>
                                </div>
                            </div>`;
                        
                        document.getElementById('confirmation-details').innerHTML = confirmationContent;
                        
                        // Move to confirmation step
                        document.querySelector(`#step${currentStep}-content`).classList.remove('active');
                        document.querySelector('#step3-content').classList.add('active');
                        document.querySelector(`#step${currentStep}`).classList.remove('active');
                        document.querySelector('#step3').classList.add('active');
                        currentStep = 3;
                    } else {
                        alert('Payment failed: ' + (data.message || 'Unknown error'));
                        console.error('Payment error details:', data.debug_info);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error processing payment. Please check the console for details.');
                });
            }


    </script>
</body>
</html>