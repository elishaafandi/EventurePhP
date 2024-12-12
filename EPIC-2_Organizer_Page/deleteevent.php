<?php
include 'config.php';
session_start();

// Check if the user is logged in by verifying the session ID
if (!isset($_SESSION["ID"])) {
    header("Location: organizerhome.php");
    exit;
}

// Check if the event ID is provided via GET request
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Validate the event ID to prevent SQL injection
    $event_id = (int)$event_id;

    // Check if the event exists and the organizer is the same as the logged-in user
    $sql_check = "SELECT event_id, organizer_id FROM events WHERE event_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $event_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows == 0) {
        echo "<script>alert('Event not found.');</script>";
        exit;
    }

    $stmt_check->bind_result($db_event_id, $db_organizer_id);
    $stmt_check->fetch();
    $stmt_check->close();

    // Check if the logged-in user is the organizer
    if ($db_organizer_id != $_SESSION['ID']) {
        echo "<script>alert('You are not authorized to delete this event.');</script>";
        exit;
    }

    // Proceed with the deletion if the user is the organizer
    $sql_delete = "DELETE FROM events WHERE event_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $event_id);

    if ($stmt_delete->execute()) {
        echo "<script>alert('Event deleted successfully!'); window.location.href='organizerhome.php';</script>";
    } else {
        echo "<script>alert('Error deleting event: " . $stmt_delete->error . "');</script>";
    }

    $stmt_delete->close();
} else {
    echo "<script>alert('Event ID is missing.');</script>";
    exit;
}
?>
