<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$pdo = (new connexion())->CNXbase(); // Assuming 'connexion' class handles PDO connection

// --- Data Fetching ---

// Évolution des ventes (6 derniers mois)
$mois = [];
$ventes = [];
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS mois, SUM(total) AS total
    FROM commandes
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY MIN(created_at)
");

while ($row = $stmt->fetch()) {
    $mois[] = $row['mois'];
    $ventes[] = (float)$row['total']; // Ensure sales are floats
}

// Répartition des produits par catégorie
/*
$categories = [];
$quantites = [];
$stmt2 = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS mois, SUM(total) AS total
    FROM commandes
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY MIN(created_at)
");

while ($row = $stmt2->fetch()) {
    $categories[] = $row['categorie'];
    $quantites[] = (int)$row['total']; // Ensure quantities are integers
}*/
// Répartition des produits par catégorie
$categories = [];
$quantites = [];
// Assuming your 'produits' table has a 'categorie' column directly storing the category name
$stmt_categories = $pdo->query("
    SELECT categorie, COUNT(*) AS total
    FROM produits
    GROUP BY categorie
    ORDER BY total DESC
");

while ($row = $stmt_categories->fetch(PDO::FETCH_ASSOC)) { // Use PDO::FETCH_ASSOC for associative array
    $categories[] = $row['categorie']; // Access the 'categorie' column directly
    $quantites[] = (int)$row['total'];
}

// KPI Data
$nbProduits = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
$nbUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$nbCommandes = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$totalRevenu = $pdo->query("SELECT IFNULL(SUM(total), 0) FROM commandes")->fetchColumn();

// For percentage change, we need previous period data
// Products
$nbProduitsLastMonth = $pdo->query("SELECT COUNT(*) FROM produits WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$produitChange = ($nbProduitsLastMonth > 0) ? (($nbProduits - $nbProduitsLastMonth) / $nbProduitsLastMonth) * 100 : 0;

// Users (assuming users have a created_at column for this comparison)
$nbUsersLastMonth = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$userChange = ($nbUsersLastMonth > 0) ? (($nbUsers - $nbUsersLastMonth) / $nbUsersLastMonth) * 100 : 0;

// Orders
$nbCommandesLastMonth = $pdo->query("SELECT COUNT(*) FROM commandes WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$commandeChange = ($nbCommandesLastMonth > 0) ? (($nbCommandes - $nbCommandesLastMonth) / $nbCommandesLastMonth) * 100 : 0;

// Revenue
$totalRevenuLastMonth = $pdo->query("SELECT IFNULL(SUM(total), 0) FROM commandes WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND created_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$revenuChange = ($totalRevenuLastMonth > 0) ? (($totalRevenu - $totalRevenuLastMonth) / $totalRevenuLastMonth) * 100 : 0;


// Produit le plus vendu (Top performer of the month)
$topProduct = [
    'name' => 'COLLIER LINA',
    'units_sold_this_month' => 12,
    'percentage_vs_last_month' => 45,
    'price_per_unit' => 129
];

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <div class="container">
        <aside class="sidebar">
            <div>
                <h2><i class="fa-solid fa-house"></i> Admin Panel</h2>
                <p>Accessoires Store</p>
                <nav>
                    <a href="dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                    <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
                    <a href="users.php"><i class="fa-solid fa-user"></i> Users</a>
                </nav>
            </div>
            <a href="../logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>

        <main class="main-content">
            <h1>Dashboard</h1>
            <div class="cards">
                <div class="card">
                    <h3>Total Products</h3>
                    <p class="stat">
                        <?= $nbProduits ?>
                        <span class="percentage <?= $produitChange >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fa-solid fa-<?= $produitChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= round(abs($produitChange)) ?>% this month
                        </span>
                    </p>
                </div>
                <div class="card">
                    <h3>Users</h3>
                    <p class="stat">
                        <?= $nbUsers ?>
                        <span class="percentage <?= $userChange >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fa-solid fa-<?= $userChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= round(abs($userChange)) ?>% this month
                        </span>
                    </p>
                </div>
                <div class="card">
                    <h3>Orders</h3>
                    <p class="stat">
                        <?= $nbCommandes ?>
                        <span class="percentage <?= $commandeChange >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fa-solid fa-<?= $commandeChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= round(abs($commandeChange)) ?>% this month
                        </span>
                    </p>
                </div>
                <div class="card">
                    <h3>Income</h3>
                    <p class="stat">
                        €<?= number_format($totalRevenu, 2, ',', ' ') ?>
                        <span class="percentage <?= $revenuChange >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fa-solid fa-<?= $revenuChange >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= round(abs($revenuChange)) ?>% this month
                        </span>
                    </p>
                </div>
            </div>

            <div class="chart-container-row">
                <div class="top-product-box">
                    <h2>Produit le plus vendu <span class="subtitle">Top performer de ce mois</span></h2>
                    <div class="top-product-item">
                        <div class="product-icon">
                            <i class="fa-solid fa-box"></i>
                        </div>
                        <div class="product-details">
                            <div class="product-name"><?= htmlspecialchars($topProduct['name']) ?></div>
                            <div class="product-info">
                                <?= $topProduct['units_sold_this_month'] ?> units sold this month
                                <?php if ($topProduct['units_sold_this_month'] > 0 || $topProduct['percentage_vs_last_month'] != 0): ?>
                                    <span class="percentage <?= $topProduct['percentage_vs_last_month'] >= 0 ? 'positive' : 'negative' ?>">
                                        <i class="fa-solid fa-<?= $topProduct['percentage_vs_last_month'] >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                        <?= round(abs($topProduct['percentage_vs_last_month'])) ?>% compared to last month
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="product-price-section">
                            <div class="product-price">€<?= number_format($topProduct['price_per_unit'], 2, ',', ' ') ?></div>
                            <div class="unit-price-label">Unit price</div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
    

</body>

</html>