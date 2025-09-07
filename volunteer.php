<?php
session_start();
include 'config.php';

// Check if logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Fetch user info
$email = $_SESSION['email'];
$result = $conn->query("SELECT * FROM users WHERE EMAIL='$email'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    header("Location: login.php");
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['comment'])) {
    $eventId = intval($_POST['event_id']);
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO volunteer_comments (event_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $eventId, $user['user_id'], $comment);
        $stmt->execute();
        // Refresh page
        header("Location: volunteer.php");
        exit;
    }
}

// Fetch upcoming events
$upcomingEvents = $conn->query("
    SELECT e.event_id, e.name, e.location, e.time
    FROM events e
    WHERE e.event_type = 'upcoming'
    ORDER BY e.time ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteers Needed - BRAC University</title>
<style>
body {
    font-family:"Segoe UI", sans-serif;
    margin:0; padding:0;
    min-height:100vh;
    display:flex; flex-direction:column;
}

/* Background with blur */
body::before {
    content:"";
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:url('images/profile.jpg') no-repeat center center fixed;
    background-size:cover;
    filter:blur(10px) brightness(0.7);
    z-index:-1;
}

/* Topbar */
.topbar {
    display:flex; justify-content: space-between; align-items:center;
    background: rgba(59,130,246,0.85);
    color:white; padding:0 30px; height:60px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
}
.topbar .actions a { margin-left:15px; text-decoration:none; color:white; font-weight:500; }

/* Container */
.container {
    padding:80px 20px;
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(300px,1fr));
    gap:25px; max-width:1200px; margin:auto;
}

/* Event Cards */
.event-card {
    background: rgba(172, 198, 218, 1);
    border-radius:15px;
    padding:20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.25);
    transition:transform 0.25s ease, box-shadow 0.25s ease;
}
.event-card:hover {
    transform:translateY(-8px) scale(1.02);
    box-shadow:0 12px 28px rgba(0,0,0,0.35);
}
.event-title { font-size:1.3rem; font-weight:bold; margin-bottom:10px; color:#1e3a8a; }

/* Comments */
.comment-box { margin-top:15px; }
.comment-box textarea {
    width:100%; padding:8px; border-radius:8px; border:1px solid #ccc;
    font-size:0.9rem; resize: vertical;
}
.comment-box button {
    margin-top:8px; background:#4f46e5; color:white; border:none;
    padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:500;
    transition:background 0.25s ease;
}
.comment-box button:hover { background:#4338ca; }

.comment {
    background:#f3f4f6; padding:6px 10px; border-radius:8px;
    margin-top:6px; font-size:0.9rem;
}
.comment strong { color:#111827; }
</style>
</head>
<body>

<div class="topbar">
    <h2>🙋 Looking For Volunteers</h2>
    <div class="actions">
        <a href="events.php">⬅ Back to Events</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <?php if($upcomingEvents && $upcomingEvents->num_rows > 0): ?>
        <?php while($event = $upcomingEvents->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-title"><?= htmlspecialchars($event['name']) ?></div>
                <div><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></div>
                <div><strong>Time:</strong> <?= date("F j, Y, g:i A", strtotime($event['time'])) ?></div>

                <!-- Comments -->
                <div class="comment-box">
                    <form method="POST">
                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                        <textarea name="comment" rows="2" placeholder="Write a comment..."></textarea>
                        <button type="submit">Post</button>
                    </form>

                    <?php
                    $cmtRes = $conn->query("
                        SELECT c.comment, u.NAME, c.created_at 
                        FROM volunteer_comments c 
                        JOIN users u ON c.user_id = u.user_id 
                        WHERE c.event_id=".$event['event_id']." 
                        ORDER BY c.created_at DESC
                    ");
                    if($cmtRes && $cmtRes->num_rows > 0){
                        while($cmt = $cmtRes->fetch_assoc()){
                            echo "<div class='comment'><strong>".htmlspecialchars($cmt['NAME']).":</strong> ".htmlspecialchars($cmt['comment'])."</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column:1/-1; text-align:center;">No upcoming events needing volunteers.</p>
    <?php endif; ?>
</div>

</body>
</html>
