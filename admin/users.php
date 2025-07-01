<?php
session_start();
require_once '../includes/config.php'; // For the 'connexion' class
require_once 'User.class.php'; // Include the User class

// Check if user is logged in and is an admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$message = ''; // For success messages
$error = '';   // For error messages

// --- Handle Form Submissions ---

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addUser') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Plain text password from form
    $is_admin = isset($_POST['is_admin']) ? 1 : 0; // Checkbox value (1 if checked, 0 if not)

    // Basic validation
    if (empty($nom) || empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires (nom d'utilisateur, email, mot de passe).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        // Hash the password securely before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Create a new User object
        $newUser = new User($nom, $email, $hashed_password, $is_admin);

        if ($newUser->insertUser()) {
            $message = "Utilisateur ajouté avec succès!";
        } else {
            // Error logged by User.class.php (e.g., duplicate email/nom)
            $error = "Erreur lors de l'ajout de l'utilisateur. L'email ou le nom d'utilisateur pourrait déjà exister.";
        }
    }
}

// Handle Edit User Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editUser') {
    $user_id = intval($_POST['id']);
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $new_password = $_POST['password']; // Plain text password from form (can be empty)
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Basic validation
    if (empty($nom) || empty($email)) {
        $error = "Veuillez remplir les champs obligatoires (nom d'utilisateur, email).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        // Retrieve the existing user object
        $existingUser = User::getUserById($user_id);

        if ($existingUser) {
            // Update its properties
            $existingUser->nom = $nom;
            $existingUser->email = $email;
            $existingUser->is_admin = $is_admin;

            // Only update password if a new one is provided in the form
            if (!empty($new_password)) {
                $existingUser->password = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if ($existingUser->updateUser()) {
                $message = "Utilisateur modifié avec succès!";
                // If the currently logged-in admin modified their own admin status, update session
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['is_admin'] = $is_admin;
                }
            } else {
                // Error logged by User.class.php (e.g., duplicate email)
                $error = "Erreur lors de la modification de l'utilisateur. L'email pourrait déjà exister pour un autre compte.";
            }
        } else {
            $error = "Utilisateur introuvable pour la modification.";
        }
    }
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prevent an admin from deleting their own account while logged in
    if ($user_id == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte pendant que vous êtes connecté.";
    } else {
        if (User::deleteUser($user_id)) {
            $message = "Utilisateur supprimé avec succès!";
            // Redirect to clear GET parameters after successful deletion
            header("Location: users.php");
            exit;
        } else {
            $error = "Erreur lors de la suppression de l'utilisateur. Veuillez vérifier les logs du serveur.";
        }
    }
}

// --- Fetch Data for Display ---

// Fetch all users to display in the list using the static method
$users = User::getAllUsers();

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/users.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <a href="products.php"><i class="fa-solid fa-box"></i> Products</a>
                    <a href="users.php" class="active"><i class="fa-solid fa-user"></i> Users</a>
                </nav>
            </div>
            <a href="../logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>

        <main class="main-content">
            <div class="users-header">
                <h1>Gestion des Utilisateurs</h1>
                <button class="add-user-btn" id="addUserBtn">
                    <i class="fa-solid fa-plus"></i> Ajouter un utilisateur
                </button>
            </div>
            <p class="subtitle">Gérez les comptes utilisateurs</p>

            <?php if ($message): ?>
                <div class="alert success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="user-list">
                <?php if (empty($users)): ?>
                    <p>Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <?php foreach ($users as $user): /* $user is now a User object */ ?>
                        <div class="user-card">
                            <div class="user-icon-wrapper">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="user-details">
                                <h3><?= htmlspecialchars($user->nom) ?>
                                    <span class="user-status <?= $user->is_admin ? 'admin' : 'regular' ?>">
                                        <?= $user->is_admin ? 'Admin' : 'Utilisateur' ?>
                                    </span>
                                </h3>
                                <p>Email: <?= htmlspecialchars($user->email) ?></p>
                                <p>Inscrit le: <?= date('d/m/Y H:i', strtotime($user->created_at)) ?></p>
                            </div>
                            <div class="user-actions">
                                <button class="edit-btn" title="Modifier"
                                    data-id="<?= htmlspecialchars($user->id) ?>"
                                    data-nom="<?= htmlspecialchars($user->nom) ?>"
                                    data-email="<?= htmlspecialchars($user->email) ?>"
                                    data-is_admin="<?= htmlspecialchars($user->is_admin) ?>">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                                <?php if ($user->id != $_SESSION['user_id']): // Prevent logged-in admin from deleting their own account ?>
                                    <a href="users.php?action=delete&id=<?= $user->id ?>" class="delete-btn" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="addUserModal" class="modal">
                <div class="modal-content">
                    <span class="close-button">&times;</span>
                    <h2>Ajouter un nouvel utilisateur</h2>
                    <p>Remplissez les informations pour créer un nouveau compte utilisateur.</p>
                    <form action="users.php" method="POST">
                        <input type="hidden" name="action" value="addUser">
                        <div class="form-group">
                            <label for="nom">Nom d'utilisateur</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin">
                            <label for="is_admin">Est administrateur ?</label>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="btn-cancel">Annuler</button>
                            <button type="submit" class="btn-create">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="editUserModal" class="modal">
                <div class="modal-content">
                    <span class="close-button edit-close-button">&times;</span>
                    <h2>Modifier l'utilisateur</h2>
                    <p>Mettez à jour les informations de l'utilisateur.</p>
                    <form action="users.php" method="POST">
                        <input type="hidden" name="action" value="editUser">
                        <input type="hidden" id="edit_user_id" name="id">
                        <div class="form-group">
                            <label for="edit_nom">Nom d'utilisateur</label>
                            <input type="text" id="edit_nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Nouveau mot de passe (laisser vide si inchangé)</label>
                            <input type="password" id="edit_password" name="password">
                        </div>
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="edit_is_admin" name="is_admin">
                            <label for="edit_is_admin">Est administrateur ?</label>
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
        // --- Add User Modal Functionality ---
        const addUserBtn = document.getElementById('addUserBtn');
        const addUserModal = document.getElementById('addUserModal');
        const closeAddUserButton = addUserModal.querySelector('.close-button');
        const btnAddUserCancel = addUserModal.querySelector('.btn-cancel');

        addUserBtn.addEventListener('click', () => {
            addUserModal.style.display = 'flex'; // Use flex for centering
        });

        closeAddUserButton.addEventListener('click', () => {
            addUserModal.style.display = 'none';
        });

        btnAddUserCancel.addEventListener('click', () => {
            addUserModal.style.display = 'none';
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', (event) => {
            if (event.target == addUserModal) {
                addUserModal.style.display = 'none';
            }
        });


        // --- Edit User Modal Functionality ---
        const editUserBtns = document.querySelectorAll('.edit-btn');
        const editUserModal = document.getElementById('editUserModal');
        const editCloseUserButton = editUserModal.querySelector('.edit-close-button');
        const editBtnUserCancel = editUserModal.querySelector('.btn-cancel');

        // Form fields in edit modal
        const editUserId = document.getElementById('edit_user_id');
        const editnom = document.getElementById('edit_nom');
        const editEmail = document.getElementById('edit_email');
        const editIsAdmin = document.getElementById('edit_is_admin');
        const editPassword = document.getElementById('edit_password'); // New password field

        editUserBtns.forEach(button => {
            button.addEventListener('click', () => {
                const userId = button.dataset.id;
                const nom = button.dataset.nom;
                const email = button.dataset.email;
                const isAdmin = button.dataset.is_admin; // Will be '0' or '1'

                // Populate the form fields
                editUserId.value = userId;
                editnom.value = nom;
                editEmail.value = email;
                editIsAdmin.checked = (isAdmin === '1'); // Set checkbox based on value

                // IMPORTANT: Always clear password field when opening edit modal for security
                editPassword.value = '';

                editUserModal.style.display = 'flex'; // Use flex for centering
            });
        });

        editCloseUserButton.addEventListener('click', () => {
            editUserModal.style.display = 'none';
        });

        editBtnUserCancel.addEventListener('click', () => {
            editUserModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target == editUserModal) {
                editUserModal.style.display = 'none';
            }
        });
    </script>

</body>

</html>