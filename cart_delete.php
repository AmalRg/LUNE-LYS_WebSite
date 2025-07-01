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
    $cartId = intval($_POST['cart_id']);

    // Vérifier que ce panier appartient à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM panier WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $userId]);
    $cartItem = $stmt->fetch();

    if (!$cartItem) {
        die("Élément du panier non trouvé.");
    }

    // Supprimer l'entrée
    $stmt = $pdo->prepare("DELETE FROM panier WHERE id = ?");
    $stmt->execute([$cartId]);

    header('Location: cart.php');
    exit;
}
?>
