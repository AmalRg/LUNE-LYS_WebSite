<?php
session_start();
include('includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $adresse = $_POST['adresse'];

    $db = (new connexion())->CNXbase();

    // Vérifier si l'email existe déjà
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $_SESSION['register_error'] = "Email already exists.";
        $_SESSION['active_form'] = "register";
        header("Location: index.php");
        exit();
    }

    // Insérer le nouvel utilisateur
    $stmt = $db->prepare("INSERT INTO users (nom, email, password, adresse, is_admin, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    if ($stmt->execute([$name, $email, $password, $adresse])) {
        header("Location: home.php");
    } else {
        $_SESSION['register_error'] = "Registration failed. Please try again.";
        $_SESSION['active_form'] = "register";
        header("Location: index.php");
    }
}
?>
