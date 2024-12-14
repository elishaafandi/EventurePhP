<?php
include 'config.php';
session_start();

if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['ID'];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

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
$eventStmt->close();

// Fetch participants for the specific event
$participantQuery = "
    SELECT s.student_photo, s.first_name, s.last_name, s.email, s.matric_no, s.year_course, ep.participant_id
    FROM students s
    INNER JOIN event_participants ep ON s.id = ep.id
    WHERE ep.event_id = ?";
$participantStmt = $conn->prepare($participantQuery);
$participantStmt->bind_param("i", $event_id);
$participantStmt->execute();
$participantResult = $participantStmt->get_result();

$participants = [];
while ($row = $participantResult->fetch_assoc()) {
    $participants[] = $row;
}

$participantStmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View List Of Participants</title>
    <link rel="stylesheet" href="viewparticipantlist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    <div class="header">
        <h1 class="header-title"> <?php echo htmlspecialchars($event['event_name']); ?> Participant Lists </h1>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by name, email, or matric number...">
            <button class="search-button" id="searchButton">
                <i class="fa fa-search"></i> Search
            </button>
        </div>
    </div>

    <div class="participants-grid" id="participantsGrid">
        <?php if (count($participants) > 0): ?>
            <?php foreach ($participants as $participant): ?>
                <div class="participant-card">
                    <!-- Handle participant image -->
                    <?php if (!empty($participant['student_photo'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($participant['student_photo']); ?>" alt="Participant Photo">
                    <?php else: ?>
                        <img src="default-profile.png" alt="Default Profile">
                    <?php endif; ?>
                    
                    <div class="participant-info">
                        <h3><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></h3>
                        <p>Email: <?php echo htmlspecialchars($participant['email']); ?></p>
                        <p>Matric No: <?php echo htmlspecialchars($participant['matric_no']); ?></p>
                        <p>Year/Course: <?php echo htmlspecialchars($participant['year_course']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-participants">No participants have registered for this event yet.</p>
        <?php endif; ?>
    </div>
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

document.getElementById('searchButton').addEventListener('click', function () {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const participants = document.querySelectorAll('.participant-card');

    participants.forEach(participant => {
        const name = participant.querySelector('.participant-info h3').textContent.toLowerCase();
        const email = participant.querySelector('.participant-info p').textContent.toLowerCase();

        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            participant.style.display = 'block';
        } else {
            participant.style.display = 'none';
        }
    });
});

    </script>
</body>
</html>