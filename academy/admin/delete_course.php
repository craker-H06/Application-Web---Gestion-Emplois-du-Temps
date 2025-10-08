<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_POST['course_id']) || !isset($_POST['level_id'])) {
    header("Location: dashboard.php");
    exit();
}

$course_id = intval($_POST['course_id']);
$level_id = intval($_POST['level_id']);

try {
    // Supprimer le cours
    $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ?");
    $stmt->execute([$course_id]);
    
    $_SESSION['success'] = "Cours supprimé avec succès.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression du cours : " . $e->getMessage();
}

// Rediriger vers la page de l'emploi du temps
header("Location: schedule.php?level_id=" . $level_id);
exit(); 