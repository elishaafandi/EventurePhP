<?php
include 'config.php';
session_start();

// Check if user is logged in by verifying the session ID
if (!isset($_SESSION["ID"])) {
    header("Location: organizerhome.php");
    exit; 
}

// Ensure the club ID is stored in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];  // Store selected club ID in session
}

// Initialize club ID from session
$selected_club_id = isset($_SESSION["SELECTEDID"]) ? $_SESSION["SELECTEDID"] : '';

// Get the event ID from the URL
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : 0;
// Get the event ID from the URL
$event_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($event_id == 0) {
    echo "<script>alert('Invalid event ID'); window.location.href='organizerhome.php';</script>";
    exit;
}
        
// Fetch the existing event details from the database
$query = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    echo "<script>alert('Event not found'); window.location.href='organizerhome.php';</script>";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Event details handling code here...
    $event_name = $_POST['event_name'] ?? $event['event_name'];
    $description = $_POST['event_description'] ?? $event['description'];
    $location = $_POST['location'] ?? $event['location'];
    $total_slots = $_POST['total_slots'] ?? $event['total_slots'];
    $available_slots = $_POST['available_slots'] ?? $total_slots;
    $event_status = $_POST['event_status'] ?? $event['event_status'];
    $event_type = $_POST['event_type'] ?? $event['event_type'];
    $event_format = $_POST['event_format'] ?? $event['event_format'];
    $start_date = $_POST['start_date'] ?? $event['start_date'];
    $end_date = $_POST['end_date'] ?? $event['end_date'];
    
    

    // Update event details in the database (except event_role and club_id)
    $sql = "UPDATE events SET event_name = ?, description = ?, location = ?, total_slots = ?, available_slots = ?, event_status = ?, event_type = ?, event_format = ?, start_date = ?, end_date = ? WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiiissssi", $event_name, $description, $location, $total_slots, $available_slots, $event_status, $event_type, $event_format, $start_date, $end_date, $event_id);

    if ($stmt->execute()) {
        echo "<script>alert('Event updated successfully!'); window.location.href='organizerhome.php';</script>";
    } else {
        echo "<script>alert('Error updating event: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizercreate.css">
</head>
<body>
    <header>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <span class="notification-bell">🔔</span>
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
                <li><a href="organizerevent.php"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
                <li><a href="organizerparticipant.php"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
                <li><a href="organizercrew.php"><i class="fas fa-users"></i>Crew Listing</a></li>
                <li><a href="organizerreport.php"><i class="fas fa-chart-line"></i>Reports</a></li>
                <li><a href="organizerfeedback.php"><i class="fas fa-star"></i>Feedback</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizersettings.php"><i class="fas fa-cog"></i>Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
       

        <main class="form-container">
        <h2>Update Event</h2>
        <p>Update the event details below.</p>
        <form method="POST" action="editevent.php?event_id=<?php echo $event_id; ?>" enctype="multipart/form-data">
            <fieldset>
                <legend>Event Details</legend>

                <div class="form-group">
                    <label for="event_name">Event Name</label>
                    <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="event_description">Description</label>
                    <textarea id="event_description" name="event_description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="total_slots">Total Slots</label>
                    <input type="number" id="total_slots" name="total_slots" value="<?php echo $event['total_slots']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="event_status">Event Status</label>
                    <input type="text" id="event_status" name="event_status" value="<?php echo $event['event_status']; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type">
                        <option value="academic" <?php if ($event['event_type'] == 'academic') echo 'selected'; ?>>Academic</option>
                        <option value="sports" <?php if ($event['event_type'] == 'sports') echo 'selected'; ?>>Sports</option>
                        <option value="cultural" <?php if ($event['event_type'] == 'cultural') echo 'selected'; ?>>Cultural</option>
                        <option value="social" <?php if ($event['event_type'] == 'social') echo 'selected'; ?>>Social</option>
                        <option value="volunteer" <?php if ($event['event_type'] == 'volunteer') echo 'selected'; ?>>Volunteer</option>
                        <option value="college" <?php if ($event['event_type'] == 'college') echo 'selected'; ?>>College</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="event_format">Event Format</label>
                    <select id="event_format" name="event_format">
                        <option value="in-person" <?php if ($event['event_format'] == 'in-person') echo 'selected'; ?>>In-Person</option>
                        <option value="online" <?php if ($event['event_format'] == 'online') echo 'selected'; ?>>Online</option>
                        <option value="hybrid" <?php if ($event['event_format'] == 'hybrid') echo 'selected'; ?>>Hybrid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date of Event</label>
                    <input type="datetime-local" id="start_date" name="start_date" value="<?php echo $event['start_date']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date of Event</label>
                    <input type="datetime-local" id="end_date" name="end_date" value="<?php echo $event['end_date']; ?>" required>
                </div>
            </fieldset>

            <fieldset>
                <legend>Additional Event Information</legend>

                <div class="form-group">
                    <label for="event-photo">Upload Event Photo:</label>
                    <input type="file" id="event-photo" name="event_photo" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label for="approval_letter">Upload Approval Letter</label>
                    <input type="file" id="approval_letter" name="approval_letter" accept=".pdf,.doc,.docx">
                </div>
            </fieldset>

            <div class="button-group">
                <button type="submit" class="submit-button">Update</button>
                <button type="reset" class="reset-button">Reset</button>
            </div>
        </form>
        </main>
    </main>

</body>
</html>
