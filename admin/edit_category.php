<?php

include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

$success = "";
$error = "";
$category = null;

// Get category ID
if (!isset($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$category_id = intval($_GET['id']);

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: categories.php");
    exit;
}

$category = $result->fetch_assoc();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description'] ?? '');

    if(empty($category_name)) {
        $error = "Category name cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $category_name, $description, $category_id);
        
        if($stmt->execute()) {
            $success = "Category updated successfully!";
            // Refresh category data
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
        } else {
            $error = "Error updating category: " . $conn->error;
        }
    }
}

?>

<div class='content-wrapper'>
    <div>
        <h2>Edit Category</h2>
    </div>
    
    <div>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>  
            </div>
        <?php endif; ?>
    </div>
    
    <div>
        <form method="POST" action="edit_category.php?id=<?= $category_id ?>">
            <div class="form-group">
                <div class="mb-3">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="category_name" class="form-control" 
                           value="<?= htmlspecialchars($category['name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='categories.php'">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
