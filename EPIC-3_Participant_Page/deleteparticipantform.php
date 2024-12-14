<?php
include 'config.php'; // Include database connection
session_start();

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

// Get user and crew details from session/URL
$user_id = $_SESSION['ID'];
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0; // Get crew ID from URL

// Prepare SQL to delete the crew application
$deleteQuery = "DELETE FROM event_participants WHERE id = ? AND event_id = ? ";
$deleteStmt = $conn->prepare($deleteQuery);
$deleteStmt->bind_param("ii", $user_id, $event_id);

if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
    // If deletion was successful, increment the available_slots in the events table
    $updateSlotsQuery = "UPDATE events SET available_slots = available_slots + 1 WHERE event_id = ?";
    $updateSlotsStmt = $conn->prepare($updateSlotsQuery);
    $updateSlotsStmt->bind_param("i", $event_id);

    if ($updateSlotsStmt->execute()) {
        echo "<script>alert('Participant application deleted successfully, and available slots updated.'); window.location.href='participantdashboard.php';</script>";
    } else {
        echo "<script>alert('Participant application deleted, but failed to update available slots.'); window.location.href='participantdashboard.php';</script>";
    }
} else {
    echo "<script>alert('Failed to delete participant application. Please try again.'); window.location.href='participantdashboard.php';</script>";
}
?>