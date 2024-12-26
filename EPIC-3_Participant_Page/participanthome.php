<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Include the config file to establish the database connection
session_start();

include 'config.php';

if (!isset($_SESSION["ID"])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Build the base query
$sql = "
    SELECT e.*, c.club_photo 
    FROM events e
    JOIN clubs c ON e.club_id = c.club_id
    WHERE e.event_status = 'approved' AND e.application = 'open'
";

// Fetch student details to autofill form
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$clubQuery = "SELECT * FROM clubs WHERE club_id = ?";
$clubStmt = $conn->prepare($clubQuery);
$clubStmt->bind_param("i", $club_id);  // Assuming $club_id is the identifier of the club
$clubStmt->execute();
$clubResult = $clubStmt->get_result();
$club = $clubResult->fetch_assoc();

// Initialize variables for organizer and event_type from user input
$organizer = isset($_GET['organizer']) ? trim($_GET['organizer']) : '';
$event_type = isset($_GET['event_type']) ? trim($_GET['event_type']) : '0'; // Set event_type to '0' by default

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : 'anytime'; // Default is anytime
$location = isset($_GET['location']) ? $_GET['location'] : ''; // Empty if no location selected
$event_role = isset($_GET['event_role']) ? $_GET['event_role'] : ''; // Corrected to event_role
$event_format = isset($_GET['event_format']) ? $_GET['event_format'] : ''; // Corrected to event_format
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Latest';
// Check if highlight_event_id is present in the URL
$highlight_event_id = isset($_GET['highlight_event_id']) ? intval($_GET['highlight_event_id']) : null;

// Check if filters are applied to modify the query
$isFiltered = !empty($organizer) || ($event_type !== '0') || ($start_date !== 'anytime') || !empty($location) || !empty($event_role) || !empty($event_format) || ($sort_by !== 'Latest');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['club_id'], $_POST['action'])) {
    $event_id = intval($_POST['event_id']);
    $club_id = intval($_POST['club_id']);
    $action = $_POST['action'];

    if ($action === 'add-notification') {
        // Add notification
        $event_date = $_POST['event_date'];
        $insertNotificationQuery = "INSERT IGNORE INTO notifications (id, event_id, club_id, event_date) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertNotificationQuery);
        $stmt->bind_param("iiis", $user_id, $event_id, $club_id, $event_date);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            // Send Email Notification
            $mail = new PHPMailer(true);
        
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'elisha03@graduate.utm.my'; // Your email
                $mail->Password = 'egmp jwea jxwn vove'; // Your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
        
                // Check recipient email in session
                if (isset($_SESSION['EMAIL']) && !empty($_SESSION['EMAIL'])) {
                    $recipient_email = $_SESSION['EMAIL'];
                } else {
                    echo "<script>alert('Error: Recipient email not found in session.');</script>";
                    exit;
                }

                $eventQuery = "SELECT event_name FROM events WHERE event_id = ?";
                $eventStmt = $conn->prepare($eventQuery);
                $eventStmt->bind_param("i", $event_id);
                $eventStmt->execute();
                $eventResult = $eventStmt->get_result();
                if ($eventResult->num_rows > 0) {
                    $eventRow = $eventResult->fetch_assoc();
                    $event_name = $eventRow['event_name']; // Get the event name
                } else {
                    echo "<script>alert('Error: Event not found.');</script>";
                    exit;
                }
        
                // Recipient
                $mail->setFrom('elisha03@graduate.utm.my', 'Eventure Team');
                $mail->addAddress($recipient_email);
        
                $logoPath = 'logo.png'; // Update this with the path to your logo file
                $mail->addEmbeddedImage($logoPath, 'eventureLogo');
        
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'New Event Notification';
                $mail->Body = '
                <div style="text-align: center; font-family: Arial, sans-serif;">
                    <img src="cid:eventureLogo" alt="Eventure Logo" style="max-width: 150px; margin-bottom: 20px;">
                    <h1 style="color: #800c12;">Event Reminder Notification</h1>
                    <p>Dear Participant,</p>
                    <p>You have a new notification for <strong>' . htmlspecialchars($event_name) . ' Event </strong>, scheduled to take place on <strong>' . htmlspecialchars($event_date) . '</strong>.</p>
                    <p>Please note that you will receive a reminder notification at your calendar 5 days prior to the event date, unless you choose to modify the reminder settings.</p>
                    <p>To manage or edit your reminder settings, please click the link below:</p>
                    <p>
                        <a href="http://localhost/eventure/participantcalendar.php?event_id=' . urlencode($event_id) . '" 
                        style="display: inline-block; padding: 10px 20px; background-color: #800c12; color: white; text-decoration: none; border-radius: 5px;">
                            Edit Reminder Settings
                        </a>
                    </p>
                    <p>We hope this reminder helps you prepare for the event.</p>
                    <br>
                    <p>Best regards,</p>
                    <p><strong>Eventure Team</strong></p>
                </div>';

                $mail->send();
        
                // Show success alert using JavaScript
                echo "<script>alert('Notification was sent to your email.');</script>";
            } catch (Exception $e) {
                // Show error alert using JavaScript
                echo "<script>alert('Error sending email: {$mail->ErrorInfo}');</script>";
            }
        } else {
            // Show failure alert using JavaScript
            echo "<script>alert('Failed to add notification. No rows affected.');</script>";
        }
              

    } elseif ($action === 'remove-notification') {
        // Remove notification
        $deleteNotificationQuery = "DELETE FROM notifications WHERE id = ? AND event_id = ?";
        $stmt = $conn->prepare($deleteNotificationQuery);
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
    } elseif ($action === 'add-favorite') {
        // Add to favorites
        $insertFavoriteQuery = "INSERT IGNORE INTO favorites (id, event_id, club_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertFavoriteQuery);
        $stmt->bind_param("iii", $user_id, $event_id, $club_id);
        $stmt->execute();
    } elseif ($action === 'remove-favorite') {
        // Remove from favorites
        $deleteFavoriteQuery = "DELETE FROM favorites WHERE id = ? AND event_id = ?";
        $stmt = $conn->prepare($deleteFavoriteQuery);
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
    }
}

// Fetch all notified event IDs for the user
$notificationsQuery = "SELECT event_id FROM notifications WHERE id = ?";
$stmt = $conn->prepare($notificationsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifiedEvents = [];
while ($row = $result->fetch_assoc()) {
    $notifiedEvents[] = $row['event_id'];
}

// Fetch all favorited event IDs for the user
$favoritesQuery = "SELECT event_id FROM favorites WHERE id = ?";
$stmt = $conn->prepare($favoritesQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$favoritedEvents = [];
while ($row = $result->fetch_assoc()) {
    $favoritedEvents[] = $row['event_id'];
}

// Execute the query
$result = mysqli_query($conn, $sql);

$events = [];
if ($result) {
    // Fetch all rows as an associative array
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    echo "Error: " . mysqli_error($conn);
}

/*$merchSql = "SELECT * FROM merch_organiser";
$merchResult = $conn->query($merchSql);

if (!$merchResult) {
    echo "Error executing query: " . $conn->error;
}*/

// Close the database connection
mysqli_close($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="participanthome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <!-- HEADER NAVIGATION BAR  -->   
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a>
                <a href="participantmerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantmerchandise.php' ? 'active' : ''; ?>"></i>Merchandise</a>
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

    <!-- SEARCH FOR EVENTS -->   
    <div class="search-section">
        <h1>Find Your Event!</h1>
        <form method="GET" action="participanthome.php" class="search-bar">
            <input type="text" name="organizer" placeholder="Enter Club Name" value="<?php echo htmlspecialchars($organizer); ?>">
            <select name="event_type">
                <option value="0" <?php echo $event_type === '0' ? 'selected' : ''; ?>>Choose Category</option>
                <option value="sports" <?php echo $event_type === '1' ? 'selected' : ''; ?>>Sports</option>
                <option value="volunteer" <?php echo $event_type === '2' ? 'selected' : ''; ?>>Volunteer</option>
                <option value="academic" <?php echo $event_type === '3' ? 'selected' : ''; ?>>Academic</option>
                <option value="social" <?php echo $event_type === '4' ? 'selected' : ''; ?>>Social</option>
                <option value="cultural" <?php echo $event_type === '5' ? 'selected' : ''; ?>>Cultural</option>
                <option value="college" <?php echo $event_type === '6' ? 'selected' : ''; ?>>College</option>
            </select>
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Search
            </button>
            <button type="button" onclick="clearSearch()" class="clear-button">
                <i class="fas fa-times"></i> Clear Search
            </button>
            <button type="button" id="filter-btn" class="filter-button">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>

<!-- FILTER + ADS + EVENT CARDS -->   
<main class="main-content">

    <!-- FILTER EVENTS THAT OPEN UP AS DRAWER -->
    <aside id = "filter-drawer" class="filter-drawer">

    <div class="filter-header">
        <h3>Filter Event</h3>
        <button id="close-filter-btn" class="close-button">&times;</button>
    </div>

    <form action="participanthome.php" class="filter-content" method="GET">
        <div class="filter-option">
            <label for="sort-by">Sort By</label>
            <select id="sort-by" name="sort_by">
                <option value="Latest" <?php echo $sort_by === 'Latest' ? 'selected' : ''; ?>>Latest</option>
                <option value="Oldest" <?php echo $sort_by === 'Oldest' ? 'selected' : ''; ?>>Oldest</option>
            </select>
        </div>
        <div class="filter-option">
            <label for="date-post">Date Post</label>
            <select id="date-post" name="start_date">
                <option value="anytime" <?php echo $start_date == 'anytime' ? 'selected' : ''; ?>>Anytime</option>
                <option value="last-week" <?php echo $start_date == 'last-week' ? 'selected' : ''; ?>>This Week</option>
                <option value="this-month" <?php echo $start_date == 'this-month' ? 'selected' : ''; ?>>This Month</option>
            </select>
        </div>
        <div class="filter-option">
            <label>Location</label>
            <label>
                <input type="checkbox" class="location-filter" name="location" value="on-campus"
                <?php echo $location === 'on-campus' ? 'checked' : ''; ?>> On-Campus
            </label>
            <label>
                <input type="checkbox" class="location-filter" name="location" value="off-campus"
                <?php echo $location === 'off-campus' ? 'checked' : ''; ?>> Off-Campus
            </label>
        </div>
        <div class="filter-option">
            <label>Role</label>
            <label>
                <input type="checkbox" name="event_role" value="crew" 
                <?php echo ($event_role === 'crew') ? 'checked' : ''; ?>> Crew
            </label>
            <label>
                <input type="checkbox" name="event_role" value="participant" 
                <?php echo ($event_role === 'participant') ? 'checked' : ''; ?>> Participant
            </label>
        </div>
        <div class="filter-option">
            <label>Format</label>
            <label>
                <input type="checkbox" name="event_format" value="in-person" 
                <?php echo ($event_format === 'in-person') ? 'checked' : ''; ?>> In-Person
            </label>
            <label>
                <input type="checkbox" name="event_format" value="online" 
                <?php echo ($event_format === 'online') ? 'checked' : ''; ?>> Online
            </label>
        </div>
        <button id="apply-filter-btn" type="button">Apply Filters</button>
    </form>
    </aside>

    <!-- ADVERTISEMENTS -->
    <aside class="ads">
    <h2>MERCHANDISE</h2>
    <div class="scroll-indicator" id="scrollUp">&#9650;</div>
    <div class="merch-container" id="merchContainer">
        <?php 
        if($merchResult && $merchResult->num_rows > 0): 
            $counter = 0;
        ?>
            <?php while($row = $merchResult->fetch_assoc()): ?>
                <!-- Wrap merch-card with an anchor tag -->
                <a href="viewmerch.php?id=<?= urlencode($row['merch_org_id']) ?>" class="merch-link">
                    <div class="merch-card" data-index="<?= $counter++ ?>">
                        <div class="image-container">
                            <?php if($row['item_image']): ?>
                                <img class="main-image" 
                                    src="data:image/jpeg;base64,<?= base64_encode($row['item_image']) ?>" 
                                    alt="<?= htmlspecialchars($row['item_name']) ?>">
                            <?php endif; ?>
                        </div>

                        <div class="merch-info">
                            <h3><?= htmlspecialchars($row['item_name']) ?></h3>
                            <div class="type-size">
                                <span><?= htmlspecialchars($row['item_type']) ?></span>
                                <?php if($row['item_size'] != 'Not Applicable'): ?>
                                    <span>• <?= htmlspecialchars($row['item_size']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p class="description"><?= htmlspecialchars($row['item_description']) ?></p>

                        <div class="stock-price">
                            <div class="stock-info">
                                <?php
                                $stockClass = $row['stock_quantity'] > 10 ? 'in-stock' : 
                                            ($row['stock_quantity'] > 0 ? 'low-stock' : 'out-stock');
                                $stockText = $row['stock_quantity'] > 10 ? 'In Stock' : 
                                           ($row['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                                ?>
                                <span class="stock-badge <?= $stockClass ?>"><?= $stockText ?></span>
                                <span>(<?= $row['stock_quantity'] ?> left)</span>
                            </div>
                            <span class="price">$<?= number_format($row['price'], 2) ?></span>
                        </div>

                        <div class="promotion-info">
                            <span>Promo: <?= date('M d', strtotime($row['promotion_start'])) ?> - 
                                        <?= date('M d', strtotime($row['promotion_end'])) ?></span>
                            <div class="address" title="<?= htmlspecialchars($row['pickup_address']) ?>">
                                📍 <?= substr($row['pickup_address'], 0, 30) . 
                                        (strlen($row['pickup_address']) > 30 ? '...' : '') ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No merchandise available at this time.</p>
        <?php endif; ?>
    </div>
    <div class="scroll-indicator" id="scrollDown">&#9660;</div>
    </aside>

    <!-- EVENT LISTING -->                
    <section class="event-list">
        
        <!-- EVENT STATUS BUTTON (ONGOING, UPCOMING, PAST) -->   
        <div class="event-status-buttons">
            <button type="button" class="status-btn active" onclick="filterEventsByStatus('all', this)">Show All</button>
            <button type="button" class="status-btn" onclick="filterEventsByStatus('ongoing', this)">Ongoing</button>
            <button type="button" class="status-btn" onclick="filterEventsByStatus('upcoming', this)">Upcoming</button>
            <button type="button" class="status-btn" onclick="filterEventsByStatus('past', this)">Completed</button>
            <button type="button" class="status-btn" onclick="filterEventsByStatus('past', this)">Past</button>
        </div>

        <!-- FILTERED MESSAGE -->
        <?php if ($isFiltered): ?>
            <p><?php echo count($events); ?> results found</p>
        <?php endif; ?>

        <?php foreach ($events as $event): ?>
            <div class="event-card" 
            data-event-id="<?php echo $event['event_id']; ?>" 
            data-start-date="<?php echo $event['start_date']; ?>" 
            data-format="<?php echo htmlspecialchars($event['event_format']); ?>"
            data-location="<?php echo htmlspecialchars($event['location']); ?>"
            data-start-date="<?php echo $event['start_date']; ?>"
            data-end-date="<?php echo $event['end_date']; ?>"
            <?php echo ($event['event_status'] === 'completed') ? 'data-completed="true"' : ''; ?>
            style="background-image: url('data:image/jpeg;base64,<?php echo base64_encode($event['event_photo']); ?>');">
                <div class="event-overlay"></div> 
                <div class="event-header">
                    <div class="event-title-organizer">
                        <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                        <span class="event-organizer"><?php echo htmlspecialchars($event['organizer']); ?></span>
                    </div>

                    <div class="event-icons">
                        <form method="POST" action="">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <input type="hidden" name="club_id" value="<?php echo $event['club_id']; ?>">
                            <input type="hidden" name="event_date" value="<?php echo $event['start_date']; ?>">
                            <input type="hidden" name="action" value="<?php echo in_array($event['event_id'], $notifiedEvents) ? 'remove-notification' : 'add-notification'; ?>">
                            <button type="submit" class="notification-button <?php echo in_array($event['event_id'], $notifiedEvents) ? 'yellow' : 'white'; ?>">
                                <i class="fas fa-bell"></i>
                            </button>
                        </form>

                        <form method="POST" action="">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <input type="hidden" name="club_id" value="<?php echo $event['club_id']; ?>">
                            <input type="hidden" name="action" value="<?php echo in_array($event['event_id'], $favoritedEvents) ? 'remove-favorite' : 'add-favorite'; ?>">
                            <button type="submit" class="heart-button <?php echo in_array($event['event_id'], $favoritedEvents) ? 'red' : 'white'; ?>">
                                <i class="fa fa-heart"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <p><?php echo htmlspecialchars($event['description']); ?></p>
                    
                <div class="event-footer">
                    <span class="event-location"><?php echo htmlspecialchars($event['location']); ?></span>
                    <span class="event-role"><?php echo htmlspecialchars($event['event_role']); ?></span>
                    <span class="event-date"> <?php echo date('Y-m-d', strtotime($event['start_date'])); ?></span>
                    <div class="event-buttons">
                        <?php
                        // Get the current date
                        $currentDate = date('Y-m-d');

                        // Check if the event has already passed
                        $isEventPast = (strtotime($event['end_date']) < strtotime($currentDate));
                        ?>
                        <button class="find-out-more-button" 
                            onclick="window.location.href='findoutmore.php?event_id=<?php echo urlencode($event['event_id']); ?>'">
                            Find Out More
                        </button>
                        <button class="join-button" 
                            data-role="<?php echo htmlspecialchars($event['event_role']); ?>" 
                            data-event-id="<?php echo htmlspecialchars($event['event_id']); ?>"
                            <?php echo $isEventPast ? 'disabled style="cursor: not-allowed; opacity: 0.6;"' : ''; ?>>
                            Join Event
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

</main>


<!-- JAVA SCRIPT FOR HANDLING FUNCTIONALITY -->
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

document.querySelectorAll('.join-button').forEach(button => {
    button.addEventListener('click', () => {
        const role = button.dataset.role;
        const eventId = button.dataset.eventId;
        window.location.href = `${role}form.php?event_id=${eventId}`;
    });
});

// Get the event ID to highlight from the URL
const urlParams = new URLSearchParams(window.location.search);
const highlightEventId = urlParams.get('highlight_event_id');

if (highlightEventId) {
    // Find the event card with the matching data-event-id
    const targetCard = document.querySelector(`.event-card[data-event-id='${highlightEventId}']`);
    if (targetCard) {
        // Add a highlight class to the card
        targetCard.classList.add('highlight');

        // Scroll to the highlighted card
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// CLEAR ALL SEARCH/REFRESH
function clearSearch() {
    // Clear input fields
    document.querySelector('input[name="organizer"]').value = '';
    document.querySelector('select[name="event_type"]').value = '0';
    // Submit the form to refresh and show all events
    document.querySelector('.search-bar').submit();
}

// OPEN AND CLOSE THE FILTER USING THE FILTER BUTTON
document.getElementById("filter-btn").addEventListener("click", () => {
    const filterDrawer = document.getElementById("filter-drawer");
    filterDrawer.classList.toggle("open");
});

// CLOSE THE FILTER DRAWER USING X BUTTON
document.getElementById("close-filter-btn").addEventListener("click", () => {
    document.getElementById("filter-drawer").classList.remove("open");
});

// Get filter drawer elements
const filterDrawer = document.getElementById("filter-drawer");
const closeBtn = document.getElementById("close-filter-btn");
const sortBySelect = document.getElementById("sort-by");

// Initialize results count on page load
document.addEventListener('DOMContentLoaded', () => {
    updateResultsCount(document.querySelectorAll(".event-card").length, false);
});

// Handle apply filter button click
document.getElementById("apply-filter-btn").addEventListener("click", () => {
    // Get all filter values
    const datePost = document.getElementById("date-post").value;
    const locationFilters = Array.from(
        document.querySelectorAll('input[name="location"]:checked')
    ).map(checkbox => checkbox.value);
    
    const eventRole = document.querySelector('input[name="event_role"]:checked')?.value || '';
    const eventFormat = document.querySelector('input[name="event_format"]:checked')?.value || '';
    
    // Get sort option
    const selectedSortOption = sortBySelect.value;
    
    // Apply filters and sorting
    applyFilters(datePost, locationFilters, eventRole, eventFormat, selectedSortOption);
    
    // Close the drawer after applying filters
    filterDrawer.classList.remove("open");
});

// Handle sort selection change
sortBySelect.addEventListener("change", () => {
    const selectedSortOption = sortBySelect.value;
    
    // Get current filter values
    const datePost = document.getElementById("date-post").value;
    const locationFilters = Array.from(
        document.querySelectorAll('input[name="location"]:checked')
    ).map(checkbox => checkbox.value);
    
    const eventRole = document.querySelector('input[name="event_role"]:checked')?.value || '';
    const eventFormat = document.querySelector('input[name="event_format"]:checked')?.value || '';
    
    // Apply filters and sorting
    applyFilters(datePost, locationFilters, eventRole, eventFormat, selectedSortOption);
});

function updateResultsCount(count, isFiltered) {
    // Create or get the results count element
    let resultsElement = document.querySelector('.results-count');
    if (!resultsElement) {
        resultsElement = document.createElement('p');
        resultsElement.className = 'results-count';
        // Insert after the status section
        const statusSection = document.querySelector('.status-section');
        if (statusSection) {
            statusSection.insertAdjacentElement('afterend', resultsElement);
        }
    }
    
    // Update the text based on whether filters are applied
    if (isFiltered) {
        resultsElement.textContent = `${count} results found after filtering`;
    } else {
        resultsElement.textContent = `Showing all ${count} results`;
    }
}

function applyFilters(datePost, locationFilters, eventRole, eventFormat, selectedSortOption) {
    const events = document.querySelectorAll(".event-card");
    const eventList = document.querySelector('.event-list');
    
    // Convert events to array for filtering and sorting
    let filteredEvents = Array.from(events);
    
    // Track if any filters are applied
    const hasFilters = datePost !== 'anytime' || 
                      locationFilters.length > 0 || 
                      eventRole !== '' || 
                      eventFormat !== '';
    
    // Apply date filter
    if (datePost !== 'anytime') {
        const currentDate = new Date();
        filteredEvents = filteredEvents.filter(event => {
            const eventDate = new Date(event.dataset.startDate);
            if (datePost === 'last-week') {
                const weekAgo = new Date();
                weekAgo.setDate(weekAgo.getDate() - 7);
                return eventDate >= weekAgo && eventDate <= currentDate;
            } else if (datePost === 'this-month') {
                const monthAgo = new Date();
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                return eventDate >= monthAgo && eventDate <= currentDate;
            }
            return true;
        });
    }
    
    // Apply location filter
    if (locationFilters.length > 0) {
        filteredEvents = filteredEvents.filter(event => {
            const location = event.querySelector('.event-location').textContent.toLowerCase();
            return locationFilters.some(filter => {
                if (filter === 'on-campus') return location.includes('utm');
                if (filter === 'off-campus') return !location.includes('utm');
                return false;
            });
        });
    }
    
    // Apply role filter
    if (eventRole) {
        filteredEvents = filteredEvents.filter(event => {
            const role = event.querySelector('.event-role').textContent.toLowerCase();
            return role === eventRole.toLowerCase();
        });
    }
    
    // Apply format filter
    if (eventFormat) {
        filteredEvents = filteredEvents.filter(event => {
            const format = event.getAttribute('data-format')?.toLowerCase();
            return format === eventFormat.toLowerCase();
        });
    }
    
    // Sort filtered events
    if (selectedSortOption) {
        filteredEvents.sort((a, b) => {
            const dateA = new Date(a.dataset.startDate);
            const dateB = new Date(b.dataset.startDate);
            
            return selectedSortOption === 'Latest' ? 
                dateB - dateA : // Latest first
                dateA - dateB;  // Oldest first
        });
    }
    
    // Hide all events first
    events.forEach(event => event.style.display = 'none');
    
    // Show filtered and sorted events
    filteredEvents.forEach(event => {
        event.style.display = 'block';
        eventList.appendChild(event); // Move to maintain sort order
    });
    
    // Update results count
    updateResultsCount(filteredEvents.length, hasFilters);
}

// Handle clear filters
document.querySelector('.clear-button')?.addEventListener('click', () => {
    // Reset all form inputs
    document.getElementById("date-post").value = "anytime";
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Reset sort selection
    sortBySelect.value = 'Latest';
    
    // Show all events in default order
    const events = document.querySelectorAll(".event-card");
    events.forEach(event => event.style.display = 'block');
    
    // Update results count to show total
    updateResultsCount(events.length, false);
    
    // Refresh the page
    window.location.href = 'participanthome.php';
});

function filterEventsByStatus(status, clickedButton) {
    const events = document.querySelectorAll(".event-card");
    const currentDate = new Date();

    // Update button states
    const buttons = document.querySelectorAll(".status-btn");
    buttons.forEach(btn => btn.classList.remove("active"));
    clickedButton.classList.add("active");

    events.forEach(event => {
        // Get the event date from the span element with class event-date
        const dateSpan = event.querySelector('.event-date');
        const startDateStr = dateSpan.textContent.trim();
        const startDate = new Date(startDateStr);
        
        // Calculate end date (assuming it's stored somewhere, if not you'll need to add it)
        // For now, let's assume end date is the same as start date
        const endDate = new Date(startDateStr);
        endDate.setHours(23, 59, 59); // Set to end of day

        let eventStatus = "";

        // Determine event status
        if (currentDate < startDate) {
            eventStatus = "upcoming";
        } else if (currentDate > endDate) {
            eventStatus = "past";
        } else if (currentDate >= startDate && currentDate <= endDate) {
            eventStatus = "ongoing";
        }

        // Handle specific status case for 'completed' if needed
        if (status === "completed" && event.hasAttribute("data-completed")) {
            event.style.display = "block";
        } else if (status === "all") {
            event.style.display = "block";
        } else if (eventStatus === status) {
            event.style.display = "block";
        } else {
            event.style.display = "none";
        }
    });

    // Update visible event count
    const visibleEvents = document.querySelectorAll(".event-card[style='display: block']").length;
    const resultsElement = document.querySelector('.event-list > p');
    if (resultsElement) {
        resultsElement.textContent = `${visibleEvents} results found`;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const merchContainer = document.getElementById("merchContainer");
    const scrollUp = document.getElementById("scrollUp");
    const scrollDown = document.getElementById("scrollDown");
    const cards = document.querySelectorAll(".merch-card");
    
    if (cards.length === 0) return;

    let currentIndex = 0;
    let isAnimating = false;
    let autoRotateInterval;

    // Show initial card
    cards[0].classList.add('active');

    function showCard(index) {
        if (isAnimating) return;
        isAnimating = true;

        // Remove all classes first
        cards.forEach(card => {
            card.classList.remove('active', 'prev');
        });

        // Get current and next card
        const currentCard = cards[currentIndex];
        const nextCard = cards[index];

        // Add transition classes
        currentCard.classList.add('prev');
        nextCard.classList.add('active');

        // Update current index
        currentIndex = index;

        // Reset animation flag after transition
        setTimeout(() => {
            isAnimating = false;
            updateScrollIndicators();
        }, 500);
    }

    function nextCard() {
        const nextIndex = (currentIndex + 1) % cards.length;
        showCard(nextIndex);
    }

    function prevCard() {
        const prevIndex = (currentIndex - 1 + cards.length) % cards.length;
        showCard(prevIndex);
    }

    function updateScrollIndicators() {
        scrollUp.style.display = currentIndex > 0 ? "flex" : "none";
        scrollDown.style.display = currentIndex < cards.length - 1 ? "flex" : "none";
    }

    // Click handlers
    scrollUp.addEventListener("click", prevCard);
    scrollDown.addEventListener("click", nextCard);

    // Auto-rotation
    function startAutoRotate() {
        if (!autoRotateInterval) {
            autoRotateInterval = setInterval(nextCard, 5000);
        }
    }

    function stopAutoRotate() {
        if (autoRotateInterval) {
            clearInterval(autoRotateInterval);
            autoRotateInterval = null;
        }
    }

    // Mouse interactions
    merchContainer.addEventListener("mouseenter", stopAutoRotate);
    merchContainer.addEventListener("mouseleave", startAutoRotate);

    // Initialize
    updateScrollIndicators();
    startAutoRotate();
});


</script>

</body>
</html>
