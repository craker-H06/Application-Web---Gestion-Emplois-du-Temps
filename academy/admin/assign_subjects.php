<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer l'ID de l'utilisateur
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$all_levels = isset($_GET['all_levels']) ? true : false;

// Vérifier si l'ID de l'utilisateur est valide
if ($user_id <= 0) {
    header("Location: teacher_approvals.php");
    exit();
}

// Récupérer les informations de l'enseignant
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'enseignant'");
$stmt->execute([$user_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    header("Location: teacher_approvals.php");
    exit();
}

// Récupérer tous les niveaux
$stmt = $conn->prepare("SELECT * FROM levels ORDER BY name");
$stmt->execute();
$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les matières actuellement attribuées à l'enseignant
$stmt = $conn->prepare("
    SELECT m.*, l.name as level_name 
    FROM matieres m 
    JOIN teacher_subjects ts ON m.id = ts.matiere_id 
    JOIN levels l ON m.level_id = l.id
    WHERE ts.user_id = ?
    ORDER BY l.name, m.libelle
");
$stmt->execute([$user_id]);
$current_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau des IDs des matières actuellement attribuées
$current_subject_ids = array_column($current_subjects, 'id');

// Récupérer les matières déjà attribuées à d'autres enseignants
$stmt = $conn->prepare("
    SELECT m.id, m.libelle, m.level_id, l.name as level_name, u.nom, u.prenom, ts.user_id
    FROM matieres m
    JOIN teacher_subjects ts ON m.id = ts.matiere_id
    JOIN users u ON ts.user_id = u.id
    JOIN levels l ON m.level_id = l.id
    ORDER BY l.name, m.libelle
");
$stmt->execute();
$assigned_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau associatif des matières attribuées
$assigned_subjects_map = [];
foreach ($assigned_subjects as $as) {
    $assigned_subjects_map[$as['id']] = [
        'libelle' => $as['libelle'],
        'teacher' => $as['prenom'] . ' ' . $as['nom'],
        'user_id' => $as['user_id'],
        'level_name' => $as['level_name']
    ];
}

// Traitement du formulaire d'attribution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'assign_subjects') {
        $teacher_id = $_POST['teacher_id'];
        $subject_ids = isset($_POST['subjects']) ? $_POST['subjects'] : [];

        try {
            $conn->beginTransaction();

            // Supprimer toutes les attributions actuelles de l'enseignant
            $stmt = $conn->prepare("DELETE FROM teacher_subjects WHERE user_id = ?");
            $stmt->execute([$teacher_id]);

            // Ajouter les nouvelles attributions
            if (!empty($subject_ids)) {
                $stmt = $conn->prepare("INSERT INTO teacher_subjects (user_id, matiere_id) VALUES (?, ?)");
                foreach ($subject_ids as $subject_id) {
                    $stmt->execute([$teacher_id, $subject_id]);
                }
            }

            $conn->commit();
            $_SESSION['success'] = "Les matières ont été affectées avec succès.";
            header("Location: teacher_approvals.php");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Une erreur est survenue lors de l'affectation des matières : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affectation des Matières - <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
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
        .levels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }
        .level-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        .level-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
        }
        .level-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #3498db;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .level-title i {
            color: #3498db;
        }
        .subject-list {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            flex-grow: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #3498db #f8f9fa;
            max-height: calc(400px - 100px);
        }
        .subject-list::-webkit-scrollbar {
            width: 6px;
        }
        .subject-list::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 3px;
        }
        .subject-list::-webkit-scrollbar-thumb {
            background-color: #3498db;
            border-radius: 3px;
        }
        .form-check {
            margin-bottom: 0.75rem;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }
        .form-check:hover {
            background-color: #e9ecef;
        }
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.2em;
        }
        .form-check-input:checked {
            background-color: #3498db;
            border-color: #3498db;
        }
        .form-check-label {
            font-size: 1rem;
            color: #2c3e50;
            margin-left: 0.5rem;
        }
        .form-check.disabled {
            opacity: 0.7;
            background-color: #f1f3f5;
        }
        .form-check.disabled .form-check-label {
            color: #6c757d;
        }
        .assigned-info {
            font-size: 0.85rem;
            color: #dc3545;
            margin-top: 0.25rem;
            display: block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.3);
        }
        .alert {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-body {
            padding: 2rem;
        }
        .subject-count {
            background-color: #e9ecef;
            color: #6c757d;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 1rem;
        }
        .action-buttons {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            margin-top: 1rem;
            border-radius: 0 0 15px 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/logo.png.png" alt="Logo Ascencia">
                Ascencia
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="teacher_approvals.php">
                            <i class="bi bi-arrow-left"></i> Retour aux Approbations
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
            <h1>
                <i class="bi bi-person-workspace"></i>
                Affectation des Matières - <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?>
            </h1>
        </div>
    </div>

    <div class="container">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="assign_subjects">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                    
                    <div class="levels-grid">
                        <?php foreach($levels as $level): 
                            // Récupérer les matières de ce niveau
                            $stmt = $conn->prepare("SELECT * FROM matieres WHERE level_id = ? ORDER BY libelle");
                            $stmt->execute([$level['id']]);
                            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($subjects)):
                        ?>
                            <div class="level-section">
                                <h3 class="level-title">
                                    <i class="bi bi-mortarboard"></i> 
                                    <?php echo htmlspecialchars($level['name']); ?>
                                    <span class="subject-count">
                                        <?php echo count($subjects); ?> matière(s)
                                    </span>
                                </h3>
                                <div class="subject-list">
                                    <?php foreach($subjects as $subject): 
                                        // Vérifier si la matière est déjà attribuée à l'enseignant
                                        $is_assigned = in_array($subject['id'], $current_subject_ids);
                                        
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
                                                   id="subject_<?php echo $subject['id']; ?>"
                                                   <?php echo $is_assigned ? 'checked' : ''; ?>
                                                   <?php echo $disabled ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="subject_<?php echo $subject['id']; ?>">
                                                <?php echo htmlspecialchars($subject['libelle']); ?>
                                                <?php if($disabled): ?>
                                                    <span class="assigned-info">
                                                        <i class="bi bi-info-circle"></i>
                                                        Attribué à <?php echo htmlspecialchars($assigned_subjects_map[$subject['id']]['teacher']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>

                    <div class="action-buttons">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les affectations
                            </button>
                            <a href="teacher_approvals.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour aux approbations
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 