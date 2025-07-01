<?php
session_start();
require_once 'includes/config.php';
$conn = new connexion();
$pdo = $conn->CNXbase();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new connexion();
$pdo = $conn->CNXbase();
$userId = $_SESSION['user_id'];

// Récupérer les produits du panier et calculer total
$stmt = $pdo->prepare("
    SELECT p.id, p.nom, p.prix, pa.quantite
    FROM panier pa
    JOIN produits p ON pa.product_id = p.id
    WHERE pa.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['prix'] * $item['quantite'];
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <title>Détail produit - <?= htmlspecialchars($product['nom']) ?></title>
    <link rel="icon" type="image/png" href="assets/images/logo.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="assets/css/home.css" />
    <link rel="stylesheet" href="assets/css/produit.css" />
    <link rel="stylesheet" href="assets/css/paiement.css" />
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
            <button id="theme-toggle"><i class="fa-solid fa-moon"></i></button>
        </div>
    </header>

<main class="payment-container">
    <h1>Paiement sécurisé</h1>
    
    <?php if (empty($cartItems)): ?>
        <p>Votre panier est vide.</p>
        <a href="home.php" class="btn">Retour à l'accueil</a>
    <?php else: ?>
        <p class="total">Montant total à payer : <strong><?= number_format($total, 2) ?> €</strong></p>
        
        <form action="" method="post" class="payment-form">
            <input type="hidden" name="total" value="<?= $total ?>">

            <div class="form-group">
                <label for="name">Nom complet sur la carte</label>
                <input type="text" id="name" name="card_name" placeholder="Nom sur la carte" required>
            </div>

            <div class="form-group">
                <label for="card-number">Numéro de carte</label>
                <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" pattern="\d{13,19}" required>
            </div>

            <div class="form-row">
                <div class="form-group small">
                    <label for="expiry">Date d'expiration (MM/AA)</label>
                    <input type="text" id="expiry" name="card_expiry" placeholder="MM/AA" maxlength="5" pattern="(0[1-9]|1[0-2])\/?([0-9]{2})" required>
                </div>
                <div class="form-group small">
                    <label for="cvv">CVV</label>
                    <input type="password" id="cvv" name="card_cvv" placeholder="123" maxlength="4" pattern="\d{3,4}" required>
                </div>
            </div>

            <button type="submit" class="btn pay-btn"><i class="fas fa-lock"></i> Payer maintenant</button>
        </form>
    <?php endif; ?>
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
