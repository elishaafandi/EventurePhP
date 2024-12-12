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

// Fetch **all events** for the selected club for "Feedback for Event" section
$sql_all_events = "SELECT event_id, event_name FROM events WHERE club_id = ?";
$stmt_all_events = $conn->prepare($sql_all_events);
$stmt_all_events->bind_param("i", $selected_club_id);
$stmt_all_events->execute();
$result_all_events = $stmt_all_events->get_result();

$all_events = [];
while ($row = $result_all_events->fetch_assoc()) {
    $all_events[] = $row;
}

// Fetch **only events with role 'crew'** for the crew rating section
$sql_crew_events = "SELECT event_id, event_name FROM events WHERE club_id = ? AND event_role = 'crew'";
$stmt_crew_events = $conn->prepare($sql_crew_events);
$stmt_crew_events->bind_param("i", $selected_club_id);
$stmt_crew_events->execute();
$result_crew_events = $stmt_crew_events->get_result();

$crew_events = [];
while ($row = $result_crew_events->fetch_assoc()) {
    $crew_events[] = $row;
}
// Handle feedback display logic for selected event in "Feedback for Event" section
$view_event_id = isset($_GET['view_event_id']) ? $_GET['view_event_id'] : null;
$feedbacks = [];

if ($view_event_id) {
    $sql_feedback = "
    SELECT 
        fe.feedbackevent_id, 
        fe.rating, 
        fe.feedback, 
        fe.feedback_date, 
        sp.first_name AS participant_name, 
        sc.first_name AS crew_name
    FROM feedbackevent fe
    LEFT JOIN event_participants ep ON fe.participant_id = ep.participant_id
    LEFT JOIN students sp ON ep.id = sp.id
    LEFT JOIN event_crews ec ON fe.crew_id = ec.crew_id
    LEFT JOIN students sc ON ec.id = sc.id
    WHERE fe.event_id = ?
    ORDER BY fe.feedback_date DESC";
    $stmt_feedback = $conn->prepare($sql_feedback);
    $stmt_feedback->bind_param("i", $view_event_id);
    $stmt_feedback->execute();
    $result_feedback = $stmt_feedback->get_result();

    while ($row = $result_feedback->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

// Check if an event is selected
$selected_event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;
$crews = [];

if ($selected_event_id) {
    // Fetch crew members for the selected event
    $sql_crew = "
        SELECT ec.crew_id, ec.role, s.first_name, s.last_name 
        FROM event_crews ec
        JOIN students s ON ec.id = s.id
        WHERE ec.event_id = ?";
    $stmt_crew = $conn->prepare($sql_crew);
    $stmt_crew->bind_param("i", $selected_event_id);
    $stmt_crew->execute();
    $result_crew = $stmt_crew->get_result();

    while ($row = $result_crew->fetch_assoc()) {
        $crews[] = $row;
    }
}

// Process feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['feedback'], $_POST['crew_id'], $_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $crew_id = $_POST['crew_id'];
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];
    $feedback_date = date('Y-m-d H:i:s');

    // Validate inputs
    if (!empty($event_id) && !empty($crew_id) && !empty($rating) && !empty($feedback)) {
        
        // Check if feedback has already been submitted for this event and crew member
        $check_feedback_query = "
        SELECT 1 
        FROM feedbackcrew 
        WHERE event_id = ? AND crew_id = ?";

        $stmt_check_feedback = $conn->prepare($check_feedback_query);
        $stmt_check_feedback->bind_param("ii", $event_id, $crew_id);
        $stmt_check_feedback->execute();
        $result_check = $stmt_check_feedback->get_result();

        if ($result_check->num_rows > 0) {
            // Feedback already submitted for this crew in this event
            echo "<script>alert('You have already rated this crew member for this event.');</script>";
        } else {
            // Proceed with inserting the feedback
            $query = "INSERT INTO feedbackcrew (event_id, crew_id, rating, feedback, feedback_date) 
                      VALUES (?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("iiiss", $event_id, $crew_id, $rating, $feedback, $feedback_date);
                if ($stmt->execute()) {
                    echo "<script>alert('Feedback submitted successfully!');</script>";
                } else {
                    echo "<script>alert('Error: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Error: " . $conn->error . "');</script>";
            }
        }

        $stmt_check_feedback->close();
    } else {
        echo "<script>alert('All fields are required.');</script>";
    }
}


$feedbacks = [];
if ($selected_event_id) {
    $sql_feedback = "
    SELECT 
        fe.feedbackevent_id, 
        fe.rating, 
        fe.feedback, 
        fe.feedback_date, 
        sp.first_name AS participant_name, 
        sc.first_name AS crew_name
    FROM feedbackevent fe
    LEFT JOIN event_participants ep ON fe.participant_id = ep.participant_id
    LEFT JOIN students sp ON ep.id = sp.id
    LEFT JOIN event_crews ec ON fe.crew_id = ec.crew_id
    LEFT JOIN students sc ON ec.id = sc.id
    WHERE fe.event_id = ?
    ORDER BY fe.feedback_date DESC";
$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("i", $selected_event_id);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

while ($row = $result_feedback->fetch_assoc()) {
    $feedbacks[] = $row;
}

}

// Close the database connection
$conn->close();
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
        <h2>Feedback Collection Page</h2>
        <p>View feedback from participants to understand the strengths and weaknesses of your events.</p>
        <div class="feedback-list">
    <h3>Feedback for Events</h3>
    <p>Click "View Feedback" to see feedback for a specific event.</p>
    <?php if (!empty($all_events)): ?>
        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['event_name']); ?></td>
                        <td>
                        <a href="feedback_event.php?event_id=<?= htmlspecialchars($event['event_id']); ?>" class="btn-view-feedback">View Feedback</a>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No events available under the selected club.</p>
    <?php endif; ?>
</div>
        
                <div class="feedback-list">
                    <h3>Your Feedbacks for Crew Members</h3>
                    <p>Select an event to view the feedback you've submitted for its crew members.</p>
                    <?php if (!empty($crew_events)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($crew_events as $event): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($event['event_name']); ?></td>
                                        <td>
                                            <a href="feedbackcrew.php?event_id=<?= htmlspecialchars($event['event_id']); ?>" class="btn-view-feedback">
                                                View Crew Feedbacks
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No events with crew members are available under the selected club.</p>
                    <?php endif; ?>
                </div>

            <div class="feedback-list">
            <h3>Select an Event to Rate Your Crew Members</h3>
            <form method="POST" action="">
                <label for="event_id">Event:</label>
                <select name="event_id" id="event_id" onchange="this.form.submit()">
                    <option value="">-- Select Event --</option>
                    <?php foreach ($crew_events as $event): ?>
                        <option value="<?= $event['event_id']; ?>" <?= $selected_event_id == $event['event_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($event['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="crew-feedback-section">
            <?php if ($selected_event_id && !empty($crews)): ?>
            <h6>Rate a Crew Member</h6>
            <form class="feedback-form" method="POST" action="">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($selected_event_id); ?>">

                <label for="crew_id">Crew Member:</label>
                <select name="crew_id" id="crew_id" required>
                    <option value="">-- Select Crew --</option>
                    <?php foreach ($crews as $crew): ?>
                        <option value="<?= htmlspecialchars($crew['crew_id']); ?>">
                            <?= htmlspecialchars($crew['first_name'] . ' ' . $crew['last_name']) . " - Role: " . htmlspecialchars($crew['role']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="rating">Rating:</label>
                <input type="number" name="rating" id="rating" min="1" max="5" required>

                <label for="feedback">Feedback:</label>
                <textarea name="feedback" id="feedback" rows="4" required></textarea>

                <button type="submit" class="btn-submit-feedback">Submit Feedback</button>
            </form>
        <?php elseif ($selected_event_id): ?>
            <p>No crew members found for the selected event.</p>
        <?php endif; ?>
        </div>
        </div>


        </div>
    </main>
</body>
</html>