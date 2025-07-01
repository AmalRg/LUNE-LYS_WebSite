<?php

session_start();
require_once 'includes/config.php';

$conn = new connexion();
$pdo = $conn->CNXbase();

// Récupérer l'id du produit dans l'URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId > 0) {
    // Récupérer le produit courant
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = :id");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Produit non trouvé.");
    }

    // Récupérer aussi les produits similaires (même catégorie, sauf le produit courant)
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE categorie = :categorie AND id != :id LIMIT 4");
    $stmt->execute([
        'categorie' => $product['categorie'],
        'id' => $product['id']
    ]);
    $alsoLikeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    die("ID du produit non spécifié.");
}

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

<!-- DÉTAIL PRODUIT -->
<main class="product-detail-container" style="padding: 2rem; max-width: 900px; margin: auto;">
    <h1><?= htmlspecialchars($product['nom']) ?></h1>
    
    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <div style="flex: 1 1 300px;">
            <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['nom'] ?? '') ?>" style="width: 300px; height: 300px; object-fit: cover; border-radius: 8px;" />
        </div>

        <div style="flex: 1 1 300px;">
            <p><strong>Catégorie :</strong> <?= htmlspecialchars($product['categorie']) ?></p>
            <p><strong>Description :</strong> <?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <p><strong>Prix :</strong> <?= number_format($product['prix'], 2) ?> €</p>

            <div class="product-actions">
                <form method="post" action="cart_add.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <button type="submit" class="btn-add-cart"><i class="fa fa-shopping-cart"></i> Ajouter au panier</button>
                </form>

                <button id="like-btn" class="btn-like">
                    <i class="fa-regular fa-heart"></i>
                </button>
            </div>
        </div>
    </div>
</main>


<!-- Vous aimerez aussi -->
<!--section class="product-grid also-like" id="also-like">
    <h2>Vous aimerez aussi</h2>
    <!?php if (!empty($alsoLikeProducts)): ?>
        <!?php foreach ($alsoLikeProducts as $productLike): ?>
            <a href="product.php?id=<!?= $productLike['id'] ?>" class="product-link">
                <div class="product-card">
                    <img src="assets/images/<!?= htmlspecialchars($productLike['image_path']) ?>" alt="<!?= htmlspecialchars($productLike['nom']) ?>">
                    <div class="product-info">
                        <h2><!?= htmlspecialchars($productLike['nom']) ?></h2>
                        <p class="price"><!?= number_format($productLike['prix'], 2) ?> €</p>
                        <div class="icons">
                            <button class="icon-btn"><i class="fa fa-shopping-basket"></i></button>
                            <button class="icon-btn"><i class="fa fa-heart"></i></button>
                        </div>
                    </div>
                </div>
            </a>
        <!?php endforeach; ?>
    <!?php else: ?>
        <p>Aucun produit similaire disponible.</p>
    <!?php endif; ?>
</section-->

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
