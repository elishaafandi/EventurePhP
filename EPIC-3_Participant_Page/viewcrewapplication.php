<?php
include 'config.php';
session_start();

if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='loginform.php';</script>";
    exit;
}

$user_id = $_SESSION['ID'];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : ''; // Get event_id from URL

$eventQuery = "SELECT * FROM events WHERE event_id = ?";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();

$interviewQuery = "SELECT * FROM interview WHERE event_id = ?";
$interviewStmt = $conn->prepare($interviewQuery);
$interviewStmt->bind_param("i", $event_id);
$interviewStmt->execute();
$interviewResult = $interviewStmt->get_result();
$interview = $interviewResult->fetch_assoc();

$studentQuery = "
    SELECT s.student_photo, s.first_name, s.last_name, s.email, s.matric_no, s.phone, s.ic, s.college, s.year_course, s.gender, ec.crew_id, ec.past_experience, ec.role, ec.commitment, ec.application_status
    FROM students s
    INNER JOIN event_crews ec ON s.id = ec.id
    WHERE ec.event_id = ? AND ec.id = ?";
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
    <title>Crew Application Details</title>
    <link rel="stylesheet" href="viewapplicationdetails.css">
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


    <main>
        <section>
            <h2>Crew Application Details</h2>
            <p><strong>Crew ID:</strong> <?php echo htmlspecialchars($student['crew_id']); ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Identification Number:</strong> <?php echo htmlspecialchars($student['ic']); ?></p>
            <p><strong>Matric Number:</strong> <?php echo htmlspecialchars($student['matric_no']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?></p>
            <p><strong>College Address:</strong> <?php echo htmlspecialchars($student['college']); ?></p>
            <p><strong>Year/Course (24/25):</strong> <?php echo htmlspecialchars($student['year_course']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            <p><strong>Past Experience:</strong> <?php echo htmlspecialchars($student['past_experience']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($student['role']); ?></p>
        </section>
        
        <section>
            <h2>Event Application Details</h2>
            <p><strong>Event ID:</strong> <?php echo htmlspecialchars($event['event_id']); ?></p>
            <p><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
            <p><strong>Date:</strong> <?php echo date("d/m/Y", strtotime($event['start_date'])); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
            <!-- Add other event details as needed -->
        </section>

        <section>
        <?php if ($student['application_status'] === "interview"): ?>
            <h2>Interview Details</h2>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($interview['location']); ?></p>
            <p><strong>Interview Mode:</strong> <?php echo htmlspecialchars($interview['interview_mode']); ?></p>
            <p><strong>Meeting Link:</strong> 
            <?php 
                if ($interview['interview_mode'] === "Face to Face") {
                    echo "Not applicable";
                } else {
                    echo htmlspecialchars($interview['meeting_link']); 
                }
            ?>
        </p>
            <p><strong>Interview Time:</strong> <?php echo date("d/m/Y", strtotime($interview['interview_time'])); ?></p>
            <p><strong>Interview Status:</strong> <?php echo htmlspecialchars($interview['interview_status']); ?></p>
        <?php else: ?>
            <h2>Interview Details</h2>
            <p>Interview details will be displayed here once selected.</p>
        <?php endif; ?>
        </section>
        
        <button onclick="window.location.href='participantdashboard.php';">Back to Home</button>
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