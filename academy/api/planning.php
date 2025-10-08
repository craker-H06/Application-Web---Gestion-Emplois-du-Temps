<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer le dernier planning
    $stmt = $conn->query("
        SELECT p.*, pd.jour, pd.heure, pd.matiere_id, pd.enseignant_id,
               m.nom as matiere_nom, u.nom as enseignant_nom, u.prenom as enseignant_prenom
        FROM planning p
        JOIN planning_details pd ON p.id = pd.planning_id
        JOIN matieres m ON pd.matiere_id = m.id
        JOIN users u ON pd.enseignant_id = u.id
        WHERE p.id = (SELECT MAX(id) FROM planning)
        ORDER BY pd.jour, pd.heure
    ");
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Structurer le planning
    $planning = [];
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    $heures = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

    // Initialiser la structure
    foreach($jours as $jour) {
        $planning[$jour] = [];
        foreach($heures as $heure) {
            $planning[$jour][$heure] = null;
        }
    }

    // Remplir le planning
    foreach($details as $detail) {
        $planning[$detail['jour']][$detail['heure']] = [
            'matiere_id' => $detail['matiere_id'],
            'matiere_nom' => $detail['matiere_nom'],
            'enseignant_id' => $detail['enseignant_id'],
            'enseignant_nom' => $detail['enseignant_nom'] . ' ' . $detail['enseignant_prenom']
        ];
    }

    echo json_encode([
        'success' => true,
        'planning' => $planning,
        'date_creation' => $details[0]['date_creation'] ?? null
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération du planning']);
}
?> 