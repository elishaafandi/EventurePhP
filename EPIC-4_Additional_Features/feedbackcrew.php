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

if (isset($_GET['event_id'])) {
    $selected_event_id = $_GET['event_id'];

    // Query to fetch feedbacks for crew members of the selected event
    $sql_feedback_crew = "
        SELECT 
            fc.feedbackcrew_id,
            fc.rating,
            fc.feedback,
            fc.feedback_date,
            CONCAT(s.first_name, ' ', s.last_name) AS crew_name,
            ec.role
        FROM feedbackcrew fc
        JOIN event_crews ec ON fc.crew_id = ec.crew_id
        JOIN students s ON ec.id = s.id
        WHERE fc.event_id = ?
        ORDER BY fc.feedback_date DESC";
        
    $stmt_feedback_crew = $conn->prepare($sql_feedback_crew);
    $stmt_feedback_crew->bind_param("i", $selected_event_id);
    $stmt_feedback_crew->execute();
    $result_feedback_crew = $stmt_feedback_crew->get_result();

    $crew_feedbacks = [];
    while ($row = $result_feedback_crew->fetch_assoc()) {
        $crew_feedbacks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crew Details</title>
    <link rel="stylesheet" href="rate_crew.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <h2>Rate Your Crew</h2>
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
                <li><a href="organizercrew.php"><i class="fas fa-users"></i> Crew Listing</a></li>
                <li><a href="organizerreport.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="rate_crew.php"class="active"><i class="fas fa-star"></i> Feedback</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizersettings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
        <div class="feedback-list">
            <h3>Feedback for Crew Members of Selected Event</h3>
            <?php if (!empty($crew_feedbacks)): ?>
                <div class="card-container">
                    <?php foreach ($crew_feedbacks as $feedback): ?>
                        <div class="feedback-card">
                            <h4><?= htmlspecialchars($feedback['crew_name']); ?></h4>
                            <p><strong>Role:</strong> <?= htmlspecialchars($feedback['role']); ?></p>
                            <p><strong>Rating:</strong> <?= htmlspecialchars($feedback['rating']); ?> / 5</p>
                            <p><strong>Feedback:</strong> <?= htmlspecialchars($feedback['feedback']); ?></p>
                            <p><small><strong>Date Submitted:</strong> <?= htmlspecialchars($feedback['feedback_date']); ?></small></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No feedbacks found for crew members in this event.</p>
            <?php endif; ?>
        </div>



        </div>
    </main>
</body>
</html>