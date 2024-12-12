<?php
// Include the config file to establish the database connection
include 'config.php';

// Start the session to access session variables
session_start();

// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Ensure the user is logged in
if (!isset($_SESSION['ID'])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Fetch student details to autofill form
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

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

// Query to get event crews (assuming 'event_role' column marks crew members)
$query_crew = "SELECT ec.crew_id, ec.event_id, e.status, e.organizer, e.event_name, ec.role, ec.application_status, ec.created_at, ec.updated_at
               FROM event_crews ec
               JOIN events e ON ec.event_id = e.event_id
               WHERE ec.id = ?";
$stmt_crew = $conn->prepare($query_crew);
$stmt_crew->bind_param("i", $user_id);
$stmt_crew->execute();
$result_crew = $stmt_crew->get_result();

// Query to get event participants (non-crew members)
$query_participant = "SELECT ep.participant_id, e.organizer, e.status, ep.event_id, e.event_name, ep.registration_status, ep.created_at, ep.updated_at
                      FROM event_participants ep
                      JOIN events e ON ep.event_id = e.event_id
                      WHERE ep.id = ?";
$stmt_participant = $conn->prepare($query_participant);
$stmt_participant->bind_param("i", $user_id);
$stmt_participant->execute();
$result_participant = $stmt_participant->get_result();

// Fetch results for display
$events_crews = $result_crew->num_rows > 0 ? $result_crew->fetch_all(MYSQLI_ASSOC) : [];
$events_participant = $result_participant->num_rows > 0 ? $result_participant->fetch_all(MYSQLI_ASSOC) : [];


// Fetch all crew IDs associated with the user
$query_crew_ids = "SELECT crew_id FROM event_crews WHERE id = ?";
$stmt_crew_ids = $conn->prepare($query_crew_ids);
$stmt_crew_ids->bind_param("i", $user_id);
$stmt_crew_ids->execute();
$result_crew_ids = $stmt_crew_ids->get_result();
$crew_ids = array_column($result_crew_ids->fetch_all(MYSQLI_ASSOC), 'crew_id');

// Fetch all participant IDs associated with the user
$query_participant_ids = "SELECT participant_id FROM event_participants WHERE id = ?";
$stmt_participant_ids = $conn->prepare($query_participant_ids);
$stmt_participant_ids->bind_param("i", $user_id);
$stmt_participant_ids->execute();
$result_participant_ids = $stmt_participant_ids->get_result();
$participant_ids = array_column($result_participant_ids->fetch_all(MYSQLI_ASSOC), 'participant_id');

// Query for feedbacks made by the Organizer 
$feedback_organizer_crew = [];
if (!empty($crew_ids)) {
    $placeholders = implode(',', array_fill(0, count($crew_ids), '?'));
    $query_feedback_organizer_crew = "
        SELECT 
            fc.feedbackcrew_id, 
            e.event_name, 
            fc.feedback, 
            fc.rating, 
            fc.feedback_date 
        FROM feedbackcrew fc
        JOIN events e ON fc.event_id = e.event_id
        WHERE fc.crew_id IN ($placeholders)
    ";

    $stmt_feedback_organizer_crew = $conn->prepare($query_feedback_organizer_crew);
    $stmt_feedback_organizer_crew->bind_param(str_repeat('i', count($crew_ids)), ...$crew_ids);
    $stmt_feedback_organizer_crew->execute();
    $result_feedback_organizer_crew = $stmt_feedback_organizer_crew->get_result();
    $feedback_organizer_crew = $result_feedback_organizer_crew->fetch_all(MYSQLI_ASSOC);
}

// Query for feedbacks made by the user as a Crew
$feedback_crew = [];
if (!empty($crew_ids)) {
    $placeholders = implode(',', array_fill(0, count($crew_ids), '?'));
    $query_feedback_crew = "
        SELECT 
            fe.feedbackevent_id, 
            e.event_name, 
            fe.feedback, 
            fe.rating, 
            fe.feedback_date 
        FROM feedbackevent fe
        JOIN events e ON fe.event_id = e.event_id
        WHERE fe.crew_id IN ($placeholders)
    ";

    $stmt_feedback_crew = $conn->prepare($query_feedback_crew);
    $stmt_feedback_crew->bind_param(str_repeat('i', count($crew_ids)), ...$crew_ids);
    $stmt_feedback_crew->execute();
    $result_feedback_crew = $stmt_feedback_crew->get_result();
    $feedback_crew = $result_feedback_crew->fetch_all(MYSQLI_ASSOC);
}

// Query for feedbacks made by the user as a Participant
$feedback_participant = [];
if (!empty($participant_ids)) {
    $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));
    $query_feedback_participant = "
        SELECT 
            fe.feedbackevent_id, 
            e.event_name, 
            fe.feedback, 
            fe.rating, 
            fe.feedback_date 
        FROM feedbackevent fe
        JOIN events e ON fe.event_id = e.event_id
        WHERE fe.participant_id IN ($placeholders)
    ";

    $stmt_feedback_participant = $conn->prepare($query_feedback_participant);
    $stmt_feedback_participant->bind_param(str_repeat('i', count($participant_ids)), ...$participant_ids);
    $stmt_feedback_participant->execute();
    $result_feedback_participant = $stmt_feedback_participant->get_result();
    $feedback_participant = $result_feedback_participant->fetch_all(MYSQLI_ASSOC);
}


// Fetch the user's favorite events
$query_favorites = "SELECT e.event_id, e.event_name, e.organizer, e.description, e.start_date
                    FROM events e
                    JOIN favorites f ON e.event_id = f.event_id
                    WHERE f.id = ?";
$stmt = $conn->prepare($query_favorites);
$stmt->bind_param("i", $_SESSION['ID']); // Assuming the user is logged in.
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Check if the user has any favorite events
$favorite_events = [];
while ($row = $result->fetch_assoc()) {
    $favorite_events[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="participantdashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a></li>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a></li>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a></li>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a></li>
            </nav>
        </div>
        <div class="nav-right">
            <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
            <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
            <span class="notification-bell">🔔</span>
            <div class="profile-menu">
                <!-- Ensure the profile image is fetched and rendered properly -->
                <?php if (!empty($student['student_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($student['student_photo']); ?>" alt="Student Photo" class="profile-icon">
                <?php else: ?>
                    <img src="default-profile.png" alt="Default Profile" class="profile-icon">
                <?php endif; ?>

                <!-- Dropdown menu -->
                <div class="dropdown-menu">
                    <a href="profilepage.php">Profile</a>
                    <hr>
                    <a href="logout.php" class="sign-out">Sign Out</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="main-content">
            <div class="event-dashboard">
                <h2>Welcome <?php echo htmlspecialchars($username); ?>!</h2>
            </div>

            <div class="event-status">
                <h2>Applied Events</h2><br>
                
                <!-- Crew Events -->
                <h3>Crew</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Organizer</th>
                            <th>Application Date</th>
                            <th>Application Status</th>
                            <th>Event Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($events_crews)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($events_crews as $event): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($event['created_at'])); ?></td>
                                <td>
                                <?php 
                                    if ($event['application_status'] == 'applied') {
                                        echo '<span class="status applied">Applied</span>';
                                    } elseif ($event['application_status'] == 'interview') {
                                        echo '<span class="status interview">Interview</span>';
                                    } elseif ($event['application_status'] == 'rejected') {
                                        echo '<span class="status rejected">Rejected</span>';
                                    } elseif ($event['application_status'] == 'pending') {
                                        echo '<span class="status pending">Pending</span>';
                                    } elseif ($event['application_status'] == 'accepted') {
                                        echo '<span class="status accepted">Accepted</span>';
                                    }
                                ?>
                                </td>
                                <td>
                                <?php 
                                    if ($event['status'] == 'upcoming') {
                                        echo '<span class="status pending">Upcoming</span>';
                                    } elseif ($event['status'] == 'ongoing') {
                                        echo '<span class="status interview">Ongoing</span>';
                                    } elseif ($event['status'] == 'completed') {
                                        echo '<span class="status applied">Completed</span>';
                                    } elseif ($event['status'] == 'cancelled') {
                                        echo '<span class="status rejected">Cancelled</span>';
                                    }
                                ?>
                                </td>
                                <td>
                                <a href="viewcrewapplication.php?event_id=<?php echo $event['event_id']; ?>" class="btn view">View</a>

                                <!-- Edit button enabled only if application status is 'pending' or 'applied' and event status is 'upcoming' or 'ongoing' -->
                                <a href="editcrewform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn edit <?php echo (in_array($event['application_status'], ['pending', 'applied']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo (in_array($event['application_status'], ['pending', 'applied']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'onclick="return false;"'; ?>>
                                    Edit
                                </a>

                                <!-- Cancel button enabled only if application status is 'pending' or 'applied' and event status is 'upcoming' or 'ongoing' -->
                                <a href="deletecrewform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn delete <?php echo (in_array($event['application_status'], ['pending', 'applied']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo (in_array($event['application_status'], ['pending', 'applied']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? 
                                    'onclick="return confirm(\'Are you sure you want to delete this application?\')"' : 'onclick="return false;"'; ?>>
                                    Cancel
                                </a>

                               <!-- Rate button enabled only if event status is 'completed' -->
                                    <a href="rate_event.php?event_id=<?php echo $event['event_id']; ?>&amp;crew_id=<?php echo $event['crew_id']; ?>"
                                    class="btn rate <?php echo $event['status'] === 'completed' ? '' : 'disabled'; ?>"
                                    <?php echo $event['status'] === 'completed' ? '' : 'onclick="return false;"'; ?>>
                                    Rate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">You have not registered for any crew events yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table><br>

                <!-- Participant Events -->
                <h3>Participant</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Organizer</th>
                            <th>Application Date</th>
                            <th>Application Status</th>
                            <th>Event Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($events_participant)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($events_participant as $event): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['organizer']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($event['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if ($event['registration_status'] == 'registered') {
                                        echo '<span class="status accepted">Registered</span>';
                                    } elseif ($event['registration_status'] == 'cancelled') {
                                        echo '<span class="status rejected">Cancelled</span>';
                                    } 
                                    ?>
                                </td>
                                <td>
                                <?php 
                                    if ($event['status'] == 'upcoming') {
                                        echo '<span class="status pending">Upcoming</span>';
                                    } elseif ($event['status'] == 'ongoing') {
                                        echo '<span class="status interview">Ongoing</span>';
                                    } elseif ($event['status'] == 'completed') {
                                        echo '<span class="status applied">Completed</span>';
                                    } elseif ($event['status'] == 'cancelled') {
                                        echo '<span class="status rejected">Cancelled</span>';
                                    }
                                ?>
                                </td>
                                <td>
                                <a href="viewparticipantapplication.php?event_id=<?php echo $event['event_id']; ?>" class="btn view">View</a>
                                    <a href="editparticipantform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn edit <?php echo ($event['registration_status'] != 'registered') ? 'disabled' : ''; ?>"
                                    <?php echo ($event['registration_status'] != 'registered') ? 'onclick="return false;"' : ''; ?>>
                                    Edit
                                    </a>
                                    <a href="deleteparticipantform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn delete <?php echo ($event['registration_status'] != 'registered') ? 'disabled' : ''; ?>"
                                    <?php echo ($event['registration_status'] != 'registered') ? 'onclick="return false;"' : 'onclick="return confirm(\'Are you sure you want to delete this application?\')"'; ?>>
                                    Cancel
                                    </a>

                                    <!-- Rate button enabled only if event status is 'completed' -->
                                    <a href="rate_event.php?event_id=<?php echo $event['event_id']; ?>&amp;participant_id=<?php echo $event['participant_id']; ?>"
                                    class="btn rate <?php echo $event['status'] === 'completed' ? '' : 'disabled'; ?>"
                                    <?php echo $event['status'] === 'completed' ? '' : 'onclick="return false;"'; ?>>
                                    Rate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">You have not registered for any events yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="event-status">
                <h2>Feedbacks Made By Organizer</h2><br>

                <!-- Crew Feedback Section -->
                <h3>Events Joined As Crew</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Feedback</th>
                            <th>Rating</th>
                            <th>Feedback Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($feedback_organizer_crew)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($feedback_organizer_crew as $feedback): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($feedback['event_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['rating'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($feedback['feedback_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No feedback received from organizer yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table><br>
            </div>

            <div class="event-status">
                <h2>Feedbacks Made By You</h2><br>

                <!-- Crew Feedback Section -->
                <h3>Events Joined As Crew</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Feedback</th>
                            <th>Rating</th>
                            <th>Feedback Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($feedback_crew)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($feedback_crew as $feedback): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($feedback['event_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['rating'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($feedback['feedback_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No feedback provided as a crew yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table><br>

                <!-- Participant Feedback Section -->
                <h3>Events Joined As Participant</h3>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Feedback</th>
                            <th>Rating</th>
                            <th>Feedback Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($feedback_participant)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($feedback_participant as $feedback): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($feedback['event_name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['feedback'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($feedback['rating'])); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($feedback['feedback_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No feedback provided as a participant yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="event-status">
    <h2>Saved/Favorite Events</h2><br>

    <h3>Events You Liked</h3>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Event Name</th>
                <th>Organizer</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($favorite_events)): ?>
            <?php $no = 1; ?>
            <?php foreach ($favorite_events as $event): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($event['organizer'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($event['description'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($event['start_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No saved events.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table><br>
</div>

        </section>
    </main>

<!-- JavaScript to handle modal functionality -->
<script>
   
   /// Handle Profile Icon Click
   document.addEventListener("DOMContentLoaded", function () {
       const profileMenu = document.querySelector(".profile-menu");
       const profileIcon = document.querySelector(".profile-icon");
   
       // Toggle dropdown on profile icon click
       profileIcon.addEventListener("click", function (event) {
           event.stopPropagation(); // Prevent event from bubbling
           profileMenu.classList.toggle("open");
       });
   
       // Close dropdown when clicking outside
       document.addEventListener("click", function (event) {
           if (!profileMenu.contains(event.target)) {
               profileMenu.classList.remove("open");
           }
       });
   });
   
</script>
   
</body>
</html>
