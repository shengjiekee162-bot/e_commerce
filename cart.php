<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add to cart
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        // Check if product exists and has enough stock
        $product_check = $conn->prepare("SELECT stock, price FROM products WHERE id = ?");
        $product_check->bind_param("i", $product_id);
        $product_check->execute();
        $result = $product_check->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            if ($product['stock'] >= $quantity) {
                // Check if item already in cart
                $cart_check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $cart_check->bind_param("ii", $user_id, $product_id);
                $cart_check->execute();
                $cart_result = $cart_check->get_result();
                
                if ($cart_result->num_rows > 0) {
                    // Update quantity
                    $cart_item = $cart_result->fetch_assoc();
                    $new_quantity = $cart_item['quantity'] + $quantity;
                    
                    if ($new_quantity <= $product['stock']) {
                        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $update->bind_param("ii", $new_quantity, $cart_item['id']);
                        $update->execute();
                        $message = "Cart updated successfully!";
                    } else {
                        $error = "Not enough stock available.";
                    }
                } else {
                    // Insert new cart item
                    $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
                    $insert->bind_param("iii", $user_id, $product_id, $quantity);
                    $insert->execute();
                    $message = "Product added to cart!";
                }
            } else {
                $error = "Not enough stock available.";
            }
        } else {
            $error = "Product not found.";
        }
    }
    
    // Update quantity
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            // Check stock
            $stock_check = $conn->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
            $stock_check->bind_param("ii", $cart_id, $user_id);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();
            
            if ($stock_result->num_rows > 0) {
                $stock_data = $stock_result->fetch_assoc();
                if ($quantity <= $stock_data['stock']) {
                    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $update->bind_param("iii", $quantity, $cart_id, $user_id);
                    $update->execute();
                    $message = "Quantity updated!";
                } else {
                    $error = "Not enough stock available.";
                }
            }
        }
    }
    
    // Remove from cart
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $cart_id = intval($_POST['cart_id']);
        $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $cart_id, $user_id);
        $delete->execute();
        $message = "Item removed from cart.";
    }
    
    // Clear cart
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        $clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear->bind_param("i", $user_id);
        $clear->execute();
        $message = "Cart cleared.";
    }
}

// Get cart items
$cart_query = "SELECT c.id as cart_id, c.quantity, c.added_at,
                      p.id as product_id, p.name, p.price, p.image_url, p.stock,
                      (c.quantity * p.price) as subtotal
               FROM cart c
               JOIN products p ON c.product_id = p.id
               WHERE c.user_id = ?
               ORDER BY c.added_at DESC";

$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result();

// Calculate totals
$total = 0;
$item_count = 0;
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4">
        <i class="bi bi-cart3"></i> Shopping Cart
    </h1>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($cart_items->num_rows > 0): ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Cart Items (<?= $cart_items->num_rows ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $cart_items->data_seek(0); // Reset pointer
                                    while($item = $cart_items->fetch_assoc()): 
                                        $total += $item['subtotal'];
                                        $item_count += $item['quantity'];
                                        $image_path = !empty($item['image_url']) ? 'uploads/products/' . $item['image_url'] : 'https://via.placeholder.com/80x80?text=No+Image';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($image_path) ?>" 
                                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                                         class="img-thumbnail me-3" 
                                                         style="width: 80px; height: 80px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                        <small class="text-muted">Stock: <?= $item['stock'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <strong>RM<?= number_format($item['price'], 2) ?></strong>
                                            </td>
                                            <td class="align-middle">
                                                <form method="POST" class="d-flex align-items-center" style="max-width: 150px;">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                    <input type="number" 
                                                           name="quantity" 
                                                           value="<?= $item['quantity'] ?>" 
                                                           min="1" 
                                                           max="<?= $item['stock'] ?>"
                                                           class="form-control form-control-sm me-2"
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td class="align-middle">
                                                <strong class="text-primary">RM<?= number_format($item['subtotal'], 2) ?></strong>
                                            </td>
                                            <td class="align-middle">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Remove this item?');">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Clear all items from cart?');">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?= $item_count ?>)</span>
                            <span>RM<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span class="text-success">
                                <?php if ($total >= 50): ?>
                                    FREE
                                <?php else: ?>
                                    RM5.00
                                <?php endif; ?>
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong class="text-primary">
                                RM<?= number_format($total >= 50 ? $total : $total + 5, 2) ?>
                            </strong>
                        </div>
                        
                        <?php if ($total < 50): ?>
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-info-circle"></i> Add RM<?= number_format(50 - $total, 2) ?> more for free shipping!
                            </div>
                        <?php endif; ?>
                        
                        <a href="checkout.php" class="btn btn-success btn-lg w-100 mb-2">
                            <i class="bi bi-credit-card"></i> Proceed to Checkout
                        </a>
                        <a href="products.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty Cart -->
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="bi bi-cart-x" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="text-muted">Looks like you haven't added any items to your cart yet.</p>
                <a href="products.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-shop"></i> Start Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.sticky-top {
    position: sticky;
    top: 100px;
    z-index: 100;
}

.img-thumbnail {
    border-radius: 8px;
}

.table td {
    vertical-align: middle;
}

@media (max-width: 991.98px) {
    .sticky-top {
        position: relative;
        top: 0;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
