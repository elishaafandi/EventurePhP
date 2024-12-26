<?php
include 'config.php';
session_start();

if (!isset($_SESSION['ID'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['ID'];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : '';
$club_id = isset($_GET['club_id']) ? $_GET['club_id'] : '';

// Fetch student details
$studentQuery = "SELECT * FROM students WHERE id = ?";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $user_id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$eventQuery = "
    SELECT e.*, c.club_photo 
    FROM events e
    JOIN clubs c ON e.club_id = c.club_id
    WHERE e.event_id = ?
";
$eventStmt = $conn->prepare($eventQuery);
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();
$event = $eventResult->fetch_assoc();
$eventStmt->close();



// Fetch the feedback data from the database
// Fetch the feedback data from the database, including the participant's name and profile photo from the 'students' table
$reviews = [];
$feedback_query = "
    SELECT fe.rating, fe.feedback, fe.participant_id, s.first_name AS participant_name, s.student_photo
    FROM feedbackevent fe
    LEFT JOIN students s ON fe.participant_id = s.id
    WHERE fe.event_id = ?
";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("i", $event_id); // Assume $event_id is already set from the URL or context
$stmt->execute();
$feedback_result = $stmt->get_result();

if ($feedback_result->num_rows > 0) {
    while ($feedback = $feedback_result->fetch_assoc()) {
        $rating = $feedback['rating'];  // Assuming rating is stored as an integer (1-5)
        $participant_name = htmlspecialchars($feedback['participant_name']);
        $profile_photo = $feedback['student_photo'] ? 'data:image/jpeg;base64,' . base64_encode($feedback['student_photo']) : 'default_profile.jpg'; // Default if no profile photo
        $comment = nl2br(htmlspecialchars($feedback['feedback']));

        // Store the review in an array
        $reviews[] = [
            'participant_name' => $participant_name,
            'student_photo' => $profile_photo,
            'rating' => $rating,
            'comment' => $comment,
        ];
    }
} else {
    $reviews = []; // No reviews found
}

$stmt->close();
        

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Out More Regarding <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link rel="stylesheet" href="findoutmore.css">
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

<!-- MAIN CONTENT / BODY -->   
<main class="event-details">

    <!-- EVENT TITLE AND TAGS -->
    <div class="event-header" style="--event-image: url('data:image/jpeg;base64,<?php echo base64_encode($event['event_photo']); ?>');">
    <div class="event-overlay"></div>
    <button class="back-button" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>
        <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>
        <div class="event-subheader">
            <p class="event-organizer"><?php echo htmlspecialchars($event['organizer']); ?></p>
            <div class="event-location">
                <i class="fa fa-map-pin" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
        </div>

                    
        <div class="event-tags">
            <span class="tag event-format"><?php echo htmlspecialchars($event['event_format']); ?></span>
            <span class="tag event-role"><?php echo htmlspecialchars($event['event_role']); ?></span>
            <span class="tag event-type"><?php echo htmlspecialchars($event['event_type']); ?></span>
            <span class="tag total-slots"><?php echo htmlspecialchars($event['total_slots']); ?> Slots</span>
            <span class="tag available-slots"><?php echo htmlspecialchars($event['available_slots']); ?> Available</span>
        </div>
    </div>

    <!-- EVENT INFORMATION SECTION -->
    <div class="event-info">
        <h2>About This Event</h2>
        <p> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    
        <h3>Photos</h3>
            <div class="event-photos">
                <?php
                // Assuming event photos are stored in BLOB format in 'event_photo' column
                if (!empty($event['event_photo'])) {
                    $photoData = $event['event_photo'];
                    echo '<img src="data:image/jpeg;base64,' . base64_encode($photoData) . '" alt="Event Photo" class="event-photo" />';
                } else {
                    echo '<p>No photos available.</p>';
                }
                ?>
            </div>

        <h3>Reviews</h3>
        <div class="reviews-container">
            <div class="reviews-scroll">
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <div class="review-header">
                            <img src="<?php echo $review['student_photo']; ?>" alt="<?php echo $review['participant_name']; ?>'s Profile" class="review-profile-photo">
                            <span class="review-author"><?php echo $review['participant_name']; ?></span>
                            <span class="review-rating"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                        </div>
                            <p class="review-comment"><?php echo $review['comment']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($reviews) > 3): ?>
                <div class="review-navigation">
                    <button class="review-nav-button prev-button" onclick="scrollReviews('left')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="review-nav-button next-button" onclick="scrollReviews('right')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            <?php endif;?>
        </div>
    </div>
   
<!-- Footer Section -->
<div class="event-footer">
    <?php
    // Get the current date
    $currentDate = date('Y-m-d');

    // Check if the event has already passed
    $isEventPast = (strtotime($event['end_date']) < strtotime($currentDate));
    ?>
    <button 
        class="register-button" 
        onclick="redirectToForm('<?php echo htmlspecialchars($event['event_id']); ?>', '<?php echo htmlspecialchars($event['event_role']); ?>')"
        <?php echo $isEventPast ? 'disabled style="cursor: not-allowed; opacity: 0.6;"' : ''; ?>
    >
        <i class="fa fa-pencil" aria-hidden="true"></i> Register Now
    </button>
    <button 
        class="view-participants-button" 
        onclick="redirectToViewParticipants('<?php echo htmlspecialchars($event['event_id']); ?>', '<?php echo htmlspecialchars($event['event_role']); ?>')"
    >
        <i class="fa fa-eye" aria-hidden="true"></i> View Participants
    </button>
</div>


</main>

<script>

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

    function redirectToForm(eventId, eventRole) {
    if (eventRole === 'crew') {
        window.location.href = 'crewform.php?event_id=' + eventId;
    } else {
        window.location.href = 'participantform.php?event_id=' + eventId;
    }
}

function redirectToViewParticipants(eventId, eventRole) {
    if (eventRole === 'crew') {
        window.location.href = 'viewcrewlist.php?event_id=' + eventId;
    } else {
        window.location.href = 'viewparticipantlist.php?event_id=' + eventId;
    }
}

function scrollReviews(direction) {
    const container = document.querySelector('.reviews-scroll');
    const scrollAmount = 320; // Width of review card + gap
    
    if (direction === 'left') {
        container.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        container.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

</script>

</body>
</html>
