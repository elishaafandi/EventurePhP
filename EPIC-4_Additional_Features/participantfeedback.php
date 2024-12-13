<?php
// Start the session to track the logged-in user (crew member)
session_start();
require("config.php");

// Ensure the user is logged in
if (!isset($_SESSION["ID"])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['ID'] = 1500;
$crew_id = $_SESSION['crew_id'] = 2;
$crew_name = $_SESSION['crew_name'] ='Bat';

// Validate session variables
if (!isset($_SESSION['crew_id']) || !isset($_SESSION['crew_name'])) {
    echo "Session variables for crew member are not set. Please log in again.";
    exit;
}



// Fetch events for feedback selection
$events = [];
$sql = "SELECT * FROM events";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Handle feedback form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming logged-in crew member info
    $crew_id = $_SESSION['crew_id']; // Replace with session data
    $crew_name = $_SESSION['crew_name']; // Replace with session data
    $event_id = $_POST['event'];
    $rating = $_POST['rating'];
    $feedback_text = $_POST['feedbackText'];
    $suggestions_text = $_POST['suggestionsText'];  // Optional
    $from_id = 3000; // Assuming feedback is coming from an organizer with ID 2 (replace with actual organizer ID)

    // Insert feedback into feedbackcrew table
    $stmt = $conn->prepare("INSERT INTO feedbackcrew (crew_id, crew_name, from_id, feedback_text, rating, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isisi", $crew_id, $crew_name, $from_id, $feedback_text, $rating);
    if ($stmt->execute()) {
        echo "Feedback submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Insert suggestion if provided
    if (!empty($suggestions_text)) {
        $stmt = $conn->prepare("INSERT INTO feedbackcrew (crew_id, crew_name, from_id, feedback_text, rating, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isisi", $crew_id, $crew_name, $from_id, $suggestions_text, $rating);
        if (!$stmt->execute()) {
            echo "Error: " . $stmt->error;
        }
    }
}

// Fetch feedback from organizers (now using crew_id stored in session)
$feedbacks = [];
if (isset($_SESSION['crew_id'])) {
    $crew_id = $_SESSION['crew_id']; // Replace with actual logged-in crew_id from session
    $sql = "SELECT * FROM feedbackevent WHERE crew_id = ?"; // Adjusted to use crew_id column
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $crew_id); // Bind the crew_id to the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $feedbacks[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Feedback for Events Participated</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="participantfeedback.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
</head>
<body>
    <header>
        <div class="header-left">
            <a href="organizerhome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="organizerhome.php" class="active">Home</a>
                <a href="#">Calendar</a>
                <a href="#">User Profile</a>
                <a href="#">Dashboard</a>
            </nav>
        </div>
        <div class="nav-right">
            <a href="#" class="participant-site">PARTICIPANT SITE</a>
            <a href="#" class="organizer-site">ORGANIZER SITE</a>
            <span class="notification-bell">🔔</span>
        </div>
    </header>

    <div class="container">
        <div class="col-10 main-content">
            <h2>Give Feedback for Events Participated</h2>
            <p>Your feedback is essential for us to enhance our future events. Please share your experience below.</p>

            <!-- Feedback Form -->
            <div class="feedback-form">
                <form method="post">
                    <div class="mb-3">
                        <label for="eventSelect" class="form-label">Select Event</label>
                        <select class="form-select" id="eventSelect" name="event" required>
                            <option selected disabled>Choose an event...</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="rating" class="form-label">Rate the Event</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option selected disabled>Choose a rating...</option>
                            <option value="5">Excellent - 5</option>
                            <option value="4">Good - 4</option>
                            <option value="3">Average - 3</option>
                            <option value="2">Below Average - 2</option>
                            <option value="1">Poor - 1</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="feedbackText" class="form-label">Your Feedback</label>
                        <textarea class="form-control" id="feedbackText" name="feedbackText" rows="4" placeholder="Share your experience..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="suggestionsText" class="form-label">Suggestions for Improvement</label>
                        <textarea class="form-control" id="suggestionsText" name="suggestionsText" rows="4" placeholder="Any suggestions for future events?"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>

            <!-- Organizer Feedback Section -->
            <div class="organizer-feedback-section mt-5">
                <h3>Receive Feedback from Program Organizers</h3>
                <p>Here’s feedback from program organizers on your participation in recent events.</p>

                <!-- Organizer Feedback List -->
                <?php if (count($feedbacks) > 0): ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="feedback-item">
                            <p><strong>Event: <?php echo htmlspecialchars($feedback['event_name']); ?></strong></p>
                            <p><strong>Organizer's Feedback:</strong> <?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                            <p><strong>Rating:</strong> <?php echo htmlspecialchars($feedback['rating']); ?>/5</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No feedback available from organizers yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
