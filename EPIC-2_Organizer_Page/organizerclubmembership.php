<?php
session_start();
include('config.php');

// Check if a club is selected and store it in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
    header("Location: organizerevent.php");
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membership_id = $_POST['membership_id'];
    $action = $_POST['action'];

    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE club_memberships SET status = ?, updated_at = NOW() WHERE memberships_id = ?");
    $stmt->bind_param("si", $status, $membership_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Membership status updated successfully!');</script>";
}

// Get the selected club ID from the session
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

if (!$selected_club_id) {
    echo "<script>alert('No club selected. Please select a club from the home page.'); window.location.href='organizerhome.php';</script>";
    exit;
}

// Fetch membership details for the selected club
$sql = "SELECT cm.memberships_id, cm.user_id, cm.club_id, cm.position, cm.status, cm.join_date, 
        c.club_name, c.description, s.first_name, s.last_name, s.faculty_name, s.year_course, s.email
        FROM club_memberships cm
        JOIN clubs c ON cm.club_id = c.club_id
        JOIN students s ON cm.user_id = s.id
        WHERE cm.club_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_club_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize arrays for different statuses
$pendingMemberships = [];
$approvedMemberships = [];
$rejectedMemberships = [];

// Organize the data into their respective arrays
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'Pending') {
            $pendingMemberships[] = $row;
        } elseif ($row['status'] === 'Approved') {
            $approvedMemberships[] = $row;
        } elseif ($row['status'] === 'Rejected') {
            $rejectedMemberships[] = $row;
        }
    }
}

//Email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer library
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membership_id = $_POST['membership_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    // Update the membership status in the database
    $stmt = $conn->prepare("UPDATE club_memberships SET status = ?, updated_at = NOW() WHERE memberships_id = ?");
    $stmt->bind_param("si", $status, $membership_id);
    $stmt->execute();
    $stmt->close();

    // Fetch the student's email and name for the notification
    $email_query = "
        SELECT s.email, s.first_name, s.last_name, c.club_name 
        FROM club_memberships cm
        JOIN students s ON cm.user_id = s.id
        JOIN clubs c ON cm.club_id = c.club_id
        WHERE cm.memberships_id = ?
    ";
    $stmt_email = $conn->prepare($email_query);
    $stmt_email->bind_param("i", $membership_id);
    $stmt_email->execute();
    $stmt_email->bind_result($student_email, $first_name, $last_name, $club_name);
    $stmt_email->fetch();
    $stmt_email->close();

    // Send email notification
    if ($student_email) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'elisha03@graduate.utm.my'; // Your email address
            $mail->Password = 'egmp jwea jxwn vove'; // Your email password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('elisha03@graduate.utm.my', 'Eventure Team');
            $mail->addAddress($student_email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Club Membership Application - $status";
            $mail->Body = "
                <div style='text-align: center;'>
                    <h1>Club Membership Update</h1>
                    <p>Dear $first_name $last_name,</p>
                    <p>Your application to join the club <strong>$club_name</strong> has been <strong>$status</strong>.</p>
                    <p>Thank you for your interest in joining the club.</p>
                    <p>Best regards,<br>Eventure Team</p>
                </div>
            ";

            $mail->send();
        } catch (Exception $e) {
            echo "<script>alert('Email could not be sent. Mailer Error: {$mail->ErrorInfo}');</script>";
        }
    }

    echo "<script>alert('Membership status updated successfully!');</script>";
}

// Get the selected club ID from the session
$selected_club_id = isset($_SESSION['SELECTEDID']) ? $_SESSION['SELECTEDID'] : null;

if (!$selected_club_id) {
    echo "<script>alert('No club selected. Please select a club from the home page.'); window.location.href='organizerhome.php';</script>";
    exit;
}

// Fetch membership details for the selected club
$sql = "SELECT cm.memberships_id, cm.user_id, cm.club_id, cm.position, cm.status, cm.join_date, 
        c.club_name, c.description, s.first_name, s.last_name, s.faculty_name, s.year_course, s.email
        FROM club_memberships cm
        JOIN clubs c ON cm.club_id = c.club_id
        JOIN students s ON cm.user_id = s.id
        WHERE cm.club_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_club_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize arrays for different statuses
$pendingMemberships = [];
$approvedMemberships = [];
$rejectedMemberships = [];

// Organize the data into their respective arrays
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'Pending') {
            $pendingMemberships[] = $row;
        } elseif ($row['status'] === 'Approved') {
            $approvedMemberships[] = $row;
        } elseif ($row['status'] === 'Rejected') {
            $rejectedMemberships[] = $row;
        }
    }
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Membership</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizerclubmembership.css">
</head>
<body>
<header>
    <h1>Club Membership Management</h1>
    <div class="header-left">
        <div class="nav-right">
            <a href="participanthome.php" class="participant-site">PARTICIPANT SITE</a>
            <a href="organizerhome.php" class="organizer-site">ORGANIZER SITE</a> 
            <a href="profilepage.php" class="profile-icon"><i class="fas fa-user-circle"></i></a>
        </div>
    </div>
</header>

<main>
<aside class="sidebar">
    <!-- Sidebar Content -->
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

<div class="main-content">
    <h1>Club Membership List</h1>
    
    <!-- Pending Section -->
    <h2>Pending Memberships</h2>
    <div class="card-container">
        <?php if (!empty($pendingMemberships)): ?>
            <?php foreach ($pendingMemberships as $membership): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']); ?></h3>
                    <p><strong>Membership ID:</strong> <?php echo $membership['memberships_id']; ?></p>
                    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($membership['faculty_name']); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($membership['year_course']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($membership['email']); ?></p>
                    <p><strong>Club:</strong> <?php echo htmlspecialchars($membership['club_name']); ?></p>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($membership['position']); ?></p>
                    <p><strong>Join Date:</strong> <?php echo htmlspecialchars($membership['join_date']); ?></p>
                    <div class="actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="membership_id" value="<?php echo $membership['memberships_id']; ?>">
                            <button type="submit" name="action" value="approve" class="action-btn approve">Approve</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="membership_id" value="<?php echo $membership['memberships_id']; ?>">
                            <button type="submit" name="action" value="reject" class="action-btn reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No pending memberships.</p>
        <?php endif; ?>
    </div>

    <!-- Approved Section -->
    <h2>Approved Memberships</h2>
    <div class="card-container">
        <?php if (!empty($approvedMemberships)): ?>
            <?php foreach ($approvedMemberships as $membership): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']); ?></h3>
                    <p><strong>Membership ID:</strong> <?php echo $membership['memberships_id']; ?></p>
                    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($membership['faculty_name']); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($membership['year_course']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($membership['email']); ?></p>
                    <p><strong>Club:</strong> <?php echo htmlspecialchars($membership['club_name']); ?></p>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($membership['position']); ?></p>
                    <p><strong>Join Date:</strong> <?php echo htmlspecialchars($membership['join_date']); ?></p>
                    <p><strong>Status:</strong> Approved</p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No approved memberships.</p>
        <?php endif; ?>
    </div>

    <!-- Rejected Section -->
    <h2>Rejected Memberships</h2>
    <div class="card-container">
        <?php if (!empty($rejectedMemberships)): ?>
            <?php foreach ($rejectedMemberships as $membership): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($membership['first_name'] . ' ' . $membership['last_name']); ?></h3>
                    <p><strong>Membership ID:</strong> <?php echo $membership['memberships_id']; ?></p>
                    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($membership['faculty_name']); ?></p>
                    <p><strong>Year:</strong> <?php echo htmlspecialchars($membership['year_course']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($membership['email']); ?></p>
                    <p><strong>Club:</strong> <?php echo htmlspecialchars($membership['club_name']); ?></p>
                    <p><strong>Position:</strong> <?php echo htmlspecialchars($membership['position']); ?></p>
                    <p><strong>Join Date:</strong> <?php echo htmlspecialchars($membership['join_date']); ?></p>
                    <p><strong>Status:</strong> Rejected</p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No rejected memberships.</p>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>

<?php
$conn->close();
?>
