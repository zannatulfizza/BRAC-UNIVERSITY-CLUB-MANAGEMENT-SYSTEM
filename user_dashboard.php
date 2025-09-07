<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// Get user details
$email = $_SESSION['email'];
$sql = "SELECT * FROM users WHERE EMAIL = '$email'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

* { margin:0; padding:0; box-sizing:border-box; font-family:'Rubik', sans-serif; }

body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    position: relative;
    background: #0f172a;
}

/* Background image with blur + opacity */
body::before {
    content:"";
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: url('images/dashboard.jpg') no-repeat center center;
    background-size: cover;
    filter: blur(4px) brightness(0.55);
    z-index:-1;
    animation: backgroundZoom 40s ease-in-out infinite alternate;
}

@keyframes backgroundZoom {
    0% { transform: scale(1) translate(0,0); }
    50% { transform: scale(1.05) translate(5px,5px); }
    100% { transform: scale(1) translate(0,0); }
}

/* Topbar */
.topbar {
    background: rgba(107, 33, 168, 0.85);
    color: white;
    padding: 20px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(6px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.25);
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}
.topbar h2 {
    margin: 0;
    font-weight: 700;
    font-size: 22px;
    letter-spacing: 0.5px;
    color: #f9fafb;
}
.actions {
    display: flex;
    gap: 15px;
}
.actions a {
    background: linear-gradient(135deg,#a78bfa,#7c3aed);
    color: #fff;
    padding: 12px 22px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.35s ease;
}
.actions a:hover {
    background: linear-gradient(135deg,#7c3aed,#a78bfa);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

/* Centered container */
.container {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 60px;
    padding: 50px;
    flex-wrap: wrap;
}

/* Section cards */
.section {
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(12px);
    width: 320px;
    padding: 50px 30px;
    border-radius: 25px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.15);
    text-align: center;
    transition: all 0.5s ease;
    animation: fadeUp 1s forwards, floaty 6s ease-in-out infinite;
}
.section:hover {
    transform: translateY(-15px) scale(1.05);
    box-shadow: 0 18px 50px rgba(0,0,0,0.25);
}
.section h3 {
    margin-top: 0;
    margin-bottom: 28px;
    font-size: 24px;
    color: #54689bff;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

/* Floating animation for cards */
@keyframes floaty {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* Buttons inside section */
.club-card {
    display: inline-block;
    padding: 16px 26px;
    background: linear-gradient(135deg, #8b5cf6, #c084fc);
    color: white;
    border-radius: 16px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.35s ease;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}
.club-card:hover {
    background: linear-gradient(135deg, #c084fc, #8b5cf6);
    transform: translateY(-5px) scale(1.07);
    box-shadow: 0 14px 40px rgba(0,0,0,0.3);
}

/* Animations */
@keyframes fadeUp {
    0% { opacity: 0; transform: translateY(50px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Icon Animations */
.icon-bounce {
    display: inline-block;
    animation: bounce 2s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.icon-wobble {
    display: inline-block;
    animation: wobble 2.5s infinite;
}
@keyframes wobble {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-12deg); }
    75% { transform: rotate(12deg); }
}

/* Responsive */
@media(max-width: 900px){
    .container { flex-direction: column; align-items: center; gap: 40px; }
    .section { width: 90%; padding: 40px 25px; }
}
</style>
</head>
<body>
<div class="topbar">
    <h2>Welcome, <?= htmlspecialchars($user['NAME']); ?> 👋</h2>
    <div class="actions">
        <a href="profile.php">Profile</a>
        <?php if ($user['ROLE'] == 'admin') { ?>
            <a href="manage_members.php">Manage Members</a>
        <?php } ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <!-- Events Section -->
    <div class="section events">
        <h3><span class="icon-bounce">📌</span> Events</h3>
        <a href="events.php" class="club-card">View All Events</a>
    </div>

    <!-- Clubs Section -->
    <div class="section clubs">
        <h3><span class="icon-wobble">🎓</span> Clubs</h3>
        <a href="all_clubs.php" class="club-card">View All Clubs</a>
    </div>
</div>
</body>
</html>
