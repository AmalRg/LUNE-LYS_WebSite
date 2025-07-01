<?php
session_start();
require_once '../includes/config.php';
require_once 'Product.class.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$message = '';
$error = '';

// Define upload directory and allowed extensions
$upload_dir = '../uploads/products/'; // Path relative to this script for file operations
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$max_file_size = 5 * 1024 * 1024; // 5 MB

// Ensure upload directory exists and is writable
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) { // 0755 permissions, true for recursive creation
        $error = "Erreur: Impossible de créer le dossier d'upload : " . $upload_dir . ". Veuillez vérifier les permissions.";
        error_log("Failed to create upload directory: " . $upload_dir);
    }
}

// Function to handle image upload
function handleImageUpload($file_input_name, $upload_dir, $allowed_extensions, $max_file_size) {
    // Ensure $_FILES key exists, otherwise it might be a missing input or incorrect name
    if (!isset($_FILES[$file_input_name])) {
        // Return a specific code for "no file input provided or name mismatch"
        return ['success' => false, 'message' => 'File input not found.', 'code' => -1]; // Using -1 as a custom error code
    }

    // Check for standard PHP file upload errors
    if ($_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        // If no file was selected, return UPLOAD_ERR_NO_FILE
        if ($_FILES[$file_input_name]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'No file was uploaded.', 'code' => UPLOAD_ERR_NO_FILE];
        }
        // For other upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)
        return ['success' => false, 'message' => 'File upload error: ' . $_FILES[$file_input_name]['error'], 'code' => $_FILES[$file_input_name]['error']];
    }

    $fileTmpPath = $_FILES[$file_input_name]['tmp_name'];
    $fileName = $_FILES[$file_input_name]['name'];
    $fileSize = $_FILES[$file_input_name]['size'];
    $fileType = $_FILES[$file_input_name]['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Basic validation
    if ($fileSize > $max_file_size) {
        return ['success' => false, 'message' => 'Image size exceeds the maximum limit (5MB).', 'code' => 'SIZE_EXCEEDED']; // Custom code
    }
    if (!in_array($fileExtension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF, WEBP are allowed.', 'code' => 'INVALID_TYPE']; // Custom code
    }

    // Generate a unique file name
    $newFileName = uniqid('prod_') . '.' . $fileExtension;
    $dest_path = $upload_dir . $newFileName; // Path for moving the file

    // Move the file
    if (move_uploaded_file($fileTmpPath, $dest_path)) {
        $path_for_db = str_replace('../', '', $dest_path);
        return ['success' => true, 'path' => $path_for_db, 'code' => UPLOAD_ERR_OK]; // Success code
    } else {
        error_log("Failed to move uploaded file from {$fileTmpPath} to {$dest_path}");
        return ['success' => false, 'message' => 'Failed to move uploaded image.', 'code' => 'MOVE_FAILED']; // Custom code
    }
}

// --- Handle Form Submissions ---

// Handle Add Product Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addProduct') {
    $nom_produit = trim($_POST['nom_produit']);
    $categorie = trim($_POST['categorie']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $statut = trim($_POST['statut']);
    $image_path_to_save = null; // Default

    // Handle image upload
    $upload_result = handleImageUpload('product_image', $upload_dir, $allowed_extensions, $max_file_size);

    // Check if a file was attempted to be uploaded and it was not just 'no file selected'
    // Line 141 is here.
    if ($upload_result['code'] !== UPLOAD_ERR_NO_FILE) {
        if ($upload_result['success']) {
            $image_path_to_save = $upload_result['path'];
        } else {
            $error = $upload_result['message']; // Set error message if upload failed
        }
    }


    // Basic validation for other fields
    if (empty($nom_produit) || empty($categorie) || empty($description) || $prix <= 0 || $stock < 0 || empty($statut)) {
        if (empty($error)) {
            $error = "Veuillez remplir tous les champs obligatoires et s'assurer que le prix et le stock sont valides.";
        }
    } else {
        if (empty($error)) {
            $newProduct = new Product($nom_produit, $description, $prix, $categorie, $stock, $image_path_to_save, 0, $statut);

            if ($newProduct->insertProduct()) {
                $message = "Produit ajouté avec succès!";
            } else {
                $error = "Erreur lors de l'ajout du produit. Veuillez vérifier les logs du serveur.";
            }
        }
    }
}

// Handle Edit Product Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editProduct') {
    $product_id = intval($_POST['id']);
    $nom_produit = trim($_POST['nom_produit']);
    $categorie = trim($_POST['categorie']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $description = trim($_POST['description']);
    $statut = trim($_POST['statut']);

    if (empty($nom_produit) || empty($categorie) || empty($description) || $prix <= 0 || $stock < 0 || empty($statut)) {
        $error = "Veuillez remplir tous les champs obligatoires et s'assurer que le prix et le stock sont valides.";
    } else {
        $existingProduct = Product::getProductById($product_id);

        if ($existingProduct) {
            $old_image_path_db = $existingProduct->image_path;
            $old_file_system_path = str_replace('uploads/products/', '../uploads/products/', $old_image_path_db);

            $image_path_to_save = $old_image_path_db;

            $upload_result = handleImageUpload('edit_product_image', $upload_dir, $allowed_extensions, $max_file_size);

            if ($upload_result['code'] !== UPLOAD_ERR_NO_FILE) { // If a new file was actually selected
                if ($upload_result['success']) {
                    $image_path_to_save = $upload_result['path'];
                    if ($old_file_system_path && file_exists($old_file_system_path)) {
                        unlink($old_file_system_path);
                    }
                } else {
                    $error = $upload_result['message'];
                }
            }

            if (empty($error)) {
                $existingProduct->nom = $nom_produit;
                $existingProduct->description = $description;
                $existingProduct->prix = $prix;
                $existingProduct->categorie = $categorie;
                $existingProduct->stock = $stock;
                $existingProduct->statut = $statut;
                $existingProduct->image_path = $image_path_to_save;

                if ($existingProduct->updateProduct()) {
                    $message = "Produit modifié avec succès!";
                } else {
                    $error = "Erreur lors de la modification du produit. Veuillez vérifier les logs du serveur.";
                }
            }
        } else {
            $error = "Produit introuvable pour la modification.";
        }
    }
}

// Handle Delete Product
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $product_to_delete = Product::getProductById($product_id); // Fetch the product first

    if ($product_to_delete) {
        $image_path_from_db = $product_to_delete->image_path;
        $file_system_path_to_delete = '../' . $image_path_from_db;

        // Attempt to delete the product from the database first
        if (Product::deleteProduct($product_id)) {
            // If database deletion is successful, then try to delete the image file
            if ($image_path_from_db && file_exists($file_system_path_to_delete)) {
                if (unlink($file_system_path_to_delete)) {
                    $message = "Produit et image associés supprimés avec succès!";
                } else {
                    $message = "Produit supprimé avec succès, mais impossible de supprimer l'image associée. Vérifiez les permissions du dossier 'uploads/products'.";
                    error_log("Failed to delete product image file: {$file_system_path_to_delete}");
                }
            } else {
                $message = "Produit supprimé avec succès. Aucune image ou image non trouvée sur le système de fichiers.";
            }
            header("Location: products.php");
            exit;
        } else {
            // This error implies a DB issue with Product::deleteProduct
            $error = "Erreur lors de la suppression du produit de la base de données. Veuillez vérifier les logs du serveur.";
        }
    } else {
        $error = "Produit introuvable pour la suppression.";
    }
}

// --- Fetch Data for Display ---

$products = Product::getAllProducts();
$existing_categories = Product::getDistinctCategories();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Produits - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/products.css">
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
                    <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                    <a href="products.php" class="active"><i class="fa-solid fa-box"></i> Products</a>
                    <a href="users.php"><i class="fa-solid fa-user"></i> Users</a>
                </nav>
            </div>
            <a href="../logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>

        <main class="main-content">
            <div class="products-header">
                <h1>Gestion des produits</h1>
                <button class="add-product-btn" id="addProductBtn">
                    <i class="fa-solid fa-plus"></i> Ajouter un produit
                </button>
            </div>
            <p class="subtitle">Gérez votre catalogue de produits</p>

            <?php if ($message): ?>
                <div class="alert success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="product-list">
                <?php if (empty($products)): ?>
                    <p>Aucun produit trouvé.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): /* $product is now a Product object */ ?>
                        <div class="product-card">
                            <div class="product-image-wrapper">
                                <?php
                                $display_image_path_admin = $product->image_path ? '../' . $product->image_path : '../assets/images/placeholder_product.png';
                                if ($product->image_path && !file_exists($display_image_path_admin)) {
                                    $display_image_path_admin = '../assets/images/placeholder_product.png';
                                }
                                ?>
                                <img src="<?= htmlspecialchars($display_image_path_admin) ?>" alt="<?= htmlspecialchars($product->nom) ?>">
                            </div>
                            <div class="product-details">
                                <div class="product-name-status">
                                    <h3><?= htmlspecialchars($product->nom) ?></h3>
                                    <span class="product-status <?= strtolower($product->statut ?? 'inconnu') == 'actif' ? 'active' : 'inactive' ?>">
                                        <?= htmlspecialchars($product->statut ?? 'Inconnu') ?>
                                    </span>
                                </div>
                                <p class="product-meta">Catégorie: <?= htmlspecialchars($product->categorie) ?> &bull; Stock: <?= htmlspecialchars($product->stock) ?></p>
                            </div>
                            <div class="product-actions">
                                <span class="product-price">€<?= number_format($product->prix, 2, ',', ' ') ?></span>
                                <button class="edit-btn" title="Modifier"
                                    data-id="<?= htmlspecialchars($product->id) ?>"
                                    data-nom="<?= htmlspecialchars($product->nom) ?>"
                                    data-description="<?= htmlspecialchars($product->description) ?>"
                                    data-prix="<?= htmlspecialchars($product->prix) ?>"
                                    data-categorie="<?= htmlspecialchars($product->categorie) ?>"
                                    data-stock="<?= htmlspecialchars($product->stock) ?>"
                                    data-image_path="<?= htmlspecialchars($product->image_path ?? '') ?>"
                                    data-featured="<?= htmlspecialchars($product->featured) ?>"
                                    data-statut="<?= htmlspecialchars($product->statut ?? 'Actif') ?>">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                                <a href="products.php?action=delete&id=<?= $product->id ?>" class="delete-btn" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible et supprimera également l\'image associée.');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="addProductModal" class="modal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2>Ajouter un nouveau produit</h2>
                    <p>Remplissez les informations pour créer un nouveau produit.</p>
                    <form action="products.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="addProduct">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom_produit">Nom du produit</label>
                                <input type="text" id="nom_produit" name="nom_produit" required>
                            </div>
                            <div class="form-group">
                                <label for="categorie">Catégorie</label>
                                <select id="categorie" name="categorie" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($existing_categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Autre">Autre (préciser si nouvelle)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prix">Prix (€)</label>
                                <input type="number" id="prix" name="prix" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="product_image">Image du produit</label>
                            <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg, image/gif, image/webp">
                            <small class="form-text-muted">Max 5MB. Formats : JPG, JPEG, PNG, GIF, WEBP.</small>
                        </div>
                        <div class="form-group">
                            <label for="statut">Statut</label>
                            <select id="statut" name="statut" required>
                                <option value="Actif">Actif</option>
                                <option value="Inactif">Inactif</option>
                            </select>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="btn-cancel">Annuler</button>
                            <button type="submit" class="btn-create">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="editProductModal" class="modal">
                <div class="modal-content">
                    <span class="close-button edit-close-button">&times;</span>
                    <h2>Modifier le produit</h2>
                    <p>Mettez à jour les informations du produit.</p>
                    <form action="products.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="editProduct">
                        <input type="hidden" id="edit_product_id" name="id">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_nom_produit">Nom du produit</label>
                                <input type="text" id="edit_nom_produit" name="nom_produit" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_categorie">Catégorie</label>
                                <select id="edit_categorie" name="categorie" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($existing_categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Autre">Autre (préciser si nouvelle)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_prix">Prix (€)</label>
                                <input type="number" id="edit_prix" name="prix" step="0.01" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_stock">Stock</label>
                                <input type="number" id="edit_stock" name="stock" min="0" required>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label for="edit_description">Description</label>
                            <textarea id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Image actuelle</label>
                            <div class="current-image-preview" id="current_image_preview">
                                <img src="" alt="Image actuelle" id="edit_current_image">
                                <span class="no-image-text" style="display: none;">Aucune image</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_image">Nouvelle image du produit (laisser vide pour garder l'actuelle)</label>
                            <input type="file" id="edit_product_image" name="edit_product_image" accept="image/png, image/jpeg, image/gif, image/webp">
                            <small class="form-text-muted">Max 5MB. Formats : JPG, JPEG, PNG, GIF, WEBP.</small>
                        </div>
                        <div class="form-group">
                            <label for="edit_statut">Statut</label>
                            <select id="edit_statut" name="statut" required>
                                <option value="Actif">Actif</option>
                                <option value="Inactif">Inactif</option>
                            </select>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="btn-cancel edit-btn-cancel">Annuler</button>
                            <button type="submit" class="btn-create">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        // --- Add Product Modal Functionality ---
        const addProductBtn = document.getElementById('addProductBtn');
        const addProductModal = document.getElementById('addProductModal');
        const closeButton = addProductModal.querySelector('.close-button');
        const btnCancel = addProductModal.querySelector('.btn-cancel');

        addProductBtn.addEventListener('click', () => {
            addProductModal.style.display = 'flex'; // Use flex for centering
        });

        closeButton.addEventListener('click', () => {
            addProductModal.style.display = 'none';
        });

        btnCancel.addEventListener('click', () => {
            addProductModal.style.display = 'none';
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target == addProductModal) {
                addProductModal.style.display = 'none';
            }
        });

        // --- Edit Product Modal Functionality ---
        const editProductBtns = document.querySelectorAll('.edit-btn');
        const editProductModal = document.getElementById('editProductModal');
        const editCloseButton = editProductModal.querySelector('.edit-close-button');
        const editBtnCancel = editProductModal.querySelector('.edit-btn-cancel');

        // Form fields in edit modal
        const editProductId = document.getElementById('edit_product_id');
        const editNomProduit = document.getElementById('edit_nom_produit');
        const editDescription = document.getElementById('edit_description');
        const editPrix = document.getElementById('edit_prix');
        const editCategorie = document.getElementById('edit_categorie');
        const editStock = document.getElementById('edit_stock');
        const editStatut = document.getElementById('edit_statut');
        const editCurrentImage = document.getElementById('edit_current_image');
        const noImageText = document.querySelector('#current_image_preview .no-image-text');

        editProductBtns.forEach(button => {
            button.addEventListener('click', () => {
                const productId = button.dataset.id;
                const nom = button.dataset.nom;
                const description = button.dataset.description;
                const prix = button.dataset.prix;
                const categorie = button.dataset.categorie;
                const stock = button.dataset.stock;
                const statut = button.dataset.statut;
                const imagePath = button.dataset.image_path; // Get the image path from DB (e.g., 'uploads/products/xyz.jpg')

                // Populate the form fields
                editProductId.value = productId;
                editNomProduit.value = nom;
                editDescription.value = description;
                editPrix.value = prix;
                editStock.value = stock;
                editStatut.value = statut;

                // Set current image preview: need to add '../' back for admin panel display
                if (imagePath && imagePath !== '') {
                    const adminDisplayPath = '../' + imagePath; // Prepend '../' for admin panel display
                    editCurrentImage.src = adminDisplayPath;
                    editCurrentImage.style.display = 'block';
                    noImageText.style.display = 'none';
                } else {
                    editCurrentImage.src = '';
                    editCurrentImage.style.display = 'none';
                    noImageText.style.display = 'block';
                }

                // Handle the category dropdown:
                let categoryFound = false;
                for (let i = 0; i < editCategorie.options.length; i++) {
                    if (editCategorie.options[i].value === categorie) {
                        editCategorie.value = categorie;
                        categoryFound = true;
                        break;
                    }
                }
                if (!categoryFound) {
                    editCategorie.value = 'Autre'; // Select "Autre" if the category is not in the list
                }

                // Clear the file input field
                document.getElementById('edit_product_image').value = '';

                editProductModal.style.display = 'flex'; // Use flex for centering
            });
        });

        editCloseButton.addEventListener('click', () => {
            editProductModal.style.display = 'none';
        });

        editBtnCancel.addEventListener('click', () => {
            editProductModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target == editProductModal) {
                editProductModal.style.display = 'none';
            }
        });
    </script>

</body>

</html>