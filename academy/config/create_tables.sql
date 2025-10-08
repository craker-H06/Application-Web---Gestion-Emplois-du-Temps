-- Création de la base de données
CREATE DATABASE IF NOT EXISTS academy_planning;
USE academy_planning;

-- Table des niveaux académiques
CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des spécialités (liées aux niveaux)
CREATE TABLE IF NOT EXISTS specializations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
);

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'enseignant', 'etudiant') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des matières
CREATE TABLE IF NOT EXISTS matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    description TEXT,
    level_id INT,
    nombre_heures INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE SET NULL
);

-- Table des attributions
CREATE TABLE IF NOT EXISTS attributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id INT NOT NULL,
    matiere_id INT NOT NULL,
    statut ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente',
    date_attribution TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enseignant_id) REFERENCES users(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
);

-- Table des disponibilités
CREATE TABLE IF NOT EXISTS disponibilites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enseignant_id INT NOT NULL,
    matiere_id INT NOT NULL,
    jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enseignant_id) REFERENCES users(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
);

-- Table du planning
CREATE TABLE IF NOT EXISTS planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des détails du planning
CREATE TABLE IF NOT EXISTS planning_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planning_id INT NOT NULL,
    jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi') NOT NULL,
    heure TIME NOT NULL,
    matiere_id INT NOT NULL,
    enseignant_id INT NOT NULL,
    FOREIGN KEY (planning_id) REFERENCES planning(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id),
    FOREIGN KEY (enseignant_id) REFERENCES users(id)
);

-- Table des profils enseignants
CREATE TABLE IF NOT EXISTS teacher_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ville VARCHAR(100) NOT NULL,
    quartier VARCHAR(100),
    dernier_diplome VARCHAR(100) NOT NULL,
    statut_matrimonial VARCHAR(50) NOT NULL,
    nombre_enfants INT DEFAULT 0,
    langues_parlees VARCHAR(255),
    langues_ecrites VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table de liaison entre enseignants et matières
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    matiere_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (user_id, matiere_id)
);

-- Table des salles
CREATE TABLE IF NOT EXISTS salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    capacite INT NOT NULL,
    type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des emplois du temps
CREATE TABLE IF NOT EXISTS emplois_du_temps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matiere_id INT NOT NULL,
    enseignant_id INT NOT NULL,
    salle_id INT NOT NULL,
    jour ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (salle_id) REFERENCES salles(id) ON DELETE CASCADE
);

-- Insertion d'un administrateur par défaut (Mot de passe : admin123)
INSERT IGNORE INTO users (nom, prenom, email, password, role) VALUES
('Admin', 'Principal', 'admin@ascencia.com', '$2y$10$kE/S1v0h6B1x2E5K8R4Z/uZl9W/Q2Y3P7b5A.1d4f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u', 'admin'); -- Assurez-vous que le hash correspond à 'admin123' ou changez le mot de passe ici

-- Table de jointure pour les matières attribuées aux enseignants
CREATE TABLE IF NOT EXISTS teacher_subjects (
    teacher_id INT NOT NULL,
    matiere_id INT NOT NULL,
    PRIMARY KEY (teacher_id, matiere_id), -- Une paire enseignant-matière unique
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE, -- Si l'enseignant est supprimé, ses attributions le sont aussi
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE -- Si une matière est supprimée, les attributions associées le sont aussi
);

-- Table pour les disponibilités approuvées
CREATE TABLE IF NOT EXISTS disponibilites_approuvees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    disponibilite_id INT NOT NULL,
    user_id INT NOT NULL,
    date_dispo DATE NOT NULL,
    periode ENUM('periode1', 'periode2', 'periode3') NOT NULL,
    matiere_id INT NOT NULL,
    level_id INT NOT NULL,
    date_approbation DATETIME NOT NULL,
    approbateur_id INT NOT NULL,
    statut ENUM('confirmee', 'annulee') DEFAULT 'confirmee',
    commentaire TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disponibilite_id) REFERENCES disponibilites(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
    FOREIGN KEY (approbateur_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_date_dispo (date_dispo),
    INDEX idx_user_id (user_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger pour enregistrer automatiquement les disponibilités approuvées
DELIMITER //
CREATE TRIGGER after_disponibilite_approbation
AFTER UPDATE ON disponibilites
FOR EACH ROW
BEGIN
    IF NEW.statut = 'approuve' AND OLD.statut != 'approuve' THEN
        INSERT INTO disponibilites_approuvees (
            disponibilite_id,
            user_id,
            date_dispo,
            periode,
            matiere_id,
            level_id,
            date_approbation,
            approbateur_id
        )
        SELECT 
            NEW.id,
            NEW.user_id,
            NEW.date_dispo,
            NEW.periode,
            ts.matiere_id,
            m.level_id,
            NOW(),
            (SELECT id FROM users WHERE role = 'admin' LIMIT 1)
        FROM teacher_subjects ts
        JOIN matieres m ON ts.matiere_id = m.id
        WHERE ts.user_id = NEW.user_id
        LIMIT 1;
    END IF;
END//
DELIMITER ; 