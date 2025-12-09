<?php

include_once '../config/db.php';
include_once 'admin_auth.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: products.php?error=invalid");
    exit;
}

$product_id = intval($_GET['id']);

// Fetch product details to get image path
$stmt = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php?error=notfound");
    exit;
}

$product = $result->fetch_assoc();

// Delete the product
$deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$deleteStmt->bind_param("i", $product_id);

if ($deleteStmt->execute()) {
    // Delete the image file if it exists
    if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
        unlink('../' . $product['image_url']);
    }
    
    header("Location: products.php?success=deleted");
} else {
    header("Location: products.php?error=deletefailed");
}

exit;
?>
