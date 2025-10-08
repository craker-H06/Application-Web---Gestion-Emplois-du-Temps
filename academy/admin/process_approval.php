<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si l'action et l'ID du profil sont présents dans l'URL
if (isset($_GET['action'], $_GET['profile_id'])) {
    $action = $_GET['action'];
    $profile_id = $_GET['profile_id'];

    // Valider l'action
    if ($action === 'approve' || $action === 'reject') {

        try {
            // Commencer une transaction
            $conn->beginTransaction();

            // Vérifier si le profil existe
            $stmt = $conn->prepare("SELECT user_id, status FROM teacher_profiles WHERE id = ?");
            $stmt->execute([$profile_id]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$profile) {
                throw new Exception("Profil non trouvé.");
            }

            // Déterminer le nouveau statut
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';

            // Mettre à jour le statut du profil
            $stmt = $conn->prepare("UPDATE teacher_profiles SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $profile_id]);

            // Si le profil est approuvé, s'assurer que l'utilisateur a le rôle 'enseignant'
            if ($new_status === 'approved') {
                $stmt = $conn->prepare("UPDATE users SET role = 'enseignant' WHERE id = ?");
                $stmt->execute([$profile['user_id']]);
            }

            // Valider la transaction
            $conn->commit();

            $_SESSION['success'] = "Le profil a été " . ($action === 'approve' ? 'approuvé' : 'refusé') . " avec succès.";
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollBack();
            error_log("Erreur lors du traitement de l'approbation : " . $e->getMessage());
            $_SESSION['error'] = "Une erreur est survenue lors du traitement de l'approbation : " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Action invalide.";
    }
} else {
    $_SESSION['error'] = "Action ou ID de profil manquant.";
}

// Rediriger vers la page des approbations
header("Location: teacher_approvals.php");
exit();
?>