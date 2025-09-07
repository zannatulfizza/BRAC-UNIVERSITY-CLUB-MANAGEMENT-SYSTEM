<?php
session_start();
include 'config.php';

// Only admins can delete users
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent admin from deleting themselves
    $current_user_id = $conn->query("SELECT user_id FROM users WHERE EMAIL='{$conn->real_escape_string($_SESSION['email'])}'")->fetch_assoc()['user_id'];
    if ($id == $current_user_id) {
        echo "<script>alert('❌ You cannot delete yourself!'); window.location.href='user_dashboard.php';</script>";
        exit;
    }

    // Delete the user
    $conn->query("DELETE FROM users WHERE user_id = $id");
}

header("Location: user_dashboard.php");
exit;
?>
