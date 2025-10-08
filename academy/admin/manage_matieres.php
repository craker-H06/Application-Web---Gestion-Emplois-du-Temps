<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Assurer que la requête est une méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add_matiere') {
        // Récupérer et valider les données du formulaire
        $code = htmlspecialchars(trim($_POST['code'] ?? ''));
        $nom = htmlspecialchars(trim($_POST['nom'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $level_id = filter_var($_POST['level_id'] ?? 0, FILTER_VALIDATE_INT);
        $nombre_heures = filter_var($_POST['nombre_heures'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]); // Récupérer et valider le nombre d'heures

        $errors = [];

        if (empty($code)) $errors[] = "Le code de la matière est requis.";
        if (empty($nom)) $errors[] = "Le nom de la matière est requis.";
        if ($level_id <= 0) $errors[] = "Niveau invalide.";
        // Valider que nombre_heures est un entier positif ou nul
        if ($nombre_heures === false) $errors[] = "Nombre d'heures invalide.";

        if (empty($errors)) {
            try {
                // Préparer la requête d'insertion
                // Inclure la colonne nombre_heures dans la requête INSERT
                $sql = "INSERT INTO matieres (code, nom, description, nombre_heures, level_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                // Exécuter la requête
                // Inclure la valeur de nombre_heures dans l'exécution
                $success = $stmt->execute([$code, $nom, $description, $nombre_heures, $level_id]);

                if ($success) {
                    // Redirection vers le dashboard du niveau avec un message de succès
                    $_SESSION['success'] = "Matière ajoutée avec succès.";
                } else {
                     // Gérer l'erreur d'insertion (ex: code matière déjà existant)
                     // On peut vérifier le code d'erreur PDO pour plus de précision si besoin (SQLSTATE 23000 pour intégrité constraint violation).
                    $_SESSION['error'] = "Erreur lors de l'ajout de la matière. Le code pourrait déjà exister.";
                }

            } catch (PDOException $e) {
                // Log l'erreur ou afficher un message générique
                error_log("Erreur PDO lors de l'ajout matière: " . $e->getMessage());
                $_SESSION['error'] = "Une erreur de base de données est survenue lors de l'ajout de la matière.";
            }
        } else {
            // Stocker les erreurs et les données du formulaire pour les réafficher si besoin
            $_SESSION['errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
             $_SESSION['error'] = "Veuillez corriger les erreurs dans le formulaire."; // Message générique
        }

         // Rediriger vers la page du dashboard de niveau quel que soit le résultat
         header("Location: level_dashboard.php?level_id=" . $level_id);
         exit();

    }
    // Ajoutez ici d'autres actions si nécessaire (modifier, supprimer matière, etc.)

} else {
    // Si la méthode n'est pas POST, rediriger ou afficher une erreur
    header("Location: dashboard.php"); // Rediriger vers le dashboard principal
    exit();
}
?> 