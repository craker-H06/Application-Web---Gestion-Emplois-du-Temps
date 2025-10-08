<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est enseignant
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si l'enseignant a un profil approuvé
$stmt = $conn->prepare("
    SELECT tp.*, u.nom, u.prenom, u.email 
    FROM teacher_profiles tp 
    JOIN users u ON tp.user_id = u.id 
    WHERE tp.user_id = ? AND tp.status = 'approved'
");
$stmt->execute([$_SESSION['user_id']]);
$teacher_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Si pas de profil approuvé, rediriger vers la page appropriée
if (!$teacher_profile) {
    // Vérifier si un profil existe mais n'est pas approuvé
    $stmt = $conn->prepare("SELECT status FROM teacher_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $existing_profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_profile) {
        if ($existing_profile['status'] === 'pending') {
            $_SESSION['error'] = "Votre profil est en attente d'approbation.";
            header("Location: waiting_approval.php");
        } else if ($existing_profile['status'] === 'rejected') {
            $_SESSION['error'] = "Votre profil a été refusé. Veuillez contacter l'administrateur.";
            header("Location: complete_profile.php");
        }
    } else {
        $_SESSION['error'] = "Vous devez compléter votre profil avant d'accéder au tableau de bord.";
        header("Location: complete_profile.php");
    }
    exit();
}

// Vérifier si l'enseignant a des matières attribuées
$stmt = $conn->prepare("
    SELECT COUNT(*) as subject_count 
    FROM teacher_subjects 
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$subject_count = $stmt->fetch(PDO::FETCH_ASSOC)['subject_count'];

if ($subject_count == 0) {
    $_SESSION['warning'] = "Aucune matière ne vous a été attribuée pour le moment.";
}

// Récupérer les matières de l'enseignant
$stmt = $conn->prepare("
    SELECT m.*, l.name as level_name 
    FROM matieres m 
    JOIN teacher_subjects ts ON m.id = ts.matiere_id 
    LEFT JOIN levels l ON m.level_id = l.id 
    WHERE ts.user_id = ?
    ORDER BY l.name, m.libelle
");
$stmt->execute([$_SESSION['user_id']]);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper les matières par niveau
$matieres_par_niveau = [];
foreach ($matieres as $matiere) {
    $niveau = $matiere['level_name'] ?? 'Non assigné';
    if (!isset($matieres_par_niveau[$niveau])) {
        $matieres_par_niveau[$niveau] = [];
    }
    $matieres_par_niveau[$niveau][] = $matiere;
}

// Récupérer les disponibilités de l'enseignant
try {
    $query = "
        SELECT 
            id,
            date_dispo,
            periode,
            statut
        FROM 
            disponibilites
        WHERE 
            user_id = ?
        ORDER BY 
            date_dispo DESC, periode
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des disponibilités : " . $e->getMessage();
    $disponibilites = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Enseignant - Ascencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
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
        .subject-card {
            transition: transform 0.2s ease-in-out;
            margin-bottom: 1rem;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .level-group {
            margin-bottom: 2rem;
        }
        .level-title {
            color: #0d6efd;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
        }
        .subject-code {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .subject-hours {
            font-size: 0.9rem;
            color: #198754;
            margin-bottom: 0.5rem;
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
                        <span class="nav-link text-light">Connecté(e) en tant que : <?php echo htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']); ?> (Enseignant)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Tableau de Bord Enseignant</h1>

        <?php if(empty($matieres)): ?>
            <div class="alert alert-info" role="alert">
                Aucune matière ne vous a été assignée pour le moment.
            </div>
        <?php else: ?>
            <?php foreach ($matieres_par_niveau as $level_name => $level_matieres): ?>
                <div class="level-group">
                    <h3 class="level-title">
                        <i class="bi bi-book"></i> <?php echo htmlspecialchars($level_name); ?>
                    </h3>
                    <div class="row">
                        <?php foreach ($level_matieres as $matiere): ?>
                            <div class="col-md-4">
                                <div class="card subject-card">
                                    <div class="card-body">
                                        <div class="subject-code">
                                            <i class="bi bi-hash"></i> <?php echo htmlspecialchars($matiere['code']); ?>
                                        </div>
                                        <h5 class="card-title"><?php echo htmlspecialchars($matiere['libelle']); ?></h5>
                                        <?php if (!empty($matiere['description'])): ?>
                                            <p class="card-text"><?php echo htmlspecialchars($matiere['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($matiere['nombre_heures'] > 0): ?>
                                            <div class="subject-hours">
                                                <i class="bi bi-clock"></i> <?php echo $matiere['nombre_heures']; ?> heures
                                            </div>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <a href="cours.php?matiere_id=<?php echo $matiere['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-book"></i> Gérer les cours
                                            </a>
                                            <a href="emploi_du_temps.php?matiere_id=<?php echo $matiere['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="bi bi-calendar"></i> Emploi du temps
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h5 class="card-title">Gérer mes disponibilités</h5>
                        <p class="card-text">Définissez vos disponibilités pour les cours</p>
                        <a href="disponibilites.php" class="btn btn-primary">
                            <i class="fas fa-calendar-alt"></i> Ouvrir le calendrier
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt"></i> Mes disponibilités
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($disponibilites)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Période</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($disponibilites as $dispo): ?>
                                            <tr class="<?php echo is_past_date($dispo['date_dispo']) ? 'table-secondary' : ''; ?>">
                                                <td><?php echo format_date($dispo['date_dispo']); ?></td>
                                                <td><?php echo get_period_name($dispo['periode']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($dispo['statut']) {
                                                        case 'en_attente':
                                                            $status_class = 'badge bg-warning';
                                                            $status_text = 'En attente';
                                                            break;
                                                        case 'approuve':
                                                            $status_class = 'badge bg-success';
                                                            $status_text = 'Approuvé';
                                                            break;
                                                        case 'rejete':
                                                            $status_class = 'badge bg-danger';
                                                            $status_text = 'Rejeté';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Aucune disponibilité enregistrée pour le moment.
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="disponibilites.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Définir mes disponibilités
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 