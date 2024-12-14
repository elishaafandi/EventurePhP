<?php
session_start();
require("config.php");

// Ensure the user is logged in
if (!isset($_SESSION['ID'])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form inputs
    $club_name = mysqli_real_escape_string($conn, $_POST['club_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $founded_date = mysqli_real_escape_string($conn, $_POST['founded_date']);
    $club_type = mysqli_real_escape_string($conn, $_POST['club_type']);
    $president_id = $_SESSION['ID']; // Ensure session is started and ID is set

    // Initialize placeholders for binary data
    $club_photo = null;
    $approval_letter = null;

    // Handle `club_photo`
    if (isset($_FILES['club_photo']) && $_FILES['club_photo']['error'] === UPLOAD_ERR_OK) {
        $club_photo_tmp = $_FILES['club_photo']['tmp_name'];
        $club_photo = file_get_contents($club_photo_tmp);  // Read file content into variable
    }

    // Handle `approval_letter`
    if (isset($_FILES['approval_letter']) && $_FILES['approval_letter']['error'] === UPLOAD_ERR_OK) {
        $approval_letter_tmp = $_FILES['approval_letter']['tmp_name'];
        $approval_letter = file_get_contents($approval_letter_tmp);  // Read file content into variable
    }

    // Prepare SQL query to insert data into the `clubs` table
    $stmt = $conn->prepare(
        "INSERT INTO clubs (club_name, description, founded_date, club_type, president_id, club_photo, approval_letter) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    // Bind parameters: s = string, i = integer, b = blob
    $stmt->bind_param("ssssiss", 
        $club_name, 
        $description, 
        $founded_date, 
        $club_type, 
        $president_id, 
        $club_photo, 
        $approval_letter
    );

    // Execute the query
    if ($stmt->execute()) {
        echo "Club details with files uploaded successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Close the database connection
$conn->close();
?>
