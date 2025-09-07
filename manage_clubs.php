<?php
include 'config.php';
session_start();

// Admin check
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}
$email = $_SESSION['email'];
$result = $conn->query("SELECT * FROM users WHERE EMAIL='$email'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if ($user['ROLE'] !== 'admin') {
        die("❌ Access denied. Admins only.");
    }
} else {
    header("Location: login.php");
    exit;
}

$msg = '';

// Handle Add Club
if (isset($_POST['add_club'])) {
    $name = trim($_POST['club_name']);
    if (!empty($name)) {
        $check = $conn->query("SELECT * FROM clubs WHERE club_name='$name'");
        if ($check->num_rows > 0) {
            $msg = "❌ Club '$name' already exists!";
        } else {
            $sql = "INSERT INTO clubs (club_name) VALUES ('$name')";
            if ($conn->query($sql)) {
                $msg = "✅ Club '$name' added successfully!";
            } else {
                $msg = "❌ Failed to add club '$name'. Error: " . $conn->error;
            }
        }
    } else {
        $msg = "❌ Club name cannot be empty.";
    }
}

// Handle Update Club
if (isset($_POST['update_club'])) {
    $old_name = trim($_POST['old_club_name']);
    $new_name = trim($_POST['new_club_name']);
    if (!empty($new_name)) {
        // Check if new name already exists
        $check = $conn->query("SELECT * FROM clubs WHERE club_name='$new_name'");
        if ($check->num_rows > 0) {
            $msg = "❌ Club name '$new_name' already exists!";
        } else {
            $updated = $conn->query("UPDATE clubs SET club_name='$new_name' WHERE club_name='$old_name'");
            if ($updated && $conn->affected_rows > 0) {
                $msg = "✅ Club '$old_name' updated to '$new_name'!";
            } else {
                $msg = "❌ Club '$old_name' not found or update failed.";
            }
        }
    } else {
        $msg = "❌ New club name cannot be empty.";
    }
}

// Handle Delete Club
if (isset($_POST['delete_club'])) {
    $name = trim($_POST['club_name']);
    if (!empty($name)) {
        $deleted = $conn->query("DELETE FROM clubs WHERE club_name='$name'");
        if ($deleted && $conn->affected_rows > 0) {
            $msg = "✅ Club '$name' deleted successfully!";
        } else {
            $msg = "❌ Club '$name' not found.";
        }
    } else {
        $msg = "❌ Club name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Clubs - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg,#e0f7fa,#fff9c4); margin:0; padding:0; }
.topbar { display:flex; justify-content: space-between; align-items:center; background: linear-gradient(90deg,#1976d2,#42a5f5); padding:15px 30px; color:white; border-radius:0 0 10px 10px; box-shadow:0 3px 10px rgba(0,0,0,0.1);}
.topbar h2 { margin:0; font-size:18px; }
.topbar .actions a { margin-left:15px; text-decoration:none; color:white; font-weight:600; transition:0.3s; }
.topbar .actions a:hover { color:#ffeb3b; }

.admin-panel { max-width:900px; margin:40px auto; background:#fff; padding:30px; border-radius:20px; box-shadow:0 8px 25px rgba(0,0,0,0.15); }
.admin-panel h2 { text-align:center; margin-bottom:25px; font-size:28px; color:#1976d2; }

.form-section { display:flex; flex-wrap: wrap; gap:15px; justify-content:center; margin-bottom:25px; }
.form-section input { padding:12px 15px; border-radius:12px; border:1px solid #ccc; font-size:16px; min-width:220px; }
.form-section button { padding:12px 20px; border-radius:12px; border:none; font-size:16px; font-weight:600; cursor:pointer; background:#1976d2; color:white; transition:0.3s; }
.form-section button:hover { background:#0d47a1; }

.alert { text-align:center; padding:12px; margin:20px auto; max-width:600px; border-radius:12px; font-weight:600; font-size:16px; }
.alert-success { background:#d0f8ce; color:#2e7d32; border:1px solid #81c784; }
.alert-error { background:#ffcdd2; color:#c62828; border:1px solid #e57373; }
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <h2>Welcome, <?= htmlspecialchars($user['NAME']); ?></h2>
    <div class="actions">
        <a href="profile.php">👤 Profile</a>
        <a href="all_clubs.php">🎓 All Clubs</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="admin-panel">
    <h2>Manage Clubs</h2>

    <?php if(!empty($msg)): ?>
        <div class="alert <?= strpos($msg,'✅')===0?'alert-success':'alert-error' ?>">
            <?= htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Add Club -->
    <form method="POST" class="form-section">
        <input type="text" name="club_name" placeholder="New Club Name" required>
        <button type="submit" name="add_club">Add Club</button>
    </form>

    <!-- Update Club Name -->
    <form method="POST" class="form-section">
        <input type="text" name="old_club_name" placeholder="Existing Club Name" required>
        <input type="text" name="new_club_name" placeholder="New Club Name" required>
        <button type="submit" name="update_club">Update Club Name</button>
    </form>

    <!-- Delete Club -->
    <form method="POST" class="form-section">
        <input type="text" name="club_name" placeholder="Club Name to Delete" required>
        <button type="submit" name="delete_club">Delete Club</button>
    </form>
</div>

</body>
</html>
