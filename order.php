<?php
/*
    ========================================================================
    ORDER HISTORY PAGE (orders.php)
    ------------------------------------------------------------------------
    Objective:
    1.  Ensure a user is logged in. This page is useless otherwise.
    2.  Fetch a LIST of ALL past orders placed by this specific user.
    3.  Display them in a clean, summary table format, newest first.
    4.  Provide a link on each order that goes to the 'confirmation.php' page
        to show the full details of that specific order.
    ========================================================================
*/

// Step 1: Include the database and enforce login.
require_once 'partials/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

// Step 2: Fetch all orders for this user.
try {
    // A simpler query, as we only need the summary data for the list.
    // ORDER BY id DESC ensures the most recent orders appear at the top.
    $stmt = $pdo->prepare("SELECT id, order_date, order_total, order_status FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Order History</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="order-history-page">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <!-- A user-friendly message if they haven't ordered anything yet. -->
            <p>You have not placed any orders yet. <a href="index.php">Let's find something!</a></p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['order_total'], 2); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($order['order_status'])); ?></td>
                            <td>
                                <!-- CODE REUSABILITY: This link points to the confirmation page we -->
                                <!-- just built, passing the specific order_id to view. -->
                                <a href="confirmation.php?order_id=<?php echo $order['id']; ?>" class="view-details-btn">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>