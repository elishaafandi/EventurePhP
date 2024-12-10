<?php
include 'config.php';
session_start();

// Check if user is logged in by verifying the session ID
if (!isset($_SESSION["ID"])) {
    // Redirect to homepage if ID is missing
    header("Location: organizerhome.php");
    exit; 
}

// Ensure the club ID is stored in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];  // Store selected club ID in session
} 

// Initialize club ID from session
$selected_club_id = isset($_SESSION["SELECTEDID"]) ? $_SESSION["SELECTEDID"] : '';

$price = 10;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
        $event_role = $_POST['event_role'] ?? 'Crew';
        $organizer_id = $_POST['organizer_id'] ?? 0;
        $club_id = $_SESSION['SELECTEDID'] ?? 0; // Use the club_id from session
        $organizer = $_POST['organizer'] ?? '';
        $event_name = $_POST['event_name'] ?? '';
        $description = $_POST['event_description'] ?? '';
        $location = $_POST['location'] ?? '';
        $total_slots = $_POST['total_slots'] ?? 0;
        $available_slots = $_POST['available_slots'] ?? $total_slots; // Default available slots to total slots
        $event_status = $_POST['event_status'] ?? 'pending';
        $event_type = $_POST['event_type'] ?? '';
        $event_format = $_POST['event_format'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $status = $_POST['status'] ?? 'upcoming';

        // Handle payment method and price
        $paymentmethod = $_POST['paymentmethod'] ?? null;
        $paymentstatus = empty($paymentmethod) ? 'failed' : 'success'; // Default to 'failed' if no payment method selected

        // Default values for binary columns
        $event_photo = null;
        $approval_letter = null;


    // Get the selected club ID from the session for event creation
    $club_id = $_SESSION['SELECTEDID'] ?? 0; // Use the club_id from session

    // Assuming the user is logged in and their ID is stored in the session
     $organizer_id = $_SESSION['ID']; // Use session ID as the organizer ID

    // Validate the organizer ID exists in the user table
    $user_check_query = "SELECT id FROM users WHERE id = ?";
    $stmt_check = $conn->prepare($user_check_query);
    $stmt_check->bind_param("i", $organizer_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows == 0) {
    echo "<script>alert('Invalid organizer ID. Please log in again.');</script>";
    exit;
    }

    // Handle photo upload
    if (isset($_FILES['event_photo']) && $_FILES['event_photo']['error'] === UPLOAD_ERR_OK) {
        $event_photo_tmp = $_FILES['event_photo']['tmp_name'];
        $event_photo = file_get_contents($event_photo_tmp);
    }

    // Handle approval letter upload
    if (isset($_FILES['approval_letter']) && $_FILES['approval_letter']['error'] === UPLOAD_ERR_OK) {
        $approval_letter_tmp = $_FILES['approval_letter']['tmp_name'];
        $approval_letter = file_get_contents($approval_letter_tmp);
    }



    // Get the club name for the organizer
    $club_query = "SELECT club_name FROM clubs WHERE club_id = ?";
    $stmt_club = $conn->prepare($club_query);
    $stmt_club->bind_param("i", $club_id);
    $stmt_club->execute();
    $stmt_club->bind_result($club_name);
    $stmt_club->fetch();
    $stmt_club->close();

    // Now you have the club_name, which will be used as the organizer
    $organizer = $club_name;


    $stmt = $conn->prepare(
        "INSERT INTO events 
        (event_role, organizer_id, club_id, organizer, event_name, description, location, total_slots, available_slots, 
        event_status, event_type, event_format, start_date, end_date, status, event_photo, approval_letter, paymentmethod, price, paymentstatus) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    

    $stmt->bind_param(
        "siissssiisssssssssss",
        $event_role,
        $organizer_id,
        $club_id,
        $organizer,
        $event_name,
        $description,
        $location,
        $total_slots,
        $available_slots,
        $event_status,  
        $event_type,
        $event_format,
        $start_date,
        $end_date,
        $status,
        $event_photo,
        $approval_letter,
        $paymentmethod,
        $price,
        $paymentstatus
    );

    // Execute the statement and check for success
    if ($stmt->execute()) {
        echo "<script>alert('Event created successfully!'); window.location.href='organizerhome.php';</script>";
    } else {
        echo "<script>alert('Error creating event: " . $stmt->error . "');</script>";
    }

    $stmt->close();
  }
 $conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventure Organizer Site</title>
    <link rel="stylesheet" href="organizercreate.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <span class="notification-bell">🔔</span>
                <a href="profilepage.php" class="profile-icon">
                <i class="fas fa-user-circle"></i> 
            </div>
        </div>
    </header>

    <main>
        <aside class="sidebar">
            <div class="logo-container">
                <a href="organizerhome.php" class="logo">EVENTURE</a>
            </div>
            <ul>
                <li><a href="organizerhome.php"><i class="fas fa-home-alt"></i> Dashboard</a></li>
                <li><a href="organizerevent.php"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
                <li><a href="organizerparticipant.php"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
                <li><a href="organizercrew.php"><i class="fas fa-users"></i>Crew Listing</a></li>
                <li><a href="organizerreport.php"><i class="fas fa-chart-line"></i>Reports</a></li>
                <li><a href="organizerfeedback.php"><i class="fas fa-star"></i>Feedback</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizersettings.php"><i class="fas fa-cog"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="form-container">
        <h2>Create New Program & Event For Crew</h2>
        <p>Please fill in all the event details below.</p>
        <form method="POST" action="organizercreateeventcrew.php" enctype="multipart/form-data">
            <fieldset>
                <legend>Event Details</legend>

                <div class="form-group">
                    <label for="event_name">Event Name</label>
                    <input type="text" id="event_name" name="event_name" required>
                </div>

                <div class="form-group">
                    <label for="event_description">Description</label>
                    <textarea id="event_description" name="event_description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label for="total_slots">Total Slots</label>
                    <input type="number" id="total_slots" name="total_slots" required>
                </div>

                <div class="form-group">
                    <label for="event_status">Event Status</label>
                    <input type="text" id="event_status" name="event_status" value="Pending" readonly>
                </div>


                <div class="form-group">
                <label for="event_type">Event Type</label>
                <select id="event_type" name="event_type">
                    <option value="academic">Academic</option>
                    <option value="sports">Sports</option>
                    <option value="cultural">Cultural</option>
                    <option value="social">Social</option>
                    <option value="volunteer">Volunteer</option>
                    <option value="college">College</option>
                </select>
                </div>

            <div class="form-group">
                <label for="event_format">Event Format</label>
                <select id="event_format" name="event_format">
                    <option value="in-person">In-Person</option>
                    <option value="online">Online</option>
                    <option value="hybrid">Hybrid</option>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date of Event</label>
                <input type="datetime-local" id="start_date" name="start_date" required>
            </div>

            <div class="form-group">
                <label for="end_date">End Date of Event</label>
                <input type="datetime-local" id="end_date" name="end_date" required>
            </div>
            </fieldset>

            <fieldset>
                <legend>Additional Event Information</legend>

                <div class="form-group">
                    <label for="club_id">Club ID:</label>
                     <input type="text" id="selected_club_id" name="club_id" value="<?php echo htmlspecialchars($selected_club_id); ?>" readonly>
                </div>

                <div class="form-group">
                        <label for="event-photo">Upload Event Photo:</label>
                        <input type="file" id="event-photo" name="event_photo" accept="image/*" required>
                        <div id="photo-preview-container" style="display: none;">
                            <img id="photo-preview" src="" alt="Preview" style="max-width: 200px; margin-top: 10px;">
                            <button type="button" id="cancel-photo" style="margin-top: 10px;">Cancel</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="approval_letter">Upload Approval Letter (PDF):</label>
                        <input type="file" id="approval_letter" name="approval_letter" accept=".pdf" required>
                        <div id="file-preview-container" style="display: none;">
                            <object id="pdf-preview" style="width: 100%; height: 500px; display: none;"></object>
                            <button type="button" id="cancel-file" style="margin-top: 10px;">Cancel</button>
                        </div>
                    </div>

                    <script>
                    // Handle event photo preview
                    document.getElementById('event-photo').addEventListener('change', function() {
                        const file = this.files[0];
                        if (file) {
                            // Show preview of image file
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                document.getElementById('photo-preview').src = e.target.result;
                                document.getElementById('photo-preview-container').style.display = 'block';
                            }
                            reader.readAsDataURL(file);
                        }
                    });

                    // Cancel event photo
                    document.getElementById('cancel-photo').addEventListener('click', function() {
                        document.getElementById('event-photo').value = ''; // Reset the file input
                        document.getElementById('photo-preview-container').style.display = 'none'; // Hide the preview container
                    });

                    // Handle approval letter (PDF) preview
                    document.getElementById('approval_letter').addEventListener('change', function() {
                        const file = this.files[0];
                        if (file && file.type === 'application/pdf') {
                            // Create a URL for the selected PDF file
                            const fileURL = URL.createObjectURL(file);
                            
                            // Show the PDF file inside an object tag for preview
                            document.getElementById('pdf-preview').data = fileURL;
                            document.getElementById('pdf-preview').style.display = 'block';
                            
                            // Show the file preview container
                            document.getElementById('file-preview-container').style.display = 'block';
                        }
                    });

                    // Cancel approval letter (PDF) preview
                    document.getElementById('cancel-file').addEventListener('click', function() {
                        document.getElementById('approval_letter').value = ''; // Reset the file input
                        document.getElementById('file-preview-container').style.display = 'none'; // Hide the preview container
                        document.getElementById('pdf-preview').data = ''; // Reset the PDF preview
                    });
                    </script>


                <div class="form-group">
                    <label>Select Crew Roles</label>
                    <label><input type="checkbox" name="crew_roles[]" value="protocol"> Protocol</label>
                    <label><input type="checkbox" name="crew_roles[]" value="technical"> Technical</label>
                    <label><input type="checkbox" name="crew_roles[]" value="gift"> Gift</label>
                    <label><input type="checkbox" name="crew_roles[]" value="food"> Food</label>
                    <label><input type="checkbox" name="crew_roles[]" value="special_task"> Special Task</label>
                    <label><input type="checkbox" name="crew_roles[]" value="multimedia"> Multimedia</label>
                    <label><input type="checkbox" name="crew_roles[]" value="sponsorship"> Sponsorship</label>
                    <label><input type="checkbox" name="crew_roles[]" value="documentation"> Documentation</label>
                    <label><input type="checkbox" name="crew_roles[]" value="transportation"> Transportation</label>
                    <label><input type="checkbox" name="crew_roles[]" value="activity"> Activity</label>
                </div>
            </fieldset>


        <fieldset>
        <legend>Payment Information</legend>
        <div class="form-group">
            <div class="payment-amount">
                <label for="amount">Amount:</label>
                <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" readonly>
            </div>
        </div>


    <div class="form-group">
        <label for="paymentmethod">Select Payment Method</label>
        <div class="payment-options">
            <label>
                <input type="radio" name="paymentmethod" value="debit" id="debit-option" required>
                <img src="debit.webp" alt="Debit Card" class="payment-icon">
                Debit Card
            </label>

            <label>
                <input type="radio" name="paymentmethod" value="fpx" id="fpx-option">
                <img src="fpx.png" alt="FPX" class="payment-icon">
                FPX (Bank Transfer)
            </label>

            <label>
                <input type="radio" name="paymentmethod" value="ewallet" id="ewallet-option">
                <img src="ewallet.png" alt="E-Wallet" class="payment-icon">
                E-wallet
            </label>
        </div>
    </div>

    <div id="debit-info" style="display: none;">
        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number">
        </div>
        <div class="form-group">
            <label for="expiry_date">Expiration Date</label>
            <input type="text" id="expiry_date" name="expiry_date">
        </div>
        <div class="form-group">
            <label for="cvv">CVV</label>
            <input type="text" id="cvv" name="cvv">
        </div>
    </div>

    <div id="fpx-info" style="display: none;">
        <div class="form-group">
            <label for="bank">Select Bank</label>
            <select id="bank" name="bank">
                <option value="bank1">RHB</option>
                <option value="bank2">MAYBANK</option>
                <option value="bank3">CIMB</option>
            </select>
        </div>
    </div>

    <div id="ewallet-info" style="display: none;">
        <div class="form-group">
            <label for="ewallet">Select E-wallet</label>
            <select id="ewallet" name="ewallet">
                <option value="touchngo">Touch 'n Go</option>
                <option value="shopeepay">ShopeePay</option>
            </select>
        </div>
    </div>

    <div id="proceed-button-container" style="display: none;">
        <button type="button" class="submit-button" id="proceed-button">Proceed</button>
    </div>
</fieldset>

<div class="button-group">
    <button type="submit" class="submit-button">Submit</button>
    <button type="button" class="cancel-button" onclick="window.location.href='organizerhome.php';">Cancel</button>
</div>

<script>
// Show/hide payment method details based on selection
document.querySelectorAll('input[name="paymentmethod"]').forEach(function(input) {
    input.addEventListener('change', function() {
        var method = this.value;
        document.getElementById('debit-info').style.display = (method === 'debit') ? 'block' : 'none';
        document.getElementById('fpx-info').style.display = (method === 'fpx') ? 'block' : 'none';
        document.getElementById('ewallet-info').style.display = (method === 'ewallet') ? 'block' : 'none';
        
        // Show the Proceed button after selecting a payment method
        document.getElementById('proceed-button-container').style.display = 'block';
    });
});

// Proceed button functionality
document.getElementById('proceed-button').addEventListener('click', function() {
    var selectedMethod = document.querySelector('input[name="paymentmethod"]:checked');
    
    if (selectedMethod) {
        var method = selectedMethod.value;
        if (method === 'debit') {
            alert('Proceeding with Debit Card');
        } else if (method === 'fpx') {
            alert('Proceeding with FPX Bank Transfer');
        } else if (method === 'ewallet') {
            alert('Proceeding with E-wallet');
        }
    }
});
</script>

 </main>
</body>
</html>