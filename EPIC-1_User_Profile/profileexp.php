<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href = 'Login.php';</script>";
    exit;
}

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


// Fetch user data for display and to get the email
$stmt = $conn->prepare("SELECT username, email, Role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['ID']);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = $user['email'];
    } else {
        echo "<script>alert('User not found.'); window.location.href = 'Login.php';</script>";
        exit;
    }
} else {
    echo "Error executing query.";
    exit;
}

// Fetch events for the logged-in participant
$eventsStmt = $conn->prepare("
    SELECT event_crews.role, events.organizer, events.event_name, events.description 
    FROM event_crews 
    JOIN events ON event_crews.event_id = events.event_id 
    WHERE event_crews.id = ?
");
$eventsStmt->bind_param("i", $_SESSION['ID']);

if ($eventsStmt->execute()) {
    $eventsResult = $eventsStmt->get_result();
    if ($eventsResult->num_rows > 0) {
        $events = $eventsResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $events = [];
    }
} else {
    echo "Error executing query.";
    exit;
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
    }

    .navbar-nav .nav-link i.bi-bell-fill {
        border-radius: 20px;
        padding: 10px;
        color: #800c12;
        background-color: #fff;
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
    }

    .btn-link {
        color: #800c12;
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
    }

    .nav-right {
        display: flex;
        align-items: center;
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

    /* New table styles */
    .events-table {
        margin-top: 20px;
    }

     /* Table Colors */
     .events-table {
        color: #f5f4e6; /* Light text color for contrast */
    }

    .events-table th, .events-table td {
        border: 60px solid; 
    }

    .events-table th {     
        color: #f5f4e6; /* Light text color */
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

</style>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Events</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
</head>

<body>

    <header>
        <div class="row">
            <div class="header-left">
                <a href="participanthome.php" class="logo">EVENTURE</a>
                <nav class="nav-left">
                    <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>">Home</a>
                    <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>">Calendar</a>
                    <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>">User Profile</a>
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
                </div>
            </div>

            <!-- Events Table -->
            <div class="col-md-8">
                <h2 class="profile-title"><?php echo $user['username']; ?>'s Events</h2>
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link " href="profilepage.php">Personal Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profileactivity.php">Activity</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profileexp.php">Experience</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profileclub.php">Club Joined</a>
                    </li>
                </ul>
                <table class="table table-bordered events-table">
                    <thead>
                        <tr style="background-color:#800c12; border: 2px solid; background-color:maroon; text-align:center;">
                            <th>Role</th>
                            <th>Organizer</th>
                            <th>Event Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <tr style="border: 2px solid; color:maroon; text-align:center;">
                                    <td><?php echo htmlspecialchars($event['role']); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No events found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
