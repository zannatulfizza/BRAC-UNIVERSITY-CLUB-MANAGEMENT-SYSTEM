<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $role     = 'user';

    // Check if email exists
    $check = $conn->query("SELECT * FROM users WHERE EMAIL='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('❌ Email already registered!'); window.location.href = 'register.php';</script>";
    } else {
        $conn->query("INSERT INTO users (NAME, EMAIL, PASSWORD, ROLE) VALUES ('$name','$email','$password','$role')");
        $_SESSION['email'] = $email;
        $_SESSION['role']  = $role;
        header("Location: user_dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - BRACU Clubs Management</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;600;700&display=swap');

* { margin:0; padding:0; box-sizing:border-box; font-family:'Rubik', sans-serif; }

body {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    background: #0f172a;
}

body::before {
    content:"";
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: url('images/bg.jpg') no-repeat center center;
    background-size: cover;
    filter: blur(4px) brightness(0.6);
    z-index:-1;
    animation: backgroundZoom 40s ease-in-out infinite alternate;
}
@keyframes backgroundZoom {
    0% { transform: scale(1) translate(0,0); }
    50% { transform: scale(1.05) translate(5px,5px); }
    100% { transform: scale(1) translate(0,0); }
}

/* Chic headline */
.background-header {
    position: absolute;
    top: 15%;
    width: 100%;
    text-align: center;
    font-size: 3rem;
    font-weight: 700;
    background: linear-gradient(90deg, #ff8a5b, #ffb86c, #5b6fff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 5px 20px rgba(0,0,0,0.35);
    letter-spacing: 1px;
}

/* Form container */
.form-container {
    background: rgba(255, 255, 255, 0.85);
    color: #1e293b;
    padding: 2rem 2.5rem;
    border-radius: 25px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    text-align: center;
    width: 360px;
    z-index:1;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.form-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

/* Inputs */
input {
    width: 100%;
    padding: 14px;
    margin: 10px 0 18px;
    border: none;
    border-radius: 15px;
    font-size: 15px;
    outline: none;
    background: rgba(255,255,255,0.9);
    transition: all 0.3s ease;
}
input:focus {
    box-shadow: 0 0 12px rgba(91,111,255,0.6);
}

/* Buttons */
button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 15px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(135deg, #5b6fff, #ff8a5b);
    transition: all 0.3s ease;
}
button:hover {
    background: linear-gradient(135deg, #ff8a5b, #5b6fff);
    transform: scale(1.05);
}

/* Login link */
p { margin-top: 15px; font-size: 14px; }
a { color:#5b6fff; text-decoration:none; font-weight:600; transition:0.3s; }
a:hover { text-decoration:underline; }

@media(max-width: 500px) {
    .background-header { font-size: 2rem; top: 10%; }
    .form-container { width: 90%; padding: 1.8rem; }
}
</style>
</head>
<body>

<div class="background-header">
    Join the Fun at <br> BRAC University Clubs
</div>

<div class="form-container">
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Enter Email" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
