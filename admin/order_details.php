<?php
include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Order ID missing.</div>';
    exit;
}
$order_id = intval($_GET['id']);

// Get order info
$order_sql = "SELECT orders.*, users.name AS user_name, users.email FROM orders INNER JOIN users ON orders.user_id = users.id WHERE orders.id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
if ($order_result->num_rows === 0) {
    echo '<div class="alert alert-danger">Order not found.</div>';
    exit;
}
$order = $order_result->fetch_assoc();

// Get order items
$item_sql = "SELECT order_items.*, products.name AS product_name FROM order_items INNER JOIN products ON order_items.product_id = products.id WHERE order_items.order_id = ?";
$stmt = $conn->prepare($item_sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$item_result = $stmt->get_result();

?>
<div class="content-wrapper">
    <h2>Order Details</h2>
    <div class="mb-3">
        <strong>Order ID:</strong> <?= $order['id'] ?><br>
        <strong>User:</strong> <?= htmlspecialchars($order['user_name']) ?> (<?= htmlspecialchars($order['email']) ?>)<br>
        <strong>Total:</strong> RM <?= number_format(isset($order['total']) && $order['total'] !== null ? $order['total'] : 0, 2) ?><br>
        <strong>Status:</strong> <?= ucfirst($order['status']) ?><br>
        <strong>Created At:</strong> <?= $order['created_at'] ?><br>
    </div>
    <h4>Items</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $item_result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>RM <?= number_format($item['price'], 2) ?></td>
                <td>RM <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
</div>
