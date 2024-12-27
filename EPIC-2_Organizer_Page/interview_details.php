<?php
// Start session
session_start();

// Include database connection
include 'config.php';

// Check if crew_id and event_id are provided in the URL
$crew_id = isset($_GET['crew_id']) ? htmlspecialchars($_GET['crew_id']) : '';
$event_id = isset($_GET['event_id']) ? htmlspecialchars($_GET['event_id']) : '';

// Fetch existing interview details if available
$interviewDetails = null;
if (!empty($crew_id) && !empty($event_id)) {
    $sql = "SELECT * FROM interview WHERE crew_id = ? AND event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $crew_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $interviewDetails = $result->fetch_assoc();
    }
    $stmt->close();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crew_id'], $_POST['event_id'], $_POST['interview_mode'], $_POST['location'], $_POST['meeting_link'], $_POST['interview_time'])) {
    // Retrieve POST data
    $crew_id = $_POST['crew_id'];
    $event_id = $_POST['event_id'];
    $interview_mode = $_POST['interview_mode'];
    $location = $_POST['location'];
    $meeting_link = $_POST['meeting_link'];
    $interview_time = $_POST['interview_time']; // Ensure format is 'Y-m-d H:i:s'

    // Validate input data
    if (empty($crew_id) || empty($event_id) || empty($interview_mode) || empty($interview_time)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: interview_details.php?crew_id=$crew_id&event_id=$event_id");
        exit;
    }

    // If no existing interview, insert new record
    if (is_null($interviewDetails)) {
        $sql = "INSERT INTO interview 
                (crew_id, event_id, interview_mode, location, meeting_link, interview_time, interview_status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: interview_details.php?crew_id=$crew_id&event_id=$event_id");
            exit;
        }

        $stmt->bind_param("iissss", $crew_id, $event_id, $interview_mode, $location, $meeting_link, $interview_time);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Interview details added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add interview details: " . $stmt->error;
        }
    }

    // Redirect back to the interview details page
    header("Location: interview_details.php?crew_id=$crew_id&event_id=$event_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Details</title>
    <link rel="stylesheet" href="interview_details.css"> <!-- Link your CSS file -->
    
    <script>
        // Function to toggle the visibility of the meeting link input
        function toggleMeetingLink() {
            const interviewMode = document.getElementById('interview_mode').value;
            const meetingLinkField = document.getElementById('meeting_link_field');
            if (interviewMode === 'Online') {
                meetingLinkField.style.display = 'block';
            } else {
                meetingLinkField.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Interview Details</h1>
        <?php
        // Display session messages
        if (isset($_SESSION['success'])) {
            echo "<p class='success'>" . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<p class='error'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }

        if ($interviewDetails): ?>
            <!-- Display existing interview details -->
            <p><strong>Crew ID:</strong> <?php echo htmlspecialchars($interviewDetails['crew_id']); ?></p>
            <p><strong>Event ID:</strong> <?php echo htmlspecialchars($interviewDetails['event_id']); ?></p>
            <p><strong>Interview Mode:</strong> <?php echo htmlspecialchars($interviewDetails['interview_mode']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($interviewDetails['location']); ?></p>
            <p><strong>Meeting Link:</strong> <?php echo htmlspecialchars($interviewDetails['meeting_link']); ?></p>
            <p><strong>Interview Time:</strong> <?php echo htmlspecialchars($interviewDetails['interview_time']); ?></p>
            <a href="edit_interview.php?crew_id=<?php echo $crew_id; ?>&event_id=<?php echo $event_id; ?>">Edit Interview Details</a>
        <?php else: ?>
            <!-- Display form for adding new interview details -->
            <form action="interview_details.php" method="POST">
                <label for="crew_id">Crew ID:</label>
                <input type="number" id="crew_id" name="crew_id" value="<?php echo $crew_id; ?>" readonly>

                <label for="event_id">Event ID:</label>
                <input type="number" id="event_id" name="event_id" value="<?php echo $event_id; ?>" readonly>

                <label for="interview_mode">Interview Mode:</label>
                <select id="interview_mode" name="interview_mode" onchange="toggleMeetingLink()" required>
                    <option value="Online">Online</option>
                    <option value="Face to Face">Face to Face</option>
                </select>

                <label for="location">Location:</label>
                <input type="text" id="location" name="location">

                <div id="meeting_link_field" style="display: none;">
                    <label for="meeting_link">Meeting Link:</label>
                    <input type="url" id="meeting_link" name="meeting_link">
                </div>

                <label for="interview_time">Interview Time:</label>
                <input type="datetime-local" id="interview_time" name="interview_time" required>

                <button type="submit">Submit</button>
            </form>
        <?php endif; ?>
        <a href="organizercrew.php">Back to Organizer Crew</a>
    </div>
</body>
</html>
