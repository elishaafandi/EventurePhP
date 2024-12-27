<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

include 'config.php';
session_start();


// Get the event ID from the URL
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

if (!$event_id) {
    die("Event ID not specified.");
}

// Fetch event details from the database
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Event not found.");
}

$event_name = $event['event_name']; 


// Handle approve/reject action if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $new_status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';

    // Update event status in the database
    $update_sql = "UPDATE events SET event_status = ?, updated_at = NOW() WHERE event_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $event_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<p>Event has been " . htmlspecialchars($new_status) . ".</p>";

        // Fetch the organizer's email using the organizer_id
        $fetch_email_sql = "SELECT users.email FROM users JOIN events ON users.id = events.organizer_id WHERE events.event_id = ?";
        $email_stmt = $conn->prepare($fetch_email_sql);
        $email_stmt->bind_param("i", $event_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $organizer = $email_result->fetch_assoc();

        if ($organizer && $organizer['email']) {
            $organizer_email = $organizer['email'];

            // Send notification email to the organizer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'elisha03@graduate.utm.my'; // Replace with your email
                $mail->Password = 'egmp jwea jxwn vove'; // Replace with your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('batriesya_irdina@yahoo.com', 'Eventure Team');
                $mail->addAddress($organizer_email);

                 // Attach the logo image
                $logoPath = 'logo.png'; // Update this with the path to your logo file
                $mail->addEmbeddedImage($logoPath, 'eventureLogo');
        
                // Email content
                $mail->isHTML(true);
                $mail->Subject = 'Event Status Update: ' . htmlspecialchars($new_status) . ' for ' . htmlspecialchars($event_name);
                $mail->Body = '
                 <div style="text-align: center;">
                    <img src="cid:eventureLogo" alt="Eventure Logo" style="max-width: 150px; margin-bottom: 20px;">
                    <p>Dear Organizer,</p>
                    <p>Your event has been <strong>' . htmlspecialchars($new_status) . '</strong>.</p>
                    <p>Event Name: ' . htmlspecialchars($event_name) . '</p>
                    <p>Event ID: ' . htmlspecialchars($event_id) . '</p>
                    <p>Thank you for using Eventure!</p>
                 </div>
                ';

            $mail->send();
                echo "<p>Notification email sent to the organizer.</p>";
            } catch (Exception $e) {
                echo "<p>Email could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
            }
        } else {
            echo "<p>Organizer email not found.</p>";
        }

        $email_stmt->close();
    } else {
        echo "<p>Failed to update event status.</p>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f2f2f2;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        .buttons button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .approve {
            background-color: #28a745;
            color: #fff;
        }
        .reject {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Event Details</h1>
        <table>
            <?php foreach ($event as $key => $value): ?>
                <tr>
                    <th><?= ucfirst(str_replace('_', ' ', $key)) ?>:</th>
                    <td>
                        <?php
                        if ($key === 'event_photo' && $value) {
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($value) . '" alt="Event Photo">';
                        } elseif ($key === 'approval_letter' && $value) {
                            echo '<a href="view_file.php?event_id=' . $event_id . '&type=approval_letter">View Approval Letter</a>';
                        } else {
                            echo nl2br(htmlspecialchars($value));
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($event['event_status'] === 'pending'): ?>
            <div class="buttons">
                <form method="POST" style="display:inline;">
                    <button type="submit" name="action" value="approve" class="approve">Approve</button>
                </form>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="action" value="reject" class="reject">Reject</button>
                </form>
            </div>
        <?php else: ?>
            <p>This event has already been <?= htmlspecialchars($event['event_status']) ?>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
