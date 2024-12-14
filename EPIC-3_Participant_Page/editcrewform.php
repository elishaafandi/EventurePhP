<?php
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

// Fetch student details
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

// Fetch existing crew details
$crewQuery = "SELECT * FROM event_crews WHERE id = ? AND event_id = ?";
$crewStmt = $conn->prepare($crewQuery);
$crewStmt->bind_param("ii", $user_id, $event_id);
$crewStmt->execute();
$crewResult = $crewStmt->get_result();
$crew = $crewResult->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $past_experience = $_POST['past_experience'];
    $role = $_POST['role'];
    $commitment = $_POST['commitment'];

    // Update existing record
    $updateQuery = "UPDATE event_crews SET past_experience = ?, role = ?, commitment = ?, updated_at = NOW() WHERE id = ? AND event_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssiii", $past_experience, $role, $commitment, $user_id, $event_id);

    if ($updateStmt->execute()) {
        echo "<script>alert('Updated successfully!'); window.location.href='participantdashboard.php';</script>";
    } else {
         echo "<script>alert('Failed to update. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Crew Recruitment Form</title>
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
        <h2>Update <?php echo htmlspecialchars($event['event_name']); ?> Crew Recruitment Form</h2>
        <p>Update the information below.</p>
        <form method="POST" action="editcrewform.php?event_id=<?php echo $event_id; ?>">
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
                <label for="past_experience">Past Experiences</label>
                <textarea id="past_experience" name="past_experience" required><?php echo htmlspecialchars($crew['past_experience'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="role">Choose your desired role</label>
                <select id="role" name="role" required>
                    <option value="">Select a role</option>
                    <option value="Protocol" <?php echo ($crew['role'] == 'Protocol') ? 'selected' : ''; ?>>Protocol</option>
                    <option value="Technical" <?php echo ($crew['role'] == 'Technical') ? 'selected' : ''; ?>>Technical</option>
                    <option value="Gift" <?php echo ($crew['role'] == 'Gift') ? 'selected' : ''; ?>>Gift</option>
                    <option value="Food" <?php echo ($crew['role'] == 'Food') ? 'selected' : ''; ?>>Food</option>
                    <option value="Special Task" <?php echo ($crew['role'] == 'Special Task') ? 'selected' : ''; ?>>Special Task</option>
                    <option value="Multimedia" <?php echo ($crew['role'] == 'Multimedia') ? 'selected' : ''; ?>>Multimedia</option>
                    <option value="Sponsorship" <?php echo ($crew['role'] == 'Sponsorship') ? 'selected' : ''; ?>>Sponsorship</option>
                    <option value="Documentation" <?php echo ($crew['role'] == 'Documentation') ? 'selected' : ''; ?>>Documentation</option>
                    <option value="Transportation" <?php echo ($crew['role'] == 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
                    <option value="Activity" <?php echo ($crew['role'] == 'Activity') ? 'selected' : ''; ?>>Activity</option>
                </select>
            </div>

            <div class="form-group">
                <label>Commitment</label>
                <div class="commitment-options">
                    <input type="radio" id="commit_yes" name="commitment" value="Yes" <?php echo ($crew['commitment'] == 'Yes') ? 'checked' : ''; ?> required>
                    <label for="commit_yes">Yes</label>
                    <input type="radio" id="commit_no" name="commitment" value="No" <?php echo ($crew['commitment'] == 'No') ? 'checked' : ''; ?> required>
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