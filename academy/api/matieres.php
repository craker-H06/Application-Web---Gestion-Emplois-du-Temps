<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Récupérer toutes les matières
        $stmt = $conn->query("SELECT * FROM matieres ORDER BY nom");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        // Ajouter une nouvelle matière
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['code']) || !isset($data['nom'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit();
        }

        try {
            $stmt = $conn->prepare("INSERT INTO matieres (code, nom, description) VALUES (?, ?, ?)");
            $stmt->execute([$data['code'], $data['nom'], $data['description'] ?? null]);
            
            echo json_encode([
                'success' => true,
                'id' => $conn->lastInsertId(),
                'message' => 'Matière ajoutée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'ajout de la matière']);
        }
        break;

    case 'PUT':
        // Modifier une matière
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['id']) || !isset($data['code']) || !isset($data['nom'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit();
        }

        try {
            $stmt = $conn->prepare("UPDATE matieres SET code = ?, nom = ?, description = ? WHERE id = ?");
            $stmt->execute([$data['code'], $data['nom'], $data['description'] ?? null, $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Matière modifiée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la modification de la matière']);
        }
        break;

    case 'DELETE':
        // Supprimer une matière
        $id = $_GET['id'] ?? null;
        
        if(!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant']);
            exit();
        }

        try {
            $stmt = $conn->prepare("DELETE FROM matieres WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Matière supprimée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression de la matière']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 