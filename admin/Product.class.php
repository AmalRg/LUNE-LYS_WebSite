<?php
require_once __DIR__ . '/../includes/config.php';


class Product {
    public $id;
    public $nom;
    public $description;
    public $prix;
    public $categorie;
    public $stock;
    public $image_path;
    public $featured;
    public $statut;
    public $created_at;
    public $updated_at; // S'attend à ce que cette colonne existe ou soit gérée par un défaut

    public function __construct(
        $nom,
        $description,
        $prix,
        $categorie,
        $stock,
        $image_path = null,
        $featured = 0, // Default to not featured
        $statut = 'Actif', // Default to Active
        $id = null,
        $created_at = null,
        $updated_at = null // Définit une valeur par défaut null pour éviter les erreurs si non fourni
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->description = $description;
        $this->prix = $prix;
        $this->categorie = $categorie;
        $this->stock = $stock;
        $this->image_path = $image_path;
        $this->featured = $featured;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Static method to get PDO connection from global scope or create it
    private static function getPdo() {
        global $pdo; // Tente de récupérer l'objet PDO global

        // Si $pdo n'est pas encore initialisé globalement, tente de le créer
        if (!$pdo) {
            try {
                $conn = new connexion(); // La classe connexion est disponible via le require_once de config.php
                $pdo = $conn->CNXbase();
            } catch (Exception $e) {
                // Log l'erreur et arrête l'exécution si la connexion ne peut être établie
                error_log("Failed to establish PDO connection in Product.class.php: " . $e->getMessage());
                die("Database connection not available.");
            }
        }
        return $pdo;
    }

    public function insertProduct() {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO produits (nom, description, prix, categorie, stock, image_path, featured, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $this->nom,
                $this->description,
                $this->prix,
                $this->categorie,
                $this->stock,
                $this->image_path,
                $this->featured,
                $this->statut
            ]);
        } catch (PDOException $e) {
            error_log("Erreur d'insertion de produit: " . $e->getMessage());
            return false;
        }
    }

    public function updateProduct() {
        $pdo = self::getPdo();
        try {
            // Assurez-vous que votre colonne `updated_at` est bien gérée par MySQL avec ON UPDATE CURRENT_TIMESTAMP
            // ou ajoutez NOW() ici si vous voulez la gérer manuellement.
            // Si la colonne n'existe pas, cette requête échouera.
            $stmt = $pdo->prepare("UPDATE produits SET nom = ?, description = ?, prix = ?, categorie = ?, stock = ?, image_path = ?, featured = ?, statut = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([
                $this->nom,
                $this->description,
                $this->prix,
                $this->categorie,
                $this->stock,
                $this->image_path,
                $this->featured,
                $this->statut,
                $this->id
            ]);
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour de produit: " . $e->getMessage());
            return false;
        }
    }

    public static function deleteProduct($id) {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur de suppression de produit (DB): " . $e->getMessage());
            return false;
        }
    }

    public static function getProductById($id) {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return new Product(
                    $data['nom'],
                    $data['description'],
                    $data['prix'],
                    $data['categorie'],
                    $data['stock'],
                    $data['image_path'] ?? null, // Utilise ?? null pour la robustesse
                    $data['featured'] ?? 0,      // Utilise ?? 0 pour la robustesse
                    $data['statut'] ?? 'Actif',  // Utilise ?? 'Actif' pour la robustesse
                    $data['id'],
                    $data['created_at'] ?? null, // Utilise ?? null pour la robustesse
                    $data['updated_at'] ?? null  // Utilise ?? null pour la robustesse
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du produit par ID: " . $e->getMessage());
            return null;
        }
    }

    public static function getAllProducts() {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->query("SELECT * FROM produits ORDER BY created_at DESC");
            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product(
                    $row['nom'],
                    $row['description'],
                    $row['prix'],
                    $row['categorie'],
                    $row['stock'],
                    $row['image_path'] ?? null,
                    $row['featured'] ?? 0,
                    $row['statut'] ?? 'Actif',
                    $row['id'],
                    $row['created_at'] ?? null,
                    $row['updated_at'] ?? null
                );
            }
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les produits: " . $e->getMessage());
            return [];
        }
    }

    public static function getDistinctCategories() {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->query("SELECT DISTINCT categorie FROM produits ORDER BY categorie ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des catégories distinctes: " . $e->getMessage());
            return [];
        }
    }
}