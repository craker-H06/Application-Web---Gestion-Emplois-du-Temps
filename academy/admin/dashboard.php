<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer la liste des niveaux
$stmt = $conn->query("SELECT * FROM levels ORDER BY id");
$levels = $stmt->fetchAll();

// Compter les profils enseignants en attente
$stmt_pending_teachers = $conn->prepare("SELECT COUNT(*) AS pending_count FROM teacher_profiles WHERE status = 'pending'");
$stmt_pending_teachers->execute();
$pending_teachers_count = $stmt_pending_teachers->fetch(PDO::FETCH_ASSOC)['pending_count'];

// Compter le nombre total d'enseignants
$stmt_total_teachers = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'enseignant'");
$stmt_total_teachers->execute();
$total_teachers = $stmt_total_teachers->fetch(PDO::FETCH_ASSOC)['total'];

// Compter le nombre total de matières
$stmt_total_subjects = $conn->prepare("SELECT COUNT(*) AS total FROM matieres");
$stmt_total_subjects->execute();
$total_subjects = $stmt_total_subjects->fetch(PDO::FETCH_ASSOC)['total'];

// Count total teacher availabilities
$stmt_total_availabilities = $conn->prepare("SELECT COUNT(*) AS total FROM disponibilites WHERE statut = 'en_attente'");
$stmt_total_availabilities->execute();
$total_availabilities = $stmt_total_availabilities->fetch(PDO::FETCH_ASSOC)['total'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Ascencia</title>
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
        .level-card {
            transition: transform 0.2s ease-in-out;
        }
        .level-card:hover {
            transform: translateY(-5px);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            border-radius: 50%;
            background-color: red;
            color: white;
            font-size: 0.7em;
        }
        .nav-link.notification {
            position: relative;
            display: inline-block;
        }
        .global-features {
            margin-bottom: 2rem;
        }
        .feature-card {
            height: 100%;
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link notification" href="teacher_approvals.php" title="Demandes d'enseignants en attente">
                            <i class="bi bi-bell"></i>
                            <?php if ($pending_teachers_count > 0): ?>
                                <span class="notification-badge"><?php echo $pending_teachers_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-light">Connecté(e) en tant que : <?php echo htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard Admin</h1>

        <!-- Fonctionnalités globales -->
        <div class="global-features">
            <h2 class="mb-4">Fonctionnalités Globales</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <!-- Gestion des Enseignants -->
                <div class="col">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="bi bi-person-workspace feature-icon text-primary"></i>
                            <h5 class="card-title">Gestion des Enseignants</h5>
                            <p class="card-text">
                                <?php echo $total_teachers; ?> enseignant(s) au total<br>
                                <?php echo $pending_teachers_count; ?> demande(s) en attente
                            </p>
                            <div class="d-grid gap-2">
                                <a href="teacher_approvals.php" class="btn btn-primary">Gérer les demandes</a>
                                <a href="manage_teachers.php" class="btn btn-outline-primary">Liste des enseignants</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestion des emplois de temps -->
                <div class="col-md-4 mb-4">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Gestion des emplois de temps</h5>
                            <p class="card-text">Gérez les emplois de temps des enseignants et des classes</p>
                            <div class="d-grid gap-2">
                                <a href="schedule.php" class="btn btn-primary">
                                    <i class="fas fa-clock"></i> Voir les emplois de temps
                                </a>
                                <a href="teacher_availabilities.php" class="btn btn-outline-info">
                                    <i class="fas fa-calendar-check"></i> Disponibilités des enseignants
                                    <?php if ($total_availabilities > 0): ?>
                                        <span class="badge bg-danger ms-1"><?php echo $total_availabilities; ?></span>
                                    <?php endif; ?>
                                </a>
                                <a href="generate_schedule.php" class="btn btn-outline-primary">Générer l'emploi du temps</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestion des Salles -->
                <div class="col">
                    <div class="card feature-card">
                        <div class="card-body text-center">
                            <i class="bi bi-building feature-icon text-info"></i>
                            <h5 class="card-title">Gestion des Salles</h5>
                            <p class="card-text">
                                Gérer les salles et leur disponibilité
                            </p>
                            <div class="d-grid gap-2">
                                <a href="manage_rooms.php" class="btn btn-info">Gérer les salles</a>
                                <a href="room_schedule.php" class="btn btn-outline-info">Planning des salles</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Niveaux -->
        <h2 class="mb-4">Gestion par Niveau</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach($levels as $level): ?>
                <div class="col">
                    <?php
                        $icon_class = '';
                        $bg_class = '';
                        $text_class = '';
                        $level_name_lower = strtolower($level['name']);

                        if (strpos($level_name_lower, 'niveau 1') !== false) {
                            $icon_class = 'bi-journal';
                            $bg_class = 'bg-primary';
                            $text_class = 'text-dark';
                        } elseif (strpos($level_name_lower, 'niveau 2') !== false) {
                            $icon_class = 'bi-file-earmark-text';
                            $bg_class = 'bg-success';
                            $text_class = 'text-dark';
                        } elseif (strpos($level_name_lower, 'licence') !== false) {
                            $icon_class = 'bi-mortarboard';
                            $bg_class = 'bg-info';
                            $text_class = 'text-dark';
                        } else {
                            $icon_class = 'bi-folder2-open';
                            $bg_class = 'bg-light';
                            $text_class = 'text-dark';
                        }
                    ?>
                    <div class="card h-100 level-card <?php echo $bg_class; ?>">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                            <i class="bi <?php echo $icon_class; ?> <?php echo $text_class; ?>" style="font-size: 4rem;"></i>
                            <h5 class="card-title mt-3 <?php echo $text_class; ?>"><?php echo htmlspecialchars($level['name']); ?></h5>
                            <a href="level_dashboard.php?level_id=<?php echo $level['id']; ?>" class="btn btn-light mt-3">Gérer le Niveau</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 