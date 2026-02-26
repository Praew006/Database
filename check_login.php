<?php
session_start();
include('db.php');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: login.php"); exit;
}

$login_id = trim($_POST['login_id'] ?? '');  // username หรือ email
$password = trim($_POST['password']  ?? '');
$redirect = trim($_POST['redirect']  ?? '');

if(!$login_id || !$password){
    header("Location: login.php?error=empty&redirect=".urlencode($redirect)); exit;
}

// ค้นหาจาก email หรือ first_name
$login_id_esc = mysqli_real_escape_string($conn, $login_id);
$pass_esc     = mysqli_real_escape_string($conn, $password);

$stmt = mysqli_query($conn,
    "SELECT * FROM users
     WHERE (email='$login_id_esc' OR first_name='$login_id_esc')
       AND password='$pass_esc'
     LIMIT 1"
);

if($stmt && mysqli_num_rows($stmt) === 1){
    $row = mysqli_fetch_assoc($stmt);
    $_SESSION['user_id']   = $row['user_id'];
    $_SESSION['fullname']  = $row['first_name'].' '.$row['last_name'];
    $_SESSION['email']     = $row['email'];
    $_SESSION['role']      = (strtolower($row['first_name']) === 'admin') ? 'admin' : 'member';
    $_SESSION['member_id'] = $row['user_id'];

    if($redirect && $_SESSION['role'] !== 'admin'){
        header("Location: " . $redirect); exit;
    }
    if($_SESSION['role'] === 'admin'){
        header("Location: admin_home.php");
    } else {
        header("Location: index.php");
    }
} else {
    header("Location: login.php?error=wrong&redirect=".urlencode($redirect));
}
exit;
?>