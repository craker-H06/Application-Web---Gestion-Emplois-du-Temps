<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupérer l'ID du niveau depuis l'URL
$level_id = isset($_GET['level_id']) ? intval($_GET['level_id']) : 0;

// Récupérer tous les niveaux pour le menu de navigation
$stmt = $conn->query("SELECT * FROM levels ORDER BY id");
$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un niveau spécifique est sélectionné, récupérer ses informations
$selected_level = null;
if ($level_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM levels WHERE id = ?");
    $stmt->execute([$level_id]);
    $selected_level = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les disponibilités approuvées par niveau
function getApprovedAvailabilities($conn, $level_id) {
    $stmt = $conn->prepare("
        SELECT 
            da.*,
            u.nom as enseignant_nom,
            u.prenom as enseignant_prenom,
            m.libelle as matiere_nom,
            m.code as matiere_code
        FROM disponibilites_approuvees da
        JOIN users u ON da.user_id = u.id
        JOIN matieres m ON da.matiere_id = m.id
        WHERE da.level_id = ? 
        AND da.statut = 'confirmee'
        ORDER BY da.date_dispo, da.periode
    ");
    $stmt->execute([$level_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les matières par niveau
function getSubjectsByLevel($conn, $level_id) {
    $stmt = $conn->prepare("
        SELECT 
            m.*,
            COUNT(DISTINCT ts.user_id) as nombre_enseignants
        FROM matieres m
        LEFT JOIN teacher_subjects ts ON m.id = ts.matiere_id
        WHERE m.level_id = ?
        GROUP BY m.id
    ");
    $stmt->execute([$level_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération des Emplois du Temps - Ascencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .level-nav {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .level-nav .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .level-nav .nav-link:hover {
            background: #e9ecef;
        }
        .level-nav .nav-link.active {
            background: #0d6efd;
            color: white;
        }
        .level-content {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-card {
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .subject-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .availability-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .availability-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .generate-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .generate-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .generate-btn:hover::after {
            width: 300px;
            height: 300px;
        }
        .generate-btn:active {
            transform: scale(0.95);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem;
            border-radius: 5px;
            display: none;
            animation: slideInRight 0.5s ease forwards;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay de chargement -->
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Message de succès -->
    <div class="success-message">
        <i class="bi bi-check-circle"></i> Planning généré avec succès !
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Retour au Dashboard
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Génération des Emplois du Temps</h1>

        <!-- Navigation entre les niveaux -->
        <div class="level-nav">
            <div class="row">
                <?php foreach($levels as $level): ?>
                    <div class="col-auto">
                        <a href="?level_id=<?php echo $level['id']; ?>" 
                           class="nav-link <?php echo ($level_id == $level['id']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($level['name']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if($selected_level): ?>
            <div class="level-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo htmlspecialchars($selected_level['name']); ?></h2>
                    <button class="btn btn-primary generate-btn" data-level-id="<?php echo $selected_level['id']; ?>">
                        <i class="bi bi-magic"></i> Générer l'Emploi du Temps
                    </button>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <?php
                    $availabilities = getApprovedAvailabilities($conn, $selected_level['id']);
                    $subjects = getSubjectsByLevel($conn, $selected_level['id']);
                    $total_teachers = count(array_unique(array_column($availabilities, 'user_id')));
                    $total_subjects = count($subjects);
                    $total_availabilities = count($availabilities);
                    ?>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5><i class="bi bi-person-workspace"></i> Enseignants Disponibles</h5>
                            <h3><?php echo $total_teachers; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5><i class="bi bi-book"></i> Matières</h5>
                            <h3><?php echo $total_subjects; ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5><i class="bi bi-calendar-check"></i> Disponibilités</h5>
                            <h3><?php echo $total_availabilities; ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Matières -->
                <h3 class="mb-3">Matières du Niveau</h3>
                <div class="row">
                    <?php foreach($subjects as $subject): ?>
                        <div class="col-md-4">
                            <div class="subject-card">
                                <h5><?php echo htmlspecialchars($subject['libelle']); ?></h5>
                                <p class="text-muted mb-2">Code: <?php echo htmlspecialchars($subject['code']); ?></p>
                                <p class="mb-0">
                                    <i class="bi bi-person"></i> 
                                    <?php echo $subject['nombre_enseignants']; ?> enseignant(s)
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Disponibilités Approuvées -->
                <h3 class="mb-3 mt-4">Disponibilités Approuvées</h3>
                <div class="row">
                    <?php foreach($availabilities as $availability): ?>
                        <div class="col-md-6">
                            <div class="card availability-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($availability['enseignant_prenom'] . ' ' . $availability['enseignant_nom']); ?>
                                            </h5>
                                            <p class="card-text">
                                                <strong>Matière:</strong> <?php echo htmlspecialchars($availability['matiere_nom']); ?><br>
                                                <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($availability['date_dispo'])); ?><br>
                                                <span class="badge bg-primary">
                                                    <?php echo ucfirst($availability['periode']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <small class="text-muted">
                                            Approuvé le <?php echo date('d/m/Y', strtotime($availability['date_approbation'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Veuillez sélectionner un niveau pour voir ses informations et générer son emploi du temps.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher le message de succès
        function showSuccessMessage() {
            const message = document.querySelector('.success-message');
            message.style.display = 'block';
            setTimeout(() => {
                message.style.display = 'none';
            }, 3000);
        }

        // Fonction pour afficher/masquer l'overlay de chargement
        function toggleLoading(show) {
            const overlay = document.querySelector('.loading-overlay');
            overlay.style.display = show ? 'flex' : 'none';
        }

        // Gestionnaire de clic pour le bouton de génération
        document.querySelector('.generate-btn')?.addEventListener('click', async function() {
            const levelId = this.dataset.levelId;
            if(confirm('Voulez-vous générer l\'emploi du temps pour ce niveau ?')) {
                try {
                    toggleLoading(true);
                    
                    const response = await fetch('../api/generate_schedule.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ level_id: levelId })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccessMessage();
                        // Recharger la page après 2 secondes
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
                    }
                } catch (error) {
                    alert('Erreur lors de la génération du planning');
                    console.error('Error:', error);
                } finally {
                    toggleLoading(false);
                }
            }
        });
    </script>
</body>
</html> 