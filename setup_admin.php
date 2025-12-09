<?php
include_once "config/db.php";

// Create admin user with email 'admin' and password 'admin'
$email = 'admin@ecommerce.local';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user exists
$checkSql = "SELECT id FROM users WHERE email = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    // Update existing admin user
    $updateSql = "UPDATE users SET password = ?, role = 'admin' WHERE email = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('ss', $hashed_password, $email);
    
    if ($updateStmt->execute()) {
        echo "Admin password updated successfully!\n";
        echo "Email: " . $email . "\n";
        echo "Password: " . $password . "\n";
    } else {
        echo "Error updating admin: " . $updateStmt->error;
    }
} else {
    // Create new admin user
    $name = 'Admin User';
    $phone = '0123456789';
    $status = 'active';
    $role = 'admin';
    
    $insertSql = "INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param('ssssss', $name, $email, $hashed_password, $phone, $role, $status);
    
    if ($insertStmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Email: " . $email . "\n";
        echo "Password: " . $password . "\n";
    } else {
        echo "Error creating admin: " . $insertStmt->error;
    }
}
?>
