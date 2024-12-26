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
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

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

// Query to fetch the crew_role for the specific event
$event_id = $_GET['event_id'];  // Assuming the event ID is passed as a query parameter
$query = "SELECT crew_role FROM events WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id); // Bind the event_id
$stmt->execute();
$result = $stmt->get_result();

// Fetch the crew_role value
$row = $result->fetch_assoc();
$crew_roles = $row['crew_role'];  // This will contain the comma-separated roles
$stmt->close();

// Split the roles into an array based on the commas
$rolesArray = explode(",", $crew_roles);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    $past_experience = $_POST['past_experience'];
    $role = $_POST['role'];
    $commitment = $_POST['commitment'];
    $event_id = $_POST['event_id'];

    // Check if the user is already registered for this specific event
    $checkQuery = "SELECT * FROM event_crews WHERE id = ? AND event_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $user_id, $event_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // User is already registered for this event
        echo "<script>alert('You are already registered for this event.'); window.location.href='participanthome.php';</script>";
        exit;
    } else {
        // Insert into event_crews table
        $sql_event_crew = "INSERT INTO event_crews (crew_id, event_id, id, role, commitment, past_experience, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt_event = $conn->prepare($sql_event_crew);
        $stmt_event->bind_param("iiisss", $crew_id, $event_id, $user_id, $role, $commitment, $past_experience);

        if ($stmt_event->execute()) {
            // Decrease the available slots in the events table
            $updateSlotsQuery = "UPDATE events SET available_slots = available_slots - 1 WHERE event_id = ? AND available_slots > 0";
            $updateSlotsStmt = $conn->prepare($updateSlotsQuery);
            $updateSlotsStmt->bind_param("i", $event_id);

            if ($updateSlotsStmt->execute()) {
                // Send Email Notification
            $mail = new PHPMailer(true);
        
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'elisha03@graduate.utm.my'; // Your email
                $mail->Password = 'egmp jwea jxwn vove'; // Your email password
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
        
                // Recipient
                $mail->setFrom('elisha03@graduate.utm.my', 'Eventure Team');
                $mail->addAddress($recipient_email);
        
                $logoPath = 'logo.png'; // Update this with the path to your logo file
                $mail->addEmbeddedImage($logoPath, 'eventureLogo');
        
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'New Event Registration';
                $mail->Body = '
                <div style="text-align: center; font-family: Arial, sans-serif;">
                    <img src="cid:eventureLogo" alt="Eventure Logo" style="max-width: 150px; margin-bottom: 20px;">
                    <h1 style="color: #800c12;">Event Registration Confirmation</h1>
                    <p>Dear Participant,</p>
                    <p>You have successfully registered for the event <strong>' . htmlspecialchars($event_name) . '</strong>.</p>
                    <p>You can manage your registration and other details by visiting your dashboard below:</p>
                    <p>
                        <a href="http://localhost/eventure/participantdashboard.php?event_id=' . urlencode($event_id) . '" 
                        style="display: inline-block; padding: 10px 20px; background-color: #800c12; color: white; text-decoration: none; border-radius: 5px;">
                            View Dashboard
                        </a>
                    </p>
                    <p>We look forward to seeing you at the event!</p>
                    <p>Best regards,<br>Eventure Team</p>";
                </div>';


                $mail->send();
        
                // Show success alert using JavaScript
                echo "<script>alert('Registration successful! Available slots updated.'); window.location.href='participanthome.php';</script>";
            } catch (Exception $e) {
                // Show error alert using JavaScript
                echo "<script>alert('Error sending email: {$mail->ErrorInfo}');</script>";
            }
            
            } else {
                echo "<script>alert('Registration successful, but failed to update available slots.');</script>";
            }
        } else {
            echo "<script>alert('Failed to register for the event. Please try again.');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="crewform.css">
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
            <span class="notification-bell">🔔</span>
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
        <h2> <?php echo htmlspecialchars($event['event_name']); ?> Crew Recruitment Form</h2>
        <p>Please fill in all the information below.</p>
        <form method="POST" action="crewform.php">
        <input type="hidden" name="event_id" value="<?php echo isset($_GET['event_id']) ? htmlspecialchars($_GET['event_id']) : ''; ?>">
            <fieldset>
                <legend>Personal Details</legend>

                <div class="form-group">
                    <label for="photo">Crew Photo</label>
                   
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
                    <label for="ic">Identification Number</label>
                    <input type="text" value="<?php echo $student['ic']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" value="<?php echo $student['email']; ?>" readonly>
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
            </fieldset>

            <fieldset>
                <legend>Requirements</legend>
                <div class="form-group">
                    <label for="past_experience">Please list your past experiences in previous programs (e.g., OPERA23 - Technical Unit)</label>
                    <textarea id="past_experience" name="past_experience" required></textarea>
                </div>

                <div class="form-group">
                    <label for="role">Choose your desired role</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>
                        <?php
                        // Loop through the roles and display them as options
                        foreach ($rolesArray as $role) {
                            echo "<option value=\"" . htmlspecialchars($role) . "\">" . htmlspecialchars($role) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>I hereby acknowledge that all information provided is accurate, and I commit to give 100% dedication to this program.</label>
                    <div class="commitment-options">
                        <input type="radio" id="commit_yes" name="commitment" value="Yes" required>
                        <label for="commit_yes">Yes</label>
                        <input type="radio" id="commit_no" name="commitment" value="No" required>
                        <label for="commit_no">No</label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Interview</legend>
                <p>An interview session will be scheduled on a later date and will be sent to your email. Check your calendar for the interview date. For more enquiries, feel free to contact us on our email. Thank you.</p>
            </fieldset>

            <div class="button-group">
                <button type="submit" class="submit-button">Submit</button>
                <button type="button" class="cancel-button" onclick="window.location.href='participanthome.php';">Cancel</button>
            </div>

        </form>
    </main>

<!-- JavaScript to handle modal functionality -->
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

