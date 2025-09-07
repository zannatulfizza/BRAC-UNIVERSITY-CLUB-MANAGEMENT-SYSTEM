<?php
include 'config.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$userStmt = $conn->prepare("SELECT user_id, NAME, ROLE FROM users WHERE EMAIL=?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$user_id = (int)$user['user_id'];
$user_role = strtolower($user['ROLE']);

if (!isset($_GET['club_id']) || !is_numeric($_GET['club_id'])) die("Invalid club ID.");
$club_id = (int)$_GET['club_id'];

$clubStmt = $conn->prepare("SELECT club_id, club_name FROM clubs WHERE club_id=?");
$clubStmt->bind_param("i",$club_id);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();
$clubStmt->close();
if (!$club) die("Club not found.");

// Fetch events
$search = isset($_GET['search']) ? "%".$_GET['search']."%" : '%';
$eventsStmt = $conn->prepare("SELECT event_id, name, location, time, event_type FROM events WHERE club_id=? AND name LIKE ? ORDER BY time DESC");
$eventsStmt->bind_param("is",$club_id, $search);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();

$events = ['current'=>[], 'upcoming'=>[], 'past'=>[]];
while($e = $eventsResult->fetch_assoc()){
    $events[strtolower($e['event_type'])][] = $e;
}
$eventsStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($club['club_name']) ?> Events</title>
<style>
body{
    margin:0;
    font-family:"Segoe UI",sans-serif;
    color:#0f172a;
    background: url('images/bg.jpg') no-repeat center center fixed;
    background-size: cover;
    line-height:1.6;
}
body::before{
    content:'';
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    backdrop-filter: blur(8px);
    background: rgba(255,255,255,0.25);
    z-index:-1;
}
.topbar{
    background: rgba(91,127,255,0.85);
    color:#fff;
    padding:22px 34px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom-left-radius:18px;
    border-bottom-right-radius:18px;
    box-shadow:0 6px 20px rgba(0,0,0,0.2);
}
.topbar a{
    text-decoration:none;
    color:#fff;
    font-weight:600;
    margin-left:20px;
    padding:12px 18px;
    border-radius:14px;
    font-size:18px;
    transition:0.3s;
}
.topbar a:hover{
    background: rgba(255,255,255,0.25);
    transform:translateY(-2px);
}
.wrap{
    max-width:1250px;
    margin:50px auto;
    padding:0 20px;
}
h1{
    font-size:40px;
    margin-bottom:30px;
    font-weight:700;
    text-align:center;
    color:#0f172a;
}
.tab-buttons{
    display:flex;
    gap:15px;
    justify-content:center;
    margin-bottom:25px;
}
.tab-buttons button{
    padding:14px 28px;
    border:none;
    border-radius:16px;
    font-weight:600;
    cursor:pointer;
    background: rgba(255,255,255,0.9);
    transition:0.3s;
    color:#0f172a;
    font-size:18px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.tab-buttons button.active{
    background: linear-gradient(135deg,#5b7fff,#4f46e5);
    color:#fff;
    box-shadow:0 6px 16px rgba(0,0,0,0.2);
}
.tab-buttons button:hover{
    transform:translateY(-2px);
}
.tab-content{
    display:none;
    animation:fadeIn 0.5s ease;
}
.tab-content.active{ display:block; }
@keyframes fadeIn{0%{opacity:0;}100%{opacity:1;}}
.event-card{
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(6px);
    border-radius:20px;
    padding:26px 28px;
    box-shadow:0 12px 28px rgba(0,0,0,0.08);
    margin-bottom:22px;
    transition:0.3s;
}
.event-card:hover{
    transform:translateY(-4px) scale(1.02);
    box-shadow:0 18px 36px rgba(0,0,0,0.15);
}
.event-card h3{
    margin-top:0;
    font-size:24px;
    font-weight:700;
}
.event-meta{
    font-size:16px;
    color:#475569;
    margin-top:12px;
}
.search-form{
    margin-bottom:30px;
    display:flex;
    gap:10px;
}
.search-form input{
    flex:1;
    padding:12px 16px;
    border-radius:14px;
    border:1px solid #ccc;
    font-size:18px;
}
.search-form button{
    padding:12px 18px;
    border-radius:14px;
    border:none;
    background: linear-gradient(135deg,#5b7fff,#4f46e5);
    color:#fff;
    font-size:18px;
    cursor:pointer;
    transition:0.3s;
}
.search-form button:hover{ opacity:0.85; }
</style>
<script>
function openTab(evt, tabName){
    document.querySelectorAll('.tab-content').forEach(el=>el.classList.remove('active'));
    document.querySelectorAll('.tab-buttons button').forEach(b=>b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}
</script>
</head>
<body>

<div class="topbar">
    <div><?= htmlspecialchars($club['club_name']) ?> Events</div>
    <div>
        <a href="club_page.php?club_id=<?= $club_id ?>">← Back</a>
        <a href="profile.php">👤 Profile</a>
    </div>
</div>

<div class="wrap">
    <h1>Events</h1>

    <form method="get" class="search-form">
        <input type="hidden" name="club_id" value="<?= $club_id ?>">
        <input type="text" name="search" placeholder="Search events..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <button type="submit">🔍 Search</button>
    </form>

    <div class="tab-buttons">
        <button class="active" onclick="openTab(event,'current')">🟢 Current</button>
        <button onclick="openTab(event,'upcoming')">🟡 Upcoming</button>
        <button onclick="openTab(event,'past')">⚪ Past</button>
    </div>

    <?php foreach(['current','upcoming','past'] as $type): ?>
    <div id="<?= $type ?>" class="tab-content <?= $type==='current'?'active':'' ?>">
        <?php if(count($events[$type])===0): ?>
            <p style="color:#475569;">No <?= ucfirst($type) ?> events.</p>
        <?php else: foreach($events[$type] as $e): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($e['name']) ?></h3>
                <p><strong>Location:</strong> <?= htmlspecialchars($e['location']) ?></p>
                <div class="event-meta"><strong>Time:</strong> <?= htmlspecialchars($e['time']) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <?php endforeach; ?>

</div>

</body>
</html>
