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
// Fetch total events for the selected club
if ($selected_club_id) {
    $totalEventsQuery = "SELECT COUNT(event_id) AS total_events FROM events WHERE club_id = ?";
    $stmt = $conn->prepare($totalEventsQuery);

    if ($stmt) {
        $stmt->bind_param("i", $selected_club_id);
        $stmt->execute();
        $totalEventsResult = $stmt->get_result();
        $totalEvents = ($totalEventsResult->num_rows > 0) ? $totalEventsResult->fetch_assoc()['total_events'] : 0;
    } else {
        die("Error preparing total events query: " . $conn->error);
    }
} else {
    $totalEvents = 0; // Default value if no club is selected
}

// Fetch total crew members
$totalCrewQuery = "SELECT COUNT(DISTINCT crew_id) AS total_crew FROM event_crews WHERE attendance_status = 'Present'";
$totalCrewResult = $conn->query($totalCrewQuery);
$totalCrew = ($totalCrewResult->num_rows > 0) ? $totalCrewResult->fetch_assoc()['total_crew'] : 0;

// Fetch total participants
$totalParticipantsQuery = "SELECT COUNT(DISTINCT participant_id) AS total_participants FROM event_participants WHERE attendance_status = 'Present'";
$totalParticipantsResult = $conn->query($totalParticipantsQuery);
$totalParticipants = ($totalParticipantsResult->num_rows > 0) ? $totalParticipantsResult->fetch_assoc()['total_participants'] : 0;



$eventReportsQuery = "
    SELECT 
        e.event_name, 
        e.start_date, 
        COUNT(DISTINCT ec.crew_id) AS total_crew, 
        COUNT(DISTINCT ep.participant_id) AS total_participants,
        ROUND(AVG(ep.attendance), 2) AS avg_feedback 
    FROM events e
    LEFT JOIN event_crews ec ON e.event_id = ec.event_id
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
    WHERE e.club_id = ?
    GROUP BY e.event_id
    ORDER BY e.start_date ASC
";

$stmt = $conn->prepare($eventReportsQuery);

if ($stmt) {
    $stmt->bind_param("i", $selected_club_id);
    $stmt->execute();
    $eventReportsResult = $stmt->get_result();
} else {
    die("Error preparing event reports query: " . $conn->error);
}

// Prepare data for Chart.js
$chartLabels = [];
$chartParticipants = [];
$chartCrew = [];

$eventEngagementQuery = "
    SELECT 
        e.event_name, 
        COUNT(DISTINCT ep.participant_id) AS total_participants,
        COUNT(DISTINCT ec.crew_id) AS total_crew
    FROM events e
    LEFT JOIN event_participants ep ON e.event_id = ep.event_id
    LEFT JOIN event_crews ec ON e.event_id = ec.event_id
    WHERE e.club_id = ?
    GROUP BY e.event_id
    ORDER BY total_participants DESC
";

$stmt = $conn->prepare($eventEngagementQuery);

$chartData = [];
if ($stmt) {
    $stmt->bind_param("i", $selected_club_id);
    $stmt->execute();
    $eventEngagementResult = $stmt->get_result();

            while ($row = $eventEngagementResult->fetch_assoc()) {
        $chartLabels[] = html_entity_decode($row['event_name'], ENT_QUOTES, 'UTF-8');
        $chartParticipants[] = $row['total_participants'];
        $chartCrew[] = $row['total_crew'];
        $chartData[] = $row;
    }
} else {
    die("Error preparing event engagement query: " . $conn->error);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventure Organizer Site</title>
    <link rel="stylesheet" href="report.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h2>REPORTS AND KEY METRICS</h2>
        <div class="header-left">
            <div class="nav-right">
                <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
                <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
                <span class="notification-bell">🔔</span>
                <a href="profilepage.php" class="profile-icon"><i class="fas fa-user-circle"></i></a>
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
                <li><a href="organizerclubmembership.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerclub membership.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Club Membership</a></li>
                <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : 'active'; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
                <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
                <li><a href="organizermerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizermerchandise.php' ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i>Merchandise</a></li>
            </ul>
            <ul style="margin-top: 60px;">
                <li><a href="organizerrevenue.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerrevenue.php' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i>Revenue</a></li>
                <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

    <!-- Main Content -->
    <div class="main-content">
    <div class="container my-4">
        <h2 class="text-center">Event Performance Dashboard</h2>

        <!-- Metrics Overview -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h4><?= $totalEvents ?></h4>
                        <p>Total Events Organized</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h4><?= $totalCrew ?></h4>
                        <p>Total Crew Members Engaged</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center bg-warning text-dark">
                    <div class="card-body">
                        <h4><?= $totalParticipants ?></h4>
                        <p>Total Participants Engaged</p>
                    </div>
                </div>
            </div>
        </div>

         <!-- Event Engagement Chart -->
         <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Event Engagement Visualization
                </div>
                <div class="card-body">
                    <canvas id="eventEngagementChart" height="100"></canvas>
                </div>
            </div>

        <!-- Event Reports -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                Event Reports
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Crew Members</th>
                            <th>Participants</th>
                            <th>Avg. Feedback Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($eventReportsResult->num_rows > 0): ?>
                        <?php while ($row = $eventReportsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['event_name'] ?></td>
                                <td><?= date("M d, Y", strtotime($row['start_date'])) ?></td>
                                <td><?= $row['total_crew'] ?></td>
                                <td><?= $row['total_participants'] ?></td>
                                <td>
                                    <?= isset($row['avg_feedback']) ? round($row['avg_feedback'] * 100, 2) . "%" : "N/A" ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No events found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                </table>
            </div>
        </div>
    </div>
    </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('eventEngagementChart').getContext('2d');
            var eventEngagementChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Participants',
                            data: <?= json_encode($chartParticipants) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Crew Members',
                            data: <?= json_encode($chartCrew) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Event Engagement Breakdown'
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of People'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    layout: {
                        padding: {
                            left: 10,
                            right: 10
                        }
                    },
                    barThickness: 40, // Adjust bar width
                    maxBarThickness: 50 // Maximum bar width
                }
            });
        });
    </script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

