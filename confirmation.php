<?php
/*
    ========================================================================
    ORDER CONFIRMATION PAGE (confirmation.php)
    ------------------------------------------------------------------------
    By: Abdiwilly
    
    Objective:
    1.  Get a specific 'order_id' from the URL (e.g., from checkout redirect).
    2.  VERY IMPORTANT: Ensure the order being viewed belongs to the currently
        logged-in user to prevent people from seeing each other's orders.
    3.  Fetch main order details (date, total, status) from the 'orders' table.
    4.  Fetch all items within that order using a complex JOIN query that pulls
        data from 'order_items', 'product_item', and 'product' tables.
    5.  Display a clear, user-friendly receipt.
    ========================================================================
*/

// Step 1: Include the database and check for a logged-in user.
require_once 'partials/database.php';
// This assumes Joe's login system creates $_SESSION['user_id'].
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

// Step 2: Sanitize and Validate Input from the URL.
$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    die("Error: Invalid Order ID.");
}

// Step 3: Fetch Data from the Database.
try {
    // --- QUERY 1: Get the main order details ---
    // CRITICAL SECURITY: The WHERE clause checks BOTH the order_id AND the user_id.
    $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt_order->execute([$order_id, $user_id]);
    $order = $stmt_order->fetch();

    if (!$order) {
        die("Order not found or you do not have permission to view it.");
    }

    // --- QUERY 2: Get all the items that were in this order ---
    // This query is complex. It joins tables to get the product name, size,
    // color, and the price it was sold at.
    $stmt_items = $pdo->prepare("
        SELECT 
            oi.quantity,
            oi.unit_price, /* The price at the time of purchase */
            p.name,
            pi.size,
            pi.colour
        FROM order_items oi
        JOIN product_item pi ON oi.product_id = pi.id
        JOIN product p ON pi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt_items->execute([$order_id]);
    $order_items = $stmt_items->fetchAll();

} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo htmlspecialchars($order['id']); ?> Confirmation</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="confirmation-page">
        <h1>✅ Thank You! Your Order is Confirmed.</h1>
        <p>A summary of your order is shown below.</p>
        
        <section class="order-summary-box">
            <h2>Order Details</h2>
            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?></p>
            <p><strong>Order Date:</strong> <?php echo date("F j, Y", strtotime($order['order_date'])); ?></p>
            <p><strong>Order Total:</strong> $<?php echo number_format($order['order_total'], 2); ?></p>
            <p><strong>Order Status:</strong> <?php echo ucfirst(htmlspecialchars($order['order_status'])); ?></p>

            <hr>
            <h3>Items Ordered</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($item['size'] ?? ''); ?> / <?php echo htmlspecialchars($item['colour'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <a href="index.php" class="button">Continue Shopping</a>
        <!-- This is a link to your final (and best) page -->
        <a href="orders.php" class="button-secondary">View Order History</a>
    </main>
</body>
</html>