<?php
session_start();
require 'config.php'; // Ensure this includes your database connection setup

$crew_id = isset($_GET['id']) ? $_GET['id'] : null;

// Check if `id` is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: organizercrew.php"); // Redirect if no `id` is provided
    exit;
}

$crew_id = intval($_GET['id']);

// Fetch details for the selected crew member
$sql_crew_details = "
    SELECT 
        ec.crew_id, ec.role, ec.application_status, 
        s.first_name, s.last_name, s.student_photo, 
        s.ic, s.matric_no, s.phone, s.faculty_name, 
        s.year_course, s.college, s.email, s.gender
    FROM 
        event_crews ec
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        ec.crew_id = ?";
$stmt_crew_details = $conn->prepare($sql_crew_details);
$stmt_crew_details->bind_param("i", $crew_id);
$stmt_crew_details->execute();
$result_crew_details = $stmt_crew_details->get_result();

// Check if the crew member exists
if ($result_crew_details->num_rows === 0) {
    echo "<p>Crew member not found.</p>";
    exit;
}

$crew = $result_crew_details->fetch_assoc();

// Fetch event details for the selected crew member
$sql_event_details = "
    SELECT 
        e.event_name, e.description, e.location, e.start_date, e.end_date, 
        e.event_type, e.event_format
    FROM 
        events e
    JOIN 
        event_crews ec ON e.event_id = ec.event_id
    WHERE 
        ec.crew_id = ?";
$stmt_event_details = $conn->prepare($sql_event_details);
$stmt_event_details->bind_param("i", $crew_id);
$stmt_event_details->execute();
$result_event_details = $stmt_event_details->get_result();

// Fetch the event details
$event = $result_event_details->fetch_assoc();

// Fetch interview details for the selected crew member
$sql_interview_details = "
    SELECT 
        i.location, i.interview_mode, i.meeting_link, i.interview_time, i.interview_status
    FROM 
        interview i
    WHERE 
        i.crew_id = ?";
$stmt_interview_details = $conn->prepare($sql_interview_details);
$stmt_interview_details->bind_param("i", $crew_id);
$stmt_interview_details->execute();
$result_interview_details = $stmt_interview_details->get_result();

// Check if interview details are available
$interview = $result_interview_details->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Details</title>
    <link rel="stylesheet" href="organizerviewcrew.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <h2>Crew Member Details</h2>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <span class="notification-bell">🔔</span>
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
                <li><a href="organizerhome.php"><i class="fas fa-home-alt"></i> Dashboard</a></li>
                <li><a href="organizerevent.php"><i class="fas fa-calendar-alt"></i> Event Hosted</a></li>
                <li><a href="organizerparticipant.php"><i class="fas fa-user-friends"></i> Participant Listing</a></li>
                <li><a href="organizercrew.php" class="active"><i class="fas fa-users"></i> Crew Listing</a></li>
                <li><a href="organizerreport.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="rate_crew.php"><i class="fas fa-star"></i> Feedback</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizersettings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
        <div class="details-box">
                <h3>Crew Information</h3>
                <!--<img src="<?php echo htmlspecialchars($crew['student_photo']); ?>" alt="Photo" width="150" height="150">-->
                
                <?php
                if (!empty($crew['student_photo'])) {
                    $base64Image = 'data:image/jpeg;base64,' . base64_encode($crew['student_photo']);
                    echo '<img class="profile-photo" src="' . $base64Image . '" alt="Student Photo">';
                } else {
                    echo '<img class="profile-photo" src="placeholder.jpg" alt="Placeholder">';
                }
                ?>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($crew['first_name'] . ' ' . $crew['last_name']); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($crew['role']); ?></p>
                <p><strong>Application Status:</strong> <?php echo htmlspecialchars($crew['application_status']); ?></p>
                <p><strong>IC:</strong> <?php echo htmlspecialchars($crew['ic']); ?></p>
                <p><strong>Matric No:</strong> <?php echo htmlspecialchars($crew['matric_no']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($crew['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($crew['email']); ?></p>
                <p><strong>Faculty:</strong> <?php echo htmlspecialchars($crew['faculty_name']); ?></p>
                <p><strong>Year & Course:</strong> <?php echo htmlspecialchars($crew['year_course']); ?></p>
                <p><strong>College:</strong> <?php echo htmlspecialchars($crew['college']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($crew['gender']); ?></p>
            </div>

            <!-- Event Details Section -->
            <div class="details-box">
                <h3>Event Information</h3>
                <p><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Start Date:</strong> <?php echo htmlspecialchars($event['start_date']); ?></p>
                <p><strong>End Date:</strong> <?php echo htmlspecialchars($event['end_date']); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
                <p><strong>Format:</strong> <?php echo htmlspecialchars($event['event_format']); ?></p>
            </div>

            <!-- Interview Details Section (only displayed if available) -->
            <?php if ($interview): ?>
                <div class="details-box">
                    <h3>Interview Information</h3>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($interview['location']); ?></p>
                    <p><strong>Mode:</strong> <?php echo htmlspecialchars($interview['interview_mode']); ?></p>
                    <?php if ($interview['interview_mode'] == 'Online' && $interview['meeting_link']): ?>
                        <p><strong>Meeting Link:</strong> <a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" target="_blank"><?php echo htmlspecialchars($interview['meeting_link']); ?></a></p>
                    <?php endif; ?>
                    <p><strong>Interview Time:</strong> <?php echo htmlspecialchars($interview['interview_time']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($interview['interview_status']); ?></p>
                </div>
            <?php else: ?>
                <p>No interview details available for this crew member.</p>
            <?php endif; ?>

            <div class="actions">
                <a href="organizercrew.php" class="back-button button">Back to Crew Listing</a>
            </div>
        </div>
    </main>
</body>
</html>
