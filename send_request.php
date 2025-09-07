<?php
include 'config.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$userQuery = $conn->query("SELECT * FROM users WHERE EMAIL='$email'");
$user = $userQuery->fetch_assoc();

$club_id = intval($_POST['club_id']);
if ($club_id <= 0) {
    header("Location: all_clubs.php");
    exit;
}

// Check if already requested
$check = $conn->query("SELECT * FROM join_requests WHERE user_id={$user['user_id']} AND club_id=$club_id");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO join_requests (user_id, club_id, status) VALUES ({$user['user_id']}, $club_id, 'pending')");
}

header("Location: club_page.php?club_id=$club_id");
?>
