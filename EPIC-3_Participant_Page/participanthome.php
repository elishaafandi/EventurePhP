<?php
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
$sql = "SELECT * FROM events WHERE event_status = 'approved' AND application = 'open'";

// Fetch student details to autofill form
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

// Initialize variables for organizer and event_type from user input
$organizer = isset($_GET['organizer']) ? trim($_GET['organizer']) : '';
$event_type = isset($_GET['event_type']) ? trim($_GET['event_type']) : '0'; // Set event_type to '0' by default

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : 'anytime'; // Default is anytime
$location = isset($_GET['location']) ? $_GET['location'] : ''; // Empty if no location selected
$event_role = isset($_GET['event_role']) ? $_GET['event_role'] : ''; // Corrected to event_role
$event_format = isset($_GET['event_format']) ? $_GET['event_format'] : ''; // Corrected to event_format

// Check if filters are applied to modify the query
$isFiltered = !empty($organizer) || ($event_type !== '0') || ($start_date !== 'anytime') || !empty($location) || !empty($event_role) || !empty($event_format);

if ($isFiltered) {
    if (!empty($organizer)) {
        $sql .= " AND organizer LIKE '%" . mysqli_real_escape_string($conn, $organizer) . "%'";
    }
    if ($event_type !== '0') {
        $sql .= " AND event_type = '" . mysqli_real_escape_string($conn, $event_type) . "'";
    }

      // Filter by start date
    if ($start_date == 'last-week') {
        $sql .= " AND start_date >= CURDATE() - INTERVAL 1 WEEK";
    } elseif ($start_date == 'this-month') {
        $sql .= " AND start_date >= CURDATE() - INTERVAL 1 MONTH";
    } elseif ($start_date !== 'anytime') {
        // If a specific start date is selected, filter by that date
        $sql .= " AND start_date = '" . mysqli_real_escape_string($conn, $start_date) . "'";
    }

    // Filter by location
    if (!empty($location)) {
        if ($location === 'on-campus') {
            $sql .= " AND location LIKE '%UTM%'";
        } elseif ($location === 'off-campus') {
            $sql .= " AND location NOT LIKE '%UTM%'";
        }
    }

   // Filter by event_role
    if (!empty($event_role)) {
        $sql .= " AND event_role = '" . mysqli_real_escape_string($conn, $event_role) . "'";
    }

// Filter by event_format
    if (!empty($event_format)) {
        $sql .= " AND event_format = '" . mysqli_real_escape_string($conn, $event_format) . "'";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['club_id'])) {
    // Ensure to sanitize and get the values
    $event_id = intval($_POST['event_id']);  // Event ID
    $club_id = intval($_POST['club_id']);    // Club ID (new field)       // Assuming user_id is stored in the session

    $action = $_POST['action'];  // Action (add or remove)

    if ($action === 'add') {
        // Add to favorites
        $insertQuery = "INSERT IGNORE INTO favorites (id, event_id, club_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iii", $user_id, $event_id, $club_id);
        $stmt->execute();
    } elseif ($action === 'remove') {
        // Remove from favorites
        $deleteQuery = "DELETE FROM favorites WHERE id = ? AND event_id = ? AND club_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("iii", $user_id, $event_id, $club_id);
        $stmt->execute();
    }
}


// Fetch all events from the database
$eventsQuery = "SELECT * FROM events";
$eventsResult = $conn->query($eventsQuery);

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

// Always order by start date
$sql .= " ORDER BY start_date DESC";

// Execute the query
$result = mysqli_query($conn, $sql);

$events = [];
if ($result) {
    // Fetch all rows as an associative array
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    echo "Error: " . mysqli_error($conn);
}

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
    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>"></i>Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>"></i>Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>"></i>Calendar</a>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>"></i>User Profile</a>
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
                    <option value="7" <?php echo $event_type === '7' ? 'selected' : ''; ?>>Uniform Bodies</option>
                </select>
                <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
                <button type="button" onclick="clearSearch()" class="clear-button"><i class="fas fa-times"></i> Clear Search</button>
            </form>
        </div>

    <main class="main-content">
    <aside class="filter-section">
    <form action="participanthome.php" method="GET">
        <h3>Filter Event <span class="clear-all">Clear all</span></h3>
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
        <button id="filter-btn">Apply Filters</button>
    </form>
    </aside>


        <section class="event-list">
            <?php if ($isFiltered): ?>
                <p><?php echo count($events); ?> results found</p>
            <?php else: ?>
                <p>Showing all events</p>
            <?php endif; ?>

            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-header">
                        <div class="event-title-organizer">
                            <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                            <span class="event-organizer"><?php echo htmlspecialchars($event['organizer']); ?></span>
                        </div>
                        <div class="event-icons">
                            <button class="notification-button"><i class="fas fa-bell"></i></button>
                            <form method="POST" action="">
                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                <input type="hidden" name="club_id" value="<?php echo $event['club_id']; ?>">
                                <input type="hidden" name="action" value="<?php echo in_array($event['event_id'], $favoritedEvents) ? 'remove' : 'add'; ?>">
                                <button type="submit" class="heart-button <?php echo in_array($event['event_id'], $favoritedEvents) ? 'red' : 'grey'; ?>">
                                    <i class="fa fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <p><?php echo htmlspecialchars($event['description']); ?></p>
                    <div class="event-footer">
                        <span class="event-location"><?php echo htmlspecialchars($event['location']); ?></span>
                        <span class="event-role"><?php echo htmlspecialchars($event['event_role']); ?></span>
                        <span class="event-status"><?php echo htmlspecialchars($event['status']); ?></span>
                        <div class="event-buttons">
                            <button class="find-out-more-button"
                            data-event-id="<?php echo htmlspecialchars($event['event_id']); ?>"
                            data-title="<?php echo htmlspecialchars($event['event_name']); ?>" 
                            data-organizer="<?php echo htmlspecialchars($event['organizer']); ?>" 
                            data-date="<?php echo date("d/m/Y", strtotime($event['start_date'])); ?>" 
                            data-time="<?php echo date("H:i", strtotime($event['start_date'])); ?>" 
                            data-location="<?php echo htmlspecialchars($event['location']); ?>" 
                            data-description="<?php echo htmlspecialchars($event['description']); ?>" 
                            data-role="<?php echo htmlspecialchars($event['event_role']); ?>"  
                            data-total-slots="<?php echo htmlspecialchars($event['total_slots']); ?>"
                            data-available-slots="<?php echo htmlspecialchars($event['available_slots']); ?>"
                            data-event-status="<?php echo htmlspecialchars($event['status']); ?>"
                            data-event-type="<?php echo htmlspecialchars($event['event_type']); ?>"
                            data-event-format="<?php echo htmlspecialchars($event['event_format']); ?>"
                            data-created-at="<?php echo date("d/m/Y H:i", strtotime($event['created_at'])); ?>"
                            data-photo="<?php echo !empty($event['event_photo']) 
                            ? 'data:image/jpeg;base64,' . base64_encode($event['event_photo']) 
                            : 'placeholder.jpg'; ?>">
                            Find Out More
                            </button>
                            <button class="join-button" 
                            data-role="<?php echo htmlspecialchars($event['event_role']); ?>" 
                            data-event-id="<?php echo htmlspecialchars($event['event_id']); ?>">
                            Join Event
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

 <!-- Modal Structure for Event Details -->
    <div id="eventModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div id="modal-details">
            <h1 id="modal-title"><?php echo isset($event['event_name']) ? htmlspecialchars($event['event_name']) : ''; ?></h1>
            <p><strong>Organizer:</strong> <span id="modal-organizer"><?php echo isset($event['organizer']) ? htmlspecialchars($event['organizer']) : ''; ?></span></p>
            <p><strong>Date:</strong> <span id="modal-date"><?php echo isset($event['start_date']) ? date("d/m/Y", strtotime($event['start_date'])) : ''; ?></span></p>
            <p><strong>Time:</strong> <span id="modal-time"><?php echo isset($event['start_date']) ? date("H:i", strtotime($event['start_date'])) : ''; ?></span></p>
            <p><strong>Location:</strong> <span id="modal-location"><?php echo isset($event['location']) ? htmlspecialchars($event['location']) : ''; ?></span></p>
            <p><strong>Description:</strong> <span id="modal-description"><?php echo isset($event['description']) ? htmlspecialchars($event['description']) : ''; ?></span></p>
            <p><strong>Total Slots:</strong> <span id="modal-total-slots"><?php echo isset($event['total_slots']) ? htmlspecialchars($event['total_slots']) : ''; ?></span></p>
            <p><strong>Available Slots:</strong> <span id="modal-available-slots"><?php echo isset($event['available_slots']) ? htmlspecialchars($event['available_slots']) : ''; ?></span></p>
            <p><strong>Status:</strong> <span id="modal-event-status"><?php echo isset($event['status']) ? htmlspecialchars($event['status']) : ''; ?></span></p>
            <p><strong>Event Type:</strong> <span id="modal-event-type"><?php echo isset($event['event_type']) ? htmlspecialchars($event['event_type']) : ''; ?></span></p>
            <p><strong>Format:</strong> <span id="modal-event-format"><?php echo isset($event['event_format']) ? htmlspecialchars($event['event_format']) : ''; ?></span></p>
            <p><strong>Event Photo:</strong> <span id="modal-event-photo"><?php echo isset($event['event_photo']) ? htmlspecialchars($event['event_photo']) : ''; ?></span></p>
                <div class="event-actions">
                    <button class="register-button" 
                        data-role="<?php echo isset($event['event_role']) ? htmlspecialchars($event['event_role']) : ''; ?>">
                        Register Now
                    </button>
                    <button 
                        class="view-participant-button <?php echo ($event_role === 'Crew') ? 'disabled' : ''; ?>" 
                        data-role="<?php echo htmlspecialchars($event_role); ?>" 
                        data-event-id="<?php echo htmlspecialchars($event['event_id']); ?>"
                        <?php echo ($event_role === 'Crew') ? 'disabled' : ''; ?>>
                        View Participant
                    </button>

                </div>
            </div>
        </div>
    </div>

<!-- JavaScript to handle modal functionality -->
<script>
    function clearSearch() {
    // Clear input fields
    document.querySelector('input[name="organizer"]').value = '';
    document.querySelector('select[name="event_type"]').value = '0';
    // Submit the form to refresh and show all events
    document.querySelector('.search-bar').submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('eventModal');
        const closeButton = document.querySelector('.close-button');

            // Function to open modal
            function openModal(eventData) {
                document.getElementById('modal-title').textContent = eventData.title;
                document.getElementById('modal-organizer').textContent = eventData.organizer;
                document.getElementById('modal-date').textContent = eventData.date;
                document.getElementById('modal-time').textContent = eventData.time;
                document.getElementById('modal-location').textContent = eventData.location;
                document.getElementById('modal-description').textContent = eventData.description;
                document.getElementById('modal-total-slots').textContent = eventData.totalSlots;
                document.getElementById('modal-available-slots').textContent = eventData.availableSlots;
                document.getElementById('modal-event-status').textContent = eventData.eventStatus;
                document.getElementById('modal-event-type').textContent = eventData.eventType;
                document.getElementById('modal-event-format').textContent = eventData.eventFormat;
                document.getElementById('modal-event-photo').textContent = eventData.eventPhoto;

                const eventPhotoContainer = document.getElementById('modal-event-photo');
                    if (eventData.eventPhoto.startsWith('data:image')) {
                        eventPhotoContainer.innerHTML = `<img src="${eventData.eventPhoto}" alt="Event Photo" class="event-photo">`;
                    } else {
                        eventPhotoContainer.innerHTML = `<img src="placeholder.jpg" alt="No image available" class="event-photo">`;
                    }

                document.querySelector('.view-participant-button').setAttribute('data-event-id', eventData.eventId);
                document.querySelector('.view-participant-button').setAttribute('data-role', eventData.role);
                document.querySelector('.register-button').setAttribute('data-role', eventData.role); // Set the role attribute
                document.querySelector('.register-button').setAttribute('data-event-id', eventData.eventId); // Set the event_id attribute

                modal.style.display = 'flex'; // Set to flex to enable centering
            }

            // Event listener to close modal
            closeButton.onclick = function () {
                modal.style.display = 'none';
            };

            // Close modal when clicking outside of it
            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            };

            // Attach click event to each "Find Out More" button
            document.querySelectorAll('.find-out-more-button').forEach(button => {
                button.addEventListener('click', function () {
                    const eventData = {
                        title: button.getAttribute('data-title'),
                        organizer: button.getAttribute('data-organizer'),
                        date: button.getAttribute('data-date'),
                        time: button.getAttribute('data-time'),
                        location: button.getAttribute('data-location'),
                        description: button.getAttribute('data-description'),
                        role: button.getAttribute('data-role'),
                        eventId: button.getAttribute('data-event-id'),
                        totalSlots: button.getAttribute('data-total-slots'),
                        availableSlots: button.getAttribute('data-available-slots'),
                        eventStatus: button.getAttribute('data-event-status'),
                        eventType: button.getAttribute('data-event-type'),
                        eventFormat: button.getAttribute('data-event-format'),
                        eventPhoto: button.getAttribute('data-photo')
                    };
                    openModal(eventData);
                });
            });

            // Event listener for register button
            document.querySelector('.register-button').addEventListener('click', function () {
                const role = this.getAttribute('data-role').trim();
                const eventId = this.getAttribute('data-event-id'); // Get the event_id
                if (role === 'Crew') {
                    window.location.href = `crewform.php?event_id=${eventId}`; // Redirect to crew form
                } else {
                    window.location.href = `participantform.php?event_id=${eventId}`; // Redirect to participant registration
                }
            });

            document.querySelectorAll('.join-button').forEach(button => {
            button.addEventListener('click', function () {
                const role = this.getAttribute('data-role').trim();
                const eventId = this.getAttribute('data-event-id'); // Get the event_id
                if (role === 'Crew') {
                    window.location.href = `crewform.php?event_id=${eventId}`; // Redirect to crew form
                } else {
                    window.location.href = `participantform.php?event_id=${eventId}`; // Redirect to participant registration
                }
            });
        });

        document.querySelectorAll('.view-participant-button').forEach(button => {
        button.addEventListener('click', function () {
            const role = button.getAttribute('data-role').trim();
            const eventId = button.getAttribute('data-event-id');
            if (role === 'Participant') {
                window.location.href = `viewparticipantlist.php?event_id=${eventId}`;
            }
        });
    });
});

    document.getElementById("filter-btn").addEventListener("click", () => {
    const datePost = document.getElementById("date-post").value;

    // Get selected location filters (can have multiple values)
    const locationFilters = Array.from(
        document.querySelectorAll(".location-filter:checked")
    ).map((checkbox) => checkbox.value);

    // Get selected role filters (can have multiple values)
    const roleFilters = Array.from(
        document.querySelectorAll(".role-filter:checked")
    ).map((checkbox) => checkbox.value);

    // Get selected format filters (can have multiple values)
    const formatFilters = Array.from(
        document.querySelectorAll(".format-filter:checked")
    ).map((checkbox) => checkbox.value);

    // Call a function to apply filters
    applyFilters(datePost, locationFilters, roleFilters, formatFilters);
});

function applyFilters(datePost, locationFilters, roleFilters, formatFilters) {
    // Assuming events are dynamically loaded with a specific class or container
    const events = document.querySelectorAll(".event-card");

    events.forEach((event) => {
        // Read attributes or data tags from events
        const eventDate = event.getAttribute("data-date");
        const eventLocation = event.getAttribute("data-location"); // e.g., "on-campus, off-campus"
        const eventRole = event.getAttribute("data-role");
        const eventFormat = event.getAttribute("data-event-format");

        // Logic to determine if event should be visible
        const matchesDate =
            datePost === "anytime" || eventDate === datePost;

        // Check if event matches selected location filters (handle multiple locations)
        const matchesLocation =
            locationFilters.length === 0 || locationFilters.some(location => eventLocation.includes(location));

        // Check if event matches selected role filters (handle multiple roles)
        const matchesRole =
            roleFilters.length === 0 || roleFilters.some(role => eventRole.includes(role));

        const matchesFormat =
            formatFilters.length === 0 || formatFilters.includes(eventFormat);

        // Show or hide based on matches
        if (matchesDate && matchesLocation && matchesRole && matchesFormat) {
            event.style.display = "block";
        } else {
            event.style.display = "none";
        }
    });
}

// Clear all filters
document.querySelector(".clear-all").addEventListener("click", () => {
    document.getElementById("date-post").value = "anytime";
    document.querySelectorAll("input[type='checkbox']").forEach((checkbox) => {
        checkbox.checked = false;
    });

    // Reset filters to show all events
    applyFilters("anytime", [], [], []);
});

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
