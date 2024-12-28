<?php
include 'config.php';
session_start();

if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['ID'];

$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : ''; // Get event_id from URL

// Fetch student details
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$eventQuery = "SELECT * FROM events WHERE event_id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();

$paymentQuery = "SELECT * FROM payment WHERE event_id = ?";
$paymentStmt = $conn->prepare($paymentQuery);
$paymentStmt->bind_param("i", $event_id);
$paymentStmt->execute();
$paymentResult = $paymentStmt->get_result();
$payment = $paymentResult->fetch_assoc();

// Fetch existing registration details for autofill
$registrationQuery = "SELECT * FROM event_participants WHERE id = ? AND event_id = ?";
$registrationStmt = $conn->prepare($registrationQuery);
$registrationStmt->bind_param("ii", $user_id, $event_id);
$registrationStmt->execute();
$registrationResult = $registrationStmt->get_result();
$registration = $registrationResult->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $attendance = $_POST['attendance'];
    $requirements = $_POST['requirements'];

    $updateQuery = "UPDATE event_participants 
                    SET attendance = ?, requirements = ?, updated_at = NOW() 
                    WHERE id = ? AND event_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssii", $attendance, $requirements, $user_id, $event_id);

    if (!$updateStmt->execute()) {
        echo "<script>alert('Update failed. Error: " . $updateStmt->error . "');</script>";
    } else {
        echo "<script>alert('Your information has been updated successfully!'); window.location.href='participantdashboard.php';</script>";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Recruitment Form</title>
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
                <a href="participantmerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantmerchandise.php' ? 'active' : ''; ?>"></i>Merchandise</a>
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
        <h2>Update <?php echo htmlspecialchars($event['event_name']); ?> Participant Form</h2>
        <p>Update the information below.</p>
        <form method="POST" action="editparticipantform.php?event_id=<?php echo $event_id; ?>">
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
                        <input type="radio" id="yes" name="attendance" value="Yes" <?php echo ($registration['attendance'] == 'Yes') ? 'checked' : ''; ?> required>
                        <label for="yes">Yes</label>
                        <input type="radio" id="maybe" name="attendance" value="Maybe" <?php echo ($registration['attendance'] == 'Maybe') ? 'checked' : ''; ?> required>
                        <label for="maybe">Maybe</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="requirements">Special Requirements</label>
                    <select id="requirements" name="requirements" required>
                        <option value="" disabled>Select one</option>
                        <option value="Meal Option" <?php echo ($registration['requirements'] == 'Meal Option') ? 'selected' : ''; ?>>Meal Option</option>
                        <option value="Vegetarian" <?php echo ($registration['requirements'] == 'Vegetarian') ? 'selected' : ''; ?>>Vegetarian</option>
                        <option value="Others" <?php echo ($registration['requirements'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend>Payment Details</legend>
                <?php if ($payment): ?>
                    <div class="form-group">
                        <label for="payment_fee">Payment Amount</label>
                        <input type="text" value="<?php echo htmlspecialchars($payment['payment_fee']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <input type="text" value="<?php echo htmlspecialchars($payment['payment_method']); ?>" readonly>
                    </div>

                    <?php if ($payment['payment_method'] === "account number"): ?>
                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($payment['account_number']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="account_holder">Account Holder</label>
                            <input type="text" value="<?php echo htmlspecialchars($payment['account_holder']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($payment['bank_name']); ?>" readonly>
                        </div>
                    <?php elseif ($payment['payment_method'] === "qr code"): ?>
                        <div class="form-group">
                            <label for="qr_code">QR Code</label>
                            <?php if (!empty($payment['qr_code'])): ?>
                                <img class="qr-code-image" src="data:image/jpeg;base64,<?php echo base64_encode($payment['qr_code']); ?>" alt="QR Code" width="300" height="300">
                            <?php else: ?>
                                <p>No QR Code available.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No payment information available for this event.</p>
                    <?php endif; ?>

                    <!-- Display Previously Uploaded Proof of Payment -->
                    <div class="form-group">
                        <label for="proof_of_payment">Proof of Payment:</label>
                        <?php if (!empty($payment['proof_of_payment'])): ?>
                            <?php
                            // Determine the file type of the LONGBLOB for inline display (e.g., image or PDF)
                            $fileType = finfo_buffer(finfo_open(), $payment['proof_of_payment'], FILEINFO_MIME_TYPE);

                            // Display as an inline image or link to download
                            if (strpos($fileType, 'image') !== false): ?>
                                <img src="data:<?php echo $fileType; ?>;base64,<?php echo base64_encode($payment['proof_of_payment']); ?>" alt="Proof of Payment" width="500">
                            <?php elseif (strpos($fileType, 'pdf') !== false): ?>
                                <embed src="data:application/pdf;base64,<?php echo base64_encode($payment['proof_of_payment']); ?>" width="600" height="400" type="application/pdf">
                                <p><a href="data:application/pdf;base64,<?php echo base64_encode($payment['proof_of_payment']); ?>" download="proof_of_payment.pdf">Download Proof of Payment</a></p>
                            <?php else: ?>
                                <p>Unsupported file type. Please download the proof of payment:</p>
                                <a href="data:<?php echo $fileType; ?>;base64,<?php echo base64_encode($payment['proof_of_payment']); ?>" download="proof_of_payment">Download Proof of Payment</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No proof of payment uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No payment information available for this event.</p>
                <?php endif; ?>
            </fieldset>


            <div class="button-group">
                <button type="submit" class="submit-button">Submit</button>
                <button type="button" class="cancel-button" onclick="window.location.href='participantdashboard.php';">Cancel</button>
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