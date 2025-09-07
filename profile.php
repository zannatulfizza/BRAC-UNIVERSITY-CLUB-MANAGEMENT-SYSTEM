<?php
session_start();
include 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$sql = "SELECT * FROM users WHERE EMAIL = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get clubs for non-admin users
$clubs = [];
if ($user['ROLE'] != 'admin') {
    $sql_clubs = "SELECT c.club_name 
                  FROM club_members cm
                  JOIN clubs c ON cm.club_id = c.club_id
                  WHERE cm.user_id = ?";
    $stmt2 = $conn->prepare($sql_clubs);
    $stmt2->bind_param("i", $user['user_id']);
    $stmt2->execute();
    $clubs_result = $stmt2->get_result();
    while ($row = $clubs_result->fetch_assoc()) {
        $clubs[] = $row['club_name'];
    }
    $stmt2->close();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap');

* { margin:0; padding:0; box-sizing:border-box; font-family:'Rubik', sans-serif; }

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow-x: hidden;
    background: #0f172a;
}

/* Background image */
body::before {
    content:"";
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: url('images/profile.jpg') no-repeat center center;
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

/* Profile Card */
.profile-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    border-radius: 25px;
    padding: 50px 60px;
    width: 550px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.25);
    text-align: center;
    animation: fadeUp 1s ease;
    transition: all 0.5s ease;
}
.profile-card:hover {
    transform: scale(1.02) translateY(-8px);
    box-shadow: 0 18px 50px rgba(0,0,0,0.3);
}

/* Profile Icon */
.profile-icon {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a78bfa, #7c3aed);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 60px;
    font-weight: bold;
    margin: 0 auto 20px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.2);
    animation: bounce 3s infinite;
    transition: all 0.4s ease;
}
.profile-icon:hover {
    transform: rotate(8deg) scale(1.1);
    box-shadow: 0 10px 35px rgba(0,0,0,0.3);
}

@keyframes bounce {
    0%,100%{ transform: translateY(0); }
    50%{ transform: translateY(-10px); }
}

.profile-card h2 {
    margin: 10px 0 5px;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
}
.profile-card p {
    margin: 5px 0;
    font-size: 16px;
    color: #374151;
}

/* Clubs */
.club-section {
    margin-top: 30px;
    text-align: left;
}
.club-section h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #4c1d95;
}

.club-card {
    background: linear-gradient(135deg, #c084fc, #8b5cf6);
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 500;
    color: white;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    animation: fadeUp 0.8s ease;
}
.club-card:hover {
    transform: translateX(8px) scale(1.02);
    box-shadow: 0 10px 28px rgba(0,0,0,0.25);
}

/* No Club Notice */
.no-club {
    background: rgba(239, 68, 68, 0.1);
    padding: 18px;
    border-radius: 12px;
    text-align: center;
    font-size: 16px;
    color: #b91c1c;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    animation: fadeUp 0.8s ease;
}

/* Back & Logout Buttons */
.button-group {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}
.back-btn, .logout-btn {
    padding: 14px 22px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    color: white;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
.back-btn { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
.back-btn:hover { background: linear-gradient(135deg, #7c3aed, #a78bfa); transform: translateY(-2px) scale(1.03); }
.logout-btn { background: linear-gradient(135deg, #ef4444, #dc2626); }
.logout-btn:hover { background: linear-gradient(135deg, #dc2626, #ef4444); transform: translateY(-2px) scale(1.03); }

/* Animations */
@keyframes fadeUp {
    0%{ opacity:0; transform: translateY(50px);}
    100%{ opacity:1; transform: translateY(0);}
}

/* Responsive */
@media(max-width: 600px){
    .profile-card { width: 90%; padding: 40px 30px; }
    .club-card { font-size: 15px; padding: 12px 16px; }
    .button-group { flex-direction: column; gap: 10px; }
}
</style>
</head>
<body>
<div class="profile-card">
    <div class="button-group">
        <button class="back-btn" onclick="history.back()">⬅ Back</button>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="profile-icon"><?= strtoupper(substr($user['NAME'],0,1)); ?></div>
    <h2><?= htmlspecialchars($user['NAME']); ?></h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['EMAIL']); ?></p>
    <p><strong>Role:</strong> <?= ucfirst($user['ROLE']); ?></p>

    <?php if ($user['ROLE'] != 'admin') { ?>
    <div class="club-section">
        <h3>Clubs</h3>
        <?php if(count($clubs)>0){ ?>
            <p><strong>Total Clubs:</strong> <?= count($clubs); ?></p>
            <?php foreach($clubs as $club){ ?>
                <div class="club-card">🎓 <?= htmlspecialchars($club); ?></div>
            <?php } ?>
        <?php } else { ?>
            <div class="no-club">🚫 You are not part of any club yet</div>
        <?php } ?>
    </div>
    <?php } ?>

</div>
</body>
</html>
