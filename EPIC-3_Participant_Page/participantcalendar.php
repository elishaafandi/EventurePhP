<?php
// Start session and include config
session_start();
include 'config.php';

if (!isset($_SESSION["ID"])) {
    echo "You must be logged in to access this page.";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['ID'];

// Initialize events array
$userEvents = [];
$reminders = [];

// Fetch user profile for header
$student = [];
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($user_id) {
    // SQL Query to get all events the user has joined (as crew or participant)
    $sql = "
        SELECT DISTINCT 
            e.event_id, 
            e.event_name, 
            e.start_date, 
            e.end_date, 
            e.location, 
            e.event_type,
            CASE 
                WHEN ec.id IS NOT NULL THEN 'crew'
                WHEN ep.id IS NOT NULL THEN 'participant'
                ELSE NULL
            END as participation_type
        FROM events e
        LEFT JOIN event_crews ec ON e.event_id = ec.event_id AND ec.id = ?
        LEFT JOIN event_participants ep ON e.event_id = ep.event_id AND ep.id = ?
        WHERE 
            (ec.id IS NOT NULL OR ep.id IS NOT NULL)
        ORDER BY e.start_date ASC";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results
    while ($row = $result->fetch_assoc()) {
        $userEvents[] = $row;
    }

    $sql_reminders = "
    SELECT DISTINCT n.notification_id, n.event_id, e.event_name, n.event_date, n.notes, n.notified_at,
    CASE 
        WHEN ec.id IS NOT NULL THEN 'crew'
        WHEN ep.id IS NOT NULL THEN 'participant'
        ELSE 'not participating'
    END as participation_type
    FROM notifications n
    LEFT JOIN event_crews ec ON n.event_id = ec.event_id AND ec.id = ?
    LEFT JOIN event_participants ep ON n.event_id = ep.event_id AND ep.id = ?
    LEFT JOIN events e ON n.event_id = e.event_id
    ORDER BY n.event_id ASC";

$stmt = $conn->prepare($sql_reminders);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reminders = [];
while ($row = $result->fetch_assoc()) {
    $reminders[] = $row;
}

} else {
    echo "User not logged in or ID missing in session.";
    exit;
}


// Add this to your existing PHP section at the top
if (isset($_POST['update_notification'])) {
    
    $event_id = $_POST['event_id'];
    $notified_at = $_POST['notified_at']; // Reminder time
    $notes = $_POST['notes']; // Notes field
    $user_id = $_SESSION['ID'];

    // Check if notification already exists
    $check_sql = "SELECT * FROM notifications WHERE event_id = ? AND id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $event_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing notification
        $update_sql = "UPDATE notifications SET notified_at = ?, notes = ? WHERE event_id = ? AND id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssii", $notified_at, $notes, $event_id, $user_id);
        $update_stmt->execute();
    } else {
        // Insert a new notification
        $insert_sql = "INSERT INTO notifications (event_id, id, notified_at, notes) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiss", $event_id, $user_id, $notified_at, $notes);
        $insert_stmt->execute();
    }
}

// Add this query to fetch existing notifications
$notifications = [];
$notif_sql = " 
SELECT DISTINCT n.notification_id, n.notified_at, n.notes, n.event_id, e.event_name, n.event_date,
CASE 
    WHEN ec.id IS NOT NULL THEN 'crew'
    WHEN ep.id IS NOT NULL THEN 'participant'
    ELSE 'not participating'
END as participation_type
FROM notifications n
LEFT JOIN event_crews ec ON n.event_id = ec.event_id AND ec.id = ?
LEFT JOIN event_participants ep ON n.event_id = ep.event_id AND ep.id = ?
LEFT JOIN events e ON n.event_id = e.event_id
ORDER BY n.event_id ASC";

$notif_stmt = $conn->prepare($notif_sql);
$notif_stmt->bind_param("ii", $user_id, $user_id); // Pass the user_id twice
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();

while ($row = $notif_result->fetch_assoc()) {
    $notifications[] = [
        'event_id' => $row['event_id'],
        'event_name' => $row['event_name'],
        'participation_type' => $row['participation_type'],
        'notified_at' => $row['notified_at'],
        'notes' => $row['notes'],
    ];
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>www.eventureutm.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="calender.css">
    <style>
        .event-dot {
            height: 8px;
            width: 8px;
            background-color: red;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .event-dot-container {
            display: flex;
            gap: 4px; /* Space between dots */
            justify-content: center;
            margin-top: 4px;
        }

        .event-item-dot {
            background-color: red; /* Blue for events */
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .reminder-item-dot {
            background-color: orange; /* Yellow for reminders */
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .calendar-day.has-event {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .event-details-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
        }

        /* Reminders Sidebar */
        .reminders {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            margin-top: 20px;
            margin-bottom: 20px;
            border: solid 1px black;
        }

        /* Reminders Title */
        .reminders h5 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

         /* Reminders Title */
         .upcoming-events h5 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Individual Reminder Item */
        .reminder-item {
            background-color: #fff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }

        /* Reminder Title */
        .reminder-item p.mb-0 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .event-item p.mb-0 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Reminder Date */
        .reminder-item small {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Reminder Date */
        .upcoming-events small {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Icon for Reminder */
        .reminder-item .fas.fa-pencil {
            margin-right: 8px;
            color: beige;
        }

        /* Empty reminder message */
        .reminders p {
            color: #333;
            text-align: left;
        }

        .edit-reminder-btn {
            background-color: #ff9b00; /* Yellow background to match "Reminders" */
            border: 1px solid #ff9b00; /* Orange border */
            color: white; /* Dark text */
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .edit-reminder-btn:hover {
            background-color: #ff9b00; /* Yellow background to match "Reminders" */
            border: 1px solid #ff9b00; /* Orange border */
            color: white; /* Dark text */
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <a href="participanthome.php" class="logo">EVENTURE</a> 
            <nav class="nav-left">
                <a href="participanthome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participanthome.php' ? 'active' : ''; ?>">Home</a>
                <a href="participantdashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantdashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="participantcalendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'participantcalendar.php' ? 'active' : ''; ?>">Calendar</a>
                <a href="profilepage.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profilepage.php' ? 'active' : ''; ?>">User Profile</a>
            </nav>
        </div>
        <div class="nav-right">
            <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
            <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
            <div class="profile-menu">
                <?php if (!empty($student['student_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($student['student_photo']); ?>" alt="Student Photo" class="profile-icon">
                <?php else: ?>
                    <img src="default-profile.png" alt="Default Profile" class="profile-icon">
                <?php endif; ?>

                <div class="dropdown-menu">
                    <a href="profilepage.php">Profile</a>
                    <hr>
                    <a href="logout.php" class="sign-out">Sign Out</a>
                </div>
            </div>
        </div>
    </header>

<main>
    <div class="container mt-4">
        <h2 class="text-center mb-4">My Events Calendar</h2>
        <div class="row">
            <!-- Upcoming Events Sidebar -->
            <div class="col-md-4">
                <div class="upcoming-events bg-danger text-white p-3 rounded">
                    <h5>Upcoming Events</h5>
                    <?php if (!empty($userEvents)) : ?>
                        <?php foreach ($userEvents as $event) : ?>
                            <div class="event-item bg-light text-dark p-2 mb-2 rounded">
                                <p class="mb-0"><?= htmlspecialchars($event['event_name']) ?> 
                                    <span class="badge bg-info"><?= $event['participation_type'] ?></span>
                                </p>
                                <small>
                                    <?= date('M d, H:i', strtotime($event['start_date'])) ?> to 
                                    <?= date('H:i', strtotime($event['end_date'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-white">No upcoming events.</p>
                    <?php endif; ?>
                </div>

                <!-- Reminders Event Sidebar -->
                <div class="reminders bg-warning text-white p-3 rounded">
                    <h5>Reminders</h5>
                    <?php if (!empty($reminders)) : ?>
                        <?php foreach ($reminders as $reminder) : ?>
                            <div class="reminder-item bg-light text-dark p-2 mb-2 rounded">
                                <p class="mb-0"><?= htmlspecialchars($reminder['event_name']) ?>
                                    <span class="badge bg-info"><?= $reminder['participation_type'] ?></span> 
                                </p>
                                <small>
                                    <?= date('M d, H:i', strtotime($reminder['notified_at'])) ?> | <?= $reminder['notes']; ?>
                                </small>
                                <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-warning edit-reminder-btn" 
                                    data-event-id="<?= $reminder['event_id']; ?>" 
                                    data-event-name="<?= htmlspecialchars($reminder['event_name']); ?>" 
                                    data-notified-at="<?= isset($notifications[$reminder['event_id']]['notified_at']) ? $notifications[$reminder['event_id']]['notified_at'] : ''; ?>"
                                    data-notes="<?= isset($notifications[$reminder['event_id']]['notes']) ? htmlspecialchars($notifications[$reminder['event_id']]['notes']) : ''; ?>">
                                    <i class="fas fa-pencil"></i> Edit
                                </button>

                                    <form action="deletenotification.php" method="POST" style="display: inline;" onsubmit="return confirmDelete(<?= htmlspecialchars($reminder['notification_id']) ?>);">
                                        <input type="hidden" name="notification_id" value="<?= htmlspecialchars($reminder['notification_id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger delete-reminder-btn">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-dark">No reminders set.</p>
                    <?php endif; ?>
                </div>


                <!-- Edit Reminder Pop Up -->
                <div class="modal fade" id="reminderModal" tabindex="-1" aria-labelledby="reminderModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="reminderModalLabel">Edit Reminder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="reminderForm" method="POST">
                                    <input type="hidden" name="event_id" id="reminder_event_id">
                                    <div class="mb-3">
                                        <label for="event_name" class="form-label">Event</label>
                                        <input type="text" class="form-control" id="event_name" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notified_at" class="form-label">Reminder Time</label>
                                        <input type="datetime-local" class="form-control" id="notified_at" name="notified_at" required>
                                    </div>
                                    <!-- New Textarea for Notes -->
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Write any notes or additional details here..."></textarea>
                                    </div>
                                    <input type="hidden" name="update_notification" value="1">
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_notification" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Date Events -->
                <div class="selected-date-events bg-light p-3 rounded">
                    <h5>Events on Selected Date</h5>
                    <ul id="event-details" class="list-unstyled">
                        <li class="text-muted">Click a date on the calendar to view events.</li>
                    </ul>
                </div>
            </div>

                <!-- Event Details Modal -->
                <div id="eventDetailsModal" class="event-details-modal">
                    <div class="modal-content">
                        <h5 id="modal-date-title">Events on [Date]</h5>
                        <div id="modal-event-list"></div>
                        <button onclick="closeModal()" class="btn btn-secondary mt-3">Close</button>
                    </div>
                </div>

            <!-- Calendar -->
            <div class="col-md-8">
                <!-- Calendar Navigation -->
               
                <!-- Calendar Header -->
                <!-- Calendar Header -->
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-outline-secondary" onclick="changeMonth(-1)">←</button>
                    <h4 id="calendar-title" class="text-center mb-4">November 2024</h4>
                    <button class="btn btn-outline-secondary" onclick="changeMonth(1)">→</button>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-container border rounded p-3">
                    <div class="row text-center fw-bold">
                        <div class="col text-muted">Mon</div>
                        <div class="col text-muted">Tue</div>
                        <div class="col text-muted">Wed</div>
                        <div class="col text-muted">Thu</div>
                        <div class="col text-muted">Fri</div>
                        <div class="col text-muted">Sat</div>
                        <div class="col text-muted">Sun</div>
                    </div>
                        <div id="calendar-grid" class="calendar-grid"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
          
// Calendar Script for Eventure Application

// Parse PHP events to JavaScript (make sure this line matches your PHP output)
const userEvents = <?= json_encode($userEvents) ?>;
const notifications = <?= json_encode($notifications) ?>;

// Current calendar state
let currentDate = new Date();
let currentView = 'month';

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCalendar();
    setupEventListeners();
});

   
// Update Calendar Function
// Assuming notifications are stored in `notifications` array and events in `userEvents` array
function updateCalendar() {
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.getElementById('calendar-title');

    // Clear previous calendar
    calendarGrid.innerHTML = '';

    // Set calendar title
    calendarTitle.textContent = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });

    // Determine first and last days of the month
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const today = new Date();

    // Calculate start and end dates for the calendar view
    const startDate = new Date(firstDay);
    startDate.setDate(1 - ((startDate.getDay() + 6) % 7)); // Start from the nearest Monday

    const endDate = new Date(lastDay);
    endDate.setDate(endDate.getDate() + (6 - endDate.getDay())); // End on the nearest Sunday

    // Create calendar grid
    while (startDate <= endDate) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('calendar-day');

        // Determine if day is in the current month
        if (startDate.getMonth() !== currentDate.getMonth()) {
            dayCell.classList.add('other-month');
        }

        // Highlight today's date
        if (startDate.toDateString() === today.toDateString()) {
            dayCell.classList.add('today');
        }

        // Create day number element
        const dayNumber = document.createElement('div');
        dayNumber.classList.add('calendar-day-number');
        dayNumber.textContent = startDate.getDate();
        dayCell.appendChild(dayNumber);

        // Filter events for the current day
        const eventsOnDay = userEvents.filter(event => {
            const eventDate = new Date(event.start_date);
            return (
                eventDate.getFullYear() === startDate.getFullYear() &&
                eventDate.getMonth() === startDate.getMonth() &&
                eventDate.getDate() === startDate.getDate()
            );
        });

        // Filter reminders (notifications) for the current day
        const notificationsOnDay = Object.entries(notifications).filter(([eventId, notification]) => {
            const notificationDate = new Date(notification.notified_at);
            return (
                notificationDate.getFullYear() === startDate.getFullYear() &&
                notificationDate.getMonth() === startDate.getMonth() &&
                notificationDate.getDate() === startDate.getDate()
            );
        });

        // Add event and reminder dots
        const eventContainer = document.createElement('div');
        eventContainer.classList.add('event-dot-container');

        [...eventsOnDay, ...notificationsOnDay].forEach(item => {
            const eventDot = document.createElement('span');
            eventDot.classList.add('event-dot');

            if (item[1]) {
                // Reminder (from notifications)
                eventDot.title = `Reminder: ${item[1].notes || 'No notes provided'}`;
                eventDot.classList.add('reminder-item-dot');
            } else if (item.event_name) {
                // Event
                eventDot.title = `Event: ${item.event_name}`;
                eventDot.classList.add('event-item-dot');
            }

            eventContainer.appendChild(eventDot);
        });

        if (eventContainer.childElementCount > 0) {
            dayCell.appendChild(eventContainer);
        }

        // Store events and reminders data for the day
        if (eventsOnDay.length > 0 || notificationsOnDay.length > 0) {
            dayCell.dataset.events = JSON.stringify([...eventsOnDay, ...notificationsOnDay]);
            dayCell.addEventListener('click', showDayEvents);
        }

        // Add day cell to calendar grid
        calendarGrid.appendChild(dayCell);

        // Move to the next day
        startDate.setDate(startDate.getDate() + 1);
    }
}

function updateCalendarDots() {
    const days = document.querySelectorAll('.calendar-day');
    days.forEach(day => {
        const dayDate = new Date(day.dataset.date);
        if (!dayDate) return;

        const eventsOnDay = userEvents.filter(event => {
            const eventDate = new Date(event.start_date);
            return eventDate.toDateString() === dayDate.toDateString();
        });

        const remindersOnDay = reminders.filter(reminder => {
            const reminderDate = new Date(reminder.notified_at);
            return reminderDate.toDateString() === dayDate.toDateString();
        });

        const dotContainer = day.querySelector('.event-dot-container') || 
                           document.createElement('div');
        dotContainer.className = 'event-dot-container';
        dotContainer.innerHTML = '';

        if (eventsOnDay.length > 0) {
            const eventDot = document.createElement('span');
            eventDot.className = 'event-item-dot';
            dotContainer.appendChild(eventDot);
        }

        if (remindersOnDay.length > 0) {
            const reminderDot = document.createElement('span');
            reminderDot.className = 'reminder-item-dot';
            dotContainer.appendChild(reminderDot);
        }

        if (dotContainer.children.length > 0) {
            day.appendChild(dotContainer);
        }
    });
}

function setupEventListeners() {
    // Month navigation buttons
    document.querySelector('.btn-outline-secondary[onclick="changeMonth(-1)"]')
        .addEventListener('click', () => changeMonth(0));
    document.querySelector('.btn-outline-secondary[onclick="changeMonth(1)"]')
        .addEventListener('click', () => changeMonth(0));

    // View change buttons
    const viewButtons = ['day', 'week', 'month', 'year'];
    viewButtons.forEach(view => {
        const button = document.querySelector(`.btn-outline-primary[onclick="changeView('${view}')"]`);
        if (button) {
            button.addEventListener('click', () => changeView(view));
        }
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('eventDetailsModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}

// Show Day Events Function
function showDayEvents(event) {
    const eventsData = event.currentTarget.dataset.events;
    if (!eventsData) return;

    const eventsAndReminders = JSON.parse(eventsData);
    const modal = document.getElementById('eventDetailsModal');
    const modalTitle = document.getElementById('modal-date-title');
    const modalEventList = document.getElementById('modal-event-list');

    // Format the date for the modal title
    const clickedDate = new Date(eventsAndReminders[0]?.start_date || eventsAndReminders[0]?.notified_at);
    const dateString = clickedDate.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    modalTitle.textContent = `Events and Reminders for ${dateString}`;

    // Separate events and reminders
    const events = eventsAndReminders.filter(item => item.event_name && !item.notes);
    const reminders = eventsAndReminders.filter(item => item.notes || item[1]?.notes);

    // Create HTML content
    let htmlContent = '';

    // Add events section if there are events
    if (events.length > 0) {
        htmlContent += '<h6 class="mb-3">Events:</h6>';
        events.forEach(event => {
            const startTime = new Date(event.start_date).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            const endTime = new Date(event.end_date).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            htmlContent += `
                <div class="mb-2 p-2 border rounded">
                    <strong>${event.event_name}</strong>
                    <span class="badge bg-info ms-2">${event.participation_type}</span>
                    <p class="mb-0 mt-1">
                        Time: ${startTime} - ${endTime}<br>
                        Location: ${event.location || 'Not specified'}
                    </p>
                </div>
            `;
        });
    }

    // Add reminders section if there are reminders
    if (reminders.length > 0) {
        htmlContent += '<h6 class="mb-3 mt-3">Reminders:</h6>';
        reminders.forEach(reminder => {
            const reminderData = reminder[1] || reminder; // Handle both array and object formats
            const reminderTime = new Date(reminderData.notified_at).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            htmlContent += `
                <div class="mb-2 p-2 border rounded">
                    <strong>${event.event_name}</strong>
                    <span class="badge bg-warning ms-2">${reminderData.participation_type || 'Reminder'}</span>
                    <p class="mb-0 mt-1">
                        Time: ${reminderTime}<br>
                        Notes: ${reminderData.notes || 'No notes provided'}
                    </p>
                </div>
            `;
        });
    }

    // If no events or reminders
    if (!events.length && !reminders.length) {
        htmlContent = '<p>No events or reminders for this date.</p>';
    }

    modalEventList.innerHTML = htmlContent;
    modal.style.display = 'block';
}



function closeModal() {
    document.getElementById('eventDetailsModal').style.display = 'none';
}

//SAVE REMINDER
function saveReminder(event) {
    event.preventDefault();

    const eventId = document.getElementById('reminder_event_id').value;
    const notifiedAt = document.getElementById('notified_at').value; // Correct date format
    const note = document.getElementById('notes').value;

    // Create the reminder object
    const reminder = {
        event_id: eventId,
        notified_at: notifiedAt, // Store as an ISO string or timestamp
        note: note
    };

    console.log('Saved Reminder:', reminder);

    // Save reminder (e.g., send to the server and update the UI)
    reminders.push(reminder);  // Add to reminders array

    // Optionally, save reminder data to the backend server here

    // Update the calendar UI to reflect the new reminder
    updateCalendar();
}


function changeMonth(direction) {
    // Update the current date by adding or subtracting months
    currentDate.setMonth(currentDate.getMonth() + direction);

    // Update the calendar view
    updateCalendar();

    // Update the calendar title with the new month and year
    const calendarTitle = document.getElementById('calendar-title');
    calendarTitle.textContent = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
}

// EDIT SET REMINDER FORM
document.querySelectorAll('.edit-reminder-btn').forEach(button => {
    button.addEventListener('click', function() {
        const eventId = this.getAttribute('data-event-id');
        const eventName = this.getAttribute('data-event-name');
        const reminderContainer = this.closest('.reminder-item');
        
        // Get the existing notification time and notes from the reminder item
        const notificationTimeMatch = reminderContainer.querySelector('small').textContent.match(/(\d{4}-\d{2}-\d{2} \d{2}:\d{2})/);
        let notificationTime = '';
        if (notificationTimeMatch) {
            // Convert the date format to datetime-local input format (YYYY-MM-DDTHH:mm)
            const dateObj = new Date(notificationTimeMatch[1]);
            notificationTime = dateObj.toISOString().slice(0, 16);
        }
        
        // Get notes from the small text content (after the | character)
        const noteContent = reminderContainer.querySelector('small').textContent.split('|')[1]?.trim() || '';

        // Populate the modal fields
        document.getElementById('reminder_event_id').value = eventId;
        document.getElementById('event_name').value = eventName;
        document.getElementById('notified_at').value = notificationTime;
        document.getElementById('notes').value = noteContent;

        // Show the modal
        const reminderModal = new bootstrap.Modal(document.getElementById('reminderModal'));
        reminderModal.show();
    });
});

// SUBMIT SAVE CHANGES
document.getElementById('reminderForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    // Get form values
    const eventId = document.getElementById('reminder_event_id').value;
    const notifiedAt = document.getElementById('notified_at').value;
    const notes = document.getElementById('notes').value;

    // Find the corresponding table row
    const row = document.querySelector(`.edit-reminder-btn[data-event-id="${eventId}"]`)?.closest('tr');
    if (row) {
        // Dynamically update the table row
        row.querySelector('.reminder-time-cell').textContent = notifiedAt || 'N/A';
        row.querySelector('.notes-cell').textContent = notes || 'No notes';
    } else {
        console.error("Table row not found for Event ID:", eventId);
    }

    // Submit the form to save changes in the database
    this.submit();
});

// DELETE REMINDER
function confirmDelete(notificationId) {
    return confirm(`Are you sure you want to delete this reminder?`);
}

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
