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

// Fetch clubs for dropdowns
$clubsRes = $conn->query("SELECT * FROM clubs ORDER BY club_name");

// --- Handle Add Event ---
if (isset($_POST['add_event'])) {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $time = $_POST['time'];
    $type = trim($_POST['event_type']);
    $club_id = intval($_POST['club_id']);
    $description = trim($_POST['description']);

    if ($name && $location && $time && $type && $club_id && $description) {
        $stmt = $conn->prepare("INSERT INTO events (name, location, time, event_type, club_id) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssssi", $name, $location, $time, $type, $club_id);
        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;
            $stmtDesc = $conn->prepare("INSERT INTO event_descriptions (event_id, description) VALUES (?,?)");
            $stmtDesc->bind_param("is", $event_id, $description);
            $stmtDesc->execute();

            $stmtHost = $conn->prepare("INSERT INTO event_hosts (event_id, club_id) VALUES (?,?)");
            $stmtHost->bind_param("ii", $event_id, $club_id);
            $stmtHost->execute();

            $msg = "✅ Event '$name' added successfully!";
        } else {
            $msg = "❌ Failed to add event: " . $conn->error;
        }
    } else {
        $msg = "❌ All fields are required.";
    }
}

// --- Handle Update Event ---
if (isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $time = $_POST['time'];
    $type = trim($_POST['event_type']);
    $club_id = intval($_POST['club_id']);
    $description = trim($_POST['description']);

    if ($name && $location && $time && $type && $club_id && $description) {
        $stmt = $conn->prepare("UPDATE events SET name=?, location=?, time=?, event_type=?, club_id=? WHERE event_id=?");
        $stmt->bind_param("ssssii", $name, $location, $time, $type, $club_id, $event_id);
        $stmt->execute();

        $stmtDesc = $conn->prepare("UPDATE event_descriptions SET description=? WHERE event_id=?");
        $stmtDesc->bind_param("si", $description, $event_id);
        $stmtDesc->execute();

        $stmtHost = $conn->prepare("UPDATE event_hosts SET club_id=? WHERE event_id=?");
        $stmtHost->bind_param("ii", $club_id, $event_id);
        $stmtHost->execute();

        $msg = "✅ Event '$name' updated successfully!";
    } else {
        $msg = "❌ All fields are required.";
    }
}

// --- Handle Delete Event ---
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    $conn->query("DELETE FROM event_descriptions WHERE event_id=$event_id");
    $conn->query("DELETE FROM event_hosts WHERE event_id=$event_id");
    $conn->query("DELETE FROM events WHERE event_id=$event_id");
    $msg = "✅ Event deleted successfully!";
}

// Fetch events by type
function getEventsByType($conn, $type) {
    $stmt = $conn->prepare("SELECT e.event_id, e.name, e.location, e.time, e.event_type, e.club_id, c.club_name, d.description
                            FROM events e
                            JOIN clubs c ON e.club_id=c.club_id
                            LEFT JOIN event_descriptions d ON e.event_id=d.event_id
                            WHERE e.event_type=?
                            ORDER BY e.time DESC");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    return $stmt->get_result();
}

$upcomingEvents = getEventsByType($conn, 'upcoming');
$ongoingEvents = getEventsByType($conn, 'current');
$previousEvents = getEventsByType($conn, 'past');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>manage_event - Admin</title>
<style>
body { font-family: Arial,sans-serif; background:#f5f7fa; margin:0; padding:0; }
.topbar { display:flex; justify-content: space-between; align-items:center; background:#1e3a8a; padding:15px 30px; color:white; }
.topbar a { color:white; margin-left:15px; text-decoration:none; font-weight:600; }
.admin-panel { max-width:1100px; margin:40px auto; background:#fff; padding:30px; border-radius:20px; }
.admin-panel h2 { text-align:center; margin-bottom:25px; color:#1e40af; }
.form-section { display:flex; flex-wrap: wrap; gap:15px; justify-content:center; margin-bottom:30px; }
.form-section input, .form-section select, .form-section textarea { padding:12px; border-radius:12px; border:1px solid #cbd5e1; font-size:16px; min-width:200px; }
.form-section button { padding:12px 20px; border:none; border-radius:12px; background:#1e40af; color:white; cursor:pointer; }
.form-section button:hover { background:#1e3a8a; }
.alert { text-align:center; padding:12px; margin:20px auto; max-width:600px; border-radius:12px; font-weight:600; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
.alert-error { background:#fee2e2; color:#991b1b; border:1px solid #f87171; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:12px; border:1px solid #e5e7eb; text-align:center; }
th { background:#1e40af; color:white; }
button.crud-btn { padding:6px 12px; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
button.edit { background:#10b981; color:white; }
button.delete { background:#ef4444; color:white; }
</style>
</head>
<body>

<div class="topbar">
    <div>Welcome, <?= htmlspecialchars($user['NAME']); ?></div>
    <div>
        <a href="user_dashboard.php">🏠 Dashboard</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="admin-panel">
    <h2>manage_event</h2>
    <?php if(!empty($msg)): ?>
        <div class="alert <?= strpos($msg,'✅')===0?'alert-success':'alert-error' ?>">
            <?= htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Add Event -->
    <form method="POST" class="form-section">
        <input type="text" name="name" placeholder="Event Name" required>
        <input type="text" name="location" placeholder="Location" required>
        <input type="datetime-local" name="time" required>
        <select name="event_type" required>
            <option value="">Select Type</option>
            <option value="upcoming">Upcoming</option>
            <option value="current">Ongoing</option>
            <option value="past">Previous</option>
        </select>
        <select name="club_id" required>
            <?php
            $clubsRes->data_seek(0);
            while($club = $clubsRes->fetch_assoc()): ?>
                <option value="<?= $club['club_id'] ?>"><?= htmlspecialchars($club['club_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <textarea name="description" placeholder="Event Description" required></textarea>
        <button type="submit" name="add_event">➕ Add Event</button>
    </form>

    <?php
    $sections = [
        'Upcoming Events' => $upcomingEvents,
        'Ongoing Events' => $ongoingEvents,
        'Previous Events' => $previousEvents
    ];
    foreach($sections as $title => $eventsRes): ?>
        <h3><?= $title ?></h3>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Location</th><th>Time</th><th>Club</th><th>Description</th><th>Actions</th>
            </tr>
            <?php while($event = $eventsRes->fetch_assoc()): ?>
            <tr>
                <form method="POST">
                <td><?= $event['event_id'] ?><input type="hidden" name="event_id" value="<?= $event['event_id'] ?>"></td>
                <td><input type="text" name="name" value="<?= htmlspecialchars($event['name']) ?>" required></td>
                <td><input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" required></td>
                <td><input type="datetime-local" name="time" value="<?= date('Y-m-d\TH:i', strtotime($event['time'])) ?>" required></td>
                <td>
                    <select name="club_id" required>
                        <?php
                        $clubsRes->data_seek(0);
                        while($club = $clubsRes->fetch_assoc()): ?>
                            <option value="<?= $club['club_id'] ?>" <?= $club['club_id']==$event['club_id']?'selected':'' ?>>
                                <?= htmlspecialchars($club['club_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </td>
                <td><textarea name="description" required><?= htmlspecialchars($event['description']) ?></textarea></td>
                <td>
                    <input type="hidden" name="event_type" value="<?= htmlspecialchars($event['event_type']) ?>">
                    <button type="submit" name="update_event" class="crud-btn edit">✏️ Update</button>
                    <button type="submit" name="delete_event" class="crud-btn delete">🗑 Delete</button>
                </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endforeach; ?>
</div>
</body>
</html>
