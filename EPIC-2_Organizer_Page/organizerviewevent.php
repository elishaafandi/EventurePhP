<?php
session_start();
include 'config.php';

// Ensure the user is logged in
if (!isset($_SESSION['ID'])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Get the event ID from the URL
$event_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$event_id) {
    echo "Event ID is missing.";
    exit;
}

// Fetch event details from the database
$sql_event = "SELECT e.event_id, e.event_name, e.start_date, e.end_date, e.event_status, e.event_type, e.event_format, 
                     e.description, e.location, e.total_slots, e.available_slots, e.status, e.organizer_id, e.event_role, 
                     e.organizer, u.username, e.event_photo, e.approval_letter 
              FROM events e 
              JOIN users u ON e.organizer_id = u.id 
              WHERE e.event_id = ?";

$stmt = mysqli_prepare($conn, $sql_event);
mysqli_stmt_bind_param($stmt, "i", $event_id);
mysqli_stmt_execute($stmt);
$result_event = mysqli_stmt_get_result($stmt);

if ($result_event && mysqli_num_rows($result_event) > 0) {
    $event = mysqli_fetch_assoc($result_event);
} else {
    echo "Event not found.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizerhome.css">
    <link rel="stylesheet" href="organizerviewevent.css">
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
            </a>
            </div>
        </div>
    </header>

    <aside class="sidebar">
            <div class="logo-container">
                <a href="organizerhome.php" class="logo">EVENTURE</a>
            </div>
            <ul>
        <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
        <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
        <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
        <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Crew Listing</a></li>
        <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
        <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
    </ul>
    <ul style="margin-top: 60px;">
        <li><a href="organizersettings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizersettings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i>Settings</a></li>
        <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
        </aside>

    <main>
        <section class="main-content">
            <h2>Event Details</h2>
            <div class="event-details">

            <?php
                if (!empty($event['event_photo'])) {
                    $base64Image = 'data:image/jpeg;base64,' . base64_encode($event['event_photo']);
                    echo '<img class="event-photo" src="' . $base64Image . '" alt="Event Photo">';
                } else {
                    echo '<img class="event-photo" src="placeholder.jpg" alt="Placeholder">';
                }?>
                
                <p><strong>Event Name:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
                <p><strong>Event Role:</strong> <?php echo htmlspecialchars($event['event_role']); ?></p>
                <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
                <p><strong>Organizer Username:</strong> <?php echo htmlspecialchars($event['username']); ?></p>
                <p><strong>Start Date:</strong> <?php echo date("d/m/Y H:i", strtotime($event['start_date'])); ?></p>
                <p><strong>End Date:</strong> <?php echo date("d/m/Y H:i", strtotime($event['end_date'])); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($event['status']); ?></p>
                <p><strong>Event Status:</strong> <?php echo htmlspecialchars($event['event_status']); ?></p>
                <p><strong>Event Type:</strong> <?php echo htmlspecialchars($event['event_type']); ?></p>
                <p><strong>Event Format:</strong> <?php echo htmlspecialchars($event['event_format']); ?></p>
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <p><strong>Total Slots:</strong> <?php echo htmlspecialchars($event['total_slots']); ?></p>
                <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($event['available_slots']); ?></p>
        
                <?php
                if (!empty($event['approval_letter'])) {
                    echo '<p><strong>Approval Letter:</strong> <a href="view_file.php?event_id=' . $event['event_id'] . '&type=approval_letter" target="_blank">View Approval Letter</a></p>';
                }
                ?>

                </div>


            <div class="event-actions">
                <!-- Edit and Delete buttons -->
                <a href="editevent.php?event_id=<?php echo $event['event_id']; ?>" class="edit-btn">Edit Event</a>
                <a href="deleteevent.php?event_id=<?php echo $event['event_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event?');">Delete Event</a>
            </div>
        </section>
    </main>
</body>
</html>
