<?php
session_start();
include('config.php');

// Check if user is logged in by verifying the session ID
if (!isset($_SESSION["ID"])) {
    header("Location: organizerhome.php");
    exit;
}

// Ensure the club ID is stored in the session
if (isset($_GET['club_id'])) {
    $_SESSION['SELECTEDID'] = $_GET['club_id'];
}

// Initialize club ID from session
$selected_club_id = isset($_SESSION["SELECTEDID"]) ? $_SESSION["SELECTEDID"] : '';
$organizer_id = $_SESSION["ID"];

    // Query to get total revenue per event for this organizer
    $query = "
        SELECT 
            e.event_id,
            e.event_name,
            e.event_type,
            e.start_date,
            e.price as ticket_price,
            COUNT(DISTINCT pp.participant_id) as total_participants,
            SUM(pp.amount) as total_revenue
        FROM 
            events e
            LEFT JOIN participant_payment pp ON e.event_id = pp.event_id
        WHERE 
            e.organizer_id = ?
            AND e.price > 0
            AND pp.payment_id IS NOT NULL
        GROUP BY 
            e.event_id
        ORDER BY 
            e.start_date DESC
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $organizer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $events = [];
        $total_overall_revenue = 0;
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $events[] = $row;
                $total_overall_revenue += $row['total_revenue'];
            }
        } else {
            die("Error fetching events: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error preparing statement: " . mysqli_error($conn));
    }

    $merch_query = "
        SELECT 
            m.item_name,
            m.item_type,
            m.price as unit_price,
            COUNT(o.order_id) as total_orders,
            SUM(o.quantity) as total_items_sold,
            SUM(o.total_price) as total_revenue,
            m.stock_quantity as current_stock
        FROM 
            merch_organiser m
            LEFT JOIN merch_participant o ON m.merch_org_id = o.merch_org_id
        WHERE 
            m.club_id = ? 
            AND o.payment_status = 'Success'
        GROUP BY 
            m.merch_org_id
        ORDER BY 
            total_revenue DESC
    ";

    $stmt_merch = mysqli_prepare($conn, $merch_query);

    if ($stmt_merch) {
        mysqli_stmt_bind_param($stmt_merch, "i", $selected_club_id);
        mysqli_stmt_execute($stmt_merch);
        $merch_result = mysqli_stmt_get_result($stmt_merch);
        
        $merchandise = [];
        $total_merch_revenue = 0;
        
        if ($merch_result) {
            while ($row = mysqli_fetch_assoc($merch_result)) {
                $merchandise[] = $row;
                $total_merch_revenue += $row['total_revenue'];
            }
        }
        mysqli_stmt_close($stmt_merch);
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Listing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="organizerrevenue.css">
</head>
<body>
    <header>
        <h1>Revenue </h1>
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
    <div class="logo-container">
        <a href="organizerhome.php" class="logo">EVENTURE</a>
    </div>
    <ul>
        <li><a href="organizerhome.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerhome.php' ? 'active' : ''; ?>"><i class="fas fa-home-alt"></i> Dashboard</a></li>
        <li><a href="organizerevent.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerevent.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i>Event Hosted</a></li>
        <li><a href="organizerparticipant.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerparticipant.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i>Participant Listing</a></li>
        <li><a href="organizercrew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizercrew.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i>Crew Listing</a></li>
        <li><a href="organizerclubmembership.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerclub membership.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Club Membership</a></li>
        <li><a href="organizerreport.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i>Reports</a></li>
        <li><a href="rate_crew.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerfeedback.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i>Feedback</a></li>
        <li><a href="organizermerchandise.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizermerchandise.php' ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i>Merchandise</a></li>
    </ul>
    <ul style="margin-top: 60px;">
        <li><a href="organizerrevenue.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'organizerrevenue.php' ? 'active' : 'active'; ?>"><i class="fas fa-hand-holding-usd"></i>Revenue</a></li>
        <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>

    <div class="main-content">

    <div class="revenue-section">
    <h2 class="section-header">Event Revenue Dashboard</h2>
        
        <div class="card mb-4">
            <div class="card-body">
                <h4>Total Overall Revenue: RM <?php echo number_format($total_overall_revenue, 2); ?></h4>
            </div>
        </div>

        <?php if (!empty($events)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Event Type</th>
                            <th>Date</th>
                            <th>Ticket Price (RM)</th>
                            <th>Total Participants</th>
                            <th>Total Revenue (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                                <td><?php echo date('d M Y', strtotime($event['start_date'])); ?></td>
                                <td><?php echo number_format($event['ticket_price'], 2); ?></td>
                                <td><?php echo $event['total_participants']; ?></td>
                                <td><?php echo number_format($event['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No paid events found.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="revenue-section">
    <h2 class="section-header">Merchandise Revenue Dashboard</h2>
    
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Total Merchandise Revenue: RM <?php echo number_format($total_merch_revenue, 2); ?></h4>
                </div>
            </div>

            <?php if (!empty($merchandise)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Unit Price (RM)</th>
                                <th>Total Orders</th>
                                <th>Items Sold</th>
                                <th>Current Stock</th>
                                <th>Total Revenue (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($merchandise as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><?php echo $item['total_orders']; ?></td>
                                    <td><?php echo $item['total_items_sold']; ?></td>
                                    <td><?php echo $item['current_stock']; ?></td>
                                    <td><?php echo number_format($item['total_revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No merchandise sales found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>