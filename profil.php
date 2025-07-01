<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new connexion();
$pdo = $conn->CNXbase();
$userId = $_SESSION['user_id'];

// Récupérer les infos actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT nom, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation simple
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }

    if ($password !== '' && $password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET nom = ?, email = ?, password = ? WHERE id = ?");
            $update->execute([$nom, $email, $hashed_password, $userId]);
        } else {
            $update = $pdo->prepare("UPDATE users SET nom = ?, email = ? WHERE id = ?");
            $update->execute([$nom, $email, $userId]);
        }
        $success = "Profil mis à jour avec succès !";

        // Recharger les données utilisateur à jour
        $stmt = $pdo->prepare("SELECT nom, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<head>
    <meta charset="UTF-8" />
    <title>Détail produit - <?= htmlspecialchars($product['nom']) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/home.css" />
    <link rel="stylesheet" href="assets/css/profil.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>

    <!-- NAVIGATION (copie depuis home.php) -->
    <header>
        <div class="logo">
            <img src="assets/images/logo.png" alt="Logo" />
        </div>
        <nav class="nav-links">
            <a href="home.php">Accueil</a>
            <a href="home.php#products">Produits</a>
            <a href="#">À propos</a>
            <a href="#">Contact</a>
            <div class="dropdown">
                <button class="dropbtn">Catégories <i class="fa fa-caret-down"></i></button>
                <div class="dropdown-content">
                    <a href="home.php?categorie=bagues">Bagues</a>
                    <a href="home.php?categorie=colliers">Colliers</a>
                    <a href="home.php?categorie=bracelets">Bracelets</a>
                    <a href="home.php?categorie=boucles">Boucles</a>
                    <a href="home.php">Tous</a>
                </div>
            </div>
        </nav>
        <div class="right-icons">
            <a href="profil.php"><i class="fa-solid fa-user"></i></a>
            <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
            <a href="logout.php" title="Déconnexion"><i class="fa-solid fa-right-from-bracket"></i></a>
            <button id="theme-toggle"><i class="fa-solid fa-moon"></i></button>
        </div>
    </header>

<main class="profile-container">
    <h1>Modifier mon profil</h1>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" action="profil.php" class="profile-form">
        <label for="nom">Nom complet :</label>
        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>

        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer) :</label>
        <input type="password" id="password" name="password" placeholder="Nouveau mot de passe">

        <label for="password_confirm">Confirmer mot de passe :</label>
        <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmer mot de passe">

        <button type="submit" class="btn">Mettre à jour</button>
    </form>
</main>

<!-- FOOTER (copie depuis home.php) -->
    <footer>
        <div class="footer-content">
            <div>
                <h3>Bijoux Store</h3>
                <p>Vente de bijoux élégants et faits main.</p>
            </div>
            <div>
                <h4>Contact</h4>
                <p>Email : contact@bijouxstore.tn</p>
                <p>Tél : +216 20 000 000</p>
                <p>Adresse : Sfax, Tunisie</p>
            </div>
            <div>
                <h4>Suivez-nous</h4>
                <p>
                    <i class="fab fa-facebook"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-twitter"></i>
                </p>
            </div>
        </div>
        <p class="footer-bottom">© 2025 Bijoux Store. Tous droits réservés.</p>
    </footer>


</body>
</html>
