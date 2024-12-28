<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

try {
    // Server settings
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com"; // Use your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = "puventhiranganeson@gmail.com"; // Your email address
    $mail->Password = "mlpjnjckdycargxa"; // App password or actual email password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->isHTML(true);
    return $mail;
} catch (Exception $e) {
    die("Mailer initialization failed: " . $e->getMessage());
}





