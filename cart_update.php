<?php
session_start();
require_once 'includes/config.php';
$conn = new connexion();
$pdo = $conn->CNXbase();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = intval($_POST['cart_id']); // ID de la ligne dans panier
    $quantity = max(1, intval($_POST['quantity'])); // quantité min 1

    // Vérifier que ce panier appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM panier WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $userId]);
    $cartItem = $stmt->fetch();

    if (!$cartItem) {
        die("Élément du panier non trouvé.");
    }

    // Mettre à jour la quantité
    $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id = ?");
    $stmt->execute([$quantity, $cartId]);

    header('Location: cart.php');
    exit;
}
?>
