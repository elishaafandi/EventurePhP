<?php
session_start(); // Start the session
require('config.php'); // Include your database configuration file

// Check if the form is submitted
$error_message = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize email and password from the form
    $email = filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST["password"]);

    if (!$email) {
        $error_message = "Invalid email address!";
    } else {
        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        if ($stmt) {
            $stmt->bind_param("s", $email); // Bind email parameter
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Fetch the result as an associative array
                $row = $result->fetch_assoc();

                // Check if the password is correct
                if (password_verify($password, $row["password"])) {
                    // Extract user details
                    $user_name = $row["username"];
                    $user_id = $row["id"];
                    $user_role = $row["Role"]; // Ensure 'Role' is fetched correctly

                    // Regenerate session ID and store user info in session
                    session_regenerate_id(true);
                    $_SESSION["Login"] = "YES";
                    $_SESSION["USER"] = $user_name;
                    $_SESSION["ID"] = $user_id;
                    $_SESSION["ROLE"] = $user_role;

                    // Redirect based on user role
                    if ($user_role == 1) {
                        header("Location: adminhome.php"); // Admin page
                    } else {
                        header("Location: participanthome.php"); // Normal user page
                    }
                    exit;
                } else {
                    $error_message = "Incorrect password!";
                }
            } else {
                $error_message = "No account found with that email!";
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare the SQL statement.";
        }
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
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword" style="color:white;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <a href="Resetpassword.php" style="color:#ffffff;">Reset password</a>
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
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            togglePassword.innerHTML = type === 'password' 
                ? '<i class="bi bi-eye"></i>' 
                : '<i class="bi bi-eye-slash"></i>';
        });
    </script>
</body>
</html>
