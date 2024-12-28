<?php
// Include your database connection file
include('config.php');

// Start the session to access the logged-in user's ID
session_start();

// Assuming the user ID is stored in session
$user_id = $_SESSION['ID'];

// Initialize the disable_button variable
$disable_button = false;

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the club_id from the form
    $club_id = intval($_POST['club_id']); // Sanitize the input

    // Get the current date for join_date
    $join_date = date('Y-m-d');

    // Set default values for position and status
    $position = 'Member'; // Default position
    $status = 'Pending';  // Default status

    // Check if the user has already requested to join or is already a member of this club
    $sql_check_membership = "SELECT * FROM club_memberships WHERE user_id = ? AND club_id = ? AND (status = 'Pending' OR status = 'Approved')";
    $stmt_check_membership = $conn->prepare($sql_check_membership);
    $stmt_check_membership->bind_param("ii", $user_id, $row['club_id']);
    $stmt_check_membership->execute();
    $result_check_membership = $stmt_check_membership->get_result();

    // Determine if the button should be disabled
    $disable_button = $result_check_membership->num_rows > 0;

    // If the user has already requested to join or is a member, disable the button
    if ($result_check->num_rows > 0) {
        $disable_button = true;
        // echo "You have already requested to join or are already a member of this club.";
    } else {
        // Insert the membership request into the database if it's not a duplicate request
        $current_timestamp = date('Y-m-d H:i:s');
        $sql_insert = "INSERT INTO club_memberships (user_id, club_id, position, status, join_date, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt_insert->bind_param("iisssss", $user_id, $club_id, $position, $status, $join_date, $current_timestamp, $current_timestamp);

        if ($stmt_insert->execute()) {
            // Redirect to profileclub.php if the insertion is successful
            echo "<script>
            alert('Club application has been sent. Please wait 5 working days to approve your membership.');
            window.location.href = 'profileclub.php?club_id=" . $club_id . "';
            </script>";
            exit(); // Ensure script execution stops after redirect
        } else {
            // If there was an error with the query, show an error message
            echo "Error: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}



// Query to fetch data from the 'clubs' table
$sql = "SELECT club_id, club_name, club_photo, description, founded_date, club_type FROM clubs";
$result = $conn->query($sql);

// Query to fetch events related to the clubs


// Close the database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs Overview</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <style>
        /* Background and Main Theme */
        /* Background and Main Theme */
        /* General Header Styles */
        header {
            background-color: #800c12;
            /* Dark red background */
            color: #f5f4e6;
            /* Light text color */
            padding: 15px 40px;
            /* Top/bottom and left/right padding */
        }

        /* Row Layout */
        header .row {
            display: flex;
            /* Use Flexbox for layout */
            justify-content: space-between;
            /* Space out left and right sections */
            align-items: center;
            /* Align elements vertically in the center */
        }

        /* Left Section (Logo and Navigation Links) */
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            /* Space between logo and links */
        }

        /* Logo */
        .logo {
            color: #f5f4e6;
            font-weight: bold;
            font-size: 28px;
            text-decoration: none;
            margin-right: 30px;
            /* Add spacing between logo and navigation links */
        }

        /* Navigation Links */
        .nav-left {
            display: flex;
            gap: 20px;
            /* Space between navigation links */
        }

        .nav-left a {
            color: #f5f4e6;
            font-size: 18px;
            text-decoration: none;
            padding: 8px 12px;
            transition: 0.3s ease-in-out;
        }

        .nav-left a:hover,
        .nav-left a.active {
            color: #f3d64c;
            /* Highlight color */
            text-decoration: underline;
        }

        /* Right Section (Buttons and Bell) */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
            /* Space between buttons and bell */
        }

        /* Navigation Links for Buttons */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Buttons */
        .participant-site,
        .organizer-site {
            padding: 10px 18px;
            border-radius: 20px;
            font-weight: 500;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
        }

        .participant-site {
            background-color: #da6124;
            color: #f5f4e6;
        }

        .organizer-site {
            background-color: #f5f4e6;
            color: #000;
        }

        .participant-site:hover {
            background-color: #e08500;
        }

        .organizer-site:hover {
            background-color: #da6124;
            color: #f5f4e6;
        }

        /* Notification Bell */
        .notification-bell {
            font-size: 22px;
            color: #f5f4e6;
            cursor: pointer;
            transition: 0.3s;
        }

        .notification-bell:hover {
            color: #f3d64c;
            /* Change bell color on hover */
        }


        /* Profile Image */
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
        }

        .modal-header {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }

        .center-text {
            text-align: center;
            font-weight: 900;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <header>
        <div class="row">
            <div class="header-left">
                <a href="participanthome.php" class="logo">EVENTURE</a>
                <nav class="nav-left">
                    <a href="participanthome.php" class="active">Home</a>
                    <a href="participantdashboard.php">Dashboard</a>
                    <a href="participantcalendar.php">Calendar</a>
                    <a href="profilepage.php">User Profile</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container mt-5">
        <h2 class="text-center mb-4" style="font-weight: 900;">Club Listings</h2>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card club-card shadow-sm">
                            <div class="card-body" style="height:400px;">
                                <h5 class="club-name"><?php echo htmlspecialchars($row['club_name']); ?></h5>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                                <p><strong>Founded:</strong> <?php echo htmlspecialchars($row['founded_date']); ?></p>
                                <div class="text-center my-3">
                                    <?php if ($row["club_photo"]): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row["club_photo"]); ?>" class="club-photo" alt="Club Photo" height=170px; width=200px;>
                                    <?php else: ?>
                                        <p class="text-muted">No Photo Available</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Modal Trigger Button -->

                                <?php
                                // Assuming you have a database connection in $conn
                                // and $row contains club data with 'club_id
                                $sql = "SELECT club_id, event_id, event_name, description, event_photo FROM events WHERE club_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $row['club_id']); // Assuming club_id is an integer
                                $stmt->execute();
                                $resultevent = $stmt->get_result();
                                ?>
                                <!-- Button to trigger modal -->
                                <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#clubModal<?php echo $row['club_id']; ?>">
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Club Details -->
                    <div class="modal fade" id="clubModal<?php echo $row['club_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="clubModalLabel<?php echo $row['club_id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content" style="">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="clubModalLabel<?php echo $row['club_id']; ?>" style="font-weight: 900;"><?php echo htmlspecialchars($row['club_name']); ?> - Details</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($row['club_type']); ?></p>
                                    <p><strong>Founded:</strong> <?php echo htmlspecialchars($row['founded_date']); ?></p>
                                    <div class="text-center my-3">
                                        <?php if ($row["club_photo"]): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($row["club_photo"]); ?>" class="club-photo" alt="Club Photo" height=170px; width=200px;>
                                        <?php else: ?>
                                            <p class="text-muted">No Photo Available</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Displaying Events Table -->
                                    <h5 class="m-4 text-center" style="font-weight: 900;">Upcoming and Ongoing Events</h5>
                                    <?php if ($resultevent->num_rows > 0): ?>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Photo</th>
                                                    <th>Event Name</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($event = $resultevent->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($event["event_photo"]): ?>
                                                                <img src="data:image/jpeg;base64,<?php echo base64_encode($event["event_photo"]); ?>" class="club-photo" alt="Event Photo" height="100px">
                                                            <?php else: ?>
                                                                <p class="text-muted">No Photo</p>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>No upcoming events for this club.</p>
                                    <?php endif; ?>

                                    <!-- Form to Join Club -->
                                    <!-- Form to Join Club -->
                                    <form action="Joinclub.php" method="POST">
                                        <input type="hidden" name="club_id" value="<?php echo $row['club_id']; ?>">
                                        <div class="form-group" style="display: flex; justify-content: center; align-items: center;">
                                            <?php
                                            // Check if the user is already a member or has a pending/approved membership
                                            $sql_check_membership = "SELECT * FROM club_memberships WHERE user_id = ? AND club_id = ? AND (status = 'Pending' OR status = 'Approved')";
                                            $stmt_check_membership = $conn->prepare($sql_check_membership);
                                            $stmt_check_membership->bind_param("ii", $user_id, $row['club_id']);
                                            $stmt_check_membership->execute();
                                            $result_check_membership = $stmt_check_membership->get_result();

                                            // Set the disable_button variable based on the membership status
                                            $disable_button = ($result_check_membership->num_rows > 0) ? true : false;
                                            ?>

                                            <button
                                                type="submit"
                                                class="btn btn-success join-button"
                                                <?php echo $disable_button ? 'disabled data-disable="true"' : ''; ?>
                                                onclick="handleJoinButtonClick(this, event)">
                                                <?php echo $disable_button ? 'Already a Member or Your Status is Still Pending' : 'Join Club'; ?>
                                            </button>

                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <h4>No clubs found.</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Close the database connection -->
    <?php $conn->close(); ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

<script>
    function handleJoinButtonClick(button, event) {
        if (button.hasAttribute('data-disable')) {
            event.preventDefault(); // Prevent form submission
            alert("You have already requested to join or are already a member of this club.");
        }
    }
</script>