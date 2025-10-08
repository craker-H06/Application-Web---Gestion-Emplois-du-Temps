<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_POST['subject_id']) || !isset($_POST['level_id'])) {
    header("Location: dashboard.php");
    exit();
}

$subject_id = intval($_POST['subject_id']);
$level_id = intval($_POST['level_id']);

try {
    $conn->beginTransaction();

    // Supprimer les attributions de la matière aux enseignants
    $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE matiere_id = ?");
    $stmt->execute([$subject_id]);

    // Supprimer les cours de l'emploi du temps
    $stmt = $conn->prepare("DELETE FROM schedule WHERE matiere_id = ?");
    $stmt->execute([$subject_id]);

    // Supprimer la matière
    $stmt = $conn->prepare("DELETE FROM matieres WHERE id = ?");
    $stmt->execute([$subject_id]);

    $conn->commit();
    $_SESSION['success'] = "Matière supprimée avec succès.";
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Erreur lors de la suppression de la matière : " . $e->getMessage();
}

// Rediriger vers la page de gestion des matières
header("Location: manage_subjects.php?level_id=" . $level_id);
exit(); 