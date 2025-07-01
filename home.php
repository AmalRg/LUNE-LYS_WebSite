<?php
session_start();
require_once 'includes/config.php';

$conn = new connexion();
$pdo = $conn->CNXbase();

// Filtrer par catégorie si spécifiée
$categoryFilter = isset($_GET['categorie']) ? $_GET['categorie'] : null;

if ($categoryFilter) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE featured = 1 AND LOWER(categorie) = LOWER(:categorie)");
    $stmt->execute(['categorie' => $categoryFilter]);
} else {
    $stmt = $pdo->query("SELECT * FROM produits WHERE featured = 1");
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <title>LUNE & LYS - Home</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/home.css"/>
    <link rel="stylesheet" href="assets/css/produit.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<!-- NAVIGATION -->
<header>
    <div class="logo">
        <img src="assets/images/logo.png" alt="Logo" />
    </div>
    <nav class="nav-links">
        <a href="home.php">Accueil</a>
        <a href="#products">Produits</a>
        <a href="#">À propos</a>
        <a href="#">Contact</a>
        <div class="dropdown">
            <button class="dropbtn">Catégories <i class="fa fa-caret-down"></i></button>
            <div class="dropdown-content">
                <a href="home.php?categorie=bagues">Bagues</a>
                <a href="home.php?categorie=colliers">Colliers</a>
                <a href="home.php?categorie=bracelets">Bracelets</a>
                <a href="home.php?categorie=boucles">Boucles</a>
                <a href="home.php">Tous</a> <!-- Pour réinitialiser le filtre -->
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

<section class="hero">
    <video autoplay muted loop playsinline id="bg-video">
        <source src="assets/images/bg_video.mp4" type="video/mp4">
        Votre navigateur ne supporte pas les vidéos HTML5.
    </video>
</section>

<section class="product-grid" id="products">
    <?php foreach ($products as $product): ?>
        <a href="product.php?id=<?= $product['id'] ?>" class="product-link">
            <div class="product-card">
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['nom']) ?>">
                <div class="product-info">
                    <h2><?= htmlspecialchars($product['nom']) ?></h2>
                    <p class="price"><?= number_format($product['prix'], 2) ?> €</p>
                    <div class="icons">
                        <button class="icon-btn"><i class="fa fa-shopping-basket"></i></button>
                        <button class="icon-btn"><i class="fa fa-heart"></i></button>
                    </div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</section>


<!-- FOOTER -->
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

<script src="assets/js/home.js"></script>

</body>
</html>
