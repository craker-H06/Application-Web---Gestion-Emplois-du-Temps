<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer l'ID du niveau depuis l'URL
$level_id = isset($_GET['level_id']) ? intval($_GET['level_id']) : 0;

// Vérifier si un ID de niveau valide a été passé
if ($level_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Récupérer les informations du niveau
$stmt = $conn->prepare("SELECT * FROM levels WHERE id = ?");
$stmt->execute([$level_id]);
$level = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$level) {
    header("Location: dashboard.php");
    exit();
}

// Récupérer tous les enseignants du niveau et ceux qui ont des matières attribuées dans ce niveau
$stmt = $conn->prepare("
    SELECT DISTINCT u.*, GROUP_CONCAT(m.libelle) as assigned_subjects,
           COALESCE(l.name, 'Non assigné') as level_name
    FROM users u
    LEFT JOIN teacher_subjects ts ON u.id = ts.user_id
    LEFT JOIN matieres m ON ts.matiere_id = m.id AND m.level_id = ?
    LEFT JOIN levels l ON u.level_id = l.id
    WHERE u.role = 'enseignant' 
    AND (
        u.level_id = ? 
        OR EXISTS (
            SELECT 1 
            FROM teacher_subjects ts2 
            JOIN matieres m2 ON ts2.matiere_id = m2.id 
            WHERE ts2.user_id = u.id 
            AND m2.level_id = ?
        )
    )
    GROUP BY u.id
    ORDER BY u.nom, u.prenom
");
$stmt->execute([$level_id, $level_id, $level_id]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les matières du niveau
$stmt = $conn->prepare("SELECT * FROM matieres WHERE level_id = ? ORDER BY libelle");
$stmt->execute([$level_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les matières déjà attribuées à d'autres enseignants
$stmt = $conn->prepare("
    SELECT m.id, m.libelle, u.nom, u.prenom, ts.user_id
    FROM matieres m
    JOIN teacher_subjects ts ON m.id = ts.matiere_id
    JOIN users u ON ts.user_id = u.id
    WHERE m.level_id = ?
    ORDER BY m.libelle
");
$stmt->execute([$level_id]);
$assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau associatif des matières attribuées
$assigned_subjects_map = [];
foreach ($assigned_subjects as $as) {
    $assigned_subjects_map[$as['id']] = [
        'libelle' => $as['libelle'],
        'teacher' => $as['prenom'] . ' ' . $as['nom'],
        'user_id' => $as['user_id']
    ];
}

// Traitement du formulaire d'attribution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'assign_subjects') {
        $teacher_id = $_POST['teacher_id'];
        $subject_ids = isset($_POST['subjects']) ? $_POST['subjects'] : [];

        try {
            $conn->beginTransaction();

            // Récupérer les matières actuellement attribuées à l'enseignant
            $stmt = $conn->prepare("SELECT matiere_id FROM teacher_subjects WHERE user_id = ?");
            $stmt->execute([$teacher_id]);
            $current_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Identifier les matières qui ont été décochées
            $removed_subjects = array_diff($current_subjects, $subject_ids);

            // Supprimer toutes les attributions actuelles de l'enseignant
            $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE user_id = ?");
            $stmt->execute([$teacher_id]);

            // Ajouter uniquement les nouvelles attributions (celles qui sont cochées)
            if (!empty($subject_ids)) {
                $stmt = $conn->prepare("INSERT INTO teacher_subjects (user_id, matiere_id) VALUES (?, ?)");
                foreach ($subject_ids as $subject_id) {
                    $stmt->execute([$teacher_id, $subject_id]);
                }
            }

            $conn->commit();
            
            // Préparer le message de succès
            $message = "Attributions mises à jour avec succès.";
            if (!empty($removed_subjects)) {
                $message .= " Les matières décochées sont maintenant disponibles pour les autres enseignants.";
            }
            
            $_SESSION['success'] = $message;
            
            // Rediriger vers la même page pour voir les changements immédiatement
            header("Location: " . $_SERVER['REQUEST_URI']);

            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attribution des Matières - <?php echo htmlspecialchars($level['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 1rem 0;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand img {
            height: 40px;
            width: auto;
        }
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .page-header .btn-light {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .page-header .btn-light:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .teacher-card {
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            transition: transform 0.2s ease;
        }
        .teacher-card:hover {
            transform: translateY(-2px);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,.1);
        }
        .subject-list {
            max-height: 200px;
            overflow-y: auto;
            padding: 1rem;
            background-color: #fff;
            border-radius: 5px;
        }
        .form-check {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .form-check:last-child {
            border-bottom: none;
        }
        .form-check.disabled {
            opacity: 0.6;
            background-color: #f8f9fa;
        }
        .form-check.disabled .form-check-label {
            color: #6c757d;
        }
        .assigned-info {
            font-size: 0.8rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }
        .btn-primary {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/logo.png" alt="Logo Ascencia">
                <span>Ascencia</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="level_dashboard.php?level_id=<?php echo $level_id; ?>">
                            <i class="bi bi-arrow-left"></i> Retour au Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>
                    <i class="bi bi-book"></i> 
                    Attribution des Matières - <?php echo htmlspecialchars($level['name']); ?>
                </h1>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach($teachers as $teacher): ?>
                <div class="col-md-6">
                    <div class="card teacher-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?>
                                <?php if($teacher['level_id'] != $level_id): ?>
                                    <span class="badge bg-info">
                                        <i class="bi bi-info-circle"></i>
                                        <?php if($teacher['level_name'] === 'Non assigné'): ?>
                                            Enseignant non assigné à un niveau
                                        <?php else: ?>
                                            Enseignant du niveau <?php echo htmlspecialchars($teacher['level_name']); ?>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="assign_subjects">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Matières attribuées</label>
                                    <div class="subject-list">
                                        <?php foreach($subjects as $subject): 
                                            // Vérifier si la matière est déjà attribuée à l'enseignant
                                            $is_assigned = !empty($teacher['assigned_subjects']) && strpos($teacher['assigned_subjects'], $subject['libelle']) !== false;
                                            
                                            // Vérifier si la matière est attribuée à un autre enseignant
                                            $is_assigned_to_other = isset($assigned_subjects_map[$subject['id']]) && 
                                                                   $assigned_subjects_map[$subject['id']]['user_id'] != $teacher['id'];
                                            
                                            // Une matière n'est désactivée que si elle est actuellement attribuée à un autre enseignant
                                            $disabled = $is_assigned_to_other;
                                        ?>
                                            <div class="form-check <?php echo $disabled ? 'disabled' : ''; ?>">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="subjects[]" 
                                                       value="<?php echo $subject['id']; ?>"
                                                       id="subject_<?php echo $teacher['id']; ?>_<?php echo $subject['id']; ?>"
                                                       <?php echo $is_assigned ? 'checked' : ''; ?>
                                                       <?php echo $disabled ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="subject_<?php echo $teacher['id']; ?>_<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['libelle']); ?>
                                                    <small class="text-muted">(<?php echo $subject['nombre_heures']; ?>h)</small>
                                                    <?php if($is_assigned_to_other): ?>
                                                        <div class="assigned-info">
                                                            <i class="bi bi-info-circle"></i>
                                                            Déjà attribuée à <?php echo htmlspecialchars($assigned_subjects_map[$subject['id']]['teacher']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer les attributions
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 