<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crew_id = $_POST['crew_id'];
    $event_id = $_POST['event_id'];
    $status = $_POST['status'];

    $sql = "UPDATE event_crews SET application_status = ?, updated_at = NOW() WHERE crew_id = ? AND event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $crew_id, $event_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Status updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update status."]);
    }

    $stmt->close();
}
$conn->close();
?>
