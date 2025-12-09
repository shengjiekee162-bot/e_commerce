<?php

include_once '../config/db.php';
include_once 'admin_auth.php';

// Check if category ID is provided
if (!isset($_GET['id'])) {
    header("Location: categories.php?error=invalid");
    exit;
}

$category_id = intval($_GET['id']);

// Check if category exists
$stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: categories.php?error=notfound");
    exit;
}

// Check if any products use this category
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
$checkStmt->bind_param("i", $category_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$checkRow = $checkResult->fetch_assoc();

if ($checkRow['count'] > 0) {
    header("Location: categories.php?error=inuse");
    exit;
}

// Delete the category
$deleteStmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$deleteStmt->bind_param("i", $category_id);

if ($deleteStmt->execute()) {
    header("Location: categories.php?success=deleted");
} else {
    header("Location: categories.php?error=deletefailed");
}

exit;
?>
