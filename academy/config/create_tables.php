<?php
require_once 'database.php';

try {
    // Table des utilisateurs
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        prenom VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'enseignant') NOT NULL,
        statut ENUM('actif', 'inactif') DEFAULT 'actif',
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Table des matières
    $sql = "CREATE TABLE IF NOT EXISTS matieres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        code VARCHAR(20) NOT NULL UNIQUE,
        description TEXT
    )";
    $conn->exec($sql);

    // Table des attributions
    $sql = "CREATE TABLE IF NOT EXISTS attributions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enseignant_id INT NOT NULL,
        matiere_id INT NOT NULL,
        date_attribution DATETIME DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
        FOREIGN KEY (enseignant_id) REFERENCES users(id),
        FOREIGN KEY (matiere_id) REFERENCES matieres(id)
    )";
    $conn->exec($sql);

    // Table des disponibilités
    $sql = "CREATE TABLE IF NOT EXISTS disponibilites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enseignant_id INT NOT NULL,
        matiere_id INT NOT NULL,
        jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
        heure_debut TIME NOT NULL,
        heure_fin TIME NOT NULL,
        date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (enseignant_id) REFERENCES users(id),
        FOREIGN KEY (matiere_id) REFERENCES matieres(id)
    )";
    $conn->exec($sql);

    // Table des emplois du temps
    $sql = "CREATE TABLE IF NOT EXISTS emplois_temps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        semaine INT NOT NULL,
        annee INT NOT NULL,
        statut ENUM('en_cours', 'valide', 'archive') DEFAULT 'en_cours',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);

    // Table des détails emplois du temps
    $sql = "CREATE TABLE IF NOT EXISTS details_emplois_temps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emploi_temps_id INT NOT NULL,
        enseignant_id INT NOT NULL,
        matiere_id INT NOT NULL,
        jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
        heure_debut TIME NOT NULL,
        heure_fin TIME NOT NULL,
        salle VARCHAR(50),
        FOREIGN KEY (emploi_temps_id) REFERENCES emplois_temps(id),
        FOREIGN KEY (enseignant_id) REFERENCES users(id),
        FOREIGN KEY (matiere_id) REFERENCES matieres(id)
    )";
    $conn->exec($sql);

    echo "Les tables ont été créées avec succès!";

} catch(PDOException $e) {
    echo "Erreur lors de la création des tables : " . $e->getMessage();
}
?> 