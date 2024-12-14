<?php

include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];  // Correct name for new password field
    $confirm_password = $_POST['secondpassword'];

    // Check if the new passwords match
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New password and confirmation password do not match.');</script>";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Check if the email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            echo "<script>alert('Email not found in the database.');</script>";
        } else {
            // Update the password in the database
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            if ($stmt->execute()) {
                echo "<script>alert('Your password has been successfully reset.');
                window.location.href = 'Login.php';
                </script>";
                

                // Optional: Delete the token after use
                $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
            } else {
                echo "<script>alert('Failed to reset the password.');</script>";
                error_log("MySQL Error: " . $stmt->error); // Log error
            }
        }
    }
}
?>



<!-- Password Reset Form
<form action="" method="post">
    <label for="email">Email:</label>
    <input type="email" name="email" required>
    <label for="new_password">New Password:</label>
    <input type="password" name="new_password" required>
    <button type="submit">Reset Password</button>
</form> -->


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Eventure Login</title>
    <link rel="icon" href="logo.png" type="image/x-icon" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .registration-form .form-control:focus {
            box-shadow: none;
            border-color: #3d7ff5;
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
        <!-- Grid Structure for Logo and Form -->
        <div class="row">
            <!-- Logo Section -->
            <div class="col-12 d-flex justify-content-center">
                <div class="Logo">
                    <img src="Eventure logo.jpg" alt="Eventure Logo" class="logo img-fluid">
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Logo Section -->
            <div class="col-12 d-flex justify-content-center">
                <div class="p-2 welcome">
                    <!-- <h1 class="text-white">Welcome to Eventure</h1> -->
                </div>
            </div>
        </div>

        <!-- Registration Form Section -->
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <div class="registration-form border border-3 border-white">
                    <div class="form-header">
                        <h3>Reset Password Form</h3>
                    </div>
                    <!-- Display error message if login fails -->
                    <!-- <?php if (!empty($error_message)): ?>
                        <p class="error"><?= htmlspecialchars($error_message) ?></p>
                    <?php endif; ?> -->

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="firstpassword" name="new_password" placeholder="Enter new password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="secondpassword" name="secondpassword" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" class="btn btn-register">Reset Password</button>
                    </form>


                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error_message)) : ?>
                        <p style="color: red;"><?php echo $error_message; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>