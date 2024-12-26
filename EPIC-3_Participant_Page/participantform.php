<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

include 'config.php';
session_start();

if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['ID'];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : ''; // Get event_id from URL

// Fetch event details
$eventQuery = "SELECT * FROM events WHERE event_id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();

// Fetch student details to autofill form
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$paymentQuery = "SELECT * FROM payment WHERE event_id = ?";
$paymentStmt = $conn->prepare($paymentQuery);
$paymentStmt->bind_param("i", $event_id);
$paymentStmt->execute();
$paymentResult = $paymentStmt->get_result();
$payment = $paymentResult->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $attendance = $_POST['attendance'];
    $requirements = $_POST['requirements'];
    $event_id = $_POST['event_id'];
    $proof_of_payment = '';
    
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $proof_of_payment_tmp = $_FILES['proof_of_payment']['tmp_name'];
        $proof_of_payment = file_get_contents($proof_of_payment_tmp);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get payment details if they exist
        $paymentQuery = "SELECT payment_fee, payment_method FROM payment WHERE event_id = ?";
        $paymentStmt = $conn->prepare($paymentQuery);
        $paymentStmt->bind_param("i", $event_id);
        $paymentStmt->execute();
        $paymentResult = $paymentStmt->get_result();
        
        // Default values for free events
        $amount = 0;
        $payment_method = 'Free';
        
        if ($paymentResult->num_rows > 0) {
            $paymentData = $paymentResult->fetch_assoc();
            $amount = $paymentData['payment_fee'];
            $payment_method = $paymentData['payment_method'];
        }
    
        // Check for existing registration
        $checkQuery = "SELECT * FROM event_participants WHERE id = ? AND event_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ii", $user_id, $event_id);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('You are already registered for this event.');
        }
    
        // Insert into event_participants first
        $sql_event_participant = "INSERT INTO event_participants (event_id, id, registration_status, attendance, attendance_status, requirements, created_at)
            VALUES (?, ?, 'registered', ?, 'pending', ?, NOW())";
        $stmt_event = $conn->prepare($sql_event_participant);
        $stmt_event->bind_param("iiss", $event_id, $user_id, $attendance, $requirements);
        
        if (!$stmt_event->execute()) {
            throw new Exception('Failed to register for the event.');
        }
        
        // Get the participant_id
        $participant_id = $stmt_event->insert_id;
        
        // Insert payment record even for free events
        $sql_payment = "INSERT INTO participant_payment (event_id, participant_id, amount, payment_method, proof_of_payment, payment_date)
            VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("iisss", $event_id, $participant_id, $amount, $payment_method, $proof_of_payment);
        
        if (!$stmt_payment->execute()) {
            throw new Exception('Failed to insert payment details.');
        }

        // Update available slots
        $updateSlotsQuery = "UPDATE events SET available_slots = available_slots - 1 
            WHERE event_id = ? AND available_slots > 0";
        $updateSlotsStmt = $conn->prepare($updateSlotsQuery);
        $updateSlotsStmt->bind_param("i", $event_id);
        
        if (!$updateSlotsStmt->execute()) {
            throw new Exception('Failed to update available slots.');
        }

        // Prepare and send email notification
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'elisha03@graduate.utm.my'; // Replace with your SMTP username
            $mail->Password = 'egmp jwea jxwn vove'; // Replace with your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

             // Check recipient email in session
             if (isset($_SESSION['EMAIL']) && !empty($_SESSION['EMAIL'])) {
                $recipient_email = $_SESSION['EMAIL'];
            } else {
                echo "<script>alert('Error: Recipient email not found in session.');</script>";
                exit;
            }

            $eventQuery = "SELECT event_name FROM events WHERE event_id = ?";
            $eventStmt = $conn->prepare($eventQuery);
            $eventStmt->bind_param("i", $event_id);
            $eventStmt->execute();
            $eventResult = $eventStmt->get_result();
            if ($eventResult->num_rows > 0) {
                $eventRow = $eventResult->fetch_assoc();
                $event_name = $eventRow['event_name']; // Get the event name
            } else {
                echo "<script>alert('Error: Event not found.');</script>";
                exit;
            }

            // Recipients
            $mail->setFrom('elisha03@graduate.utm.my', 'Eventure Team'); // Replace with your email
            $mail->addAddress($recipient_email);

            $logoPath = 'logo.png'; // Update this with the path to your logo file
            $mail->addEmbeddedImage($logoPath, 'eventureLogo');

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'New Event Registration';
            $mail->Body = '
            <div style="text-align: center; font-family: Arial, sans-serif;">
                <img src="cid:eventureLogo" alt="Eventure Logo" style="max-width: 150px; margin-bottom: 20px;">
                <h1 style="color: #800c12;">Event Registration Confirmation</h1>
                <p>Dear Participant,</p>
                <p>You have successfully registered for the event <strong>' . htmlspecialchars($event_name) . '</strong>.</p>';
            
            if ($paymentData['payment_fee'] > 0) {
                $mail->Body .= '
                <p><strong>Payment Details:</strong></p>
                <table style="margin: 0 auto; text-align: left; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 5px 10px; font-weight: bold;">Amount Paid:</td>
                        <td style="padding: 5px 10px;">RM' . htmlspecialchars($paymentData['payment_fee']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 10px; font-weight: bold;">Payment Method:</td>
                        <td style="padding: 5px 10px;">' . htmlspecialchars($paymentData['payment_method']) . '</td>
                    </tr>
                </table>';
            }
            
            $mail->Body .= '
                <p>You can manage your registration and other details by visiting your dashboard below:</p>
                <p>
                    <a href="http://localhost/eventure/participantdashboard.php?event_id=' . urlencode($event_id) . '" 
                    style="display: inline-block; padding: 10px 20px; background-color: #800c12; color: white; text-decoration: none; border-radius: 5px;">
                    View Dashboard
                    </a>
                </p>
                <p>We look forward to seeing you at the event!</p>
                <p>Best regards,<br>Eventure Team</p>
            </div>';
            

            // Send the email
            $mail->send();

        } catch (Exception $e) {
            throw new Exception('Email notification failed: ' . $mail->ErrorInfo);
        }


        // If we got here, commit the transaction
        $conn->commit();
        echo "<script>alert('Registration successful!'); window.location.href='participanthome.php';</script>";
        
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $conn->rollback();
        echo "<script>alert('" . $e->getMessage() . "'); window.location.href='participanthome.php';</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="participantform.css">
</head>
<body>
<header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a>
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


    <main class="form-container">
        <h2> <?php echo htmlspecialchars($event['event_name']); ?> Participant Form</h2>
        <p>Please fill in all the information below.</p>
        <form method="POST" action="participantform.php" enctype="multipart/form-data">
        <input type="hidden" name="event_id" value="<?php echo isset($_GET['event_id']) ? htmlspecialchars($_GET['event_id']) : ''; ?>">
            <fieldset>
                <legend>Personal Details</legend>

                <div class="form-group">
                    <label for="photo">Participant Photo</label>
                   
                    <?php if (!empty($student['student_photo'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($student['student_photo']); ?>" alt="Student Photo">
                    <?php else: ?>
                        <p>No photo available</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" value="<?php echo $student['first_name']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" value="<?php echo $student['last_name']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" value="<?php echo $student['email']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="ic">Identification Number</label>
                    <input type="text" value="<?php echo $student['ic']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="matric_no">Matric Number</label>
                    <input type="text" value="<?php echo $student['matric_no']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" value="<?php echo $student['phone']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="address">College Address</label>
                    <input type="text" value="<?php echo $student['college']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="year_course">Year/Course (in 24/25)</label>
                    <input type="text" value="<?php echo $student['year_course']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <div class="gender-options">
                        <input type="radio" id="male" name="gender" value="Male" <?php echo ($student['gender'] == 'Male') ? 'checked' : ''; ?> disabled>
                        <label for="male">Male</label>
                        <input type="radio" id="female" name="gender" value="Female" <?php echo ($student['gender'] == 'Female') ? 'checked' : ''; ?> disabled>
                        <label for="female">Female</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Will you be able to attend the event?</label>
                    <div class="attendance-options">
                        <input type="radio" id="yes" name="attendance" value="Yes" required>
                        <label for="male">Yes</label>
                        <input type="radio" id="maybe" name="attendance" value="Maybe" required>
                        <label for="female">Maybe</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="requirements">Special Requirements</label>
                    <select id="requirements" name="requirements" required>
                        <option value="">Select one</option>
                        <option value="Meal Option">Meal Options</option>
                        <option value="Vegetarian">Vegetarian</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
            </fieldset>
            <fieldset>
                <legend>Payment Details</legend>
                <?php if ($payment): ?>
                    <div class="form-group">
                        <label for="payment_fee">Payment Amount</label>
                        <input type="text" value="<?php echo $payment['payment_fee']; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <input type="text" value="<?php echo $payment['payment_method']; ?>" readonly>
                    </div>

                    <?php if ($payment['payment_method'] === "account number"): ?>
                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <input type="text" value="<?php echo $payment['account_number']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="account_holder">Account Holder</label>
                            <input type="text" value="<?php echo $payment['account_holder']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" value="<?php echo $payment['bank_name']; ?>" readonly>
                        </div>
                    <?php elseif ($payment['payment_method'] === "qr code"): ?>
                        <div class="form-group">
                            <label for="qr_code">QR Code</label>
                            <?php if (!empty($payment['qr_code'])): ?>
                                <img class="qr-code-image" src="data:image/jpeg;base64,<?php echo base64_encode($payment['qr_code']); ?>" alt="QR CODE" width="700" height="700">
                            <?php else: ?>
                                <p>No photo available</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No payment information available for this event.</p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="proof_of_payment">Upload Proof of Payment (PDF):</label>
                        <input type="file" id="proof_of_payment" name="proof_of_payment" accept=".pdf" required>
                        <div id="file-preview-container" style="display: none;">
                            <object id="pdf-preview" style="width: 100%; height: 500px; display: none;"></object>
                            <button type="button" id="cancel-file" style="margin-top: 10px;">Cancel</button>
                        </div>
                    </div>

                    <script>
                     // Handle approval letter (PDF) preview
                     document.getElementById('proof_of_payment').addEventListener('change', function() {
                        const file = this.files[0];
                        if (file && file.type === 'proof/pdf') {
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
                        document.getElementById('proof_of_payment').value = ''; // Reset the file input
                        document.getElementById('file-preview-container').style.display = 'none'; // Hide the preview container
                        document.getElementById('pdf-preview').data = ''; // Reset the PDF preview
                    });
                    </script>
                
                
                <?php else: ?>
                    <p>No payment information available for this event.</p>
                <?php endif; ?>


            </fieldset>

            <div class="button-group">
                <button type="submit" class="submit-button">Submit</button>
                <button type="button" class="cancel-button" onclick="window.location.href='participanthome.php';">Cancel</button>
            </div>
        </form>
    </main>

<script>
    
    /// Handle Profile Icon Click
document.addEventListener("DOMContentLoaded", function () {
    const profileMenu = document.querySelector(".profile-menu");
    const profileIcon = document.querySelector(".profile-icon");

    // Toggle dropdown on profile icon click
    profileIcon.addEventListener("click", function (event) {
        event.stopPropagation(); // Prevent event from bubbling
        profileMenu.classList.toggle("open");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        if (!profileMenu.contains(event.target)) {
            profileMenu.classList.remove("open");
        }
    });
});

</script>

</body>
</html>