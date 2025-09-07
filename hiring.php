<?php
include 'config.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
// Fetch user
$userStmt = $conn->prepare("SELECT user_id, NAME, ROLE FROM users WHERE EMAIL=?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$user_id = (int)$user['user_id'];
$role = $user['ROLE'];

// ============================
// Determine allowed clubs
// ============================
$allowedClubs = [];
if ($role === 'admin') {
    $result = $conn->query("SELECT club_id, club_name FROM clubs ORDER BY club_name ASC");
    while ($row = $result->fetch_assoc()) {
        $allowedClubs[$row['club_id']] = $row['club_name'];
    }
} elseif ($role === 'president') {
    $stmt = $conn->prepare("
        SELECT c.club_id, c.club_name 
        FROM clubs c
        JOIN club_members cm ON c.club_id = cm.club_id
        WHERE cm.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $allowedClubs[$row['club_id']] = $row['club_name'];
    }
    $stmt->close();
}

// ============================
// Handle new hiring post
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['club_id'], $_POST['position'], $_POST['start_date'], $_POST['end_date'])) {
    $club_id = intval($_POST['club_id']);
    $position = trim($_POST['position']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($position !== '' && $start_date !== '' && $end_date !== '') {
        if (array_key_exists($club_id, $allowedClubs)) {
            $stmt = $conn->prepare("INSERT INTO club_hiring (club_id, position, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $club_id, $position, $start_date, $end_date, $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: hiring.php");
            exit;
        } else {
            $error = "You are not allowed to post hiring for this club.";
        }
    }
}

// ============================
// Fetch hiring posts
// ============================
$hiringPosts = $conn->query("
    SELECT h.*, c.club_name, u.NAME as creator_name
    FROM club_hiring h
    JOIN clubs c ON h.club_id = c.club_id
    LEFT JOIN users u ON u.user_id = h.created_by
    ORDER BY h.start_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>We Are Hiring - BRAC University</title>
<style>
body {
    font-family:'Segoe UI', sans-serif;
    margin:0; padding:0;
    min-height:100vh;
    display:flex; flex-direction:column;
}
body::before {
    content:"";
    position:fixed; top:0; left:0;
    width:100%; height:100%;
    background:url('images/profile.jpg') no-repeat center center fixed;
    background-size:cover;
    filter:blur(6px) brightness(0.75);
    z-index:-1;
}
.topbar {
    display:flex; justify-content: space-between; align-items:center;
    background: rgba(59,130,246,0.85);
    color:white; padding:0 30px; height:60px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
    position:fixed; width:98%; top:0; z-index:10;
}
.topbar-left a.back-btn {
    color:white; text-decoration:none; font-weight:500; margin-right:10px;
    transition:all 0.3s ease; padding:6px 12px; border-radius:20px;
}
.topbar-left a.back-btn:hover {
    background:white; color:#3b82f6; transform:translateY(-2px);
}
.topbar-right a {
    color:white; text-decoration:none; font-weight:500; margin-left:12px;
    padding:6px 14px; border-radius:20px; background:rgba(255,255,255,0.1);
    transition:all 0.3s ease;
}
.topbar-right a:hover { background:white; color:#3b82f6; transform:translateY(-2px); }

.container { padding:100px 20px 60px; max-width:1200px; margin:auto; display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:25px; }

/* Fun Glassy Form */
.admin-form {
    grid-column: 1 / -1;
    background: rgba(255, 255, 255, 0.15);
    padding: 30px;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    backdrop-filter: blur(10px);
    max-width: 700px;
    margin: auto;
}
.admin-form label {
    font-weight: 600;
    font-size: 16px;
    color: #fff;
    text-shadow: 0 0 6px rgba(0,0,0,0.6);
}
.admin-form input,
.admin-form select {
    padding: 14px 16px;
    border-radius: 12px;
    border: none;
    width: 100%;
    font-size: 15px;
    background: rgba(255,255,255,0.85);
    box-sizing: border-box;
    transition: all 0.3s ease;
}
.admin-form input:focus,
.admin-form select:focus {
    outline: none;
    border: 2px solid #6366f1;
    background: #fff;
    box-shadow: 0 0 12px rgba(99, 102, 241, 0.6);
    transform: scale(1.02);
}
.admin-form button {
    margin-top: 10px;
    padding: 14px 20px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366f1, #ec4899);
    color: white;
    cursor: pointer;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    align-self: flex-start;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.admin-form button:hover {
    transform: scale(1.05) rotate(-1deg);
    box-shadow: 0 6px 16px rgba(0,0,0,0.4);
}

.hiring-card {
    position:relative; background: rgba(255,255,255,0.9);
    padding:20px; border-radius:16px; box-shadow:0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hiring-card:hover { transform:translateY(-6px) scale(1.03); box-shadow:0 12px 28px rgba(0,0,0,0.25); }
.hiring-card h3 { margin:0 0 10px; color:#1e3a8a; }
.hiring-card p { margin:4px 0; color:#1f2937; }

.search-bar { grid-column:1/-1; text-align:center; margin-bottom:20px; }
.search-bar input {
    width:60%; max-width:500px; padding:14px 18px; font-size:16px; border-radius:12px; border:1px solid #ccc;
}
.search-bar input:focus { outline:none; border-color:#3b82f6; box-shadow:0 4px 12px rgba(59,130,246,0.3); }
</style>
<script>
function searchHiring() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.getElementsByClassName('hiring-card');
    for(let i=0;i<cards.length;i++){
        let club = cards[i].dataset.club.toLowerCase();
        let position = cards[i].dataset.position.toLowerCase();
        cards[i].style.display = (club.includes(input)||position.includes(input))?"":"none";
    }
}
</script>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <a href="all_clubs.php" class="back-btn">← Clubs</a>
        🙋‍♂️ We Are Hiring
    </div>
    <div class="topbar-right">
        <a href="profile.php">👤 Profile</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="container">

    <div class="search-bar">
        <input type="text" id="searchInput" onkeyup="searchHiring()" placeholder="Search by club or position...">
    </div>

    <?php if (!empty($allowedClubs)) { ?>
        <div class="admin-form">
            <?php if (isset($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
            <form method="POST">
                <label>Club:</label>
                <select name="club_id" required>
                    <option value="">Select a club</option>
                    <?php foreach ($allowedClubs as $id => $name) {
                        echo '<option value="'.$id.'">'.htmlspecialchars($name).'</option>';
                    } ?>
                </select>
                <label>Position:</label>
                <input type="text" name="position" placeholder="Position name" required>
                <label>Start Date:</label>
                <input type="date" name="start_date" required>
                <label>End Date:</label>
                <input type="date" name="end_date" required>
                <button type="submit">✨ Post Hiring</button>
            </form>
        </div>
    <?php } ?>

    <?php
    if($hiringPosts && $hiringPosts->num_rows > 0){
        while($h = $hiringPosts->fetch_assoc()){
            echo '<div class="hiring-card" data-club="'.htmlspecialchars($h['club_name']).'" data-position="'.htmlspecialchars($h['position']).'">';
            echo '<h3>'.htmlspecialchars($h['position']).'</h3>';
            echo '<p><strong>Club:</strong> '.htmlspecialchars($h['club_name']).'</p>';
            echo '<p><strong>Start:</strong> '.htmlspecialchars($h['start_date']).'</p>';
            echo '<p><strong>End:</strong> '.htmlspecialchars($h['end_date']).'</p>';
            echo '<p><strong>Posted by:</strong> '.htmlspecialchars($h['creator_name'] ?? "Unknown").'</p>';
            echo '</div>';
        }
    } else {
        echo '<p style="grid-column:1/-1; text-align:center; color:white;">No hiring posts available.</p>';
    }
    ?>

</div>
</body>
</html>
