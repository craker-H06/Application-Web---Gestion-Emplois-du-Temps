<?php
// Activer le rapport d'erreurs mais ne pas les afficher directement
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1); // Activer le log des erreurs
ini_set('error_log', __DIR__ . '/../logs/update_disponibilite_error.log'); // Définir le fichier de log

// S'assurer que le type de contenu est bien JSON et prévenir la mise en cache
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Inclure les fichiers nécessaires - assurez-vous qu'ils ne produisent pas de sortie
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Fonction utilitaire pour envoyer une réponse JSON et quitter
function sendJsonResponse($success, $message, $data = null) {
    // S'assurer qu'aucune sortie n'a eu lieu avant les en-têtes
    if (headers_sent()) {
        error_log("Headers already sent before sending JSON response in update_disponibilite_status.php");
    }
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier les paramètres requis
if (!isset($_POST['dispo_id']) || !isset($_POST['status']) || !isset($_POST['teacher_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

$dispo_id = (int)$_POST['dispo_id'];
$teacher_id = (int)$_POST['teacher_id'];
$status = $_POST['status'];

// Valider le statut (selon l'enum de la table)
if (!in_array($status, ['approuve', 'rejete'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

try {
    // Vérifier si la disponibilité existe et appartient à l'enseignant
    $query = "SELECT id, statut, date_dispo FROM disponibilites WHERE id = :dispo_id AND user_id = :teacher_id";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Erreur lors de la préparation de la requête');
    }
    
    $stmt->bindValue(':dispo_id', $dispo_id, PDO::PARAM_INT);
    $stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'exécution de la requête');
    }
    
    $disponibilite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$disponibilite) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Disponibilité non trouvée']);
        exit();
    }

    // Vérifier si la date n'est pas passée
    $date_dispo = strtotime($disponibilite['date_dispo']);
    $today = strtotime('today');
    
    if ($date_dispo === false || $today === false) {
        throw new Exception('Erreur lors de la conversion des dates');
    }
    
    if ($date_dispo < $today) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Impossible de modifier le statut d\'une disponibilité passée']);
        exit();
    }

    // Vérifier si le statut actuel n'est pas déjà celui demandé
    if ($disponibilite['statut'] === $status) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Le statut est déjà ' . ($status === 'approuve' ? 'approuvé' : 'rejeté')]);
        exit();
    }

    // Mettre à jour le statut
    $update_query = "UPDATE disponibilites SET statut = :status WHERE id = :dispo_id AND user_id = :teacher_id";
    $update_stmt = $conn->prepare($update_query);
    
    if (!$update_stmt) {
        throw new Exception('Erreur lors de la préparation de la requête de mise à jour');
    }
    
    $update_stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $update_stmt->bindValue(':dispo_id', $dispo_id, PDO::PARAM_INT);
    $update_stmt->bindValue(':teacher_id', $teacher_id, PDO::PARAM_INT);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }

    if ($update_stmt->rowCount() === 0) {
        throw new Exception('Aucune ligne n\'a été mise à jour');
    }

    // Envoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $status === 'approuve' ? 'Disponibilité approuvée avec succès' : 'Disponibilité rejetée avec succès',
        'new_status' => $status
    ]);

} catch (Exception $e) {
    error_log("Erreur dans update_disponibilite_status.php : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour']);
}

// Le code ne devrait jamais arriver ici si tout va bien grâce aux exit();

?> 