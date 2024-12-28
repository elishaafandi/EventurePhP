<?php
include 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION["ID"])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Get the notification ID from the POST data
$notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

// Check if notification_id is provided
if ($notification_id === 0) {
    echo "<script>alert('Notification ID not provided.'); window.location.href='participantcalendar.php';</script>";
    exit;
}

// Prepare SQL to delete the notification
$deleteQuery = "DELETE FROM notifications WHERE notification_id = ? AND id = ?";
$deleteStmt = $conn->prepare($deleteQuery);

if (!$deleteStmt) {
    echo "<script>alert('Failed to prepare the SQL statement: " . htmlspecialchars($conn->error) . "'); window.location.href='participantcalendar.php';</script>";
    exit;
}

$deleteStmt->bind_param("ii", $notification_id, $user_id);

// Execute the query and check for success
if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
    echo "<script>alert('Reminder deleted successfully.'); window.location.href='participantcalendar.php';</script>";
} else {
    echo "<script>alert('Failed to delete the reminder. Please check the notification ID or try again.'); window.location.href='participantcalendar.php';</script>";
}

// Close the statement
$deleteStmt->close();

// Close the connection
$conn->close();
?>
