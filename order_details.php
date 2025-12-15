<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

// Get order details
$stmt = $conn->prepare("SELECT o.*, a.full_name, a.phone, a.address_line1, a.address_line2, a.city, a.state, a.postal_code, a.country,
                        pm.method_type, pm.card_number, pm.bank_name, pm.ewallet_type
                        FROM orders o
                        LEFT JOIN addresses a ON o.address_id = a.id
                        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: orders.php');
    exit;
}

$order = $result->fetch_assoc();

// Get order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

$status_class = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger'
][$order['status']] ?? 'secondary';
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Order Details</h1>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    <!-- Order Status -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <small class="text-muted">Order Number</small><br>
                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Order Date</small><br>
                    <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Status</small><br>
                    <span class="badge bg-<?= $status_class ?> fs-6"><?= ucfirst($order['status']) ?></span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Payment Status</small><br>
                    <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?> fs-6">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Items -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = $order_items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>RM<?= number_format($item['product_price'], 2) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><strong>RM<?= number_format($item['subtotal'], 2) ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <!-- Shipping Address -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Shipping Address</h6>
                </div>
                <div class="card-body">
                    <?php if ($order['address_id']): ?>
                        <strong><?= htmlspecialchars($order['full_name']) ?></strong><br>
                        <small class="text-muted">
                            <?= htmlspecialchars($order['phone']) ?><br>
                            <?= htmlspecialchars($order['address_line1']) ?><br>
                            <?php if ($order['address_line2']): ?>
                                <?= htmlspecialchars($order['address_line2']) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($order['postal_code']) ?> <?= htmlspecialchars($order['city']) ?><br>
                            <?= htmlspecialchars($order['state']) ?>, <?= htmlspecialchars($order['country']) ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted">No address information</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-credit-card"></i> Payment Method</h6>
                </div>
                <div class="card-body">
                    <?php if ($order['payment_method_id']): ?>
                        <strong><?= ucfirst(str_replace('_', ' ', $order['method_type'])) ?></strong><br>
                        <small class="text-muted">
                            <?php if ($order['card_number']): ?>
                                **** **** **** <?= substr($order['card_number'], -4) ?>
                            <?php elseif ($order['bank_name']): ?>
                                <?= htmlspecialchars($order['bank_name']) ?>
                            <?php elseif ($order['ewallet_type']): ?>
                                <?= htmlspecialchars($order['ewallet_type']) ?>
                            <?php endif; ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted">No payment information</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>RM<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping Fee</span>
                        <span>RM<?= number_format($order['shipping_fee'], 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total</strong>
                        <strong class="text-primary fs-5">RM<?= number_format($order['total'], 2) ?></strong>
                    </div>
                </div>
            </div>

            <?php if ($order['notes']): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-sticky"></i> Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
