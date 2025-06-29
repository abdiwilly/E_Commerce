<?php
/*
    ========================================================================
    PRODUCT DETAILS PAGE (product.php)
    ------------------------------------------------------------------------
    By: Abdiwilly
    
    Objective: 
    1.  Get a general 'product_id' from the URL (e.g., from index.php).
    2.  Fetch the main product information (name, description) from the 'product' table.
    3.  Fetch all the specific, purchasable "items" (with price, size, color, stock)
        from the 'product_item' table that are linked to this main product.
    4.  Fetch all associated images from the 'product_images' table.
    5.  Display all this information clearly.
    6.  Provide a form that allows a user to select a *specific* item and add it to their cart.
        The form is built to work with the cart logic when it's ready.
    ========================================================================
*/

// Step 1: Include the unified database connection.
// This assumes the connection file is at 'partials/database.php'.
// This is the ONLY dependency on another file.
require_once 'partials/database.php';

// Step 2: Sanitize and Validate Input
// Get the general product ID from the URL (e.g., product.php?id=5).
// We cast it to an integer (int) for security and ensure it's a positive number.
$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    // If no valid ID is provided, it's best to stop or redirect.
    // We'll show a simple error message for now.
    die("Error: Invalid Product ID provided.");
}

// Step 3: Fetch Data from the Database using PDO Prepared Statements

try {
    // --- QUERY 1: Get the main product details ---
    // Fetches the general product's name and description.
    $stmt_product = $pdo->prepare("SELECT * FROM product WHERE id = ?");
    $stmt_product->execute([$product_id]);
    $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

    // If the main product doesn't exist, we can't continue.
    if (!$product) {
        die("Product not found. It may have been removed.");
    }

    // --- QUERY 2: Get all associated product items (the different versions) ---
    // These are the actual items a user can buy (e.g., different sizes/colors).
    // We only fetch items that are in stock (stock_quantity > 0).
    $stmt_items = $pdo->prepare("SELECT * FROM product_item WHERE product_id = ? AND stock_quantity > 0");
    $stmt_items->execute([$product_id]);
    $product_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // --- QUERY 3: Get all images for this product ---
    // This creates a nice gallery for the user.
    $stmt_images = $pdo->prepare("SELECT img_url FROM product_images WHERE product_id = ?");
    $stmt_images->execute([$product_id]);
    $images = $stmt_images->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch just the 'img_url' column

} catch (PDOException $e) {
    // In case of a database error, show a generic error to the user
    // and stop the script. The actual error details are logged for the developer.
    // For this project, dying with the message is fine.
    die("Database query failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The page title is dynamically set to the product's name for good SEO and UX -->
    <title><?php echo htmlspecialchars($product['name']); ?> - Our Store</title>
    <!-- We assume a single CSS file located in assets/css/ -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Assume a shared header would be included here -->
    <?php // include 'partials/header.php'; ?>

    <main class="product-details-page">
        <!-- Display the product name and main description -->
        <header>
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="product-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </header>

        <div class="product-content">
            <!-- Left Side: Image Gallery -->
            <section class="product-gallery">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $img): ?>
                        <!-- We use htmlspecialchars on URLs to prevent XSS attacks -->
                        <img src="assets/images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- A fallback default image if none are uploaded for this product -->
                    <img src="assets/images/default-product.png" alt="No image available">
                <?php endif; ?>
            </section>

            <!-- Right Side: Purchase Options -->
            <section class="purchase-options">
                <h3>Select Your Option</h3>
                
                <?php if (empty($product_items)): ?>
                    <!-- If no variations are in stock, inform the user -->
                    <p class="out-of-stock">Sorry, this product is currently out of stock.</p>
                <?php else: ?>
                    <!-- The form is essential for adding an item to the cart. -->
                    <!-- Its action will point to the cart processing script when ready. -->
                    <!-- We'll use JS for a smooth "AJAX" experience later. -->
                    <form action="cart_logic_handler.php" method="POST">
                        <div class="item-selector">
                            <?php foreach ($product_items as $item): ?>
                                <div class="radio-option">
                                    <!-- A radio button ensures the user can only select ONE specific item to buy -->
                                    <!-- The value sent is the 'product_item.id', which is unique -->
                                    <input type="radio" name="product_item_id" value="<?php echo $item['id']; ?>" id="item_<?php echo $item['id']; ?>" required>
                                    <label for="item_<?php echo $item['id']; ?>">
                                        <span>
                                            <?php 
                                                // Build a readable name for the item, e.g., "Large / Red"
                                                echo htmlspecialchars($item['size'] ?? '') . ' / ' . htmlspecialchars($item['colour'] ?? '');
                                            ?>
                                        </span>
                                        <span class="price">$<?php echo number_format($item['price'], 2); ?></span>
                                        <span class="stock">(<?php echo $item['stock_quantity']; ?> left in stock)</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" required>
                        </div>
                        
                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Assume a shared footer would be included here -->
    <?php // include 'partials/footer.php'; ?>

</body>
</html>