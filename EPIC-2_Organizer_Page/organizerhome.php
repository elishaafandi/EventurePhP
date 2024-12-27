<?php
// Ensure that session is started and club_id is stored in session
session_start();

include 'config.php';

if (!isset($_SESSION["ID"])) {
    echo "You must be logged in to access this page.";
    exit;
}


// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Fetch the username of the logged-in user
$sql_username = "SELECT username FROM users WHERE id = ?";
$stmt_username = mysqli_prepare($conn, $sql_username);
mysqli_stmt_bind_param($stmt_username, "i", $user_id);
mysqli_stmt_execute($stmt_username);
$result_username = mysqli_stmt_get_result($stmt_username);

// Check if username was found
if ($result_username && mysqli_num_rows($result_username) > 0) {
    $row = mysqli_fetch_assoc($result_username);
    $username = $row['username']; // Retrieve the username
} else {
    $username = "User"; // Default in case of an error
}

// Fetch the user's clubs
$sql_clubs = "SELECT club_id, club_name FROM clubs WHERE president_id = $user_id";
$result_clubs = mysqli_query($conn, $sql_clubs);

$clubs = [];
if ($result_clubs) {
    while ($row = mysqli_fetch_assoc($result_clubs)) {
        $clubs[] = $row;
    }
}

// Get selected club from the dropdown and store in session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id']; // Store selected club ID in session
    header("Location: organizerhome.php"); // Redirect to prevent re-submission
    exit;
}

// Retrieve events for the selected club (if a club is selected)
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

if ($selected_club_id) {
    $sql_events = "SELECT event_id, club_id, event_name, start_date, end_date, event_status, total_slots - available_slots AS participants 
                   FROM events
                   WHERE club_id = ? 
                   ORDER BY start_date";
    $stmt = mysqli_prepare($conn, $sql_events);
    mysqli_stmt_bind_param($stmt, "i", $selected_club_id);
    mysqli_stmt_execute($stmt);
    $result_events = mysqli_stmt_get_result($stmt);

    $events = [];
    if ($result_events) {
        while ($row = mysqli_fetch_assoc($result_events)) {
            $events[] = $row;
        }
    }
} else {
    $events = []; // No events if no club is selected
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventure Organizer Site</title>
    <link rel="stylesheet" href="organizerhome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <a href="profilepage.php" class="profile-icon">
                <i class="fas fa-user-circle"></i> 
            </a>
            </div>
        </div>

        <div class="header-center">
        <div class="welcome-section">
            <h2>Welcome Back, <?php echo htmlspecialchars($username); ?>!</h2>
            <form method="get" action="">
                <select name="club_id" onchange="this.form.submit()" class="club-role-dropdown">
                    <option value="" disabled selected>Select Club</option>
                    <?php foreach ($clubs as $club): ?>
                        <option value="<?php echo $club['club_id']; ?>" <?php echo $selected_club_id == $club['club_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($club['club_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="addclub.php" class="add-club-button">Add Club</a>
            </form>
        </div>
    </div>
    </header>


    <main>
    <aside class="sidebar">
    <div class="logo-container">
        <a href="organizerhome.php" class="logo">EVENTURE</a>
    </div>
    <ul>
        <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
        <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
        <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
        <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Crew Listing</a></li>
        <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
        <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
        <li><a href="organizerclubmembership.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerclub membership.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Club Membership</a></li>
    </ul>
    <ul style="margin-top: 60px;">
        <li><a href="organizersettings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizersettings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i>Settings</a></li>
        <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

    <section class="main-content">

        <div class="create-event-section">
            <h2>Create New Program & Event</h2>
            <p>Elevate your membership status and indulge in rewards of luxury and exclusivity.</p>
            <a href="organizercreateeventcrew.php"><div class="pill">For Crew</div></a>
            <a href="organizercreateeventpart.php"><div class="pill">For Participant</div></a>
        </div>

        <div class="event-status">
            <h3>Event Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Participants</th>
                        <th>Event Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($event['start_date'])); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($event['end_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['participants']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_status']); ?></td>
                                <td><a href="organizerviewevent.php?id=<?php echo $event['event_id']; ?>" class="view-button">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No events available for the selected club.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    </main>
</body>
</html>
