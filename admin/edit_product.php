<?php

include_once '../config/db.php';
include_once 'admin_header.php';
include_once 'admin_auth.php';

$success = "";
$error = "";
$product = null;

// Get product ID
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();

// Fetch categories
$category = $conn->query("SELECT * FROM categories");

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == "POST"){
    $category_id = $_POST['category'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image_path = $product['image_url']; // Keep existing image by default

    // Handle image upload
    if(!empty($_FILES['image']['name'])){
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $target_dir = "../uploads/products/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_path = $target_dir . $filename;

        if(move_uploaded_file($_FILES['image']['tmp_name'], $target_path)){
            // Delete old image if exists
            if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                unlink('../' . $product['image_url']);
            }
            $image_path = 'uploads/products/' . $filename;
        } else {
            $error = "Image upload failed.";
        }
    }

    if($error === ""){
        $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, image_url=? WHERE id=?");
        $stmt->bind_param("issdiis", $category_id, $product_name, $description, $price, $stock, $image_path, $product_id);
        
        if($stmt->execute()){
            $success = "Product updated successfully!";
            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

?>

<div class='content-wrapper'>
    <div>
        <h2>Edit Product</h2>
    </div>
    <div>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>  
            </div>
        <?php endif; ?>
    </div>
  
    <div>
        <form action="edit_product.php?id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <div class="mb-3">
                    <label for="category_id">Category:</label>
                    <select class="form-control" name="category" id="category_id" required>
                        <option value=""> -- SELECT CATEGORY --</option>
                        <?php while ($cat = $category->fetch_assoc()) : ?>
                            <option value="<?= $cat['id']?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                <?= $cat['name']?>
                            </option>
                        <?php endwhile;?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="product_name" class="form-control" 
                           value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="price">Price:</label>
                    <input type="number" step="0.01" id="price" name="price" class="form-control" 
                           value="<?= $product['price'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" class="form-control" 
                           value="<?= $product['stock'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="image">Image:</label>
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="mb-2">
                            <img src="../<?= $product['image_url'] ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover;">
                            <p class="text-muted">Current image</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" class="form-control">
                    <small class="text-muted">Leave empty to keep current image</small>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='products.php'">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
