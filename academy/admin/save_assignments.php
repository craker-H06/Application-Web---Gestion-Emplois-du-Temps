<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si les données nécessaires sont présentes
if(!isset($_POST['user_id']) || !isset($_POST['subjects'])) {
    $_SESSION['error'] = "Données manquantes pour l'affectation des matières.";
    header("Location: teacher_approvals.php");
    exit();
}

$user_id = $_POST['user_id'];
$selected_subjects = $_POST['subjects'];

try {
    // Commencer une transaction
    $conn->beginTransaction();

    // Supprimer toutes les affectations existantes pour cet enseignant
    $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Insérer les nouvelles affectations
    $stmt = $conn->prepare("INSERT INTO teacher_subjects (user_id, matiere_id) VALUES (?, ?)");
    foreach($selected_subjects as $subject_id) {
        $stmt->execute([$user_id, $subject_id]);
    }

    // Valider la transaction
    $conn->commit();

    $_SESSION['success'] = "Les matières ont été affectées avec succès.";
} catch(PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $conn->rollBack();
    $_SESSION['error'] = "Une erreur est survenue lors de l'affectation des matières : " . $e->getMessage();
}

// Rediriger vers la page des approbations
header("Location: teacher_approvals.php");
exit();
?>