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

if($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer toutes les matières avec leurs attributions acceptées
    $stmt = $conn->query("
        SELECT m.*, a.enseignant_id, u.nom, u.prenom
        FROM matieres m
        JOIN attributions a ON m.id = a.matiere_id
        JOIN users u ON a.enseignant_id = u.id
        WHERE a.statut = 'accepte'
        ORDER BY m.nom
    ");
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer toutes les disponibilités
    $stmt = $conn->query("
        SELECT d.*, u.nom, u.prenom, m.nom as matiere_nom
        FROM disponibilites d
        JOIN users u ON d.enseignant_id = u.id
        JOIN matieres m ON d.matiere_id = m.id
        ORDER BY d.jour, d.heure_debut
    ");
    $disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialiser le planning
    $planning = [];
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    $heures = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

    // Créer la structure du planning
    foreach($jours as $jour) {
        $planning[$jour] = [];
        foreach($heures as $heure) {
            $planning[$jour][$heure] = null;
        }
    }

    // Fonction pour vérifier si un créneau est disponible
    function isCreneauDisponible($planning, $jour, $heure, $enseignant_id) {
        // Vérifier si le créneau est déjà occupé
        if($planning[$jour][$heure] !== null) {
            return false;
        }

        // Vérifier si l'enseignant a déjà un cours à cette heure
        foreach($planning[$jour] as $h => $cours) {
            if($cours !== null && $cours['enseignant_id'] === $enseignant_id) {
                return false;
            }
        }

        return true;
    }

    // Algorithme de génération du planning
    foreach($matieres as $matiere) {
        $enseignant_id = $matiere['enseignant_id'];
        $disponibilites_enseignant = array_filter($disponibilites, function($d) use ($enseignant_id, $matiere) {
            return $d['enseignant_id'] === $enseignant_id && $d['matiere_id'] === $matiere['id'];
        });

        foreach($disponibilites_enseignant as $dispo) {
            $jour = $dispo['jour'];
            $heure_debut = $dispo['heure_debut'];
            $heure_fin = $dispo['heure_fin'];

            // Vérifier si le créneau est disponible
            if(isCreneauDisponible($planning, $jour, $heure_debut, $enseignant_id)) {
                $planning[$jour][$heure_debut] = [
                    'matiere_id' => $matiere['id'],
                    'matiere_nom' => $matiere['nom'],
                    'enseignant_id' => $enseignant_id,
                    'enseignant_nom' => $matiere['nom'] . ' ' . $matiere['prenom']
                ];
            }
        }
    }

    // Sauvegarder le planning dans la base de données
    $conn->beginTransaction();

    // Supprimer l'ancien planning
    $conn->query("DELETE FROM planning");
    $conn->query("DELETE FROM planning_details");

    // Insérer le nouveau planning
    $stmt_planning = $conn->prepare("
        INSERT INTO planning (date_creation) 
        VALUES (NOW())
    ");
    $stmt_planning->execute();
    $planning_id = $conn->lastInsertId();

    // Insérer les détails du planning
    $stmt_details = $conn->prepare("
        INSERT INTO planning_details (planning_id, jour, heure, matiere_id, enseignant_id) 
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach($planning as $jour => $creneaux) {
        foreach($creneaux as $heure => $cours) {
            if($cours !== null) {
                $stmt_details->execute([
                    $planning_id,
                    $jour,
                    $heure,
                    $cours['matiere_id'],
                    $cours['enseignant_id']
                ]);
            }
        }
    }

    $conn->commit();

    // Envoyer les emails aux enseignants
    require_once '../config/mail.php';
    $enseignants_notifies = [];

    foreach($planning as $jour => $creneaux) {
        foreach($creneaux as $heure => $cours) {
            if($cours !== null && !in_array($cours['enseignant_id'], $enseignants_notifies)) {
                $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$cours['enseignant_id']]);
                $email = $stmt->fetchColumn();

                $subject = "Nouveau planning disponible";
                $message = "Bonjour,\n\n";
                $message .= "Un nouveau planning a été généré. ";
                $message .= "Veuillez vous connecter à votre espace pour le consulter.\n\n";
                $message .= "Cordialement,\nL'équipe administrative";

                sendMail($email, $subject, $message);
                $enseignants_notifies[] = $cours['enseignant_id'];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Planning généré avec succès',
        'planning' => $planning
    ]);

} catch(PDOException $e) {
    if($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la génération du planning']);
}
?> 