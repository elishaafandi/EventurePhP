<?php
include 'config.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['ID'])) {
    echo "You must be logged in to access this page.";
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

$studentQuery = "
    SELECT s.student_photo, s.first_name, s.last_name, s.email, s.matric_no, s.phone, s.ic, s.college, s.year_course, s.gender, ep.participant_id, ep.registration_status, ep.attendance_status, ep.requirements
    FROM students s
    INNER JOIN event_participants ep ON s.id = ep.id
    WHERE ep.event_id = ? AND ep.id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("ii", $event_id, $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$studentStmt->close();
$eventStmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Application Details</title>
    <link rel="stylesheet" href="viewapplicationdetails.css">
</head>
<body>
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="active">Home</a>
                <a href="#">Calendar</a>
                <a href="#">User Profile</a>
                <a href="#">Dashboard</a>
            </nav>
        </div>
        <div class="nav-right">
            <a href="#" class="participant-site">PARTICIPANT SITE</a>
            <a href="#" class="organizer-site">ORGANIZER SITE</a>
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

    <main>
        <section>
            <h2>Participant Application Details</h2>
            <p><strong>Participant ID:</strong> <?php echo htmlspecialchars($student['participant_id']); ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Identification Number:</strong> <?php echo htmlspecialchars($student['ic']); ?></p>
            <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($student['matric_no']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
            <p><strong>College Address:</strong> <?php echo htmlspecialchars($student['college']); ?></p>
            <p><strong>Year/Course (24/25):</strong> <?php echo htmlspecialchars($student['year_course']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($student['requirements']); ?></p>
        </section>
        
        <section>
            <h2>Event Application Details</h2>
            <p><strong>Event ID:</strong> <?php echo htmlspecialchars($event['event_id']); ?></p>
            <p><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
            <p><strong>Date:</strong> <?php echo date("d/m/Y", strtotime($event['start_date'])); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
        </section>
        
        <button onclick="window.location.href='participantdashboard.php';">Back</button>
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