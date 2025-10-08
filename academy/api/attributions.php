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
            // Récupérer toutes les attributions pour l'admin
            $stmt = $conn->query("
                SELECT a.*, u.nom, u.prenom, u.email, m.nom as matiere_nom, m.code as matiere_code
                FROM attributions a
                JOIN users u ON a.enseignant_id = u.id
                JOIN matieres m ON a.matiere_id = m.id
                ORDER BY a.date_attribution DESC
            ");
        } else {
            // Récupérer les attributions de l'enseignant connecté
            $stmt = $conn->prepare("
                SELECT a.*, m.nom as matiere_nom, m.code as matiere_code
                FROM attributions a
                JOIN matieres m ON a.matiere_id = m.id
                WHERE a.enseignant_id = ?
                ORDER BY a.date_attribution DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        if($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Accès non autorisé']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['enseignant_id']) || !isset($data['matiere_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit();
        }

        try {
            // Vérifier si l'attribution existe déjà
            $stmt = $conn->prepare("
                SELECT id FROM attributions 
                WHERE enseignant_id = ? AND matiere_id = ? AND statut != 'refuse'
            ");
            $stmt->execute([$data['enseignant_id'], $data['matiere_id']]);
            
            if($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cette attribution existe déjà']);
                exit();
            }

            // Ajouter l'attribution
            $stmt = $conn->prepare("
                INSERT INTO attributions (enseignant_id, matiere_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$data['enseignant_id'], $data['matiere_id']]);
            
            // Récupérer les informations pour l'email
            $stmt = $conn->prepare("
                SELECT u.email, u.nom, u.prenom, m.nom as matiere_nom
                FROM users u
                JOIN matieres m ON m.id = ?
                WHERE u.id = ?
            ");
            $stmt->execute([$data['matiere_id'], $data['enseignant_id']]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            // Envoyer l'email
            require_once '../config/mail.php';
            $subject = "Nouvelle attribution de matière";
            $message = "Bonjour {$info['prenom']} {$info['nom']},\n\n";
            $message .= "Une nouvelle matière vous a été attribuée : {$info['matiere_nom']}\n";
            $message .= "Veuillez vous connecter à votre espace pour renseigner vos disponibilités.\n\n";
            $message .= "Cordialement,\nL'équipe administrative";
            
            sendMail($info['email'], $subject, $message);
            
            echo json_encode([
                'success' => true,
                'id' => $conn->lastInsertId(),
                'message' => 'Attribution ajoutée avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'ajout de l\'attribution']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['id']) || !isset($data['statut'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit();
        }

        try {
            $stmt = $conn->prepare("UPDATE attributions SET statut = ? WHERE id = ?");
            $stmt->execute([$data['statut'], $data['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Statut de l\'attribution mis à jour avec succès'
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la mise à jour de l\'attribution']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}
?> 