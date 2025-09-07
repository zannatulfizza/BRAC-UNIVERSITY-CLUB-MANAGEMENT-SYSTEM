<?php
session_start();
include 'config.php';

// Only admins can access
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all users with club info (multiple rows per club)
$sql = "
SELECT u.user_id, u.name, u.email, u.role, c.club_name
FROM users u
LEFT JOIN club_members cm ON u.user_id = cm.user_id
LEFT JOIN clubs c ON cm.club_id = c.club_id
ORDER BY u.user_id ASC
";
$members = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Members</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #e0f2fe, #fef9c3);
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            animation: fadeUp 0.8s ease;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #1f2937;
            font-size: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: white;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        tr:hover { background: #f3f4f6; transition:0.3s; }

        /* Action buttons */
        .crud-btn {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            margin: 2px;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-block;
        }
        .edit { background:#2563eb; color:white; }
        .edit:hover { background:#1d4ed8; transform: translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.2); }
        .delete { background:#dc2626; color:white; }
        .delete:hover { background:#b91c1c; transform: translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.2); }

        .back { text-align:center; margin-top:25px; }
        .back a {
            text-decoration:none;
            color:white;
            background:#374151;
            padding:12px 22px;
            border-radius:12px;
            transition: all 0.3s ease;
        }
        .back a:hover { background:#1f2937; transform: translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.2); }

        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @media(max-width: 1000px){
            table, th, td { font-size:14px; }
            .crud-btn { font-size:12px; padding:5px 10px; }
        }

        /* Highlight presidents */
        tr.president td {
            background: rgba(255, 223, 93, 0.15);
        }
    </style>
</head>
<body>
<div class="container">
    <h2>👥 Manage Members</h2>

    <?php if ($members->num_rows > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Club</th>
                <th>Actions</th>
            </tr>
            <?php while($row = $members->fetch_assoc()) { 
                $roleClass = strtolower($row['role']) === 'president' ? 'president' : '';
            ?>
                <tr class="<?= $roleClass ?>">
                    <td><?= htmlspecialchars($row['user_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['role'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($row['club_name'] ?? 'Not part of any club') ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="crud-btn edit">✏ Edit</a>
                        <a href="delete_user.php?id=<?= $row['user_id'] ?>" class="crud-btn delete" onclick="return confirm('Are you sure?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>No members found.</p>
    <?php } ?>

    <div class="back"><a href="user_dashboard.php">⬅ Back to Dashboard</a></div>
</div>
</body>
</html>
