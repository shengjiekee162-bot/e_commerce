<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT * FROM orders WHERE user_id = ?";
if ($status_filter !== 'all') {
    $query .= " AND status = '$status_filter'";
}
$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4"><i class="bi bi-box-seam"></i> My Orders</h1>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'all' ? 'active' : '' ?>" href="?status=all">All Orders</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'pending' ? 'active' : '' ?>" href="?status=pending">Pending</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'processing' ? 'active' : '' ?>" href="?status=processing">Processing</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'shipped' ? 'active' : '' ?>" href="?status=shipped">Shipped</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'delivered' ? 'active' : '' ?>" href="?status=delivered">Delivered</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'cancelled' ? 'active' : '' ?>" href="?status=cancelled">Cancelled</a>
        </li>
    </ul>

    <?php if ($orders->num_rows > 0): ?>
        <?php while($order = $orders->fetch_assoc()): 
            // Get order items count
            $order_id = $order['id'];
            $items_count = $conn->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = $order_id")->fetch_assoc()['count'];
            
            // Status badge color
            $status_class = [
                'pending' => 'warning',
                'processing' => 'info',
                'shipped' => 'primary',
                'delivered' => 'success',
                'cancelled' => 'danger'
            ][$order['status']] ?? 'secondary';
            
            $payment_class = [
                'unpaid' => 'warning',
                'paid' => 'success',
                'refunded' => 'info'
            ][$order['payment_status']] ?? 'secondary';
        ?>
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <small class="text-muted">Order Number</small><br>
                            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Date</small><br>
                            <?= date('d M Y', strtotime($order['created_at'])) ?>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Total</small><br>
                            <strong class="text-primary">RM<?= number_format($order['total'], 2) ?></strong>
                        </div>
                        <div class="col-md-2">
                            <span class="badge bg-<?= $status_class ?>"><?= ucfirst($order['status']) ?></span>
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <small class="text-muted">
                                <i class="bi bi-box"></i> <?= $items_count ?> item(s)
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <small>
                                Payment: <span class="badge bg-<?= $payment_class ?>"><?= ucfirst($order['payment_status']) ?></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="bi bi-inbox" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">No orders found</h3>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="index.php" class="btn btn-primary btn-lg mt-3">
                    <i class="bi bi-shop"></i> Start Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
