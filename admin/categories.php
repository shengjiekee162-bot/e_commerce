<?php

include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

// Handle success/error messages
$success = '';
$error = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'deleted') {
        $success = 'Category deleted successfully!';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') {
        $error = 'Invalid category ID.';
    } elseif ($_GET['error'] === 'notfound') {
        $error = 'Category not found.';
    } elseif ($_GET['error'] === 'inuse') {
        $error = 'Cannot delete category. Products are using this category.';
    } elseif ($_GET['error'] === 'deletefailed') {
        $error = 'Failed to delete category.';
    }
}

$sql = "SELECT * FROM categories ORDER BY created_at DESC";
$result = $conn->query($sql);

?>

<div class='content-wrapper'>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Category List</h2>
        <a href="add_category.php" class="btn btn-primary">＋ Add New Category</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="category-list">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= $row["id"]; ?></td>
                            <td><?= htmlspecialchars($row["name"]); ?></td>
                            <td><?= htmlspecialchars(substr($row["description"] ?? '', 0, 50)); ?></td>
                            <td><?= $row["created_at"]; ?></td>
                            <td>
                                <a href="edit_category.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="delete_category.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No categories found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>