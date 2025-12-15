<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? 0);

// Verify order belongs to user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: orders.php');
    exit;
}

$order = $result->fetch_assoc();
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card text-center shadow-lg">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    
                    <h1 class="display-5 mb-3">Order Placed Successfully!</h1>
                    <p class="lead text-muted mb-4">
                        Thank you for your purchase. Your order has been received and is being processed.
                    </p>

                    <div class="bg-light p-4 rounded mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Order Number</small>
                                <strong class="fs-5"><?= htmlspecialchars($order['order_number']) ?></strong>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Order Total</small>
                                <strong class="fs-5 text-primary">RM<?= number_format($order['total'], 2) ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Payment Status</small>
                                <span class="badge bg-warning fs-6"><?= ucfirst($order['payment_status']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Order Status</small>
                                <span class="badge bg-info fs-6"><?= ucfirst($order['status']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i> 
                        We've sent a confirmation email to your registered email address with order details.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-receipt"></i> View Order Details
                        </a>
                        <a href="orders.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-box-seam"></i> My Orders
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-house"></i> Continue Shopping
                        </a>
                    </div>

                    <div class="mt-5 pt-4 border-top">
                        <h5 class="mb-3">What's Next?</h5>
                        <div class="row text-start">
                            <div class="col-md-4 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-1-circle-fill text-primary fs-3"></i>
                                    </div>
                                    <div>
                                        <strong>Order Processing</strong>
                                        <p class="small text-muted mb-0">We're preparing your items for shipment</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-2-circle-fill text-info fs-3"></i>
                                    </div>
                                    <div>
                                        <strong>Shipment</strong>
                                        <p class="small text-muted mb-0">Your order will be shipped soon</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-3-circle-fill text-success fs-3"></i>
                                    </div>
                                    <div>
                                        <strong>Delivery</strong>
                                        <p class="small text-muted mb-0">Delivered to your doorstep</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
