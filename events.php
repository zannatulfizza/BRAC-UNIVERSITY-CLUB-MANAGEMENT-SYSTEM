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

// Fetch events by type
function getEvents($conn, $type){
    $stmt = $conn->prepare("
        SELECT e.event_id, e.name, e.location, e.time, e.event_type
        FROM events e
        WHERE e.event_type = ?
        ORDER BY e.time DESC
    ");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    return $stmt->get_result();
}

$upcomingEvents = getEvents($conn, 'upcoming');
$ongoingEvents  = getEvents($conn, 'current');
$previousEvents = getEvents($conn, 'past');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events - BRAC University</title>
<style>
/* ===== Background ===== */
body {
    font-family:"Segoe UI", sans-serif;
    margin:0;
    padding:0;
    min-height:100vh;
    background: url('images/dashboard.jpg') no-repeat center center fixed;
    background-size: cover;
    display:flex;
    flex-direction: column;
}

/* Overlay */
body::before {
    content:"";
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(255,255,255,0.3);
    backdrop-filter: blur(5px);
    z-index:-1;
}

/* ===== Topbar ===== */
.topbar {
    display:flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(59,130,246,0.9);
    color:white;
    padding:0 30px;
    height:5vh;
    min-height:50px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
}
.topbar h2 {
    margin:0;
    font-size:1rem;
    font-weight:600;
}
.topbar .actions a {
    margin-left:15px;
    text-decoration:none;
    color:white;
    font-weight:bold;
    font-size:0.95rem;
    transition:0.3s;
}
.topbar .actions a:hover {
    color:#ffd700;
}

/* 🚨 Flashy Volunteers Button */
.topbar .volunteer-btn {
    background: linear-gradient(135deg, #ff0000, #ff9900);
    padding: 8px 15px;
    border-radius: 12px;
    font-weight: bold;
    color: white !important;
    box-shadow: 0 0 15px rgba(255,0,0,0.8);
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
  0% { transform: scale(1); box-shadow: 0 0 10px rgba(255,0,0,0.6); }
  50% { transform: scale(1.1); box-shadow: 0 0 25px rgba(255,153,0,1); }
  100% { transform: scale(1); box-shadow: 0 0 10px rgba(255,0,0,0.6); }
}

/* ===== Container ===== */
.container {
    padding:80px 20px 60px;
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(300px,1fr));
    gap:25px;
    max-width:1300px;
    width:100%;
    margin:auto;
}

/* ===== Section Titles ===== */
.section-title {
    grid-column:1/-1;
    font-size:2rem;
    font-weight:700;
    margin:25px 0 15px;
    color:#1f2937;
    text-align:center;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
    opacity:0;
    animation: fadeIn 1s ease forwards;
}

/* ===== Event Cards ===== */
.event-card {
    background: rgba(255,255,255,0.9);
    border-radius:20px;
    padding:30px 25px;
    box-shadow:0 10px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    opacity:0;
    transform: translateY(20px);
    animation: floatIn 0.8s ease forwards;
}
.event-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow:0 16px 35px rgba(0,0,0,0.25);
}

/* ===== Event Titles & Info ===== */
.event-title {
    font-size:1.3rem;
    font-weight:700;
    margin-bottom:12px;
    color:#1f2937;
}
.event-info {
    font-size:1rem;
    margin-bottom:10px;
    color:#374151;
}

/* ===== Toggle Buttons ===== */
.toggle-btn {
    background: linear-gradient(135deg,#facc15,#f59e0b);
    border:none;
    color:white;
    font-weight:600;
    padding:10px 14px;
    border-radius:12px;
    cursor:pointer;
    margin-bottom:10px;
    width:100%;
    transition: all 0.3s ease;
}
.toggle-btn:hover {
    background: linear-gradient(135deg,#f59e0b,#facc15);
    transform: translateY(-3px);
    box-shadow:0 6px 15px rgba(0,0,0,0.2);
}

/* ===== Event Descriptions & Hosts ===== */
.event-desc, .event-hosts {
    display:none;
    margin-top:10px;
    padding:12px;
    background:#fef9c3;
    border-radius:12px;
    font-size:0.95rem;
    color:#1f2937;
}

/* ===== Animations ===== */
@keyframes floatIn {
    0% { opacity:0; transform:translateY(20px); }
    100% { opacity:1; transform:translateY(0); }
}
@keyframes fadeIn {
    0% { opacity:0; }
    100% { opacity:1; }
}

/* ===== Responsive ===== */
@media(max-width:600px){
    .container { padding:100px 15px 50px; }
    .section-title { font-size:1.5rem; }
}
</style>
<script>
function toggleDesc(id){
    const el = document.getElementById(id);
    el.style.display = (el.style.display==="block") ? "none" : "block";
}
</script>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <h2>Welcome, <?= htmlspecialchars($user['NAME']); ?></h2>
    <div class="actions">
        <a href="profile.php">👤 Profile</a>
        <?php if($user['ROLE']==='admin'){ ?>
            <a href="manage_event.php">🛠 Manage Events</a>
        <?php } ?>
        <a href="user_dashboard.php">🏠 Dashboard</a>
        
        <!-- 🚨 New Flashy Button -->
        <a href="volunteer.php" class="volunteer-btn">🙋 Looking For Volunteers</a>

        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="container">

    <!-- Upcoming Events -->
    <div class="section-title">⏭ Upcoming Events</div>
    <?php if($upcomingEvents && $upcomingEvents->num_rows > 0): ?>
        <?php while($event = $upcomingEvents->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-title"><?= htmlspecialchars($event['name']) ?></div>
                <div class="event-info"><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></div>
                <div class="event-info"><strong>Time:</strong> <?= date("F j, Y, g:i A", strtotime($event['time'])) ?></div>

                <?php
                $descRes = $conn->query("SELECT description FROM event_descriptions WHERE event_id=".$event['event_id']);
                $descriptions = [];
                if($descRes) while($row = $descRes->fetch_assoc()) $descriptions[] = $row['description'];
                ?>
                <?php if(count($descriptions)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('desc<?= $event['event_id'] ?>')">View Description</button>
                    <div id="desc<?= $event['event_id'] ?>" class="event-desc">
                        <?php foreach($descriptions as $desc) echo htmlspecialchars($desc)."<br>"; ?>
                    </div>
                <?php endif; ?>

                <?php
                $hostRes = $conn->query("
                    SELECT c.club_name 
                    FROM event_hosts h 
                    JOIN clubs c ON h.club_id = c.club_id 
                    WHERE h.event_id=".$event['event_id']
                );
                $hosts = [];
                if($hostRes) while($row = $hostRes->fetch_assoc()) $hosts[] = $row['club_name'];
                ?>
                <?php if(count($hosts)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('host<?= $event['event_id'] ?>')">View Hosts</button>
                    <div id="host<?= $event['event_id'] ?>" class="event-hosts">
                        <?php foreach($hosts as $host) echo htmlspecialchars($host)."<br>"; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column:1/-1; text-align:center;">No upcoming events.</p>
    <?php endif; ?>

    <!-- Ongoing Events -->
    <div class="section-title">🔄 Ongoing Events</div>
    <?php if($ongoingEvents && $ongoingEvents->num_rows > 0): ?>
        <?php while($event = $ongoingEvents->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-title"><?= htmlspecialchars($event['name']) ?></div>
                <div class="event-info"><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></div>
                <div class="event-info"><strong>Time:</strong> <?= date("F j, Y, g:i A", strtotime($event['time'])) ?></div>

                <?php
                $descRes = $conn->query("SELECT description FROM event_descriptions WHERE event_id=".$event['event_id']);
                $descriptions = [];
                if($descRes) while($row = $descRes->fetch_assoc()) $descriptions[] = $row['description'];
                ?>
                <?php if(count($descriptions)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('desc<?= $event['event_id'] ?>')">View Description</button>
                    <div id="desc<?= $event['event_id'] ?>" class="event-desc">
                        <?php foreach($descriptions as $desc) echo htmlspecialchars($desc)."<br>"; ?>
                    </div>
                <?php endif; ?>

                <?php
                $hostRes = $conn->query("
                    SELECT c.club_name 
                    FROM event_hosts h 
                    JOIN clubs c ON h.club_id = c.club_id 
                    WHERE h.event_id=".$event['event_id']
                );
                $hosts = [];
                if($hostRes) while($row = $hostRes->fetch_assoc()) $hosts[] = $row['club_name'];
                ?>
                <?php if(count($hosts)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('host<?= $event['event_id'] ?>')">View Hosts</button>
                    <div id="host<?= $event['event_id'] ?>" class="event-hosts">
                        <?php foreach($hosts as $host) echo htmlspecialchars($host)."<br>"; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column:1/-1; text-align:center;">No ongoing events.</p>
    <?php endif; ?>

    <!-- Previous Events -->
    <div class="section-title">⏮ Previous Events</div>
    <?php if($previousEvents && $previousEvents->num_rows > 0): ?>
        <?php while($event = $previousEvents->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-title"><?= htmlspecialchars($event['name']) ?></div>
                <div class="event-info"><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></div>
                <div class="event-info"><strong>Time:</strong> <?= date("F j, Y, g:i A", strtotime($event['time'])) ?></div>

                <?php
                $descRes = $conn->query("SELECT description FROM event_descriptions WHERE event_id=".$event['event_id']);
                $descriptions = [];
                if($descRes) while($row = $descRes->fetch_assoc()) $descriptions[] = $row['description'];
                ?>
                <?php if(count($descriptions)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('desc<?= $event['event_id'] ?>')">View Description</button>
                    <div id="desc<?= $event['event_id'] ?>" class="event-desc">
                        <?php foreach($descriptions as $desc) echo htmlspecialchars($desc)."<br>"; ?>
                    </div>
                <?php endif; ?>

                <?php
                $hostRes = $conn->query("
                    SELECT c.club_name 
                    FROM event_hosts h 
                    JOIN clubs c ON h.club_id = c.club_id 
                    WHERE h.event_id=".$event['event_id']
                );
                $hosts = [];
                if($hostRes) while($row = $hostRes->fetch_assoc()) $hosts[] = $row['club_name'];
                ?>
                <?php if(count($hosts)>0): ?>
                    <button class="toggle-btn" onclick="toggleDesc('host<?= $event['event_id'] ?>')">View Hosts</button>
                    <div id="host<?= $event['event_id'] ?>" class="event-hosts">
                        <?php foreach($hosts as $host) echo htmlspecialchars($host)."<br>"; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="grid-column:1/-1; text-align:center;">No previous events.</p>
    <?php endif; ?>

</div>
</body>
</html>
