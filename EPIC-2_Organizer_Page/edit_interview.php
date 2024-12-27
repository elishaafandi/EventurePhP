<?php
// Start session
session_start();

// Include database connection
include 'config.php';

// Check if crew_id and event_id are provided in the URL
$crew_id = isset($_GET['crew_id']) ? htmlspecialchars($_GET['crew_id']) : '';
$event_id = isset($_GET['event_id']) ? htmlspecialchars($_GET['event_id']) : '';

if (empty($crew_id) || empty($event_id)) {
    $_SESSION['error'] = "Invalid crew or event ID.";
    header("Location: organizercrew.php");
    exit;
}

// Retrieve interview details
$sql = "SELECT * FROM interview WHERE crew_id = ? AND event_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: organizercrew.php");
    exit;
}

$stmt->bind_param("ii", $crew_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();
$interview = $result->fetch_assoc();
$stmt->close();

if (!$interview) {
    $_SESSION['error'] = "No interview found for the provided Crew ID and Event ID.";
    header("Location: organizercrew.php");
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interview_mode'], $_POST['location'], $_POST['meeting_link'], $_POST['interview_time'])) {
    // Retrieve POST data
    $interview_mode = $_POST['interview_mode'];
    $location = $_POST['location'];
    $meeting_link = $_POST['meeting_link'];
    $interview_time = $_POST['interview_time']; // Ensure format is 'Y-m-d H:i:s'

    // Validate input
    if (empty($interview_mode) || empty($interview_time)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: edit_interview.php?crew_id=$crew_id&event_id=$event_id");
        exit;
    }

    // Update interview details
    $sql = "UPDATE interview 
            SET interview_mode = ?, location = ?, meeting_link = ?, interview_time = ?, updated_at = NOW() 
            WHERE crew_id = ? AND event_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: edit_interview.php?crew_id=$crew_id&event_id=$event_id");
        exit;
    }

    $stmt->bind_param("ssssii", $interview_mode, $location, $meeting_link, $interview_time, $crew_id, $event_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Interview details updated successfully.";
        header("Location: organizercrew.php?event_id=$event_id");
        exit;
    } else {
        $_SESSION['error'] = "Failed to update interview details: " . $stmt->error;
        header("Location: edit_interview.php?crew_id=$crew_id&event_id=$event_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Interview Details</title>
    <link rel="stylesheet" href="edit_interview.css"> 
    <script>
        // Function to toggle the visibility of the meeting link field
        function toggleMeetingLink() {
            const interviewMode = document.getElementById('interview_mode').value;
            const meetingLinkField = document.getElementById('meeting_link_field');
            if (interviewMode === 'Online') {
                meetingLinkField.style.display = 'block';
            } else {
                meetingLinkField.style.display = 'none';
            }
        }
        
        window.onload = function() {
            toggleMeetingLink();
        };
    </script>
</head>
<body>
    <div class="container">
        <h1>Edit Interview Details</h1>
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
        ?>
        <form action="edit_interview.php?crew_id=<?php echo $crew_id; ?>&event_id=<?php echo $event_id; ?>" method="POST">
            <label for="crew_id">Crew ID:</label>
            <input type="number" id="crew_id" name="crew_id" value="<?php echo $interview['crew_id']; ?>" readonly>

            <label for="event_id">Event ID:</label>
            <input type="number" id="event_id" name="event_id" value="<?php echo $interview['event_id']; ?>" readonly>

            <label for="interview_mode">Interview Mode:</label>
            <select id="interview_mode" name="interview_mode" onchange="toggleMeetingLink()" required>
                <option value="Online" <?php echo ($interview['interview_mode'] === 'Online') ? 'selected' : ''; ?>>Online</option>
                <option value="Face to Face" <?php echo ($interview['interview_mode'] === 'Face to Face') ? 'selected' : ''; ?>>Face to Face</option>
            </select>

            <label for="location">Location:</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($interview['location']); ?>">

            <!-- Meeting link field, hidden if not "Online" -->
            <div id="meeting_link_field" style="display: none;">
                <label for="meeting_link">Meeting Link:</label>
                <input type="url" id="meeting_link" name="meeting_link" value="<?php echo htmlspecialchars($interview['meeting_link']); ?>">
            </div>

            <label for="interview_time">Interview Time:</label>
            <input type="datetime-local" id="interview_time" name="interview_time" value="<?php echo date('Y-m-d\TH:i', strtotime($interview['interview_time'])); ?>" required>

            <button type="submit">Save Changes</button>
        </form>
        <a href="organizercrew.php?event_id=<?php echo $event_id; ?>">Back to Organizer Crew</a>
    </div>
</body>
</html>
