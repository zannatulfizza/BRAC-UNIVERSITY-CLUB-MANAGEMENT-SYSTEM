<?php
include 'config.php';
session_start();

// ---------- Auth ----------
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

// ---------- Current User ----------
$email = $_SESSION['email'];
$userStmt = $conn->prepare("SELECT user_id, NAME, ROLE, EMAIL FROM users WHERE EMAIL = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$user_id = (int)$user['user_id'];
$user_role = strtolower($user['ROLE']); // admin/president/general member

// ---------- Club Info ----------
if (!isset($_GET['club_id']) || !is_numeric($_GET['club_id'])) die("Invalid club ID.");
$club_id = (int)$_GET['club_id'];

$clubStmt = $conn->prepare("SELECT club_id, club_name, founding_year, club_email FROM clubs WHERE club_id=?");
$clubStmt->bind_param("i", $club_id);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();
$clubStmt->close();
if (!$club) die("Club not found.");

// ---------- Determine membership ----------
$membershipStmt = $conn->prepare("SELECT * FROM club_members WHERE user_id=? AND club_id=?");
$membershipStmt->bind_param("ii", $user_id, $club_id);
$membershipStmt->execute();
$isMember = $membershipStmt->get_result()->num_rows > 0;
$membershipStmt->close();

// ---------- Determine pending join request ----------
$requestStmt = $conn->prepare("SELECT status FROM join_requests WHERE user_id=? AND club_id=? ORDER BY request_id DESC LIMIT 1");
$requestStmt->bind_param("ii", $user_id, $club_id);
$requestStmt->execute();
$requestData = $requestStmt->get_result()->fetch_assoc();
$requestStmt->close();

$pendingRequest = $requestData && $requestData['status']==='pending';
$approvedRequest = $requestData && $requestData['status']==='approved';
$rejectedRequest = $requestData && $requestData['status']==='rejected';

// ---------- Admin/President panel for join requests ----------
$canManage = $user_role==='admin';
if(!$canManage && $user_role==='president'){
    $presidentStmt = $conn->prepare("SELECT * FROM users u JOIN club_members cm ON u.user_id=cm.user_id WHERE u.user_id=? AND u.ROLE='president' AND cm.club_id=?");
    $presidentStmt->bind_param("ii", $user_id, $club_id);
    $presidentStmt->execute();
    $isPresident = $presidentStmt->get_result()->num_rows>0;
    $presidentStmt->close();
    $canManage = $isPresident;
}

// ---------- Handle POST ----------
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['send_request'])){
        $stmt = $conn->prepare("INSERT INTO join_requests (user_id, club_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $club_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['notification'] = "Your join request has been sent.";
        header("Location: club_page.php?club_id=$club_id");
        exit;
    }

    if(isset($_POST['add_comment'])){
        $comment = trim($_POST['comment']);
        if($comment!==''){
            $stmt = $conn->prepare("INSERT INTO club_comments (club_id, user_id, comment) VALUES (?,?,?)");
            $stmt->bind_param("iis",$club_id,$user_id,$comment);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: club_page.php?club_id=$club_id");
        exit;
    }

    if(isset($_POST['delete_comment'])){
        $cid = (int)$_POST['comment_id'];
        $isAdmin = $canManage?1:0;
        $stmt = $conn->prepare("DELETE FROM club_comments WHERE comment_id=? AND (user_id=? OR ?=1)");
        $stmt->bind_param("iii",$cid,$user_id,$isAdmin);
        $stmt->execute();
        $stmt->close();
        header("Location: club_page.php?club_id=$club_id");
        exit;
    }

    if(isset($_POST['approve_request'])){
        $rid = (int)$_POST['request_id'];

        $stmt = $conn->prepare("SELECT user_id, club_id FROM join_requests WHERE request_id=? AND status='pending'");
        $stmt->bind_param("i",$rid);
        $stmt->execute();
        $rdata = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if($rdata){
            $new_user_id = $rdata['user_id'];
            $club_id_req = $rdata['club_id'];

            $stmt = $conn->prepare("UPDATE users SET ROLE='general member' WHERE user_id=?");
            $stmt->bind_param("i",$new_user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO club_members (club_id,user_id,joined_at) VALUES (?,?,NOW())");
            $stmt->bind_param("ii",$club_id_req,$new_user_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM join_requests WHERE request_id=?");
            $stmt->bind_param("i",$rid);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['notification'] = "A join request has been approved.";
        header("Location: club_page.php?club_id=$club_id");
        exit;
    }

    if(isset($_POST['reject_request'])){
        $rid = (int)$_POST['request_id'];
        $stmt = $conn->prepare("UPDATE join_requests SET status='rejected' WHERE request_id=?");
        $stmt->bind_param("i",$rid);
        $stmt->execute();
        $stmt->close();
        $_SESSION['notification'] = "A join request has been rejected.";
        header("Location: club_page.php?club_id=$club_id");
        exit;
    }
}

// ---------- Fetch comments ----------
$commentsStmt = $conn->prepare("SELECT cc.comment_id,cc.comment,cc.created_at,u.NAME,u.user_id FROM club_comments cc JOIN users u ON cc.user_id=u.user_id WHERE cc.club_id=? ORDER BY cc.created_at DESC");
$commentsStmt->bind_param("i",$club_id);
$commentsStmt->execute();
$comments = $commentsStmt->get_result();
$commentsStmt->close();

// ---------- Fetch members ----------
$membersStmt = $conn->prepare("SELECT u.user_id,u.NAME,u.ROLE FROM users u JOIN club_members cm ON u.user_id=cm.user_id WHERE cm.club_id=? ORDER BY u.ROLE DESC,u.NAME ASC");
$membersStmt->bind_param("i",$club_id);
$membersStmt->execute();
$members = $membersStmt->get_result();
$membersStmt->close();

// ---------- Fetch pending requests ----------
if($canManage){
    $pendingStmt = $conn->prepare("SELECT jr.request_id,u.NAME,u.EMAIL FROM join_requests jr JOIN users u ON jr.user_id=u.user_id WHERE jr.club_id=? AND jr.status='pending'");
    $pendingStmt->bind_param("i",$club_id);
    $pendingStmt->execute();
    $pendingRequests = $pendingStmt->get_result();
    $pendingStmt->close();
}

// ---------- Notifications ----------
$notification = '';
if(isset($_SESSION['notification'])){
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}
if($approvedRequest) $notification = "Your join request has been approved!";
if($rejectedRequest) $notification = "Your join request has been rejected.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($club['club_name']) ?></title>
<style>
:root{
    --primary:#5b7fff;
    --accent:#ff7ab6;
    --text:#0f172a;
    --muted:#475569;
    --danger:#ef4444;
    --success:#10b981;
}
body{
    margin:0;
    font-family:"Segoe UI",sans-serif;
    background: url('images/clubgsbg.png') no-repeat center center fixed;
    background-size: cover;
    color: var(--text);
}
body::before{
    content:"";
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    backdrop-filter: blur(6px);
    background-color: rgba(255,255,255,0.2);
    z-index:-1;
}
.topbar{
    background: rgba(91,127,255,0.9);
    color:#fff;
    padding:12px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
}
.topbar button{
    background:#fff;
    color:var(--primary);
    border:none;
    padding:8px 14px;
    border-radius:8px;
    font-weight:600;
    cursor:pointer;
    transition:0.3s;
}
.topbar button:hover{
    transform: translateY(-2px);
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
}
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:20px;
    max-width:1100px;
    margin:50px auto;
    padding:0 20px;
}
.card{
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(10px);
    border-radius:15px;
    padding:20px;
    box-shadow:0 8px 25px rgba(0,0,0,0.12);
    transition:0.3s;
}
.card:hover{
    transform: translateY(-4px);
    box-shadow:0 12px 35px rgba(0,0,0,0.2);
}
.btn{
    padding:10px 16px;
    border:none;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    color:#fff;
    transition:0.3s;
}
.btn-primary{background: linear-gradient(135deg,var(--primary),#4f46e5);}
.btn-primary:hover{background: linear-gradient(135deg,#4f46e5,var(--primary));}
.btn-danger{background: var(--danger);}
.btn-danger:hover{opacity:0.85;}
.notification{
    background:#d1fae5;
    color:#065f46;
    padding:12px;
    border-radius:10px;
    text-align:center;
    margin:15px auto;
}
.member-item{
    padding:10px;
    border-radius:8px;
    margin-bottom:8px;
    background: rgba(255,255,255,0.2);
    transition:0.3s;
}
.member-item:hover{background: rgba(255,255,255,0.3);}
.member-item.president{
    font-weight:700;
    background: linear-gradient(135deg,#ffecb3,#ffe082);
}
.comment-form textarea{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
    resize:none;
}
.comment-container{
    max-height:300px;
    overflow-y:auto;
    margin-top:10px;
}
.comment-item{
    background: rgba(255,255,255,0.2);
    padding:12px 14px;
    border-radius:10px;
    margin-bottom:12px;
    border-left:4px solid var(--primary);
}
.toggle-btn{
    background:#fff;
    color:var(--primary);
    padding:8px 12px;
    border-radius:8px;
    border:none;
    font-weight:600;
    margin-top:10px;
    cursor:pointer;
}
</style>
<script>
function toggleSection(id, btn){
    const section = document.getElementById(id);
    if(section.style.display === "none"){
        section.style.display = "block";
        btn.textContent = "Hide";
    } else {
        section.style.display = "none";
        btn.textContent = "See " + (id === "members-section" ? "Members" : "Comments");
    }
}
</script>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <button onclick="window.location.href='user_dashboard.php'">⬅ Back</button>
    <span><?= htmlspecialchars($club['club_name']) ?></span>
    <button onclick="window.location.href='profile.php'">👤 Profile</button>
</div>

<h1 style="text-align:center;margin-top:30px;"><?= htmlspecialchars($club['club_name']) ?></h1>

<?php if($notification): ?>
<div class="notification"><?= htmlspecialchars($notification) ?></div>
<?php endif; ?>

<div class="grid">

<!-- Founding Year & Email -->
<div class="card">
<h2>📅 Founded</h2>
<p><?= htmlspecialchars($club['founding_year'] ?? '—') ?></p>
<h3>📧 Email</h3>
<p><?= htmlspecialchars($club['club_email'] ?? 'Not provided') ?></p>
</div>

<!-- Achievements -->
<div class="card">
<h2>🏆 Achievements</h2>
<p><a href="club_achievements.php?club_id=<?= $club_id ?>" class="btn btn-primary">View Achievements</a></p>
</div>

<!-- Events -->
<div class="card">
<h2>🎉 Events</h2>
<p><a href="club_events.php?club_id=<?= $club_id ?>" class="btn btn-primary">View Events</a></p>
</div>

<!-- Join Request -->
<?php if(!$isMember && !$pendingRequest && $user_role!=='admin' && $user_role!=='president'): ?>
<div class="card">
<h2>🤝 Join Club</h2>
<form method="post">
<button type="submit" name="send_request" class="btn btn-primary">Request to Join</button>
</form>
</div>
<?php elseif($pendingRequest): ?>
<div class="card">
<h2>🤝 Join Club</h2>
<p style="color:var(--muted)">Your request is pending...</p>
</div>
<?php endif; ?>

<!-- Members -->
<div class="card">
<h2>👥 Members</h2>
<button class="toggle-btn" onclick="toggleSection('members-section', this)">See Members</button>
<div id="members-section" style="display:none; margin-top:10px;">
<?php if($members->num_rows===0): ?>
<p style="color:var(--muted)">No members yet.</p>
<?php else: while($m=$members->fetch_assoc()): ?>
<div class="member-item <?= strtolower($m['ROLE'])==='president'?'president':'' ?>">
<?= htmlspecialchars($m['NAME']) ?> <span style="float:right; font-size:12px; color:var(--muted)"><?= ucfirst($m['ROLE']) ?></span>
</div>
<?php endwhile; endif; ?>
</div>
</div>

<!-- Comments -->
<div class="card">
<h2>💬 Chat Box</h2>
<form method="post" class="comment-form">
<input type="hidden" name="add_comment" value="1">
<textarea name="comment" rows="3" placeholder="Write your comment..."></textarea>
<button type="submit" class="btn btn-primary">Send</button>
</form>
<button class="toggle-btn" onclick="toggleSection('comments-section', this)">See Comments</button>
<div id="comments-section" style="display:none; margin-top:10px;" class="comment-container">
<?php if($comments->num_rows===0): ?>
<p style="color:var(--muted)">No messages yet.</p>
<?php else: while($c=$comments->fetch_assoc()): ?>
<div class="comment-item">
<strong><?= htmlspecialchars($c['NAME']) ?>:</strong><br>
<?= nl2br(htmlspecialchars($c['comment'])) ?>
<small style="color:var(--muted); float:right;"><?= htmlspecialchars($c['created_at']) ?></small>
<?php if($user_id==$c['user_id'] || $canManage): ?>
<form method="post" style="margin-top:5px;">
<input type="hidden" name="comment_id" value="<?= $c['comment_id'] ?>">
<button type="submit" name="delete_comment" class="btn btn-danger" style="padding:4px 8px;font-size:12px;">Delete</button>
</form>
<?php endif; ?>
</div>
<?php endwhile; endif; ?>
</div>
</div>

<!-- Pending Requests -->
<?php if($canManage && isset($pendingRequests) && $pendingRequests->num_rows>0): ?>
<div class="card">
<h2>⏳ Pending Requests</h2>
<?php while($r=$pendingRequests->fetch_assoc()): ?>
<div class="member-item">
<?= htmlspecialchars($r['NAME']) ?> (<?= htmlspecialchars($r['EMAIL']) ?>)
<form method="post" style="margin-top:5px;">
<input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
<button type="submit" name="approve_request" class="btn btn-primary">Approve</button>
<button type="submit" name="reject_request" class="btn btn-danger">Reject</button>
</form>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>

</div>

</body>
</html>
