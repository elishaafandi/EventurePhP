<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href = 'Login.php';</script>";
    exit;
}
$stmt = $conn->prepare("SELECT username, email, Role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['ID']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $email = $user['email'];
} else {
    echo "<script>alert('User not found.'); window.location.href = 'Login.php';</script>";
    exit;
}


// Fetch student details for the given email
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("s",  $_SESSION['ID']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    echo "<script>alert('Student profile not found.');</script>";
    exit;
}
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fieldsToUpdate = [];
    $params = [];
    $types = ""; // Parameter types for bind_param

    // Check and prepare data for each field
    if (!empty($_POST['faculty'])) {
        $fieldsToUpdate[] = "faculty_name = ?";
        $params[] = htmlspecialchars(trim($_POST['faculty']));
        $types .= "s";
    }

    if (!empty($_POST['sem'])) {
        $fieldsToUpdate[] = "year_course = ?";
        $params[] = htmlspecialchars(trim($_POST['sem']));
        $types .= "s";
    }

    if (!empty($_POST['college'])) {
        $fieldsToUpdate[] = "college = ?";
        $params[] = htmlspecialchars(trim($_POST['college']));
        $types .= "s";
    }

    // Handle file upload (if a new photo is uploaded)
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileType = $_FILES['profile_photo']['type'];

        // Validate the file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($fileType, $allowedMimeTypes)) {
            $imageData = file_get_contents($fileTmpPath);
            $fieldsToUpdate[] = "student_photo = ?";
            $params[] = $imageData;
            $types .= "s";
        } else {
            echo "<script>alert('Invalid file type. Please upload a JPEG, PNG, or GIF image.');</script>";
            exit;
        }
    }

    // Build the SQL query dynamically
    if (!empty($fieldsToUpdate)) {
        echo "Form submitted.<br>"; 
        $query = "UPDATE students SET " . implode(", ", $fieldsToUpdate) . " WHERE id = ?";
        $params[] = $_POST['id']; // Assuming ID is passed in the form
        $types .= "i"; // Integer type for ID
        echo "Query: " . $query . "<br>";
        echo "Parameters: " . json_encode($params) . "<br>";
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo "<script>alert('Profile updated successfully!'); window.location.href = 'profilepage.php';</script>";
        } else {
            echo "<script>alert('Error updating profile: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('No changes detected.');</script>";
    }
}
$conn->close();
?>

<style>
    /* Background and Text Styles */
    body {
        background: linear-gradient(to right, #FFB29D, #ffffc5);
    }

    .navbar {
        background-color: #800c12;
    }

    .navbar-nav .btn-warning,
    .navbar-nav .btn-light {
        border-radius: 20px;
        /* Rounded corners for Participant and Organizer buttons */
    }

    .navbar-nav .nav-link i.bi-bell-fill {
        border-radius: 20px;
        /* Rounded corners for the bell icon */
        padding: 10px;
        /* Add padding for a button-like look */
        color: #800c12;
        background-color: #fff;
        /* Optional: Add background color for the icon */
    }

    .profile-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }

    .profile-title {
        font-size: 28px;
        color: #800c12;
    }

    .nav-tabs .nav-link {
        color: #800c12;
    }

    .nav-tabs .nav-link.active {
        background-color: #FFDFD4;
        color: #800c12;
    }

    .btn-warning {
        color: #fff;
        background-color: #efbf04;
        border: none;
        width: 100px;
        height: 80px;

    }

    .btn-link {
        color: #800c12;
        background-color: #800c12;
    }


    header {
        background-color: #800c12;
        color: #f5f4e6;
        padding: 25px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    header .logo {
        color: #f5f4e6;
        font-weight: bold;
        font-size: 25px;
        font-family: Arial;
        text-decoration: none;
    }

    header .logo:hover {
        color: #ff9b00;
    }

    .header-left {
        display: flex;
        align-items: center;
        flex-grow: 1;
    }

    .nav-left a {
        color: #fff;
        margin-left: 20px;
        text-decoration: none;
        justify-content: flex-start;
        /* Align to the left side */
    }

    .nav-right {
        display: flex;
        align-items: center;
    }

    .nav-left a {
        color: #f5f4e6;
        margin-left: 20px;
        text-decoration: none;
        font-family: Arial;
        transition: 0.3s ease-in-out;
    }

    .nav-left a:hover {
        color: #f3d64c;
        text-decoration: underline;
    }

    .nav-left a.active {
        color: #f3d64c;
        text-decoration: underline;
    }

    .participant-site,
    .organizer-site {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #000;
        font-weight: 400;
        text-decoration: none;
        margin-left: 10px;
    }

    .participant-site {
        background-color: #da6124;
        color: #f5f4e6;
    }

    .organizer-site {
        background-color: #f5f4e6;
        color: #000;
    }

    .participant-site:hover {
        background-color: #e08500;
    }

    .organizer-site:hover {
        background-color: #da6124;
        color: #f5f4e6;
    }

    .notification-bell {
        font-size: 18px;
        margin-left: 10px;
    }

    body {
        font-family: sans-serif;
        background-color: #eeeeee;
    }

    .file-upload {
        background-color: #ffffff;
        width: 600px;
        margin: 2 auto;
        padding: 20px;
    }

    .file-upload-btn {
        width: 100%;
        margin: 0;
        color: #fff;
        background: #800c12;
        border: none;
        padding: 10px;
        border-radius: 4px;
        transition: all .2s ease;
        outline: none;
        text-transform: uppercase;
        font-weight: 700;
    }

    .file-upload-btn:hover {
        background: red;
        color: #ffffff;
        transition: all .2s ease;
        cursor: pointer;
    }

    .file-upload-btn:active {
        border: 0;
        transition: all .2s ease;
    }

    .file-upload-content {
        display: none;
        text-align: center;
    }

    .file-upload-input {
        position: absolute;
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100px;
        outline: none;
        opacity: 0;
        cursor: pointer;
    }

    .image-upload-wrap {
        margin-top: 20px;
        border: 4px dashed #800c12;
        position: relative;
    }

    .image-dropping,
    .image-upload-wrap:hover {
        background-color: #800c12;
        border: 4px dashed #ffffff;
    }

    .image-title-wrap {
        padding: 0 15px 15px 15px;
        color: #222;
    }

    .drag-text {
        text-align: center;
    }

    .drag-text h3 {
        font-weight: 100 bold;
        text-transform: uppercase;
        color: black;
        padding: 60px 0;
        height: 40px;
    }

    .file-upload-image {
        max-height: 200px;
        max-width: 200px;
        margin: auto;
        padding: 20px;
    }

    .profile-pic {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        /* Ensures the image fits within the circle without distortion */
        border: 2px solid #ccc;
        /* Optional: Add a border around the circle */
    }

    .remove-image {
        width: 200px;
        margin: 0;
        color: #fff;
        background: #ffffff;
        border: none;
        padding: 10px;
        border-radius: 4px;
        border-bottom: 4px solid #b02818;
        transition: all .2s ease;
        outline: none;
        text-transform: uppercase;
        font-weight: 700;
    }

    .remove-image:hover {
        background: #800c12;
        color: #ffffff;
        transition: all .2s ease;
        cursor: pointer;
    }

    .remove-image:active {
        border: 0;
        transition: all .2s ease;
    }

    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 5px;
        width: 50%;
        position: relative;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        color: maroon;
        /* Red color */
        display: flex;
        align-items: center;
    }

    .close i {
        margin-left: 5px;
        font-size: 24px;
    }

    /* Style for the modal */
#profileModal {
    width: 1000px;
    max-width: 90%; /* Ensure responsiveness */
    position: fixed; 
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-radius: 8px;
    z-index: 1000;
}

/* Optional backdrop */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

</style>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
</head>

<body>

    <header>
        <div class="row">
            <div class="header-left">
                <a href="participanthome.php" class="logo">EVENTURE</a>


                <nav class="nav-left">
                    <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a></li>
                    <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a></li>
                    <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a></li>
                    <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a></li>


                </nav>
            </div>
            <div class="header-right">
                <nav class="nav-right">
                    <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                    <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a>
                    <span class="notification-bell">🔔</span>
                </nav>
            </div>
        </div>
    </header>

    <!-- Profile Section -->
    <!-- Profile Section -->
    <div class="container mt-5">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-md-4 text-center">
                <div class="profile-card p-4">
                    <?php
                    if (!empty($student['student_photo'])) {
                        $base64Image = 'data:image/jpeg;base64,' . base64_encode($student['student_photo']);
                        echo '<img src="' . $base64Image . '" alt="Student Photo" class="profile-pic" >';
                    } else {
                        echo '<img src="placeholder.jpg" alt="Placeholder" width="150" height="150">';
                    }
                    ?>
                    <h4><?php echo $user['username']; ?></h4>
                    <p><i class="bi bi-envelope-fill"></i> <?php echo $user['email']; ?></p>
                    <p><i class="bi bi-person-fill"></i> <?php echo ($user['Role'] == 1) ? ' Admin' : ' Student'; ?></p>
                    <button class="btn btn-danger" id="editProfileBtn">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </button>
                </div>
            </div>

            <!-- Profile Form Display (Visible by Default) -->
            <div class="col-md-8">
                <h2 class="profile-title"><?php echo $user['username']; ?>'s Profile</h2>
                <div id="profileDisplay">
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="profilepage.php">Personal Details</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profileactivity.php">Activity</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profileexp.php">Experience</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profileclub.php">Club Joined</a>
                        </li>
                    </ul>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="firstname">First Name:</label>
                            <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo htmlspecialchars($student['first_name']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="lastname">Last Name:</label>
                            <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo htmlspecialchars($student['last_name']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="idNumber">Identification Number:</label>
                            <input type="text" name="idNumber" id="idNumber" class="form-control" value="<?php echo htmlspecialchars($student['ic']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="gender">Gender:</label>
                            <select name="gender" id="gender" class="form-control" disabled>
                                <option value="Male" <?php echo $student['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $student['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="matricNumber">Matric Number:</label>
                            <input type="text" name="matricNumber" id="matricNumber" class="form-control" value="<?php echo htmlspecialchars($student['matric_no']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="faculty">Faculty:</label>
                            <input type="text" name="faculty" id="faculty" class="form-control" value="<?php echo htmlspecialchars($student['faculty_name']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="sem">Year/Course:</label>
                            <input type="text" name="sem" id="sem" class="form-control" value="<?php echo htmlspecialchars($student['year_course']); ?>" required disabled>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="college">College:</label>
                            <input type="text" name="college" id="college" class="form-control" value="<?php echo htmlspecialchars($student['college']); ?>" required disabled>
                        </div>
                    </div>
                </div>

                <!-- Modal Form (Hidden by Default) -->
                <div id="profileModal" class="modal" style="overflow-y: auto; max-height: 100%;">
                    <div class="modal-content">
                        <span class="close text-danger">
                            <i class="bi bi-x-circle-fill"></i>
                        </span>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="form-row">
                                <!-- Profile Photo Upload Section -->
                                <div class="file-upload">
                                    <button class="file-upload-btn" type="button" onclick="$('.file-upload-input').trigger('click')">Add Profile Photo</button>
                                    <div class="image-upload-wrap">
                                        <input class="file-upload-input" type="file" onchange="readURL(this);" accept="image/*" name="profile_photo" />
                                        <div class="drag-text">
                                            <h3>Drag and drop your picture</h3>
                                        </div>
                                    </div>
                                    <div class="file-upload-content">
                                        <img class="file-upload-image" src="#" alt="your image" />
                                        <div class="image-title-wrap">
                                            <button type="button" onclick="removeUpload()" class="remove-image">Remove <span class="image-title">Uploaded Image</span></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Editable Form Fields -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="firstname">First Name:</label>
                                    <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo htmlspecialchars($student['first_name']); ?>" required disabled>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="lastname">Last Name:</label>
                                    <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo htmlspecialchars($student['last_name']); ?>" required disabled>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="idNumber">Identification Number:</label>
                                    <input type="text" name="idNumber" id="idNumber" class="form-control" value="<?php echo htmlspecialchars($student['ic']); ?>" required disabled>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="gender">Gender:</label>
                                    <select name="gender" id="gender" class="form-control" disabled>
                                        <option value="Male" <?php echo $student['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $student['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="matricNumber">Matric Number:</label>
                                    <input type="text" name="matricNumber" id="matricNumber" class="form-control" value="<?php echo htmlspecialchars($student['matric_no']); ?>" required disabled>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="faculty">Faculty:</label>
                                    <input type="text" name="faculty" id="faculty" class="form-control" value="<?php echo htmlspecialchars($student['faculty_name']); ?>" required>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="sem">Year/Course:</label>
                                    <input type="text" name="sem" id="sem" class="form-control" value="<?php echo htmlspecialchars($student['year_course']); ?>" required>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="college">College:</label>
                                    <input type="text" name="college" id="college" class="form-control" value="<?php echo htmlspecialchars($student['college']); ?>" required>
                                </div>

                                <!-- Add additional fields here for editing -->
                                <button type="submit" class="btn btn-warning">Save</button>
                                <input type="hidden" name="id" value="<?php echo $_SESSION['ID']; ?>">
                                <button type="reset" class="btn btn-link" style=" background-color: #800c12; color:#000;  width:100px; height:80px; ">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <script>
            // Show the modal when clicking the Edit Profile button
            document.getElementById("editProfileBtn").addEventListener("click", function() {
                document.getElementById("profileDisplay").style.display = "none";
                document.getElementById("profileModal").style.display = "block";
            });

            // Close the modal when clicking on the close button
            document.querySelector(".close").addEventListener("click", function() {
                document.getElementById("profileModal").style.display = "none";
                document.getElementById("profileDisplay").style.display = "block";
            });
            // Close modal if clicked outside of modal content
            window.addEventListener("click", function(event) {
                const modal = document.getElementById("profileModal");
                if (event.target === modal) {
                    modal.style.display = "none";
                    document.getElementById("profileDisplay").style.display = "block";
                }
            });

            // File upload functionality
            // Preview image after upload
            function readURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.file-upload-image').attr('src', e.target.result);
                        $('.file-upload-content').show();
                        $('.image-upload-wrap').hide();
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function removeUpload() {
                $('.file-upload-input').replaceWith($('.file-upload-input').clone());
                $('.file-upload-content').hide();
                $('.image-upload-wrap').show();
            }


            // Dragover effect
            $('.image-upload-wrap').bind('dragover', function() {
                $('.image-upload-wrap').addClass('image-dropping');
            });
            $('.image-upload-wrap').bind('dragleave', function() {
                $('.image-upload-wrap').removeClass('image-dropping');
            });
        </script>