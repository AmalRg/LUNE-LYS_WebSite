<?php
session_start();
require_once 'includes/config.php';
$conn = new connexion();
$pdo = $conn->CNXbase();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    die("Votre panier est vide.");
}

// Enregistrer chaque produit commandé
$userId = $_SESSION['user_id'];
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO commandes (user_id, produit_id, quantite, date_commande, statut) VALUES (?, ?, ?, NOW(), 'en attente')");

    foreach ($cart as $productId => $quantity) {
        $stmt->execute([$userId, $productId, $quantity]);
    }

    $pdo->commit();

    // Vider le panier
    unset($_SESSION['cart']);

    echo "Commande passée avec succès !";
    echo '<br><a href="home.php">Retour à l\'accueil</a>';

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erreur lors de la commande : " . $e->getMessage());
}
?>
