<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est enseignant
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si l'ID de la matière est fourni
if (!isset($_GET['matiere_id'])) {
    header("Location: dashboard.php");
    exit();
}

$matiere_id = intval($_GET['matiere_id']);

// Vérifier si l'enseignant est bien assigné à cette matière
$stmt = $conn->prepare("
    SELECT m.*, l.name as level_name 
    FROM matieres m 
    JOIN teacher_subjects ts ON m.id = ts.matiere_id 
    LEFT JOIN levels l ON m.level_id = l.id 
    WHERE ts.user_id = ? AND m.id = ?
");
$stmt->execute([$_SESSION['user_id'], $matiere_id]);
$matiere = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$matiere) {
    header("Location: dashboard.php");
    exit();
}

// Traitement de l'ajout d'un cours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_course') {
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $date = $_POST['date'];
        $heure_debut = $_POST['heure_debut'];
        $heure_fin = $_POST['heure_fin'];
        $salle_id = $_POST['salle_id'];

        try {
            $stmt = $conn->prepare("
                INSERT INTO cours (matiere_id, titre, description, date, heure_debut, heure_fin, salle_id, enseignant_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$matiere_id, $titre, $description, $date, $heure_debut, $heure_fin, $salle_id, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "Cours ajouté avec succès.";
            header("Location: cours.php?matiere_id=" . $matiere_id);
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du cours : " . $e->getMessage();
        }
    }
}

// Récupérer les cours de cette matière
$stmt = $conn->prepare("
    SELECT c.*, s.nom as salle_nom
    FROM cours c
    JOIN salles s ON c.salle_id = s.id
    WHERE c.matiere_id = ? AND c.enseignant_id = ?
    ORDER BY c.date DESC, c.heure_debut
");
$stmt->execute([$matiere_id, $_SESSION['user_id']]);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les salles disponibles
$stmt = $conn->query("SELECT * FROM salles ORDER BY nom");
$salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Cours - <?php echo htmlspecialchars($matiere['libelle']); ?></title>
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
            text-decoration: none;
        }
        .navbar-brand .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .navbar-brand .main-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        .navbar-brand .sub-text {
            font-size: 0.9rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.9);
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
        .course-card {
            transition: transform 0.2s ease;
            margin-bottom: 1rem;
        }
        .course-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/logo.png" alt="Logo ASCENCIA">
                <div class="logo-text">
                    <span class="main-text">ASCENCIA</span>
                    <span class="sub-text">business school</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
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
                    Gestion des Cours - <?php echo htmlspecialchars($matiere['libelle']); ?>
                </h1>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="bi bi-plus-circle"></i> Ajouter un Cours
                </button>
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

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($cours): ?>
                <?php foreach($cours as $c): ?>
                    <div class="col-md-6">
                        <div class="card course-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($c['titre']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($c['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo date('d/m/Y', strtotime($c['date'])); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo $c['heure_debut'] . ' - ' . $c['heure_fin']; ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($c['salle_nom']); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-warning" onclick="editCourse(<?php echo $c['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCourse(<?php echo $c['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Aucun cours n'a été ajouté pour cette matière.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Ajout Cours -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Cours</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_course">
                        
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre du cours</label>
                            <input type="text" class="form-control" id="titre" name="titre" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salle_id" class="form-label">Salle</label>
                                <select class="form-select" id="salle_id" name="salle_id" required>
                                    <option value="">Sélectionnez une salle</option>
                                    <?php foreach($salles as $salle): ?>
                                        <option value="<?php echo $salle['id']; ?>">
                                            <?php echo htmlspecialchars($salle['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="heure_debut" class="form-label">Heure de début</label>
                                <input type="time" class="form-control" id="heure_debut" name="heure_debut" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="heure_fin" class="form-label">Heure de fin</label>
                                <input type="time" class="form-control" id="heure_fin" name="heure_fin" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter le cours</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCourse(courseId) {
            // À implémenter : redirection vers la page d'édition du cours
            alert('Fonctionnalité à venir');
        }

        function deleteCourse(courseId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')) {
                // À implémenter : suppression du cours
                alert('Fonctionnalité à venir');
            }
        }
    </script>
</body>
</html> 