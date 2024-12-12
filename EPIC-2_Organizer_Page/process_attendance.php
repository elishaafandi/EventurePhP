<?php
session_start();
require 'config.php'; // Ensure this includes your database connection setup

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $attendance_action = $_POST['attendance_action'];
    $selected_crew = $_POST['selected_crew'] ?? [];

    if (!empty($selected_crew) && in_array($attendance_action, ['present', 'absent'])) {
        $crew_ids = implode(',', array_map('intval', $selected_crew)); // Sanitize input
        $sql_update_attendance = "
            UPDATE event_crews
            SET attendance_status = ?
            WHERE crew_id IN ($crew_ids) AND event_id = ?";
        $stmt_update = $conn->prepare($sql_update_attendance);
        $stmt_update->bind_param("si", $attendance_action, $event_id);
        $stmt_update->execute();

        $_SESSION['message'] = "Attendance updated successfully.";
    } else {
        $_SESSION['message'] = "Please select crew members to update.";
    }

    header("Location: organizercrew.php?event_id=$event_id");
    exit;
}
?>
