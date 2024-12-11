<?php
session_start();
include('config.php');

// Check if a club is selected and store it in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
    header("Location: organizerevent.php");
    exit;
}

// Get the selected club ID from the session
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

// Initialize an empty array for events
$events = [];

// Get the search query from the form
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// Fetch events for the selected club
if ($selected_club_id) {
    $sql = "SELECT 
                event_id, 
                event_photo, 
                event_name, 
                description, 
                location, 
                total_slots, 
                available_slots, 
                event_status, 
                event_type, 
                event_format, 
                start_date, 
                end_date, 
                status, 
                application 
            FROM events 
            WHERE club_id = ?";

    // If a search query is provided, add a condition to filter events
    if (!empty($search_query)) {
        $sql .= " AND event_name LIKE ?";
    }

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        if (!empty($search_query)) {
            $like_query = "%" . $search_query . "%";
            mysqli_stmt_bind_param($stmt, "is", $selected_club_id, $like_query);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $selected_club_id);
        }
        
        mysqli_stmt_execute($stmt);
        $result_events = mysqli_stmt_get_result($stmt);

        if ($result_events) {
            while ($row = mysqli_fetch_assoc($result_events)) {
                $events[] = $row;
            }
        } else {
            die("Error fetching events: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error preparing statement: " . mysqli_error($conn));
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Created</title>
    <link rel="stylesheet" href="organizerevent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-container">
            <a href="organizerhome.php" class="logo">EVENTURE</a>
        </div>
        <ul>
            <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
            <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Event Hosted</a></li>
            <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> Participant Listing</a></li>
            <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Crew Listing</a></li>
            <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a></li>
            <li><a href="organizerfeedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Feedback</a></li>
        </ul>
        <ul style="margin-top: 60px;">
            <li><a href="organizersettings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizersettings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <h1>Events Created</h1>
            <button class="participant-site">PARTICIPANT SITE</button>
            <button class="organizer-site">ORGANIZER SITE</button>
            <span class="notification-bell">🔔</span>
        </header>

        <div class="search-bar">
            <input type="text" placeholder="Search event name..." />
        </div>


        <div class="top-bar">

            <div class="status-tabs">
                <span>All Events</span>
                <span>Upcoming</span>
                <span>Ongoing</span>
                <span>Completed</span>
                <span>Cancelled</span>
            </div>
            <a href="organizercreateeventpart.php" class="create-event-button">CREATE EVENT FOR PARTICIPANT</a>
            <a href="organizercreateeventcrew.php" class="create-event-button">CREATE EVENT FOR CREW</a>
        </div>

        <div class="event-list">
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
            <div class="event-row">
                <div class="event-card" data-status="<?php echo htmlspecialchars($event["status"]); ?>"> 
                    <!-- Event Photo -->
                    <?php if (!empty($event["event_photo"])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($event["event_photo"]); ?>" alt="Event Photo" class="event-photo">
                    <?php endif; ?>

                    <!-- Event Details -->
                    <div class="event-info">
                        <h2><?php echo htmlspecialchars($event["event_name"]); ?></h2>
                        <p><?php echo htmlspecialchars($event["description"]); ?></p>
                        <p>Location: <?php echo htmlspecialchars($event["location"]); ?></p>
                        <p>Type: <?php echo htmlspecialchars($event["event_type"]); ?>, Format: <?php echo htmlspecialchars($event["event_format"]); ?></p>
                        <p>Slots: <?php echo htmlspecialchars($event["available_slots"]); ?> / <?php echo htmlspecialchars($event["total_slots"]); ?></p>
                        <p>Status: <?php echo htmlspecialchars($event["status"]); ?></p>
                    </div>

                    <!-- Event Actions -->
                    <div class="event-actions">
                        <a href="organizerviewevent.php?id=<?php echo $event['event_id']; ?>" class="view">View</a>
                        <a href="editevent.php?id=<?php echo $event['event_id']; ?>" class="edit">Edit</a>
                        <a href="deleteevent.php?id=<?php echo $event['event_id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                    </div>

                    <!-- Application Status -->
                    <div class="status-container">
                        <button class="close-application close-btn" data-event-id="<?php echo $event['event_id']; ?>">Close Applications</button>
                        <button class="open-application open-btn" style="display: none;" data-event-id="<?php echo $event['event_id']; ?>">Open Applications</button>
                    </div>
               </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-events-message">No events available for the selected club or filter.</p>
    <?php endif; ?>
</div>


    <script>
    document.addEventListener("DOMContentLoaded", () => {
    const statusTabs = document.querySelectorAll(".status-tabs span");
    const eventCards = document.querySelectorAll(".event-card");
    const noEventsMessage = document.querySelector(".no-events-message");

    // Add click event listener to each status tab
    statusTabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const selectedStatus = tab.textContent.trim().toLowerCase();
            let hasVisibleEvent = false;

            // Update active class on tabs
            statusTabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            // Filter events based on the selected status
            eventCards.forEach(card => {
                const eventStatus = card.getAttribute("data-status").toLowerCase();

                if (selectedStatus === "all events" || eventStatus === selectedStatus) {
                    card.style.display = "block"; // Show matching cards
                    hasVisibleEvent = true; // At least one event is visible
                } else {
                    card.style.display = "none"; // Hide non-matching cards
                }
            });

            // Show or hide the "No events available" message
            noEventsMessage.style.display = hasVisibleEvent ? "none" : "block";
        });
    });

    const searchInput = document.querySelector(".search-bar input");

    searchInput.addEventListener("input", () => {
        const searchTerm = searchInput.value.toLowerCase();
        let hasVisibleEvent = false;

        eventCards.forEach(card => {
            const eventName = card.querySelector("h2").textContent.toLowerCase();

            if (eventName.includes(searchTerm)) {
                card.style.display = "block"; // Show matching events
                hasVisibleEvent = true; // At least one event is visible
            } else {
                card.style.display = "none"; // Hide non-matching events
            }
        });

        // Show or hide the "No events available" message
        noEventsMessage.style.display = hasVisibleEvent ? "none" : "block";
    });
});


</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.querySelector(".search-bar input");
    const eventCards = document.querySelectorAll(".event-card");

    searchInput.addEventListener("input", () => {
        const searchTerm = searchInput.value.toLowerCase();

        eventCards.forEach(card => {
            const eventName = card.querySelector("h2").textContent.toLowerCase();
            if (eventName.includes(searchTerm)) {
                card.style.display = "block"; // Show matching events
            } else {
                card.style.display = "none"; // Hide non-matching events
            }
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Get all close and open application buttons
    const closeButtons = document.querySelectorAll(".close-btn");
    const openButtons = document.querySelectorAll(".open-btn");

    closeButtons.forEach((closeButton, index) => {
        const openButton = openButtons[index]; // Get corresponding open button

        closeButton.addEventListener("click", function () {
            // Hide the Close Application button
            closeButton.style.display = "none";
            // Show the Open Application button
            openButton.style.display = "inline-block";
        });

        openButton.addEventListener("click", function () {
            // Hide the Open Application button
            openButton.style.display = "none";
            // Show the Close Application button
            closeButton.style.display = "inline-block";
        });
    });
});

</script>



</body>
</html>
