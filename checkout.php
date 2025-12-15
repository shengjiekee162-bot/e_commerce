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

// Handle adding new address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $postal_code = trim($_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if ($is_default) {
        $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
    }
    
    $stmt = $conn->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssi", $user_id, $full_name, $phone, $address_line1, $address_line2, $city, $state, $postal_code, $is_default);
    
    if ($stmt->execute()) {
        $message = "Address added successfully!";
    } else {
        $error = "Failed to add address.";
    }
}

// Handle adding new payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $method_type = $_POST['method_type'];
    $is_default = isset($_POST['is_default_payment']) ? 1 : 0;
    
    if ($is_default) {
        $conn->query("UPDATE payment_methods SET is_default = 0 WHERE user_id = $user_id");
    }
    
    $stmt = $conn->prepare("INSERT INTO payment_methods (user_id, method_type, card_number, card_holder, expiry_date, bank_name, account_number, ewallet_type, ewallet_number, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssi", 
        $user_id, 
        $method_type,
        $_POST['card_number'] ?? null,
        $_POST['card_holder'] ?? null,
        $_POST['expiry_date'] ?? null,
        $_POST['bank_name'] ?? null,
        $_POST['account_number'] ?? null,
        $_POST['ewallet_type'] ?? null,
        $_POST['ewallet_number'] ?? null,
        $is_default
    );
    
    if ($stmt->execute()) {
        $message = "Payment method added successfully!";
    } else {
        $error = "Failed to add payment method.";
    }
}

// Check if cart is empty
$cart_check = $conn->query("SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id");
$cart_count = $cart_check->fetch_assoc()['count'];

if ($cart_count == 0) {
    header('Location: cart.php');
    exit;
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address_id = intval($_POST['address_id']);
    $payment_id = intval($_POST['payment_id']);
    $notes = trim($_POST['notes'] ?? '');
    
    if (!$address_id || !$payment_id) {
        $error = "Please select both shipping address and payment method.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Get cart items with prices
            $cart_query = "SELECT c.*, p.name, p.price, p.stock 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = $user_id";
            $cart_items = $conn->query($cart_query);
            
            // Calculate totals
            $subtotal = 0;
            $items_data = [];
            
            while ($item = $cart_items->fetch_assoc()) {
                // Check stock
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Insufficient stock for " . $item['name']);
                }
                
                $item_subtotal = $item['quantity'] * $item['price'];
                $subtotal += $item_subtotal;
                
                $items_data[] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item_subtotal
                ];
            }
            
            // Calculate shipping
            $shipping_fee = $subtotal >= 50 ? 0 : 5;
            $total = $subtotal + $shipping_fee;
            
            // Generate order number
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Insert order
            $order_stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, address_id, payment_method_id, subtotal, shipping_fee, total, notes, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')");
            $order_stmt->bind_param("isiiddds", $user_id, $order_number, $address_id, $payment_id, $subtotal, $shipping_fee, $total, $notes);
            
            if (!$order_stmt->execute()) {
                throw new Exception("Failed to create order");
            }
            
            $order_id = $conn->insert_id;
            
            // Insert order items and update stock
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            
            foreach ($items_data as $item) {
                $item_stmt->bind_param("iisdid", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $item['subtotal']);
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to add order items");
                }
                
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }
            
            // Clear cart
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to success page
            header("Location: order_success.php?order_id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get cart items
$cart_items = $conn->query("SELECT c.*, p.name, p.price, p.image_url 
                           FROM cart c 
                           JOIN products p ON c.product_id = p.id 
                           WHERE c.user_id = $user_id");

// Calculate totals
$subtotal = 0;
$items_array = [];
while ($item = $cart_items->fetch_assoc()) {
    $item_subtotal = $item['quantity'] * $item['price'];
    $subtotal += $item_subtotal;
    $items_array[] = $item;
}
$shipping_fee = $subtotal >= 50 ? 0 : 5;
$total = $subtotal + $shipping_fee;

// Get user addresses
$addresses = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");

// Get payment methods
$payment_methods = $conn->query("SELECT * FROM payment_methods WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h1>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                
                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Shipping Address</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($addresses->num_rows > 0): ?>
                            <?php while($addr = $addresses->fetch_assoc()): ?>
                                <div class="form-check mb-3 p-3 border rounded <?= $addr['is_default'] ? 'border-primary' : '' ?>">
                                    <input class="form-check-input" type="radio" name="address_id" id="addr<?= $addr['id'] ?>" 
                                           value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> required>
                                    <label class="form-check-label w-100" for="addr<?= $addr['id'] ?>">
                                        <strong><?= htmlspecialchars($addr['full_name']) ?></strong>
                                        <?php if ($addr['is_default']): ?>
                                            <span class="badge bg-primary">Default</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($addr['phone']) ?><br>
                                            <?= htmlspecialchars($addr['address_line1']) ?><br>
                                            <?php if ($addr['address_line2']): ?>
                                                <?= htmlspecialchars($addr['address_line2']) ?><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($addr['postal_code']) ?> <?= htmlspecialchars($addr['city']) ?>, 
                                            <?= htmlspecialchars($addr['state']) ?>
                                        </small>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal">
                                <i class="bi bi-plus-lg"></i> Add New Address
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> You don't have any saved addresses.
                                <button type="button" class="btn btn-link alert-link p-0" data-bs-toggle="modal" data-bs-target="#addressModal">Add an address</button> to continue.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($payment_methods->num_rows > 0): ?>
                            <?php while($pm = $payment_methods->fetch_assoc()): ?>
                                <div class="form-check mb-3 p-3 border rounded <?= $pm['is_default'] ? 'border-success' : '' ?>">
                                    <input class="form-check-input" type="radio" name="payment_id" id="pay<?= $pm['id'] ?>" 
                                           value="<?= $pm['id'] ?>" <?= $pm['is_default'] ? 'checked' : '' ?> required>
                                    <label class="form-check-label w-100" for="pay<?= $pm['id'] ?>">
                                        <strong><?= ucfirst(str_replace('_', ' ', $pm['method_type'])) ?></strong>
                                        <?php if ($pm['is_default']): ?>
                                            <span class="badge bg-success">Default</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php if ($pm['card_number']): ?>
                                                **** **** **** <?= substr($pm['card_number'], -4) ?>
                                            <?php elseif ($pm['bank_name']): ?>
                                                <?= htmlspecialchars($pm['bank_name']) ?>
                                            <?php elseif ($pm['ewallet_type']): ?>
                                                <?= htmlspecialchars($pm['ewallet_type']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="bi bi-plus-lg"></i> Add New Payment Method
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> You don't have any saved payment methods.
                                <button type="button" class="btn btn-link alert-link p-0" data-bs-toggle="modal" data-bs-target="#paymentModal">Add a payment method</button> to continue.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-sticky"></i> Order Notes (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" placeholder="Any special instructions for your order..."></textarea>
                    </div>
                </div>

            </div>

            <!-- Right Column - Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <div class="mb-3" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach($items_array as $item): 
                                $image_path = !empty($item['image_url']) ? 'uploads/products/' . $item['image_url'] : 'https://via.placeholder.com/60x60?text=No+Image';
                            ?>
                                <div class="d-flex mb-2 pb-2 border-bottom">
                                    <img src="<?= htmlspecialchars($image_path) ?>" class="me-2" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    <div class="flex-grow-1">
                                        <small class="fw-bold"><?= htmlspecialchars($item['name']) ?></small><br>
                                        <small class="text-muted">Qty: <?= $item['quantity'] ?> × RM<?= number_format($item['price'], 2) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="fw-bold">RM<?= number_format($item['quantity'] * $item['price'], 2) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?= count($items_array) ?> items)</span>
                            <span>RM<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fee</span>
                            <span class="<?= $shipping_fee == 0 ? 'text-success' : '' ?>">
                                <?= $shipping_fee == 0 ? 'FREE' : 'RM' . number_format($shipping_fee, 2) ?>
                            </span>
                        </div>
                        
                        <?php if ($subtotal < 50 && $shipping_fee > 0): ?>
                            <div class="alert alert-info small p-2 mb-3">
                                <i class="bi bi-info-circle"></i> Add RM<?= number_format(50 - $subtotal, 2) ?> more for free shipping!
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong class="fs-5">Total</strong>
                            <strong class="text-primary fs-4">RM<?= number_format($total, 2) ?></strong>
                        </div>

                        <button type="submit" name="place_order" class="btn btn-success btn-lg w-100 mb-2" 
                                onclick="return confirm('Confirm and place your order?')">
                            <i class="bi bi-check-circle"></i> Place Order
                        </button>
                        <a href="cart.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Back to Cart
                        </a>
                        
                        <div class="mt-3 p-3 bg-light rounded small">
                            <div class="mb-2"><i class="bi bi-shield-check text-success"></i> Secure Checkout</div>
                            <div class="mb-2"><i class="bi bi-truck text-primary"></i> Fast Delivery</div>
                            <div><i class="bi bi-arrow-repeat text-info"></i> Easy Returns</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.sticky-top {
    position: sticky;
    top: 100px;
    z-index: 100;
}

@media (max-width: 991.98px) {
    .sticky-top {
        position: relative;
        top: 0;
    }
}

.form-check:hover {
    background-color: #f8f9fa;
}
</style>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_address" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone *</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Line 1 *</label>
                        <input type="text" class="form-control" name="address_line1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" name="address_line2">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postal Code *</label>
                            <input type="text" class="form-control" name="postal_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City *</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">State *</label>
                        <select class="form-select" name="state" required>
                            <option value="">Select State</option>
                            <option value="Johor">Johor</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Kuala Lumpur">Kuala Lumpur</option>
                            <option value="Labuan">Labuan</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Penang">Penang</option>
                            <option value="Perak">Perak</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Putrajaya">Putrajaya</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                            <option value="Selangor">Selangor</option>
                            <option value="Terengganu">Terengganu</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default" id="is_default">
                        <label class="form-check-label" for="is_default">Set as default address</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_payment" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Type *</label>
                        <select class="form-select" name="method_type" id="method_type" required onchange="togglePaymentFields()">
                            <option value="">Select Type</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="online_banking">Online Banking</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <div id="card_fields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" maxlength="16">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Card Holder Name</label>
                            <input type="text" class="form-control" name="card_holder">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date (MM/YY)</label>
                            <input type="text" class="form-control" name="expiry_date" placeholder="MM/YY" maxlength="5">
                        </div>
                    </div>
                    
                    <div id="bank_fields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" class="form-control" name="bank_name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" name="account_number">
                        </div>
                    </div>
                    
                    <div id="ewallet_fields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">E-Wallet Type</label>
                            <select class="form-select" name="ewallet_type">
                                <option value="">Select</option>
                                <option value="Touch 'n Go">Touch 'n Go</option>
                                <option value="GrabPay">GrabPay</option>
                                <option value="Boost">Boost</option>
                                <option value="ShopeePay">ShopeePay</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="ewallet_number">
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default_payment" id="is_default_payment">
                        <label class="form-check-label" for="is_default_payment">Set as default payment method</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Payment Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePaymentFields() {
    const type = document.getElementById('method_type').value;
    document.getElementById('card_fields').style.display = 'none';
    document.getElementById('bank_fields').style.display = 'none';
    document.getElementById('ewallet_fields').style.display = 'none';
    
    if (type === 'credit_card' || type === 'debit_card') {
        document.getElementById('card_fields').style.display = 'block';
    } else if (type === 'online_banking') {
        document.getElementById('bank_fields').style.display = 'block';
    } else if (type === 'ewallet') {
        document.getElementById('ewallet_fields').style.display = 'block';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
