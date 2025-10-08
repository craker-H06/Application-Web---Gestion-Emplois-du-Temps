<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$level_id = isset($data['level_id']) ? intval($data['level_id']) : 0;

if ($level_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de niveau invalide']);
    exit();
}

try {
    // Vérifier si le niveau existe
    $stmt = $conn->prepare("SELECT * FROM levels WHERE id = ?");
    $stmt->execute([$level_id]);
    $level = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$level) {
        throw new Exception('Niveau non trouvé');
    }

    error_log("Niveau trouvé: " . print_r($level, true));

    // Vérifier la structure de la table emplois_du_temps
    $stmt = $conn->query("DESCRIBE emplois_du_temps");
    $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Structure de la table emplois_du_temps: " . print_r($table_structure, true));

    // Récupérer les disponibilités approuvées pour ce niveau
    $stmt = $conn->prepare("
        SELECT da.*, m.libelle as matiere_nom, m.code as matiere_code, u.nom as enseignant_nom, u.prenom as enseignant_prenom
        FROM disponibilites_approuvees da
        JOIN matieres m ON da.matiere_id = m.id
        JOIN users u ON da.user_id = u.id
        WHERE da.level_id = ? AND da.statut = 'confirmee'
        ORDER BY da.date_dispo, da.periode
    ");
    $stmt->execute([$level_id]);
    $availabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log des disponibilités trouvées
    error_log("Disponibilités trouvées pour le niveau " . $level_id . ": " . print_r($availabilities, true));

    // Récupérer ou créer les matières de remplacement
    $stmt = $conn->prepare("SELECT id, libelle, code FROM matieres WHERE libelle IN ('Travaux Personnel de l\'Etudiant', 'Projet') AND level_id = ?");
    $stmt->execute([$level_id]);
    $remplacement_matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Matières de remplacement existantes: " . print_r($remplacement_matieres, true));

    // Récupérer l'ID d'un enseignant par défaut (premier enseignant trouvé)
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'enseignant' LIMIT 1");
    $stmt->execute();
    $default_teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$default_teacher) {
        throw new Exception("Aucun enseignant trouvé dans le système.");
    }
    
    $default_teacher_id = $default_teacher['id'];
    error_log("Enseignant par défaut trouvé: " . $default_teacher_id);

    // Si les matières de remplacement n'existent pas, les créer
    if (count($remplacement_matieres) < 2) {
        $matieres_a_creer = [
            ['libelle' => 'Travaux Personnel de l\'Etudiant', 'code' => 'TPE'],
            ['libelle' => 'Projet', 'code' => 'PRJ']
        ];
        foreach ($matieres_a_creer as $matiere) {
            if (!in_array($matiere['libelle'], array_column($remplacement_matieres, 'libelle'))) {
                try {
                    $stmt = $conn->prepare("INSERT INTO matieres (libelle, code, level_id) VALUES (?, ?, ?)");
                    $stmt->execute([$matiere['libelle'], $matiere['code'], $level_id]);
                    $remplacement_matieres[] = [
                        'id' => $conn->lastInsertId(),
                        'libelle' => $matiere['libelle'],
                        'code' => $matiere['code']
                    ];
                    error_log("Matière de remplacement créée: " . print_r($matiere, true));
                } catch (PDOException $e) {
                    error_log("Erreur lors de la création de la matière de remplacement: " . $e->getMessage());
                    throw new Exception("Erreur lors de la création de la matière de remplacement: " . $e->getMessage());
                }
            }
        }
    }

    if (empty($availabilities)) {
        throw new Exception("Aucune disponibilité approuvée trouvée pour ce niveau.");
    }

    // Supprimer l'ancien emploi du temps s'il existe
    try {
        $stmt = $conn->prepare("
            DELETE FROM emplois_du_temps 
            WHERE matiere_id IN (
                SELECT m.id 
                FROM matieres m 
                WHERE m.level_id = ?
            )
        ");
    $stmt->execute([$level_id]);
        error_log("Ancien emploi du temps supprimé pour le niveau " . $level_id);
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression de l'ancien emploi du temps: " . $e->getMessage());
        throw new Exception("Erreur lors de la suppression de l'ancien emploi du temps: " . $e->getMessage());
    }

    // Définir les heures de début et fin pour chaque période
    $periodes = [
        'periode1' => ['heure_debut' => '08:30:00', 'heure_fin' => '12:30:00'],
        'periode2' => ['heure_debut' => '12:30:00', 'heure_fin' => '17:30:00'],
        'periode3' => ['heure_debut' => '18:00:00', 'heure_fin' => '22:00:00']
    ];

    // Insérer le nouvel emploi du temps
    $stmt = $conn->prepare("
        INSERT INTO emplois_du_temps 
        (matiere_id, enseignant_id, salle_id, jour, heure_debut, heure_fin) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Créer un tableau pour suivre les périodes déjà utilisées
    $periodes_utilisees = [];

    foreach ($availabilities as $availability) {
        try {
            // Convertir la date en jour de la semaine
            $date = new DateTime($availability['date_dispo']);
            $jour = $date->format('l'); // Retourne le jour en anglais
            $jours_fr = [
                'Monday' => 'Lundi',
                'Tuesday' => 'Mardi',
                'Wednesday' => 'Mercredi',
                'Thursday' => 'Jeudi',
                'Friday' => 'Vendredi',
                'Saturday' => 'Samedi'
            ];
            $jour_fr = $jours_fr[$jour];

            // Créer une clé unique pour cette période
            $periode_key = $jour_fr . '_' . $availability['periode'];
            
            // Récupérer une salle disponible (pour l'instant, on utilise l'ID 1 par défaut)
            $salle_id = 1;

            // Si la période est déjà utilisée, utiliser une matière de remplacement
            if (isset($periodes_utilisees[$periode_key])) {
                // Alterner entre les deux matières de remplacement
                $matiere_remplacement = $remplacement_matieres[count($periodes_utilisees[$periode_key]) % 2];
                error_log("Utilisation de la matière de remplacement: " . print_r($matiere_remplacement, true));
                $params = [
                    $matiere_remplacement['id'],
                    $default_teacher_id,
                    $salle_id,
                    $jour_fr,
                    $periodes[$availability['periode']]['heure_debut'],
                    $periodes[$availability['periode']]['heure_fin']
                ];
            } else {
                $periodes_utilisees[$periode_key] = [];
                $params = [
                    $availability['matiere_id'],
                    $availability['user_id'],
                    $salle_id,
                    $jour_fr,
                    $periodes[$availability['periode']]['heure_debut'],
                    $periodes[$availability['periode']]['heure_fin']
                ];
            }

            // Log des paramètres d'insertion
            error_log("Paramètres d'insertion: " . print_r($params, true));

            // Vérifier que tous les paramètres sont valides
            if (empty($params[0]) || empty($params[1]) || empty($params[2]) || empty($params[3]) || empty($params[4]) || empty($params[5])) {
                error_log("Erreur: Paramètres invalides pour l'insertion: " . print_r($params, true));
                throw new Exception("Paramètres invalides pour l'insertion");
            }

            $stmt->execute($params);
            $periodes_utilisees[$periode_key][] = $params;
            error_log("Insertion réussie pour la période: " . $periode_key);
        } catch (Exception $e) {
            error_log("Erreur lors du traitement de la disponibilité: " . $e->getMessage());
            error_log("Disponibilité en erreur: " . print_r($availability, true));
            error_log("Trace complète: " . $e->getTraceAsString());
            throw new Exception("Erreur lors du traitement de la disponibilité: " . $e->getMessage());
        }
    }

    // Marquer les disponibilités comme annulées
    try {
        $stmt = $conn->prepare("
            UPDATE disponibilites_approuvees 
            SET statut = 'annulee' 
            WHERE level_id = ? AND statut = 'confirmee'
        ");
        $stmt->execute([$level_id]);
        error_log("Disponibilités marquées comme annulées pour le niveau " . $level_id);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour des disponibilités: " . $e->getMessage());
        throw new Exception("Erreur lors de la mise à jour des disponibilités: " . $e->getMessage());
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Erreur lors de la génération du planning: " . $e->getMessage());
    error_log("Trace complète: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 