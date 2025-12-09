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
    <style>
        .order-list-table {
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(90deg, #fff 0%, #eee 100%);
        }
        .order-list-table th {
            background: linear-gradient(90deg, #222 0%, #888 100%);
            color: #fff;
            font-weight: bold;
            letter-spacing: 1px;
            font-size: 1.08em;
            border: none;
        }
        .order-list-table th, .order-list-table td {
            vertical-align: middle;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .order-list-table tbody tr {
            cursor: pointer;
            background: linear-gradient(90deg, #fff 0%, #eee 100%);
            color: #222;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .order-list-table tbody tr:hover {
            background: linear-gradient(90deg, #eee 0%, #ccc 100%);
            color: #000;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: scale(1.01);
        }
        .order-list-table tbody tr.active-row {
            background: linear-gradient(90deg, #000 0%, #444 100%);
            color: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.18);
            transform: scale(1.02);
        }
        .btn {
            position: relative;
            overflow: hidden;
            background: linear-gradient(90deg, #222 0%, #555 100%);
            color: #fff;
            border: none;
            transition: box-shadow 0.2s, transform 0.2s, background 0.2s, color 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #000 0%, #333 100%);
            color: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.18);
            transform: translateY(-2px) scale(1.06);
        }
    </style>
    <script>
        // 行点击高亮
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.order-list-table tbody tr').forEach(function(row) {
                row.addEventListener('click', function() {
                    document.querySelectorAll('.order-list-table tbody tr').forEach(function(r) {
                        r.classList.remove('active-row');
                    });
                    row.classList.add('active-row');
                });
            });
        });
    </script>
    <div class="table-responsive">
        <table class="order-list-table table table-striped table-bordered">
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
