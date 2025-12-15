<?php
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===================== ADDRESS ===================== */
    if (isset($_POST['action']) && $_POST['action'] === 'save_address') {

        $address_id     = $_POST['address_id'] ?? null;
        $full_name      = trim($_POST['full_name']);
        $phone          = trim($_POST['phone']);
        $address_line1  = trim($_POST['address_line1']);
        $address_line2  = trim($_POST['address_line2']);
        $city           = trim($_POST['city']);
        $state          = trim($_POST['state']);
        $postal_code    = trim($_POST['postal_code']);
        $is_default     = isset($_POST['is_default']) ? 1 : 0;

        if ($is_default) {
            $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        if ($address_id) {
            $stmt = $conn->prepare("
                UPDATE addresses 
                SET full_name=?, phone=?, address_line1=?, address_line2=?, city=?, state=?, postal_code=?, is_default=? 
                WHERE id=? AND user_id=?
            ");
            $stmt->bind_param(
                "sssssssiii",
                $full_name,
                $phone,
                $address_line1,
                $address_line2,
                $city,
                $state,
                $postal_code,
                $is_default,
                $address_id,
                $user_id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO addresses 
                (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "isssssssi",
                $user_id,
                $full_name,
                $phone,
                $address_line1,
                $address_line2,
                $city,
                $state,
                $postal_code,
                $is_default
            );
        }

        $stmt->execute() ? $message = "Address saved successfully!" : $error = "Failed to save address.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_address') {
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $_POST['address_id'], $user_id);
        $stmt->execute();
        $message = "Address deleted successfully!";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'set_default_address') {
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $_POST['address_id'], $user_id);
        $stmt->execute();

        $message = "Default address updated!";
    }

    /* ===================== PAYMENT ===================== */
    if (isset($_POST['action']) && $_POST['action'] === 'save_payment') {

        $method_type = $_POST['method_type'];
        $is_default  = isset($_POST['is_default_payment']) ? 1 : 0;

        if ($is_default) {
            $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("
            INSERT INTO payment_methods 
            (user_id, method_type, card_number, card_holder, expiry_date, bank_name, account_number, ewallet_type, ewallet_number, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssssi",
            $user_id,
            $method_type,
            $_POST['card_number'] ?? null,
            $_POST['card_holder'] ?? null,
            $_POST['expiry_date'] ?? null,
            $_POST['bank_name'] ?? null,
            $_POST['account_number'] ?? null,
            $_POST['ewallet_type'] ?? null,
            $_POST['ewallet_number'] ?? null,
            $is_default
        );

        $stmt->execute() ? $message = "Payment method added!" : $error = "Failed to add payment.";
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_payment') {
        $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $_POST['payment_id'], $user_id);
        $stmt->execute();
        $message = "Payment method deleted!";
    }
}

/* ===================== DATA ===================== */
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$addresses = $stmt->get_result();

$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_methods = $stmt->get_result();
?>
