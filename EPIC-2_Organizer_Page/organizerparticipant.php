<?php
session_start();
require 'config.php'; // Ensure this includes your database connection setup


// Check if a club is selected and store it in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
    header("Location: organizerparticipant.php"); // Redirect to avoid form resubmission
    exit;
}

// Get the selected club ID from the session
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

// Fetch events for the selected club

$sql_events = "SELECT event_id, event_name FROM events WHERE club_id = ? AND event_role = 'participant'";
$stmt_events = $conn->prepare($sql_events);
$stmt_events->bind_param("i", $selected_club_id);
$stmt_events->execute();
$result_events = $stmt_events->get_result();

$events = [];
while ($row = $result_events->fetch_assoc()) {
    $events[] = $row;
}

// Get the selected event ID
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : (isset($events[0]['event_id']) ? $events[0]['event_id'] : null);

// Fetch event details for the selected event
$event = null;
if ($event_id) {
    $event_query = "SELECT * FROM events WHERE event_id = ? AND  event_role = 'participant'";
    $stmt_event = $conn->prepare($event_query);
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $event_result = $stmt_event->get_result();

    $event = $event_result->num_rows > 0 ? $event_result->fetch_assoc() : null;
}

// Fetch participants for the selected event
$participant_query = "
    SELECT ep.*, s.first_name, s.last_name, s.ic, s.phone, s.email, s.faculty_name, s.year_course 
    FROM event_participants ep
    JOIN students s ON ep.id = s.id
    WHERE ep.event_id = ?
";
$stmt_participants = $conn->prepare($participant_query);
$stmt_participants->bind_param("i", $event_id);
$stmt_participants->execute();
$participant_result = $stmt_participants->get_result();

// Fetch participants with updated attendance status (excluding "Pending")
$marked_participants_query = "
    SELECT ep.*, s.first_name, s.last_name, s.ic, s.phone, s.email, s.faculty_name, s.year_course 
    FROM event_participants ep
    JOIN students s ON ep.id = s.id
    WHERE ep.event_id = ? 
    AND ep.attendance_status IN ('Present', 'Absent')
";
$stmt_marked_participants = $conn->prepare($marked_participants_query);
$stmt_marked_participants->bind_param("i", $event_id);
$stmt_marked_participants->execute();
$marked_participants_result = $stmt_marked_participants->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Listing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizerparticipant.css">
</head>
<body>
    <header>
        <h1>Participant Listing</h1>
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

        <div class="main-content">
            <!-- Event Selection -->
<form method="GET">
    <label for="event_id">Select Event:</label>
    <select name="event_id" id="event_id" onchange="this.form.submit()">
        <option value="" <?php echo !$event_id ? 'selected' : ''; ?>>No events selected</option>
        <?php foreach ($events as $event_option): ?>
            <option value="<?php echo htmlspecialchars($event_option['event_id']); ?>"
                <?php echo $event_id == $event_option['event_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($event_option['event_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


            <!-- Event Info -->
        
        <?php if (isset($event) && $event): ?>
            <div class="event-info">
            <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
            <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
            <p>Start Date: 
                <?php 
                echo htmlspecialchars(date('d/m/Y', strtotime($event['start_date']))); 
                echo " (" . htmlspecialchars(date('g:i A', strtotime($event['start_date']))) . ")";
                ?>
            </p>
            <p>End Date: 
                <?php 
                echo htmlspecialchars(date('d/m/Y', strtotime($event['end_date']))); 
                echo " (" . htmlspecialchars(date('g:i A', strtotime($event['end_date']))) . ")";
                ?>
            </p>

            <p>Status: <?php echo htmlspecialchars($event['status']); ?></p>
            </div>
        <?php elseif (!empty($events)): ?>
                <p>Please select an event from the dropdown above.</p>
        <?php else: ?>
            <p>No events found.</p>
        <?php endif; ?>


          <!-- Participant Table with Checkboxes -->
<h3>Participant Applications</h3>
<form method="POST" action="update_attendance.php">
    <table class="participant-table">
        <thead>
            <tr>
                <th>Select</th>
                <th>Name</th>
                <th>IC Number</th>
                <th>Mobile Number</th>
                <th>Email</th>
                <th>Faculty</th>
                <th>Year</th>
                <th>Attendance Status</th>
                <th>Special Requirements</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($participant_result && $participant_result->num_rows > 0): ?>
                <?php while ($participant = $participant_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="participant_ids[]" value="<?php echo htmlspecialchars($participant['participant_id']); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($participant['ic']); ?></td>
                        <td><?php echo htmlspecialchars($participant['phone']); ?></td>
                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                        <td><?php echo htmlspecialchars($participant['faculty_name']); ?></td>
                        <td><?php echo htmlspecialchars($participant['year_course']); ?></td>
                        <td><?php echo htmlspecialchars($participant['attendance_status']); ?></td>
                        <td><?php echo htmlspecialchars($participant['requirements']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No participants found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Attendance Status Update Controls -->
    <div class="action-container">
    <input type="hidden" name="new_status" id="new_status" value="">
    <button type="submit" onclick="setStatus('Present')" class="attend-btn">Mark Present</button>
    <button type="submit" onclick="setStatus('Absent')" class="absent-btn">Mark Absent</button>
    </div>
</form>

<script>
    // Set the new_status value before submitting the form
    function setStatus(status) {
        document.getElementById('new_status').value = status;
    }
</script>

<h3>Marked Attendance Participants</h3>
<table class="participant-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>IC Number</th>
            <th>Mobile Number</th>
            <th>Email</th>
            <th>Faculty</th>
            <th>Year</th>
            <th>Attendance Status</th>
            <th>Special Requirements</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($marked_participants_result && $marked_participants_result->num_rows > 0): ?>
            <?php while ($participant = $marked_participants_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($participant['ic']); ?></td>
                    <td><?php echo htmlspecialchars($participant['phone']); ?></td>
                    <td><?php echo htmlspecialchars($participant['email']); ?></td>
                    <td><?php echo htmlspecialchars($participant['faculty_name']); ?></td>
                    <td><?php echo htmlspecialchars($participant['year_course']); ?></td>
                    <td>
                        <?php 
                        $participantStatus = trim($participant['attendance_status']); 
                        ?>
                        <span style="color: <?php echo strcasecmp($participantStatus, 'Present') === 0 ? 'green' : 'red'; ?>;">
                            <?php echo htmlspecialchars($participant['attendance_status']); ?>
                        </span>
                    </td>

                    <td><?php echo htmlspecialchars($participant['requirements']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No marked participants found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

        </div>
    </main>
</body>
</html>
