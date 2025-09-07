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

$isAdmin = ($user['ROLE'] === 'admin');
$isPresident = ($user['ROLE'] === 'president');

if (!$isAdmin && !$isPresident) {
    die("Unauthorized");
}

$request_id = intval($_GET['id']);
$action = $_GET['action'];
$club_id = intval($_GET['club_id']);

if ($request_id > 0 && in_array($action, ['accept','reject'])) {
    if ($action == 'accept') {
        // Get user_id
        $res = $conn->query("SELECT user_id FROM join_requests WHERE request_id=$request_id");
        if ($res->num_rows > 0) {
            $user_id = $res->fetch_assoc()['user_id'];
            $conn->query("UPDATE join_requests SET status='accepted' WHERE request_id=$request_id");
            $conn->query("UPDATE users SET member_of=$club_id WHERE user_id=$user_id");
        }
    } else {
        $conn->query("UPDATE join_requests SET status='rejected' WHERE request_id=$request_id");
    }
}

header("Location: club_page.php?club_id=$club_id");
?>
