<?php
// club_achievements.php
include 'config.php';
session_start();

/* ---------- Auth ---------- */
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

/* ---------- Input: club_id ---------- */
if (!isset($_GET['club_id']) || !is_numeric($_GET['club_id'])) {
    die("Invalid club ID.");
}
$club_id = (int)$_GET['club_id'];

/* ---------- Current User ---------- */
$email = $_SESSION['email'];
$userStmt = $conn->prepare("SELECT user_id, NAME, ROLE FROM users WHERE EMAIL = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) { die("User not found."); }

$user_id   = (int)$user['user_id'];
$user_role = strtolower((string)$user['ROLE']); // 'admin' or 'president' or others

/* ---------- Check if president is member of this club ---------- */
$is_president = false;
if ($user_role === 'president') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM club_members WHERE club_id=? AND user_id=?");
    $stmt->bind_param("ii", $club_id, $user_id);
    $stmt->execute();
    $is_president = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
    $stmt->close();
}

/* ---------- Permissions ---------- */
$canManage = ($user_role === 'admin') || ($user_role === 'president' && $is_president);

/* ---------- Club Info ---------- */
$clubStmt = $conn->prepare("SELECT club_id, club_name, founding_year FROM clubs WHERE club_id = ?");
$clubStmt->bind_param("i", $club_id);
$clubStmt->execute();
$club = $clubStmt->get_result()->fetch_assoc();
$clubStmt->close();
if (!$club) die("Club not found.");

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['action'] ?? '';
    $name  = trim($_POST['name'] ?? '');
    $award = trim($_POST['award'] ?? '');
    $year  = trim($_POST['year'] ?? '');
    $yearVal = ($year === '' ? null : $year);

    if ($action === 'add' && ($name !== '' || $award !== '')) {
        $stmt = $conn->prepare("INSERT INTO achievements (club_id, name, award, year) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $club_id, $name, $award, $yearVal);
        $stmt->execute();
        $stmt->close();
        header("Location: club_achievements.php?club_id=".$club_id);
        exit;
    }

    if ($action === 'edit') {
        $aid = (int)($_POST['achievement_id'] ?? 0);
        if ($aid > 0 && ($name !== '' || $award !== '')) {
            $stmt = $conn->prepare("UPDATE achievements SET name=?, award=?, year=? WHERE achievement_id=? AND club_id=?");
            $stmt->bind_param("sssii", $name, $award, $yearVal, $aid, $club_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: club_achievements.php?club_id=".$club_id);
        exit;
    }

    if ($action === 'delete') {
        $aid = (int)($_POST['achievement_id'] ?? 0);
        if ($aid > 0) {
            $stmt = $conn->prepare("DELETE FROM achievements WHERE achievement_id=? AND club_id=?");
            $stmt->bind_param("ii", $aid, $club_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: club_achievements.php?club_id=".$club_id);
        exit;
    }
}

/* ---------- Fetch Achievements ---------- */
$achStmt = $conn->prepare("SELECT achievement_id, name, award, year FROM achievements WHERE club_id=? ORDER BY (year IS NULL) ASC, year DESC");
$achStmt->bind_param("i", $club_id);
$achStmt->execute();
$achievements = $achStmt->get_result();
$achStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($club['club_name']) ?> Achievements</title>
<style>
body{
    margin:0;
    font-family:"Segoe UI",sans-serif;
    color:#0f172a;
    background: url('images/bg.jpg') no-repeat center center fixed;
    background-size: cover;
}

/* ---------- Overlay blur ---------- */
body::before{
    content:'';
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    backdrop-filter: blur(8px);
    background: rgba(255,255,255,0.25); /* subtle frosted glass effect */
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

.grid{
    display:grid;
    gap:35px;
    grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); /* slightly bigger cards */
}

.card{
    background: rgba(255,255,255,0.85); /* semi-transparent glass */
    backdrop-filter: blur(6px);
    border-radius:22px;
    padding:28px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
    position:relative;
    min-height:220px;
    transition:0.3s;
}
.card:hover{
    transform:translateY(-6px) scale(1.03);
    box-shadow:0 16px 36px rgba(0,0,0,0.18);
}

.year-tag{
    position:absolute;
    bottom:15px;
    right:15px;
    background: linear-gradient(135deg,#ffecb3,#ffe082);
    color:#0f172a;
    padding:8px 16px;
    border-radius:22px;
    font-size:15px;
    font-weight:600;
    box-shadow:0 4px 10px rgba(0,0,0,0.15);
}

.ach-name{
    font-size:26px;
    font-weight:700;
    margin-bottom:14px;
}
.ach-award{
    font-size:18px;
    color:#475569;
}
.card-actions{
    margin-top:22px;
    display:flex;
    gap:12px;
}
.btn{
    padding:12px 18px;
    border:none;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
    color:#fff;
    transition:0.3s;
    font-size:16px;
}
.btn-edit{ background:#10b981; }
.btn-edit:hover{ transform:translateY(-2px) scale(1.05); }
.btn-del{ background:#ef4444; }
.btn-del:hover{ opacity:0.9; }
.btn-add{ background: linear-gradient(135deg,#5b7fff,#4f46e5); margin-bottom:28px; font-size:18px; padding:14px 22px;}
.btn-add:hover{ opacity:0.92; }

.empty{
    color:#475569;
    font-style:italic;
    font-size:18px;
    text-align:center;
}

/* Modals */
.modal{
    position:fixed; inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(0,0,0,0.55);
    z-index:50;
}
.modal.active{display:flex;}
.modal-content{
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(8px);
    border-radius:20px;
    padding:28px;
    width:90%;
    max-width:450px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
}
.modal-content h3{
    margin-top:0;
    font-size:24px;
    font-weight:700;
}
.modal-content input{
    padding:12px;
    border-radius:12px;
    border:1px solid #ccc;
    width:100%;
    margin-bottom:14px;
    font-size:16px;
}
.modal-content .actions{display:flex;gap:12px;justify-content:flex-end;}
</style>

<script>
function openModal(id){ document.getElementById(id).classList.add('active'); }
function closeModal(id){ document.getElementById(id).classList.remove('active'); }
function openEditModal(id,name,award,year){
    openModal('editModal');
    document.getElementById('edit_id').value=id;
    document.getElementById('edit_name').value=name;
    document.getElementById('edit_award').value=award;
    document.getElementById('edit_year').value=year;
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
    <h1>Achievements</h1>
    <?php if($canManage): ?>
        <button class="btn btn-add" onclick="openModal('addModal')">＋ Add Achievement</button>
    <?php endif; ?>

    <?php if($achievements->num_rows===0): ?>
        <p class="empty">No achievements yet.</p>
    <?php endif; ?>

    <div class="grid">
    <?php while($row=$achievements->fetch_assoc()): ?>
        <?php
        $aid = (int)$row['achievement_id'];
        $aname = $row['name'] ?: 'Unnamed Achievement';
        $award = $row['award'] ?: null;
        $year = $row['year'] ?: null;
        ?>
        <div class="card">
            <?php if($year): ?><div class="year-tag"><?= htmlspecialchars($year) ?></div><?php endif; ?>
            <div class="ach-name"><?= htmlspecialchars($aname) ?></div>
            <div class="ach-award"><strong>Award:</strong> <?= $award ? htmlspecialchars($award) : '<span class="empty">No award specified</span>' ?></div>
            <?php if($canManage): ?>
            <div class="card-actions">
                <button class="btn btn-edit" onclick="openEditModal(<?= $aid ?>,'<?= htmlspecialchars($aname,ENT_QUOTES) ?>','<?= htmlspecialchars($award ?? '',ENT_QUOTES) ?>','<?= htmlspecialchars($year ?? '',ENT_QUOTES) ?>')">✎ Edit</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this achievement?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="achievement_id" value="<?= $aid ?>">
                    <button type="submit" class="btn btn-del">🗑 Delete</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
    </div>
</div>

<?php if($canManage): ?>
<!-- Add Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h3>＋ Add Achievement</h3>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" placeholder="Achievement Name">
            <input type="text" name="award" placeholder="Award">
            <input type="text" name="year" placeholder="Year">
            <div class="actions">
                <button type="submit" class="btn btn-add">Add</button>
                <button type="button" class="btn" style="background:#ccc;color:#000;" onclick="closeModal('addModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>✎ Edit Achievement</h3>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="achievement_id" id="edit_id">
            <input type="text" name="name" id="edit_name" placeholder="Achievement Name">
            <input type="text" name="award" id="edit_award" placeholder="Award">
            <input type="text" name="year" id="edit_year" placeholder="Year">
            <div class="actions">
                <button type="submit" class="btn btn-edit">Save</button>
                <button type="button" class="btn" style="background:#ccc;color:#000;" onclick="closeModal('editModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>
