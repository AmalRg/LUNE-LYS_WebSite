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
    $productId = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    // Vérifier que le produit existe
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Produit introuvable.");
    }

    // Vérifier si produit déjà dans panier
    $stmt = $pdo->prepare("SELECT * FROM panier WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Mettre à jour la quantité
        $newQuantity = $cartItem['quantite'] + $quantity;
        $stmt = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $cartItem['id']]);
    } else {
        // Insérer nouvelle ligne
        $stmt = $pdo->prepare("INSERT INTO panier (user_id, product_id, quantite) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $productId, $quantity]);
    }

    header('Location: cart.php');
    exit;
}
?>
