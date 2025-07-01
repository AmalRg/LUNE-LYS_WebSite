<?php

require_once '../includes/config.php'; // Assuming config.php contains the 'connexion' class

class User
{
    // Attributes (adjust based on your 'users' table structure)
    public $id;
    public $nom;
    public $email;
    public $password; // Hashed password
    public $is_admin; // Boolean (or tinyint in DB)
    public $created_at;

    // Constructor
    public function __construct(
        $nom = '',
        $email = '',
        $password = '', // This should be the HASHED password when setting
        $is_admin = 0,
        $id = null,
        $created_at = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->password = $password;
        $this->is_admin = $is_admin;
        $this->created_at = $created_at;
    }

    // Get PDO instance
    private function getPdo()
    {
        $cnx = new connexion();
        return $cnx->CNXbase();
    }

    /**
     * Inserts a new user into the database.
     * IMPORTANT: Password should be hashed BEFORE passing to the constructor or this method.
     * Example: password_hash($plainPassword, PASSWORD_DEFAULT)
     * @return bool True on success, false on failure.
     */
    public function insertUser()
    {
        $pdo = $this->getPdo();
        $sql = "INSERT INTO users (nom, email, password, is_admin, created_at)
                VALUES (:nom, :email, :password, :is_admin, NOW())";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $this->password); // Expects hashed password
            $stmt->bindParam(':is_admin', $this->is_admin, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error inserting user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a single user by their ID.
     * @param int $id The user ID.
     * @return User|null The user object if found, null otherwise.
     */
    public static function getUserById($id)
    {
        $user = null;
        $pdo = (new connexion())->CNXbase();
        $sql = "SELECT * FROM users WHERE id = :id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $user = new User(
                    $data['nom'],
                    $data['email'],
                    $data['password'],
                    $data['is_admin'],
                    $data['id'],
                    $data['created_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
        }
        return $user;
    }

    /**
     * Retrieves a single user by their email. Used for login.
     * @param string $email The user's email.
     * @return User|null The user object if found, null otherwise.
     */
    public static function getUserByEmail($email)
    {
        $user = null;
        $pdo = (new connexion())->CNXbase();
        $sql = "SELECT * FROM users WHERE email = :email";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $user = new User(
                    $data['nom'],
                    $data['email'],
                    $data['password'],
                    $data['is_admin'],
                    $data['id'],
                    $data['created_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
        }
        return $user;
    }

    /**
     * Retrieves all users from the database.
     * @return array An array of User objects.
     */
    public static function getAllUsers()
    {
        $users = [];
        $pdo = (new connexion())->CNXbase();
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        try {
            $stmt = $pdo->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new User(
                    $row['nom'],
                    $row['email'],
                    $row['password'],
                    $row['is_admin'],
                    $row['id'],
                    $row['created_at']
                );
            }
        } catch (PDOException $e) {
            error_log("Error fetching all users: " . $e->getMessage());
        }
        return $users;
    }

    /**
     * Updates an existing user in the database.
     * IMPORTANT: Password should be hashed BEFORE passing to the constructor or this method if it's being updated.
     * @return bool True on success, false on failure.
     */
    public function updateUser()
    {
        if ($this->id === null) {
            error_log("Cannot update user: ID is not set.");
            return false;
        }
        $pdo = $this->getPdo();
        $sql = "UPDATE users SET
                    nom = :nom,
                    email = :email,
                    password = :password,
                    is_admin = :is_admin
                WHERE id = :id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':password', $this->password);
            $stmt->bindParam(':is_admin', $this->is_admin, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a user from the database by their ID.
     * @param int $id The user ID to delete.
     * @return bool True on success, false on failure.
     */
    public static function deleteUser($id)
    {
        $pdo = (new connexion())->CNXbase();
        $sql = "DELETE FROM users WHERE id = :id";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if a given plain password matches the hashed password of this user.
     * @param string $plainPassword The plain text password to verify.
     * @return bool True if the password matches, false otherwise.
     */
    public function verifyPassword($plainPassword)
    {
        return password_verify($plainPassword, $this->password);
    }
}