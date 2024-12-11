<?php
// Include the configuration file
include ("config.php");

// Function to fetch feedback data from feedbackevent table
function fetchEventFeedback() {
    global $mysqli;

    reconnectDatabase($mysqli);  // Reconnect if needed

    // SQL query to fetch feedback data for events
    $sqlEventFeedback = "SELECT feedback_text, rating, from_id, event_name 
                         FROM feedbackevent 
                         JOIN events ON feedbackevent.event_id = events.event_id
                         ORDER BY feedbackevent.created_at DESC";

    $eventResult = $mysqli->query($sqlEventFeedback);

    // Check if results are returned
    if ($eventResult && $eventResult->num_rows > 0) {
        while ($row = $eventResult->fetch_assoc()) {
            // Process each feedback row
            echo "<div class='feedback-item'>";
            echo "<p><strong>" . htmlspecialchars($row['from_id']) . "</strong> - Rating: " . $row['rating'] . "/5</p>";
            echo "<p><em>Event: " . htmlspecialchars($row['event_name']) . "</em></p>"; // Display event name
            echo "<p>" . htmlspecialchars($row['feedback_text']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No event feedback available at the moment.</p>";
    }
}

// Function to fetch feedback data for crew from feedbackcrew table
function fetchCrewFeedback() {
    global $mysqli;

    reconnectDatabase($mysqli);  // Reconnect if needed

    // SQL query to fetch feedback data for crew
    $sqlCrewFeedback = "SELECT feedback_text, rating, from_id, crew_name 
                        FROM feedbackcrew 
                        JOIN crew ON feedbackcrew.crew_id = crew.id  /* Updated to 'id' if 'crew_id' doesn't exist */
                        ORDER BY feedbackcrew.created_at DESC";

    $crewResult = $mysqli->query($sqlCrewFeedback);

    // Check if results are returned
    if ($crewResult && $crewResult->num_rows > 0) {
        while ($row = $crewResult->fetch_assoc()) {
            echo "<div class='feedback-item'>";
            echo "<p><strong>" . htmlspecialchars($row['from_id']) . "</strong> - Rating: " . $row['rating'] . "/5</p>";
            echo "<p><em>Crew: " . htmlspecialchars($row['crew_name']) . "</em></p>"; // Display crew name
            echo "<p>" . htmlspecialchars($row['feedback_text']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No crew feedback available at the moment.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Collection Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizerfeedback.css">
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
        <!-- Sidebar -->
        <div class="submenu">
            <div class="menu p-2">
                <div class="menu-group">
                    <div class="menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</div>
                    <div class="menu-item"><i class="fas fa-calendar-alt"></i> Events Hosted</div>
                    <div class="menu-item"><i class="fas fa-users"></i> Participant Listing</div>
                    <div class="menu-item"><i class="fas fa-user-friends"></i> Crew Listing</div>
                    <div class="menu-item"><i class="fas fa-chart-bar"></i> Reports</div>
                </div>
                <div class="menu-group">
                    <div class="menu-item"><i class="fas fa-comment-dots"></i> Feedback Collection</div>
                </div>
                <div class="menu-group">
                    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
                    <div class="menu-item"><i class="fas fa-sign-out-alt"></i> Log Out</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="form-container">
            <h2>Feedback Collection Page</h2>
            <p>View feedback from participants to understand the strengths and weaknesses of your events.</p>

            <!-- Event Feedback List -->
            <div class="feedback-list">
                <h3>Event Feedback</h3>
                <?php fetchEventFeedback(); ?>
            </div>

            <!-- Crew Feedback List -->
            <div class="feedback-list">
                <h3>Crew Feedback</h3>
                <?php fetchCrewFeedback(); ?>
            </div>

            <!-- Crew/Participant Feedback Section -->
            <div class="crew-feedback-section">
                <h3>Provide Feedback for Crew/Participants</h3>
                <form class="feedback-form" method="post" action="submit_feedback.php">
                    <label for="feedback-to">Name of Crew/Participant:</label>
                    <input type="text" id="feedback-to" name="feedback-to" placeholder="Enter the name of the participant or crew member" required>

                    <label for="feedback">Your Feedback:</label>
                    <textarea id="feedback" name="feedback" rows="4" placeholder="Share your experience..." required></textarea>

                    <label for="rating">Rating:</label>
                    <input type="number" id="rating" name="rating" min="1" max="5" placeholder="Rate out of 5" required>

                    <button type="submit" class="btn-submit-feedback">Submit Feedback</button>
                </form>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
