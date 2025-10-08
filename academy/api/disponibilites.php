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

switch($method) {
    case 'GET':
        if($_SESSION['role'] === 'admin') {
            // Récupérer toutes les disponibilités pour l'admin
            $stmt = $conn->query("
                SELECT d.*, u.nom, u.prenom, m.nom as matiere_nom
                FROM disponibilites d
                JOIN users u ON d.enseignant_id = u.id
                JOIN matieres m ON d.matiere_id = m.id
                ORDER BY d.jour, d.heure_debut
            ");
        } else {
            // Récupérer les disponibilités de l'enseignant connecté
            $stmt = $conn->prepare("
                SELECT d.*, m.nom as matiere_nom
                FROM disponibilites d
                JOIN matieres m ON d.matiere_id = m.id
                WHERE d.enseignant_id = ?
                ORDER BY d.jour, d.heure_debut
            ");
            $stmt->execute([$_SESSION['user_id']]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['matiere_id']) || !isset($data['jour']) || 
           !isset($data['heure_debut']) || !isset($data['heure_fin'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit();
        }

        try {
            // Vérifier si l'enseignant est bien attribué à cette matière
            $stmt = $conn->prepare("
                SELECT id FROM attributions 
                WHERE enseignant_id = ? AND matiere_id = ? AND statut = 'accepte'
            ");
            $stmt->execute([$_SESSION['user_id'], $data['matiere_id']]);
            
            if($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Vous n\'êtes pas attribué à cette matière']);
                exit();
            }

            // Vérifier les chevauchements
            $stmt = $conn->prepare("
                SELECT id FROM disponibilites 
                WHERE enseignant_id = ? AND jour = ? AND 
                ((heure_debut <= ? AND heure_fin > ?) OR 
                (heure_debut < ? AND heure_fin >= ?) OR
                (heure_debut >= ? AND heure_fin <= ?))
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $data['jour'],
                $data['heure_debut'],
                $data['heure_debut'],
                $data['heure_fin'],
                $data['heure_fin'],
                $data['heure_debut'],
                $data['heure_fin']
            ]);
            
            if($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cette plage horaire chevauche une autre disponibilité']);
                exit();
            }

            // Ajouter la disponibilité
            $stmt = $conn->prepare("
                INSERT INTO disponibilites (enseignant_id, matiere_id, jour, heure_debut, heure_fin) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $data['matiere_id'],
                $data['jour'],
                $data['heure_debut'],
                $data['heure_fin']
            ]);
            
            echo json_encode([
                'success' => true,
                'id' => $conn->lastInsertId(),
                'message' => 'Disponibilité ajoutée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'ajout de la disponibilité']);
        }
        break;

    case 'DELETE':
        if(!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant']);
            exit();
        }

        try {
            $stmt = $conn->prepare("
                DELETE FROM disponibilites 
                WHERE id = ? AND enseignant_id = ?
            ");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            
            if($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Disponibilité non trouvée']);
                exit();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Disponibilité supprimée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la suppression de la disponibilité']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 