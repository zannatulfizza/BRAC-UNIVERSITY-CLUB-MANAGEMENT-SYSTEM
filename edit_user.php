<?php
session_start();
include 'config.php';

// ---------- Auth ----------
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ---------- Validate ID ----------
if (!isset($_GET['id'])) {
    header("Location: user_dashboard.php");
    exit;
}

$id = intval($_GET['id']);

// ---------- Get user ----------
$user_result = $conn->query("SELECT * FROM users WHERE user_id = $id");
if ($user_result->num_rows == 0) {
    echo "<script>alert('User not found!'); window.location.href='user_dashboard.php';</script>";
    exit;
}
$user_to_edit = $user_result->fetch_assoc();

// ---------- Get all clubs ----------
$clubs_result = $conn->query("SELECT club_id, club_name FROM clubs");
$clubs = [];
while ($row = $clubs_result->fetch_assoc()) {
    $clubs[] = $row;
}

// ---------- Get existing club membership ----------
$club_member_result = $conn->query("SELECT club_id FROM club_members WHERE user_id=$id");
$current_club_id = ($club_member_result->num_rows > 0) ? $club_member_result->fetch_assoc()['club_id'] : null;

// ---------- Handle form ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = $conn->real_escape_string($_POST['name']);
    $email    = $conn->real_escape_string($_POST['email']);
    $role     = $conn->real_escape_string($_POST['role']);
    $club_id  = isset($_POST['club_id']) && $_POST['club_id'] !== '' ? intval($_POST['club_id']) : null;

    // Prevent admin from demoting themselves
    if ($user_to_edit['ROLE'] === 'admin' && $role !== 'admin' && $_SESSION['email'] === $user_to_edit['EMAIL']) {
        echo "<script>alert('❌ You cannot change your own admin role!'); window.location.href='edit_user.php?id=$id';</script>";
        exit;
    }

    // Check if club already has a president
    if ($role == 'president' && $club_id) {
        $check_president = $conn->query("
            SELECT u.user_id, u.NAME 
            FROM users u
            JOIN club_members cm ON u.user_id = cm.user_id
            WHERE cm.club_id = $club_id AND u.ROLE = 'president' AND u.user_id != $id
        ");
        if ($check_president->num_rows > 0) {
            $existing = $check_president->fetch_assoc();
            echo "<script>alert('❌ This club already has a president: {$existing['NAME']}'); window.location.href='edit_user.php?id=$id';</script>";
            exit;
        }
    }

    // Update user
    $conn->query("UPDATE users SET NAME='$name', EMAIL='$email', ROLE='$role' WHERE user_id=$id");

    // Sync club_members
    if ($role == 'president' || $role == 'general member') {
        if ($club_id) {
            if ($club_member_result->num_rows > 0) {
                $conn->query("UPDATE club_members SET club_id=$club_id, joined_at=NOW() WHERE user_id=$id");
            } else {
                $conn->query("INSERT INTO club_members (club_id, user_id, joined_at) VALUES ($club_id, $id, NOW())");
            }
        }
    } else {
        // Admins or other roles have no club membership
        $conn->query("DELETE FROM club_members WHERE user_id=$id");
    }

    echo "<script>alert('✅ User updated successfully'); window.location.href='user_dashboard.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
--bg:#f5f7fa;--card:#ffffffdd;--primary:#5b7fff;--accent:#ff7ab6;--text:#0f172a;--muted:#475569;--danger:#ef4444;
}
body {
    margin:0;
    font-family:'Rubik', sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
    background:#0f172a;
    overflow-x:hidden;
}

/* Background */
body::before {
    content:"";
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    background:url('images/profile.jpg') no-repeat center center;
    background-size:cover;
    filter:blur(4px) brightness(0.55);
    z-index:-1;
    animation: bgZoom 40s ease-in-out infinite alternate;
}
@keyframes bgZoom {
    0% { transform:scale(1) translate(0,0);}
    50% { transform:scale(1.05) translate(5px,5px);}
    100% { transform:scale(1) translate(0,0);}
}

/* Form Card */
.form-box {
    background: var(--card);
    border-radius:25px;
    padding:40px 50px;
    width:450px;
    box-shadow:0 12px 35px rgba(0,0,0,0.25);
    text-align:center;
    animation: fadeUp 1s ease;
    transition:all 0.5s ease;
}
.form-box:hover {
    transform:scale(1.02) translateY(-8px);
    box-shadow:0 18px 50px rgba(0,0,0,0.3);
}
h2 {
    font-size:28px;
    margin-bottom:20px;
    color:var(--text);
}

/* Inputs */
input, select {
    width:100%;
    padding:12px;
    margin:12px 0;
    border-radius:12px;
    border:1px solid #ccc;
    font-size:16px;
    transition:0.3s;
}
input:focus, select:focus {
    border-color:var(--primary);
    outline:none;
    box-shadow:0 0 8px rgba(91,127,255,0.4);
}

/* Buttons */
button {
    width:100%;
    padding:14px;
    font-size:16px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    background:linear-gradient(135deg,var(--primary),#4f46e5);
    color:#fff;
    font-weight:600;
    transition:0.3s;
}
button:hover {
    transform:translateY(-2px) scale(1.03);
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
}

/* Back */
.back {
    margin-top:15px;
}
.back a {
    text-decoration:none;
    color:white;
    background:var(--accent);
    padding:12px 18px;
    border-radius:12px;
    display:inline-block;
    transition:0.3s;
}
.back a:hover {
    background:#e05595;
    transform:scale(1.05);
}

/* Club Dropdown Hide */
.hidden { display:none; }

/* Animations */
@keyframes fadeUp {
    0%{opacity:0; transform:translateY(50px);}
    100%{opacity:1; transform:translateY(0);}
}

/* Responsive */
@media(max-width:500px){
    .form-box { width:90%; padding:30px 20px; }
}
</style>
</head>
<body>
<div class="form-box">
    <h2>Edit User</h2>
    <form method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($user_to_edit['NAME']) ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($user_to_edit['EMAIL']) ?>" required>

        <select name="role" id="role" onchange="toggleClubDropdown()" required>
            <option value="general member" <?= $user_to_edit['ROLE']=='general member'?'selected':'' ?>>General Member</option>
            <option value="president" <?= $user_to_edit['ROLE']=='president'?'selected':'' ?>>President</option>
            <option value="admin" <?= $user_to_edit['ROLE']=='admin'?'selected':'' ?>>Admin</option>
        </select>

        <div id="club-wrapper">
            <select name="club_id" id="club_id">
                <option value="">-- Select Club --</option>
                <?php foreach($clubs as $club): ?>
                    <option value="<?= $club['club_id'] ?>" <?= ($current_club_id==$club['club_id'])?'selected':'' ?>>
                        <?= htmlspecialchars($club['club_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">✅ Update User</button>
    </form>
    <div class="back"><a href="user_dashboard.php">⬅ Back to Dashboard</a></div>
</div>

<script>
function toggleClubDropdown() {
    const role = document.getElementById('role').value;
    const wrapper = document.getElementById('club-wrapper');
    if(role==='admin'){
        wrapper.classList.add('hidden');
        document.getElementById('club_id').value='';
    } else {
        wrapper.classList.remove('hidden');
    }
}
toggleClubDropdown(); // on page load
</script>
</body>
</html>
