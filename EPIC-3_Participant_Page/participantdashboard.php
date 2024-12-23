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
$query_crew = "SELECT ec.crew_id, ec.event_id, e.status, e.organizer, e.event_name, ec.role, ec.application_status, ec.created_at, ec.updated_at, c.club_photo
               FROM event_crews ec
               JOIN events e ON ec.event_id = e.event_id
               JOIN clubs c ON e.club_id = c.club_id
               WHERE ec.id = ?";
$stmt_crew = $conn->prepare($query_crew);
$stmt_crew->bind_param("i", $user_id);
$stmt_crew->execute();
$result_crew = $stmt_crew->get_result();

// Query to get event participants (non-crew members)
$query_participant = "SELECT ep.participant_id, e.organizer, e.status, ep.event_id, e.event_name, ep.registration_status, ep.created_at, ep.updated_at, c.club_photo
                      FROM event_participants ep
                      JOIN events e ON ep.event_id = e.event_id
                      JOIN clubs c ON e.club_id = c.club_id
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

if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    // Fetch event details based on $event_id
    $query_event = "SELECT * FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($query_event);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    // Display event details
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

<!-- Welcome Banner -->
<div class="welcome-banner">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
</div>

<section class="main-content">
    <div class="primary-content">

        <!-- CREW EVENTS APPLICATION STATUS -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h2>Crew Events</h2>
            </div>
            <div class="event-grid-crew">
                <?php if (!empty($events_crews)): ?>
                    <?php foreach ($events_crews as $event): ?>
                        <div class="event-card-crew">
                        <div class="event-card-icon">
                            <?php if (!empty($event['club_photo'])): ?>
                                <?php
                                    // Convert the BLOB data to a base64-encoded string
                                    $clubPhotoBase64 = base64_encode($event['club_photo']);
                                    $clubPhotoSrc = 'data:image/jpeg;base64,' . $clubPhotoBase64; // Adjust MIME type if needed
                                ?>
                                <img src="<?php echo $clubPhotoSrc; ?>" alt="Club Photo" class="club-photo">
                            <?php else: ?>
                                <img src="default_club_photo.jpg" alt="Default Club Photo" class="club-photo">
                            <?php endif; ?>
                        </div>
                        
                            <div class="event-card-details">
                                <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p><?php echo htmlspecialchars($event['organizer']); ?></p>
                                <p>Applied On: <?php echo date('d-m-y', strtotime($event['created_at'])); ?></p>
                                <p class="event-status">Event Status: <span class="status-<?php echo strtolower($event['status']); ?>"><?php echo htmlspecialchars($event['status']); ?></span></p>
                            </div>
                            <span class="event-status-badge status-<?php echo strtolower($event['application_status']); ?>">
                                <?php echo htmlspecialchars($event['application_status']); ?>
                            </span>
                            
                            <!-- View Button -->
                            <div class="event-card-actions">
                                <a href="viewcrewapplication.php?event_id=<?php echo $event['event_id']; ?>" class="btn view">View</a>

                                <!-- Edit button -->
                                <a href="editcrewform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn edit <?php echo (in_array($event['application_status'], ['pending']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo (in_array($event['application_status'], ['pending']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'onclick="return false;"'; ?>>
                                    Edit
                                </a>

                                <!-- Cancel button -->
                                <a href="deletecrewform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn delete <?php echo (in_array($event['application_status'], ['pending']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo (in_array($event['application_status'], ['pending']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? 
                                    'onclick="return confirm(\'Are you sure you want to delete this application?\')"' : 'onclick="return false;"'; ?>>
                                    Cancel
                                </a>

                                <!-- Rate button -->
                                <a href="rate_event.php?event_id=<?php echo $event['event_id']; ?>&amp;crew_id=<?php echo $event['crew_id']; ?>"
                                    class="btn rate <?php echo $event['status'] === 'completed' ? '' : 'disabled'; ?>"
                                    <?php echo $event['status'] === 'completed' ? '' : 'onclick="return false;"'; ?>>
                                    Rate
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No crew event applications.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- PARTICIPANT EVENTS APPLICATION STATUS -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h2>Participant Events</h2>
            </div>
            
            <div class="event-grid-participant">
                <?php if (!empty($events_participant)): ?>
                    <?php foreach ($events_participant as $event): ?>
                        <div class="event-card-participant">
                        <div class="event-card-icon">
                            <?php if (!empty($event['club_photo'])): ?>
                                <?php
                                    // Convert the BLOB data to a base64-encoded string
                                    $clubPhotoBase64 = base64_encode($event['club_photo']);
                                    $clubPhotoSrc = 'data:image/jpeg;base64,' . $clubPhotoBase64; // Adjust MIME type if needed
                                ?>
                                <img src="<?php echo $clubPhotoSrc; ?>" alt="Club Photo" class="club-photo">
                            <?php else: ?>
                                <img src="default_club_photo.jpg" alt="Default Club Photo" class="club-photo">
                            <?php endif; ?>
                        </div>

                            <div class="event-card-details">
                                <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p><?php echo htmlspecialchars($event['organizer']); ?></p>
                                <p>Registered on: <?php echo date('Y-m-d', strtotime($event['created_at'])); ?></p>
                                <p class="event-status">Event Status: <span class="status-<?php echo strtolower($event['status']); ?>"><?php echo htmlspecialchars($event['status']); ?></span></p>
                            </div>
                            <span class="event-status-badge status-<?php echo strtolower($event['registration_status']); ?>">
                                <?php echo htmlspecialchars($event['registration_status']); ?>
                            </span>
                            
                            <!-- View Button -->
                            <div class="event-card-actions">
                                <a href="viewparticipantapplication.php?event_id=<?php echo $event['event_id']; ?>" class="btn view">View</a>

                                <!-- Edit button -->
                                <a href="editparticipantform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn edit <?php echo ($event['registration_status'] === 'registered' && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo ($event['registration_status'] === 'registered' && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'onclick="return false;"'; ?>>
                                    Edit
                                </a>

                                <!-- Cancel button -->
                                <a href="deleteparticipantform.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn delete <?php echo (in_array($event['registration_status'], ['registered']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? '' : 'disabled'; ?>"
                                    <?php echo (in_array($event['registration_status'], ['registered']) && in_array($event['status'], ['upcoming', 'ongoing'])) ? 
                                    'onclick="return confirm(\'Are you sure you want to cancel your registration?\')"' : 'onclick="return false;"'; ?>>
                                    Cancel
                                </a>

                                <!-- Rate button -->
                                <a href="rate_event.php?event_id=<?php echo $event['event_id']; ?>&amp;participant_id=<?php echo $event['participant_id']; ?>"
                                    class="btn rate <?php echo $event['status'] === 'completed' ? '' : 'disabled'; ?>"
                                    <?php echo $event['status'] === 'completed' ? '' : 'onclick="return false;"'; ?>>
                                    Rate
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No participant event registrations.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FEEDBACKS MADE BY YOU & ORGANIZER -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h2>Feedbacks Made By You & Organizer</h2>
            </div>
        
            <div class="event-grid-feedback">
                <?php 
                // Combine and sort feedbacks
                    $all_feedbacks = array_merge (
                    $feedback_organizer_crew, 
                    $feedback_crew, 
                    $feedback_participant
                );
            
                // Sort by date, most recent first
                    usort($all_feedbacks, function($a, $b) {
                    return strtotime($b['feedback_date']) - strtotime($a['feedback_date']);
                });
            
                // Limit to 3 most recent feedbacks
                    $recent_feedbacks = array_slice($all_feedbacks, 0, 3);
                ?>
            
            <?php foreach ($recent_feedbacks as $feedback): ?>
                <div class="event-card-feedback">
                    <div class="event-card-icon">
                        <i class="fas fa-comment"></i>
                    </div>

                    <div class="event-card-details">
                        <h3><?php echo htmlspecialchars($feedback['event_name']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars(substr($feedback['feedback'], 0, 50) . '...')); ?></p>
                        <p>Date: <?php echo date('Y-m-d', strtotime($feedback['feedback_date'])); ?></p>
                    </div>

                    <span>
                        <?php
                        $rating = (int)$feedback['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>'; // Solid star for filled rating
                            } else {
                                echo '<i class="far fa-star"></i>'; // Empty star for unfilled rating
                            }
                        }
                        ?>
                    </span>
                </div>
            <?php endforeach;?>
        </div>
    </div>
</div>


    <!-- SIDEBAR -->
    <div class="sidebar">

        <!-- QUICK STATS -->
        <div class="dashboard-card quick-stats">
            <div class="dashboard-card-header">
                <h2>Quick Stats</h2>
            </div>
                <div class="quick-stats-grid">
                <div class="quick-stat-crew">
                    <div class="quick-stat-number"><?php echo count($events_crews); ?></div>
                    <div class="quick-stat-label">Crew Events</div>
                </div>
                <div class="quick-stat-participant">
                    <div class="quick-stat-number"><?php echo count($events_participant); ?></div>
                    <div class="quick-stat-label">Participant Events</div>
                </div>
                <div class="quick-stat-fave">
                    <div class="quick-stat-number"><?php echo count($favorite_events); ?></div>
                    <div class="quick-stat-label">Saved Events</div>
                </div>
                <div class="quick-stat-feedback">
                    <div class="quick-stat-number"><?php echo count($feedback_crew) + count($feedback_participant); ?></div>
                    <div class="quick-stat-label">Feedbacks</div>
                </div>
            </div>
        </div>

        <!-- SAVED EVENTS -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h2>Saved Events</h2>          
            </div>
                
            <div class="event-grid">
                <?php if (!empty($favorite_events)): ?>
                    <?php foreach ($favorite_events as $event): ?>
                        <a href="participanthome.php?highlight_event_id=<?php echo $event['event_id']; ?>" class="event-link">
                        <div class="event-card">
                            <div class="event-card-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="event-card-details">
                                <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p><?php echo htmlspecialchars($event['organizer']); ?></p>
                                <p><?php echo date('Y-m-d', strtotime($event['start_date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No saved events.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- PURCHASEs MERCHANDISE -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h2>Your Purchases</h2>
            </div>
        
            <div class="event-grid">
                <?php foreach ($recent_feedbacks as $feedback): ?>
                    <div class="event-card">
                        <div class="event-card-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <div class="event-card-details">
                            <h3><?php echo htmlspecialchars($feedback['event_name']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars(substr($feedback['feedback'], 0, 50) . '...')); ?></p>
                            <p>Date: <?php echo date('Y-m-d', strtotime($feedback['feedback_date'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
         </div>
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
