<?php
include 'config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['ok'=>false,'msg'=>'Not logged in']); exit;
}

$action = $_POST['action'] ?? '';
$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
if ($club_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid club']); exit; }

// Current user
$email = $_SESSION['email'];
$u = $conn->prepare("SELECT user_id, NAME, ROLE, member_of FROM users WHERE EMAIL=?");
$u->bind_param("s", $email);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$u->close();

if (!$user) { echo json_encode(['ok'=>false,'msg'=>'User not found']); exit; }

$user_id = (int)$user['user_id'];
$user_role = strtolower($user['ROLE'] ?? '');
$user_member_of = isset($user['member_of']) ? (int)$user['member_of'] : null;

$isAdmin = ($user_role === 'admin');
$isPresident = ($user_role === 'president' && $user_member_of === $club_id);

// Helpers
function is_member($conn,$club_id,$uid){
  $q=$conn->prepare("SELECT 1 FROM club_members WHERE club_id=? AND user_id=?");
  $q->bind_param("ii",$club_id,$uid);
  $q->execute(); $q->store_result();
  $ans = $q->num_rows>0; $q->close(); return $ans;
}
function has_pending($conn,$club_id,$uid){
  $q=$conn->prepare("SELECT status FROM join_requests WHERE club_id=? AND user_id=?");
  $q->bind_param("ii",$club_id,$uid);
  $q->execute(); $r=$q->get_result()->fetch_assoc(); $q->close();
  return $r ? $r['status'] : null;
}

if ($action === 'join') {
    // Admin/President cannot request to join
    if ($isAdmin || $isPresident) {
        echo json_encode(['ok'=>false,'msg'=>'Admins/Presidents cannot request to join']); exit;
    }
    // Already a member?
    if (is_member($conn,$club_id,$user_id)) {
        echo json_encode(['ok'=>false,'msg'=>'You are already a member']); exit;
    }
    // Existing request?
    $st = has_pending($conn,$club_id,$user_id);
    if ($st) {
        echo json_encode(['ok'=>true,'status'=>$st,'msg'=>'Request already exists']); exit;
    }
    // Insert
    $ins = $conn->prepare("INSERT INTO join_requests (user_id, club_id, status) VALUES (?, ?, 'pending')");
    $ins->bind_param("ii",$user_id,$club_id);
    $ins->execute(); $ins->close();
    echo json_encode(['ok'=>true,'status'=>'pending']); exit;
}

if ($action === 'approve') {
    // Only admin or this club’s president
    if (!($isAdmin || $isPresident)) {
        echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); exit;
    }
    $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    if ($target_user_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid user']); exit; }

    // Ensure pending exists
    $q = $conn->prepare("SELECT request_id FROM join_requests WHERE club_id=? AND user_id=? AND status='pending'");
    $q->bind_param("ii",$club_id,$target_user_id);
    $q->execute(); $req = $q->get_result()->fetch_assoc(); $q->close();
    if(!$req){ echo json_encode(['ok'=>false,'msg'=>'No pending request']); exit; }

    // Add to members (ignore duplicates)
    $ins = $conn->prepare("INSERT IGNORE INTO club_members (club_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii",$club_id,$target_user_id);
    $ins->execute(); $ins->close();

    // Delete request
    $del = $conn->prepare("DELETE FROM join_requests WHERE request_id=?");
    $del->bind_param("i",$req['request_id']);
    $del->execute(); $del->close();

    // If the user's global role is empty/'none'/NULL, set to 'general member'
    $roleCheck = $conn->prepare("SELECT ROLE FROM users WHERE user_id=?");
    $roleCheck->bind_param("i",$target_user_id);
    $roleCheck->execute(); $r = $roleCheck->get_result()->fetch_assoc(); $roleCheck->close();
    $curr = strtolower(trim($r['ROLE'] ?? ''));
    if ($curr === '' || $curr === 'none' || $curr === 'null') {
        $upd = $conn->prepare("UPDATE users SET ROLE='general member' WHERE user_id=?");
        $upd->bind_param("i",$target_user_id);
        $upd->execute(); $upd->close();
    }

    echo json_encode(['ok'=>true]); exit;
}

if ($action === 'reject') {
    if (!($isAdmin || $isPresident)) {
        echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); exit;
    }
    $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    if ($target_user_id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid user']); exit; }

    $del = $conn->prepare("DELETE FROM join_requests WHERE club_id=? AND user_id=?");
    $del->bind_param("ii",$club_id,$target_user_id);
    $del->execute(); $del->close();

    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
