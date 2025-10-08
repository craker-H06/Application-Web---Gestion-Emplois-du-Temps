<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un enseignant
if(!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'enseignant') {
    header("Location: ../login.php");
    exit();
}

// Assurer que la requête est une méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer l'user_id depuis la session (plus sécurisé que depuis un champ caché)
    $user_id = $_SESSION['user_id'];

    // Récupérer et valider les données du formulaire
    $ville = htmlspecialchars(trim($_POST['ville'] ?? ''));
    $quartier = htmlspecialchars(trim($_POST['quartier'] ?? ''));
    $dernier_diplome = htmlspecialchars(trim($_POST['dernier_diplome'] ?? ''));
    $statut_matrimonial = $_POST['statut_matrimonial'] ?? '';
    $nombre_enfants = filter_var($_POST['nombre_enfants'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $langues_parlees = htmlspecialchars(trim($_POST['langues_parlees'] ?? ''));
    $langues_ecrites = htmlspecialchars(trim($_POST['langues_ecrites'] ?? ''));

    $errors = [];

    if (empty($ville)) $errors[] = "La ville est requise.";
    if (empty($dernier_diplome)) $errors[] = "Le dernier diplôme est requis.";
    // Validation simple pour statut matrimonial - vérifier s'il fait partie des options si nécessaire
    // Validation pour nombre_enfants - filter_var avec min_range gère déjà les non-entiers et les négatifs

    if (empty($errors)) {
        try {
            // Vérifier si un profil existe déjà pour cet utilisateur (double sécurité)
            $stmt_check = $conn->prepare("SELECT id FROM teacher_profiles WHERE user_id = ?");
            $stmt_check->execute([$user_id]);
            if ($stmt_check->fetch()) {
                // Un profil existe déjà, ne pas insérer et rediriger (peut-être avec un message)
                 $_SESSION['error'] = "Votre profil a déjà été soumis ou est en cours de traitement.";
            } else {
                 // Préparer la requête d'insertion
                $sql = "INSERT INTO teacher_profiles (user_id, ville, quartier, dernier_diplome, statut_matrimonial, nombre_enfants, langues_parlees, langues_ecrites) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                // Exécuter la requête
                $success = $stmt->execute([$user_id, $ville, $quartier, $dernier_diplome, $statut_matrimonial, $nombre_enfants, $langues_parlees, $langues_ecrites]);

                if ($success) {
                    // Redirection vers la page d'attente avec un message de succès
                    $_SESSION['success'] = "Votre profil a été soumis pour approbation.";
                } else {
                    // Gérer l'erreur d'insertion
                    $_SESSION['error'] = "Erreur lors de la soumission de votre profil.";
                }
            }

        } catch (PDOException $e) {
            // Log l'erreur de base de données
            error_log("Erreur PDO lors de la soumission profil enseignant: " . $e->getMessage());
            $_SESSION['error'] = "Une erreur de base de données est survenue lors de la soumission de votre profil.";
        }
    } else {
        // Stocker les erreurs et les données du formulaire (optionnel pour réaffichage dans le formulaire)
        $_SESSION['errors'] = $errors;
         $_SESSION['form_data'] = $_POST; // Stocker les données valides pour pré-remplir si redirection
         $_SESSION['error'] = "Veuillez corriger les erreurs dans le formulaire."; // Message générique pour les erreurs de validation
    }

    // Rediriger vers la page d'attente (ou la page de complétion si erreurs)
    if (!empty($errors)) {
         header("Location: complete_profile.php"); // Rediriger vers le formulaire si erreurs de validation
    } else {
         header("Location: waiting_approval.php"); // Rediriger vers la page d'attente si soumission réussie ou déjà soumis
    }
    exit();

} else {
    // Si la méthode n'est pas POST, rediriger ou afficher une erreur
    header("Location: complete_profile.php"); // Rediriger vers le formulaire si accès direct
    exit();
}
?>