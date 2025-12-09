<?php
include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM orders WHERE id = $delete_id");
    $conn->query("DELETE FROM order_items WHERE order_id = $delete_id");
}

$sql = "SELECT orders.*, users.name AS user_name, users.email FROM orders INNER JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC";
$result = $conn->query($sql);
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Order List</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>RM <?= number_format(isset($row['total']) && $row['total'] !== null ? $row['total'] : 0, 2) ?></td>
                    <td>
                        <form method="POST" action="orders.php" class="d-inline">
                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                            <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                <?php foreach(['pending','paid','shipped','completed','cancelled'] as $status): ?>
                                    <option value="<?= $status ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a href="orders.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this order?');">Delete</a>
                        <a href="order_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
