<?php
include 'config.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$result = $conn->query("SELECT * FROM users WHERE EMAIL='$email'");
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Clubs - BRAC University</title>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0; 
    padding: 0;
    min-height: 100vh;
    background: url('images/clubgsbg.png') no-repeat center center;
    background-size: cover;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}

body::before {
    content: "";
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.35);
    backdrop-filter: blur(4px);
    z-index: -1;
}

/* ===== Topbar Full Width ===== */
.topbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 95%;
    z-index: 20;
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(59,130,246,0.85);
    color: #fff;
    font-size: 18px;
    border-radius: 0;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    backdrop-filter: blur(8px);
}

.topbar-left {
    font-weight: 600;
    font-size: 17px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.topbar-left .back-btn {
    padding: 6px 14px;
    font-size: 14px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}
.topbar-left .back-btn:hover {
    background: #fff;
    color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.topbar-right a {
    color: #fff; 
    text-decoration: none; 
    font-weight: 500; 
    font-size: 16px;
    padding: 8px 16px;
    border-radius: 25px;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}
.topbar-right a:hover {
    background: #fff;
    color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

/* ===== We Are Hiring Button ===== */
.hiring-btn {
    background: linear-gradient(135deg, #f59e0b, #facc15);
    color: #fff;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 25px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.hiring-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    background: linear-gradient(135deg, #facc15, #f59e0b);
    text-shadow: 0 0 4px rgba(0,0,0,0.2);
}

/* ===== Search Bar ===== */
.search-bar { text-align: center; margin: 120px auto 40px; width: 100%; }
.search-bar input {
    width: 60%; max-width: 550px;
    padding: 18px 22px;
    border-radius: 16px; border: 1px solid #ccc; font-size: 18px;
    transition: all 0.3s ease;
}
.search-bar input:focus {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    outline: none;
}

/* ===== Clubs Grid ===== */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px; 
    padding: 20px 40px 60px;
    max-width: 1300px; 
    width: 100%;
}

/* ===== Club Cards ===== */
.club {
    position: relative;
    background: rgba(255,255,255,0.9); 
    border-radius: 20px;
    padding: 60px 20px 40px; /* extra top padding for icon */
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: all 0.4s ease;
    cursor: pointer;
    transform: translateY(0);
    animation: floatIn 0.8s ease forwards;
}
.club:hover {
    transform: translateY(-10px) scale(1.05);
    background: linear-gradient(135deg, #e0f2fe, #dbeafe);
    box-shadow: 0 16px 35px rgba(0,0,0,0.2);
}

.club a { 
    text-decoration: none; 
    color: #1e3a8a; 
    font-weight: 600; 
    font-size: 20px; 
    display: inline-block;
    transition: all 0.3s ease;
}
.club a:hover {
    color: #2563eb;
    text-shadow: 0 0 8px rgba(37,99,235,0.5);
}

/* ===== Club Icon ===== */
.club-icon {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 32px;
    animation: bounce 2s infinite;
    transition: all 0.3s ease;
}
.club:hover .club-icon {
    transform: translateX(-50%) rotate(10deg) scale(1.2);
    color: #2563eb;
}

/* ===== Animations ===== */
@keyframes floatIn {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

/* ===== Footer Year ===== */
.footer-year {
    position: fixed;
    bottom: 15px;
    right: 20px;
    font-size: 14px;
    color: #555;
    opacity: 0.8;
}

/* ===== Responsive ===== */
@media(max-width: 600px){
    .search-bar input { width: 80%; }
}
</style>

<script>
function searchClubs() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let clubs = document.getElementsByClassName('club');
    for (let i = 0; i < clubs.length; i++) {
        let name = clubs[i].innerText.toLowerCase();
        clubs[i].style.display = name.includes(input) ? "" : "none";
    }
}
</script>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="user_dashboard.php" class="back-btn">← Dashboard</a>
        👋 Welcome, <?= htmlspecialchars($user['NAME']); ?>  
        | Role: <?= htmlspecialchars($user['ROLE'] ?? 'Member'); ?>
    </div>
    <div class="topbar-right">
        <a href="hiring.php" class="hiring-btn">💼 We Are Hiring</a>
        <a href="profile.php">👤 Profile</a>
        <?php if ($user['ROLE'] === 'admin') { ?>
            <a href="manage_clubs.php">🛠 Manage Clubs</a>
        <?php } ?>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="search-bar">
    <input type="text" id="searchInput" onkeyup="searchClubs()" placeholder="Search for a club...">
</div>

<div class="grid">
<?php
$sql = "SELECT * FROM clubs ORDER BY club_name ASC";
$result = $conn->query($sql);

$icons = ['🎓','🏆','🎯','⚡','🌟','💡','📚','🛡️','🎨','🎵'];
$i = 0;

while ($club = $result->fetch_assoc()) {
    $icon = $icons[$i % count($icons)];
    echo '<div class="club">
            <div class="club-icon">' . $icon . '</div>
            <a href="club_page.php?club_id=' . $club['club_id'] . '">' . htmlspecialchars($club['club_name']) . '</a>
          </div>';
    $i++;
}
?>
</div>

<div class="footer-year">
    &copy; <?= date("Y"); ?> BRAC University
</div>

</body>
</html>
