<?php
$db_host = 'localhost';
$db_name = 'academy_planning'; // Nom de la base de données confirmé
$db_user = 'root'; // Utilisateur par défaut sous XAMPP
$db_pass = ''; // Mot de passe vide par défaut

$conn = null;

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    // Configurer PDO pour lancer des exceptions en cas d'erreur
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Désactiver les émulations de requêtes préparées pour une meilleure sécurité
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
