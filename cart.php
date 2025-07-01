<?php
session_start();
require_once 'includes/config.php';
$conn = new connexion();
$pdo = $conn->CNXbase();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn = new connexion();
$pdo = $conn->CNXbase();
$userId = $_SESSION['user_id'];

// Supprimer un produit du panier
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM panier WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    header("Location: cart.php");
    exit;
}

// Mettre à jour les quantités
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantite'])) {
    foreach ($_POST['quantite'] as $productId => $quantite) {
        $quantite = max(1, intval($quantite));
        $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantite, $userId, $productId]);
    }
    header("Location: cart.php");
    exit;
}

// Récupérer les produits du panier
$stmt = $pdo->prepare("
    SELECT p.id, p.nom, p.prix, p.image_path, pa.quantite
    FROM panier pa
    JOIN produits p ON pa.product_id = p.id
    WHERE pa.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
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
    <link rel="stylesheet" href="assets/css/cart.css" />
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

    <!-- CONTENU -->
    <main>
        <h1>Votre Panier</h1>

        <?php if (empty($cartItems)): ?>
            <p>Votre panier est vide.</p>
        <?php else: ?>
            <form method="post" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix</th>
                            <th>Sous-total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item):
                            $subtotal = $item['prix'] * $item['quantite'];
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['nom']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td>
                                    <a href="product.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: #333;">
                                        <?= htmlspecialchars($item['nom']) ?>
                                    </a>
                                </td>

                                <td>
                                    <input type="number" name="quantite[<?= $item['id'] ?>]" value="<?= $item['quantite'] ?>" min="1" style="width: 60px;">
                                </td>
                                <td><?= number_format($item['prix'], 2) ?> €</td>
                                <td><?= number_format($subtotal, 2) ?> €</td>
                                <td>
                                    <a href="cart.php?delete=<?= $item['id'] ?>" onclick="return confirm('Supprimer ce produit ?')" title="Supprimer">
                                        <i class="fa-solid fa-trash" style="color: #000000;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>


                <p><strong>Total : <?= number_format($total, 2) ?> €</strong></p>

                <button type="submit" class="btn">Mettre à jour</button>
                <a href="paiement.php" class="btn">Passer la commande</a>
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