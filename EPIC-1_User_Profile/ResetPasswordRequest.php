<?php
include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $reset_token_hash = hash("sha256", $token);
        $reset_token_expires_at = date('Y-m-d H:i:s' ,time() + 60 * 30 );
        $created_at = date('Y-m-d H:i:s');

        // Insert or update the token in the password_reset_tokens table
        $stmt = $conn->prepare("REPLACE INTO password_reset_tokens (email, token, reset_token_hash, reset_token_expires_at, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $token, $reset_token_hash, $reset_token_expires_at, $created_at);
        $stmt->execute();

        $mail = require __DIR__ . "/mailer.php";

        $mail->setFrom("noreply@example.com");
        $mail->addAddress($email);
        $mail->Subject = "Password Request";
        $mail->Body = "Click the link given to reset your password";

       
        $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . '/dashboard/LatestEventure/Eventure/Resetpassword.php?token=' . urlencode($token);
        $mail->Body = 'Click <a href="' . $reset_link . '">here</a> to reset your password.';
        
        //END;

        try{
            $mail->send();
            try {
                $mail->send();
                echo '<script>alert("Password reset link has been sent to your email.");</script>';
            } catch (Exception $e) {
                echo '<script>alert("Message could not be sent. Mailer error: ' . $mail->ErrorInfo . '");</script>';
            }
            
        }catch(Exception $e){
            echo"Message are not be sent. Mailer error: {$mail->ErrorInfo}";
        }

        echo "Message sent";

        // Prepare the reset link
        // $reset_link = "https://Eventure/resetpassword.php?token=$token";

        // // Send the email
        // $subject = "Password Reset Request";
        // $message = "Click the following link to reset your password: $reset_link";
        // $headers = "From: no-reply@yourwebsite.com";

        // if (mail($email, $subject, $message, $headers)) {
        //     echo "A password reset link has been sent to your email.";
        // } else {
        //     echo "Failed to send the email.";
        // }
    } else {
        echo "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Eventure Login</title>
    <link rel="icon" href="logo.png" type="image/x-icon" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #800c12;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .registration-form {
            background-color: #800c12;
            border-radius: 10px;
            padding: 30px;
            width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .registration-form h2 {
            color: #ffffff;
            text-align: center;
            margin-bottom: 20px;
        }
        .registration-form .form-label {
            color: #ffffff;
        }
        .registration-form input {
            border-radius: 20px;
            padding: 10px;
        }
        .btn-register {
            background-color: #d20424;
            border: none;
            border-radius: 20px;
            padding: 10px;
            width: 100%;
            color: #ffffff;
            margin-top: 20px;
        }
        .btn-register:hover {
            background-color: #e43b40;
        }
        .form-header {
            background-color: #d20424;
            color: #ffffff;
            padding: 10px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin-bottom: 10px;
        }
        .text-light {
            color: #9ca2ad !important;
        }

        .logo {
            max-width: 250px;
            margin-bottom: 10px;
        }

        .legal_logo {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
        }

        .welcome {
            position: absolute;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="row">
            <!-- Logo Section -->
            <div class="col-12 d-flex justify-content-center">
                <div class="Logo">
                    <img src="Eventure logo.jpg" alt="Eventure Logo" class="logo img-fluid">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <div class="registration-form border border-3 border-white">
                    <div class="form-header">
                        <h3>Login Form</h3>
                    </div>
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                        </div>
                        <button type="submit" class="btn btn-register">Login</button>
                    </form>
                    <?php if (!empty($error_message)) : ?>
                        <p style="color: red;"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>