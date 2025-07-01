<?php
$form = $_GET['form'] ?? 'login';

session_start();

// Récupérer les erreurs et le formulaire actif depuis la session
$registerError = $_SESSION['register_error'] ?? null;
$loginError = $_SESSION['login_error'] ?? null;
$activeForm = $_SESSION['active_form'] ?? 'login';

// Nettoyer la session après lecture
unset($_SESSION['register_error'], $_SESSION['login_error'], $_SESSION['active_form']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Lune & Lys</title>
</head>

<body>

    
    <div class="container <?= $activeForm === 'register' ? 'active' : '' ?>" id="container">
        <div class="form-container sign-up">
            <form action="register.php" method="POST">
                <h1>Create Account</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>or use your email for registeration</span>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="text" name="adresse" placeholder="Adresse" required>
                <button type="submit">Sign Up</button>
                <?php if ($registerError): ?>
                    <p class="error-message"><?= $registerError ?></p>
                <?php endif; ?>


            </form>
        </div>
        <div class="form-container sign-in">
            <form action="login.php" method="POST">
                <h1>Sign In</h1>
                <div class="social-icons">
                    <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
                <span>or use your email password</span>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="forgot_password.php">Forget Your Password?</a>
                <button type="submit">Sign In</button>
                <?php if ($loginError): ?>
                    <p class="error-message"><?= $loginError ?></p>
                <?php endif; ?>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Enter your personal details to use all of site features</p>
                    <button class="hidden" id="switch-to-login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Hello, My Friend!</h1>
                    <p>Register with your personal details to use all of site features</p>
                    <button class="hidden" id="switch-to-register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>