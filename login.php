<?php
session_start();
include('includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = (new connexion())->CNXbase();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];

        if ($user['is_admin']) {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: home.php");
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Incorrect email or password.";
        $_SESSION['active_form'] = "login";
        header("Location: index.php");
        exit();
    }
}
?>
