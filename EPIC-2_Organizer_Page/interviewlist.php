<?php
session_start();
include 'config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve the selected event_id from the session
$event_id = isset($_SESSION['SELECTED_EVENT_ID']) ? $_SESSION['SELECTED_EVENT_ID'] : null;


// Handle Accept/Reject action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_status'])) {
    $application_status = $_POST['application_status'];
    $crew_id = $_POST['crew_id'];

    $update_sql = "UPDATE event_crews SET application_status = ? WHERE crew_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $application_status, $crew_id);

    if ($stmt->execute()) {
        echo "<script>alert('Application status updated successfully.');</script>";
    } else {
        echo "<script>alert('Failed to update application status.');</script>";
    }
    $stmt->close();
}

// Handle "Done" action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
    $selected_interviews = $_POST['interview_ids'] ?? [];
    if (!empty($selected_interviews)) {
        $placeholders = implode(',', array_fill(0, count($selected_interviews), '?'));
        $update_sql = "UPDATE interview SET interview_status = 'Done' WHERE interview_id IN ($placeholders)";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param(str_repeat('i', count($selected_interviews)), ...$selected_interviews);

        if ($stmt->execute()) {
            echo "<script>alert('Selected interviews marked as Done.');</script>";
        } else {
            echo "<script>alert('Failed to update interview status.');</script>";
        }
        $stmt->close();
    }
}

    $sql_event = "
    SELECT 
        e.event_name
    FROM 
        events e
    WHERE 
        e.event_id = ?";
    $stmt_event = $conn->prepare($sql_event);
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();
    $event = $result_event->fetch_assoc();
    $stmt_event->close();
    

    // SQL query to get interview data along with crew and student details
    $sql = "
    SELECT 
        i.interview_id,
        i.crew_id,
        i.event_id,
        i.location,
        i.interview_mode,
        i.meeting_link,
        i.interview_time,
        i.interview_status,
        ec.role,
        ec.application_status,
        s.first_name AS student_first_name,
        s.last_name AS student_last_name,
        s.email AS student_email
    FROM 
        interview i
    JOIN 
        event_crews ec ON i.crew_id = ec.crew_id
    JOIN 
        students s ON ec.id = s.id
    WHERE 
        i.event_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventure Organizer Site</title>
    <link rel="stylesheet" href="interviewlist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <h1>Interview Listing</h1>
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

    <main>
        <aside class="sidebar">
            <div class="logo-container">
                <a href="organizerhome.php" class="logo">EVENTURE</a>
            </div>
            <ul>
                <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
                <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Event Hosted</a></li>
                <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> Participant Listing</a></li>
                <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Crew Listing</a></li>
                <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="organizerfeedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Feedback</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizersettings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizersettings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">

         
            <?php if (isset($event['event_name'])): ?>
                <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>
            <?php endif; ?>

            <h3>Interview Details</h3>
            <div class="table-container">
                <form method="POST">
                    <table class="interview-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Interview ID</th>
                                <th>Location</th>
                                <th>Interview Mode</th>
                                <th>Meeting Link</th>
                                <th>Interview Time</th>
                                <th>Interview Status</th>
                                <th>Student Name</th>
                                <th>Student Email</th>
                                <th>Role</th>
                                <th>Application Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="interview_ids[]" value="<?php echo $row['interview_id']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['interview_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo htmlspecialchars($row['interview_mode']); ?></td>
                                        <td>
                                            <?php if (!empty($row['meeting_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['meeting_link']); ?>" target="_blank" rel="noopener noreferrer">LINK</a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['interview_time']); ?></td>
                                        <td><?php echo htmlspecialchars($row['interview_status']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_first_name'] . " " . $row['student_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['role']); ?></td>
                                        <td><?php echo htmlspecialchars($row['application_status']); ?></td>
                                        <td>
                                            <?php if ($row['interview_status'] === "Done"): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="crew_id" value="<?php echo $row['crew_id']; ?>">
                                                    <button type="submit" name="application_status" value="Accepted">Accept</button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="crew_id" value="<?php echo $row['crew_id']; ?>">
                                                    <button type="submit" name="application_status" value="Rejected">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12">No results found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="action-buttons">
                        <button type="submit" name="mark_done">Done</button>
                        <a href="organizercrew.php" class="back-button">Back to Crew Listing Page</a>
                    </div>

                </form>
            </div>
        </div>
    </main>
</body>
</html>
