<?php
class connexion {
    public function CNXbase() {
        return new PDO('mysql:host=localhost;dbname=bijoux_store', 'root', '');
    }
}
?>
