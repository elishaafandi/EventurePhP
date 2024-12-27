<?php
session_start();
require 'config.php'; // Ensure this includes your database connection setup

// Check if a club is selected and store it in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
    header("Location: organizerevent.php"); // Redirect to avoid form resubmission
    exit;
}

// Get the selected club ID from the session
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

// Fetch events for the selected club
$sql_events = "SELECT event_id, event_name FROM events WHERE club_id = ? AND event_role = 'crew'";
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

$_SESSION['SELECTED_EVENT_ID'] = $event_id;

// Fetch event details for the selected event
if ($event_id) {
    $sql_event = "SELECT e.event_name, e.start_date, e.end_date, e.event_status, e.event_type, 
                         e.event_format, e.description, e.location, e.total_slots, e.available_slots
                  FROM events e
                  WHERE e.event_id = ? ";
    $stmt_event = $conn->prepare($sql_event);
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();

    if ($result_event->num_rows > 0) {
        $event = $result_event->fetch_assoc();
    } else {
        $event = null;
    }
}

// Fetch crew applications for the selected event
$crew_filter = isset($_GET['crew_filter']) ? $_GET['crew_filter'] : 'all';
$sql_crew = "
    SELECT 
        ec.crew_id, ec.role, ec.application_status, 
        s.id, s.first_name, s.last_name, s.student_photo, 
        s.ic, s.matric_no, s.phone, s.faculty_name, 
        s.year_course, s.college, s.email, s.gender
    FROM 
        event_crews ec
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        ec.event_id = ? ";
if ($crew_filter !== 'all') {
    $sql_crew .= " AND ec.application_status = ?";
    $stmt_crew = $conn->prepare($sql_crew);
    $stmt_crew->bind_param("is", $event_id, $crew_filter);
} else {
    $stmt_crew = $conn->prepare($sql_crew);
    $stmt_crew->bind_param("i", $event_id);
}
$stmt_crew->execute();
$result_crew = $stmt_crew->get_result();

// Fetch all crew applications for the selected event
$applications_query = "
    SELECT ec.*, s.first_name, s.last_name, s.email, s.year_course 
    FROM event_crews ec
    JOIN students s ON ec.id = s.id
    WHERE ec.event_id = ?
";
$stmt_applications = $conn->prepare($applications_query);
$stmt_applications->bind_param("i", $event_id);
$stmt_applications->execute();
$applications_result = $stmt_applications->get_result();
 
// Fetch accepted crew members marked as present or absent
$accepted_crew_query = "
    SELECT ec.*, s.first_name, s.last_name, s.email, s.year_course 
    FROM event_crews ec
    JOIN students s ON ec.id = s.id
    WHERE ec.event_id = ? AND ec.application_status = 'accepted' 
    AND ec.attendance_status IN ('Present', 'Absent')
";
$stmt_accepted_crew = $conn->prepare($accepted_crew_query);
$stmt_accepted_crew->bind_param("i", $event_id);
$stmt_accepted_crew->execute();
$accepted_crew_result = $stmt_accepted_crew->get_result();

// Fetch crew members marked as present or absent for the summary table
$sql_summary_crew = "
    SELECT 
        ec.crew_id, ec.role, ec.attendance_status, 
        s.id, s.first_name, s.last_name, s.student_photo, 
        s.email, s.year_course
    FROM 
        event_crews ec
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        ec.event_id = ? AND ec.attendance_status IN ('Present', 'Absent')";
$stmt_summary_crew = $conn->prepare($sql_summary_crew);
$stmt_summary_crew->bind_param("i", $event_id);
$stmt_summary_crew->execute();
$result_summary_crew = $stmt_summary_crew->get_result();

// Fetch crew members with pending attendance for the attendance table
$sql_pending_attendance = "
    SELECT 
        ec.crew_id, ec.role, ec.attendance_status, 
        s.id, s.first_name, s.last_name, s.student_photo, 
        s.email, s.year_course
    FROM 
        event_crews ec
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        ec.event_id = ? AND ec.application_status = 'accepted'
        AND (ec.attendance_status IS NULL OR ec.attendance_status = 'Pending')";
$stmt_pending_attendance = $conn->prepare($sql_pending_attendance);
$stmt_pending_attendance->bind_param("i", $event_id);
$stmt_pending_attendance->execute();
$result_pending_attendance = $stmt_pending_attendance->get_result();

// Fetch crew members marked as present or absent for the summary table
$attendance_filter = isset($_GET['crew_filter']) ? $_GET['crew_filter'] : 'all';
$sql_summary_crew = "
    SELECT 
        ec.crew_id, ec.role, ec.attendance_status, 
        s.id, s.first_name, s.last_name, s.student_photo, 
        s.email, s.year_course
    FROM 
        event_crews ec
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        ec.event_id = ? AND ec.attendance_status IN ('Present', 'Absent') 
        AND ec.attendance_status <> 'Pending'";


if ($attendance_filter !== 'all') {
    $sql_summary_crew .= " AND ec.attendance_status = ?";
    $stmt_summary_crew = $conn->prepare($sql_summary_crew);
    $stmt_summary_crew->bind_param("is", $event_id, $attendance_filter);
} else {
    $stmt_summary_crew = $conn->prepare($sql_summary_crew);
    $stmt_summary_crew->bind_param("i", $event_id);
}

$stmt_summary_crew->execute();
$result_summary_crew = $stmt_summary_crew->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Listing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizercrew.css">
</head>
<body>
    <header>
        <h1>Crew Listing</h1>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
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
        <li><a href="organizerclubmembership.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerclub membership.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Club Membership</a></li>
    </ul>
    <ul style="margin-top: 60px;">
        <li><a href="organizersettings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizersettings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i>Settings</a></li>
        <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Event Selection -->
            <form method="GET">
                <label for="event_id">Select Event:</label>
                <select name="event_id" id="event_id" onchange="this.form.submit()">
                    <?php foreach ($events as $event_option): ?>
                        <option value="<?php echo htmlspecialchars($event_option['event_id']); ?>"
                            <?php echo $event_id == $event_option['event_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event_option['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (isset($event)): ?>
                <div class="event-info">
                    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                    <p>Program Date: 
                        <?php 
                        echo htmlspecialchars(date('d/m/Y', strtotime($event['start_date']))); 
                        echo " to "; 
                        echo htmlspecialchars(date('d/m/Y', strtotime($event['end_date']))); 
                        ?>
                    </p>
                    <p>Time: 
                        <?php 
                        echo htmlspecialchars(date('g:i A', strtotime($event['start_date']))); 
                        echo " to "; 
                        echo htmlspecialchars(date('g:i A', strtotime($event['end_date']))); 
                        ?>
                    </p>
                    <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                    <p>Description: <?php echo htmlspecialchars($event['description']); ?></p>
            </div>
            <?php endif; ?>



            <!-- Crew Applications -->
            <h3 class="crew-header">Crew Applications</h3>
            <label for="crew-filter">Filter by Status:</label>
            <select id="crew-filter" onchange="window.location.href='?event_id=<?php echo $event_id; ?>&crew_filter=' + this.value;">
                <option value="all" <?php echo $crew_filter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="applied" <?php echo $crew_filter === 'applied' ? 'selected' : ''; ?>>Applied</option>
                <option value="interview" <?php echo $crew_filter === 'interview' ? 'selected' : ''; ?>>Interview</option>
                <option value="rejected" <?php echo $crew_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="accepted" <?php echo $crew_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
            </select>

         
        <form action="process_crew_action.php" method="POST">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        
        <table class="crew-table">
        <thead>
            <tr>
                <th>Select</th>
                <th>Photo</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Year & Course</th>
                <th>Role</th>
                <th>Application Status</th>
                <th>View Details</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($crew = $result_crew->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($crew['application_status'] !== 'accepted' && $crew['application_status'] !== 'rejected'): ?>
                        <input type="checkbox" name="selected_crew[]" value="<?php echo htmlspecialchars($crew['crew_id']); ?>">
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    if (!empty($crew['student_photo'])) {
                        $base64Image = 'data:image/jpeg;base64,' . base64_encode($crew['student_photo']);
                        echo '<img src="' . $base64Image . '" alt="Student Photo" width="50" height="50">';
                    } else {
                        echo '<img src="placeholder.jpg" alt="Placeholder" width="50" height="50">';
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($crew['first_name'] . " " . $crew['last_name']); ?></td>
                <td><?php echo htmlspecialchars($crew['email']); ?></td>
                <td><?php echo htmlspecialchars($crew['year_course']); ?></td>
                <td><?php echo htmlspecialchars($crew['role']); ?></td>
                <td><?php echo htmlspecialchars($crew['application_status']); ?></td>
                <td>
                    <a href="organizerviewcrew.php?id=<?php echo $crew['crew_id']; ?>" class="view-button">View</a>
                    <?php if ($crew['application_status'] === 'interview'): ?>
                        <a href="interview_details.php?crew_id=<?php echo $crew['crew_id']; ?>&event_id=<?php echo $event_id; ?>" class="interview-details-button">Interview Details</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>


    <div class="action-buttons">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <button type="submit" name="action" value="accept" class="accept-btn">Accept</button>
        <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
        <button type="submit" name="action" value="interview" class="interview-btn">Interview</button>
        <!--<button class="view-interview-btn">View Interview List</button>-->
        <a href="interviewlist.php" class="view-interview-btn">View Interview List</a>
    </div>
</form>

<script>
    // Set the new_status value before submitting the form
    function setStatus(status) {
        document.getElementById('new_status').value = status;
    }
</script>

<!-- Accepted Crew Table -->
<h3 class="accepted-crew-header">Crew Attendance</h3>
<form method="POST" action="process_attendance.php">
    <table class="accepted-crew">
        <thead>
            <tr>
                <th>Select</th>
                <th>Photo</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Year & Course</th>
                <th>Role</th>
                <th>Attendance Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_pending_attendance->num_rows > 0): ?>
                <?php while ($pending = $result_pending_attendance->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_crew[]" value="<?php echo htmlspecialchars($pending['crew_id']); ?>">
                        </td>
                        <td>
                            <?php
                            if (!empty($pending['student_photo'])) {
                                $base64Image = 'data:image/jpeg;base64,' . base64_encode($pending['student_photo']);
                                echo '<img src="' . $base64Image . '" alt="Student Photo" width="50" height="50">';
                            } else {
                                echo '<img src="placeholder.jpg" alt="Placeholder" width="50" height="50">';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($pending['first_name'] . " " . $pending['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($pending['email']); ?></td>
                        <td><?php echo htmlspecialchars($pending['year_course']); ?></td>
                        <td><?php echo htmlspecialchars($pending['role']); ?></td>
                        <td><?php echo htmlspecialchars($pending['attendance_status'] ?? 'Pending'); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No pending attendance crew members found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="accepted-crew-action-container">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <button type="submit" name="attendance_action" value="present" class="present-btn">Mark as Present</button>
        <button type="submit" name="attendance_action" value="absent" class="absent-btn">Mark as Absent</button>
    </div>
</form>


<!-- Summary Crew Table -->
<h3 class="summary-header">Summary</h3>
<label for="attendance-filter">Filter by Attendance:</label>
<select id="attendance-filter" onchange="filterAttendance()">
    <option value="all" <?php echo $crew_filter === 'all' ? 'selected' : ''; ?>>All</option>
    <option value="Present" <?php echo $crew_filter === 'Present' ? 'selected' : ''; ?>>Present</option>
    <option value="Absent" <?php echo $crew_filter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
</select>

<table class="summary-table">
    <thead>
        <tr>
            <th>Photo</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Year & Course</th>
            <th>Role</th>
            <th>Attendance Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_summary_crew->num_rows > 0): ?>
            <?php while ($summary = $result_summary_crew->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php
                        if (!empty($summary['student_photo'])) {
                            $base64Image = 'data:image/jpeg;base64,' . base64_encode($summary['student_photo']);
                            echo '<img src="' . $base64Image . '" alt="Student Photo" width="50" height="50">';
                        } else {
                            echo '<img src="placeholder.jpg" alt="Placeholder" width="50" height="50">';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($summary['first_name'] . " " . $summary['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($summary['email']); ?></td>
                    <td><?php echo htmlspecialchars($summary['year_course']); ?></td>
                    <td><?php echo htmlspecialchars($summary['role']); ?></td>
                    <td>
                        <span style="color: <?php echo strcasecmp($summary['attendance_status'], 'Present') === 0 ? 'green' : 'red'; ?>;">
                            <?php echo htmlspecialchars($summary['attendance_status']); ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No crew members marked as present or absent.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
function filterAttendance() {
    const filter = document.getElementById('attendance-filter').value;
    window.location.href = `?event_id=<?php echo $event_id; ?>&crew_filter=${filter}`;
}
</script>



        </div>
    </main>
</body>
</html>
