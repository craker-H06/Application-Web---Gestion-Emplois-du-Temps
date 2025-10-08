<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer le filtre de statut depuis l'URL (par défaut: tous)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Construire la requête SQL en fonction du filtre
$sql = "SELECT ";
$sql .= "tp.id AS profile_id, ";
$sql .= "tp.ville, ";
$sql .= "tp.quartier, ";
$sql .= "tp.dernier_diplome, ";
$sql .= "tp.statut_matrimonial, ";
$sql .= "tp.nombre_enfants, ";
$sql .= "tp.langues_parlees, ";
$sql .= "tp.langues_ecrites, ";
$sql .= "tp.date_soumission, ";
$sql .= "tp.status, ";
$sql .= "u.id AS user_id, ";
$sql .= "u.nom, ";
$sql .= "u.prenom, ";
$sql .= "u.email ";
$sql .= "FROM teacher_profiles tp ";
$sql .= "JOIN users u ON tp.user_id = u.id ";

// Ajouter le filtre de statut si nécessaire
if ($status_filter !== 'all') {
    $sql .= "WHERE tp.status = :status ";
}

$sql .= "ORDER BY tp.date_soumission DESC";

$stmt = $conn->prepare($sql);
if ($status_filter !== 'all') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour obtenir la classe CSS du badge de statut
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Fonction pour obtenir le texte en français du statut
function getStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'En attente';
        case 'approved':
            return 'Approuvé';
        case 'rejected':
            return 'Refusé';
        default:
            return 'Inconnu';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Enseignants - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .navbar-brand img {
            height: 30px;
            margin-right: 10px;
        }
        .status-badge {
            font-size: 0.85em;
            padding: 0.5em 0.8em;
        }
        .teacher-card {
            transition: transform 0.2s ease-in-out;
            margin-bottom: 1rem;
        }
        .teacher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .filter-buttons .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .teacher-info {
            font-size: 0.9rem;
        }
        .action-buttons .btn {
            margin-right: 0.3rem;
        }
        .date-info {
            font-size: 0.8rem;
            color: #6c757d;
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
                        <a class="nav-link" href="dashboard.php">Tableau de Bord</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-light">Connecté(e) en tant que : <?php echo htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']); ?> (Admin)</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Enseignants</h1>
            <div class="filter-buttons">
                <a href="?status=all" class="btn btn-outline-primary <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    Tous
                </a>
                <a href="?status=pending" class="btn btn-outline-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    En attente
                </a>
                <a href="?status=approved" class="btn btn-outline-success <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    Approuvés
                </a>
                <a href="?status=rejected" class="btn btn-outline-danger <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    Refusés
                </a>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($teachers)): ?>
            <div class="alert alert-info" role="alert">
                Aucun profil enseignant trouvé pour ce filtre.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($teachers as $teacher): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card teacher-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($teacher['prenom']) . ' ' . htmlspecialchars($teacher['nom']); ?>
                                </h5>
                                <span class="badge <?php echo getStatusBadgeClass($teacher['status']); ?> status-badge">
                                    <?php echo getStatusText($teacher['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="teacher-info">
                                    <p class="mb-2">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($teacher['ville']); ?>
                                        <?php if (!empty($teacher['quartier'])): ?>
                                            - <?php echo htmlspecialchars($teacher['quartier']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($teacher['dernier_diplome']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($teacher['statut_matrimonial']); ?>
                                        <?php if ($teacher['nombre_enfants'] > 0): ?>
                                            - <?php echo $teacher['nombre_enfants']; ?> enfant(s)
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($teacher['langues_parlees'])): ?>
                                        <p class="mb-2">
                                            <i class="bi bi-chat-dots"></i> <?php echo htmlspecialchars($teacher['langues_parlees']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($teacher['langues_ecrites'])): ?>
                                        <p class="mb-2">
                                            <i class="bi bi-pencil"></i> <?php echo htmlspecialchars($teacher['langues_ecrites']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="date-info mt-2">
                                    <small>
                                        <i class="bi bi-clock"></i> Soumis le <?php echo date('d/m/Y H:i', strtotime($teacher['date_soumission'])); ?>
                                    </small>
                                </div>
                                <div class="action-buttons mt-3">
                                    <?php if ($teacher['status'] === 'pending'): ?>
                                        <a href="process_approval.php?action=approve&profile_id=<?php echo $teacher['profile_id']; ?>" 
                                           class="btn btn-success btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir approuver ce profil ?');">
                                            <i class="bi bi-check-circle"></i> Approuver
                                        </a>
                                        <a href="process_approval.php?action=reject&profile_id=<?php echo $teacher['profile_id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir refuser ce profil ?');">
                                            <i class="bi bi-x-circle"></i> Refuser
                                        </a>
                                    <?php endif; ?>
                                    <a href="assign_subjects.php?user_id=<?php echo $teacher['user_id']; ?>&all_levels=1" 
                                       class="btn btn-info btn-sm">
                                        <i class="bi bi-book"></i> Affecter Matières
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 