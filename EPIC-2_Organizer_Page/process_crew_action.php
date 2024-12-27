<?php
include 'config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;
    $selected_crew = isset($_POST['selected_crew']) ? $_POST['selected_crew'] : [];
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($event_id) {
        // Retrieve the organizer's email and details using event_id
        $fetch_organizer_query = "
            SELECT 
                u.email AS organizer_email, e.organizer, e.event_name 
            FROM 
                events e
            JOIN 
                users u ON e.organizer_id = u.id
            WHERE 
                e.event_id = ?";
        $stmt_organizer = $conn->prepare($fetch_organizer_query);
        $stmt_organizer->bind_param("i", $event_id);
        $stmt_organizer->execute();
        $result_organizer = $stmt_organizer->get_result();
        $organizer_details = $result_organizer->fetch_assoc();

        if ($organizer_details) {
            $organizer_email = $organizer_details['organizer_email'];
            $organizer = $organizer_details['organizer'];
            $event_name = $organizer_details['event_name'];
        } else {
            die("Organizer details not found.");
        }

        // Rest of your code for updating crew status and sending email
        if (!empty($selected_crew) && in_array($action, ['accept', 'reject', 'interview'])) {
            $status = '';
            if ($action == 'accept') {
                $status = 'accepted';
            } elseif ($action == 'reject') {
                $status = 'rejected';
            } elseif ($action == 'interview') {
                $status = 'interview';
            }

            $sql_update = "UPDATE event_crews SET application_status = ? WHERE crew_id = ? AND event_id = ?";
            $stmt_update = $conn->prepare($sql_update);

            foreach ($selected_crew as $crew_id) {
                $stmt_update->bind_param("sii", $status, $crew_id, $event_id);
                $stmt_update->execute();

                // Retrieve crew details for sending email
                $sql_crew = "
                    SELECT 
                        ec.crew_id, s.email, s.first_name, s.last_name
                    FROM 
                        event_crews ec
                    JOIN 
                        students s ON ec.id = s.id
                    WHERE 
                        ec.crew_id = ? AND ec.event_id = ?";
                $stmt_crew = $conn->prepare($sql_crew);
                $stmt_crew->bind_param("ii", $crew_id, $event_id);
                $stmt_crew->execute();
                $result_crew = $stmt_crew->get_result();

                if ($row = $result_crew->fetch_assoc()) {
                    $crew_email = $row['email'];
                    $crew_name = $row['first_name'] . ' ' . $row['last_name'];

                    // Send email notification
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Update with your SMTP host
                        $mail->SMTPAuth = true;
                        $mail->Username = 'elisha03@graduate.utm.my'; // Organizer's email
                        $mail->Password = 'egmp jwea jxwn vove'; // Organizer's email password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('elisha03@graduate.utm.my', $organizer);
                        $mail->addAddress($crew_email, $crew_name);

                        $logoPath = 'logo.png'; // Update this with the path to your logo file
                        $mail->addEmbeddedImage($logoPath, 'eventureLogo');
        

                        $mail->isHTML(true);
                        $mail->Subject = "Application Status Update for Event: $event_name";
                        $mail->Body = "
                            <div style='text-align: center;'>
                                <img src='cid:eventureLogo' alt='Eventure Logo' style='max-width: 150px; margin-bottom: 20px;'>
                                <p>Dear $crew_name,</p>
                                <p>Your application status for Event \"$event_name\" has been updated to: <strong>$status</strong>.</p>
                                <p>Thank you for your participation.</p>
                                <p>Best regards,<br>$organizer</p>
                            </div>
                        ";

                        $mail->send();
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Email could not be sent to $crew_name. Mailer Error: {$mail->ErrorInfo}";
                    }
                }
            }

            // Redirect based on the action
            if ($action == 'interview') {
                header("Location: interview_details.php?event_id=$event_id&status=success");
            } else {
                header("Location: organizercrew.php?event_id=$event_id&status=success");
            }
            exit;
        }
    } else {
        die("Event ID not provided.");
    }
}
?>
