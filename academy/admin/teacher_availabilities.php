<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Requête pour obtenir les enseignants avec le nombre de disponibilités en attente
$query = "
    SELECT 
        u.id,
        u.nom,
        u.prenom,
        u.email,
        COALESCE(GROUP_CONCAT(
            DISTINCT CONCAT(m.libelle, ' (', COALESCE(l.name, 'Non assigné'), ')')
            ORDER BY m.libelle
            SEPARATOR ','
        ), '') as matieres,
        COUNT(DISTINCT CASE WHEN d.statut = 'en_attente' THEN d.id END) as disponibilites_en_attente
    FROM 
        users u
    LEFT JOIN 
        teacher_subjects ts ON u.id = ts.user_id
    LEFT JOIN 
        matieres m ON ts.matiere_id = m.id
    LEFT JOIN 
        levels l ON m.level_id = l.id
    LEFT JOIN 
        disponibilites d ON u.id = d.user_id
    WHERE 
        u.role = 'enseignant'
    GROUP BY 
        u.id
    ORDER BY 
        u.nom, u.prenom
";

$stmt = $conn->prepare($query);
$stmt->execute();
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilités des Enseignants - ASCENCIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .teacher-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 400px; /* Hauteur fixe pour toutes les cartes */
            display: flex;
            flex-direction: column;
        }
        .teacher-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .teacher-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 25px;
            position: relative;
            flex-shrink: 0; /* Empêche l'en-tête de rétrécir */
        }
        .teacher-header h5 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }
        .teacher-header small {
            font-size: 1rem;
            opacity: 0.9;
            display: block;
            margin-top: 5px;
        }
        .teacher-body {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Cache le débordement */
        }
        .matieres-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Important pour le défilement */
            margin-bottom: 20px;
        }
        .matieres-container h6 {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            flex-shrink: 0; /* Empêche le titre de rétrécir */
        }
        .matieres-list {
            flex: 1;
            overflow-y: auto; /* Permet le défilement vertical */
            padding-right: 10px; /* Espace pour la barre de défilement */
            margin-right: -10px; /* Compense le padding */
        }
        .matieres-list::-webkit-scrollbar {
            width: 6px;
        }
        .matieres-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .matieres-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .matieres-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        .badge {
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 20px;
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
            white-space: normal;
            text-align: left;
            line-height: 1.4;
            margin-bottom: 8px;
            display: block; /* Chaque badge sur sa propre ligne */
        }
        .btn-primary {
            width: 100%;
            padding: 12px 20px;
            font-size: 1.1rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            transition: all 0.3s ease;
            flex-shrink: 0; /* Empêche le bouton de rétrécir */
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #2c3e50);
            transform: translateY(-2px);
        }
        .availability-table {
            margin-top: 15px;
            width: 100%;
        }
        .availability-table th {
            background: #f8f9fa;
            font-size: 1rem;
            padding: 12px;
            font-weight: 600;
            color: #2c3e50;
        }
        .availability-table td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .btn-group .btn {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        .past-date {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .modal-dialog {
            max-width: 800px;
        }
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px 25px;
            border: none;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        .modal-title {
            font-size: 1.4rem;
            font-weight: 600;
        }
        .modal-body {
            padding: 25px;
        }
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-badge.en-attente {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.approuve {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.rejete {
            background: #f8d7da;
            color: #721c24;
        }
        .badge .position-absolute {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }
        .matieres-list {
            max-height: 100px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 400px; /* Taille fixe pour toutes les cartes */
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem;
            flex-shrink: 0; /* Empêche le header de rétrécir */
        }
        .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            overflow: hidden; /* Cache le contenu qui dépasse */
        }
        .card-text {
            margin-bottom: 1rem;
        }
        .matieres-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Important pour le défilement */
        }
        .matieres-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 0.5rem;
            max-height: 200px; /* Hauteur maximale fixe */
        }
        .matieres-list::-webkit-scrollbar {
            width: 6px;
        }
        .matieres-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .matieres-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .matieres-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .badge.bg-info {
            background: #e3f2fd !important;
            color: #1976d2;
            border: 1px solid #bbdefb;
            padding: 0.5rem 0.8rem;
            margin: 0.2rem;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-size: 0.9rem;
        }
        .badge.bg-info .level {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-left: 0.3rem;
        }
        .badge.bg-info:hover {
            background: #bbdefb !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .matieres-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 0.5rem;
            max-height: 200px;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-content: flex-start;
        }
        .notification-bell {
            position: relative;
            display: inline-block;
            color: #fff;
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-bell:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .notification-bell .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 0.25rem 0.5rem;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .notification-bell i {
            animation: bellRing 2s infinite;
        }

        @keyframes bellRing {
            0% { transform: rotate(0); }
            5% { transform: rotate(15deg); }
            10% { transform: rotate(-15deg); }
            15% { transform: rotate(7deg); }
            20% { transform: rotate(-7deg); }
            25% { transform: rotate(0); }
            100% { transform: rotate(0); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/logo.png" alt="Logo ASCENCIA" height="40">
                <span class="ms-2">ASCENCIA</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="page-header mb-4">
            <h1 class="mb-0">Disponibilités des Enseignants</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($teachers as $teacher): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?>
                            </h5>
                            <?php if ($teacher['disponibilites_en_attente'] > 0): ?>
                                <div class="notification-bell" title="<?php echo $teacher['disponibilites_en_attente']; ?> disponibilité(s) en attente">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge"><?php echo $teacher['disponibilites_en_attente']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?>
                            </p>
                            <div class="matieres-container">
                                <p class="card-text mb-2">
                                    <strong>Matières:</strong>
                                </p>
                                <div class="matieres-list">
                                    <?php 
                                    $matieres = !empty($teacher['matieres']) ? explode(',', $teacher['matieres']) : [];
                                    if (!empty($matieres)): 
                                        foreach ($matieres as $matiere): 
                                            if (!empty($matiere)):
                                                // Extraire le nom de la matière et le niveau
                                                preg_match('/^(.*?)\s*\((.*?)\)$/', $matiere, $matches);
                                                $nom_matiere = $matches[1] ?? $matiere;
                                                $niveau = $matches[2] ?? '';
                                    ?>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($nom_matiere); ?>
                                                <?php if (!empty($niveau)): ?>
                                                    <span class="level"><?php echo htmlspecialchars($niveau); ?></span>
                                                <?php endif; ?>
                                            </span>
                                    <?php 
                                            endif;
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="text-muted">Aucune matière assignée</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#disponibilitesModal<?php echo $teacher['id']; ?>">
                                <i class="fas fa-calendar-alt"></i> Voir les disponibilités
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal pour les disponibilités -->
                <div class="modal fade" id="disponibilitesModal<?php echo $teacher['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Disponibilités de <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                try {
                                    $query_dispo = "
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
                                    $stmt_dispo = $conn->prepare($query_dispo);
                                    $stmt_dispo->execute([$teacher['id']]);
                                    $disponibilites = $stmt_dispo->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($disponibilites)) {
                                        echo '<div class="table-responsive">';
                                        echo '<table class="table table-striped availability-table">';
                                        echo '<thead>';
                                        echo '<tr>';
                                        echo '<th>Date</th>';
                                        echo '<th>Période</th>';
                                        echo '<th>Statut</th>';
                                        echo '<th>Actions</th>';
                                        echo '</tr>';
                                        echo '</thead>';
                                        echo '<tbody>';

                                        foreach ($disponibilites as $dispo) {
                                            $rowClass = is_past_date($dispo['date_dispo']) ? 'past-date' : '';
                                            echo "<tr class='$rowClass'>";
                                            
                                            // Date
                                            echo '<td>' . format_date($dispo['date_dispo']) . '</td>';
                                            
                                            // Période
                                            echo '<td>' . get_period_name($dispo['periode']) . '</td>';
                                            
                                            // Statut
                                            $status_class = '';
                                            $status_text = '';
                                            switch($dispo['statut']) {
                                                case 'en_attente':
                                                    $status_class = 'status-badge en-attente';
                                                    $status_text = 'En attente';
                                                    break;
                                                case 'approuve':
                                                    $status_class = 'status-badge approuve';
                                                    $status_text = 'Approuvé';
                                                    break;
                                                case 'rejete':
                                                    $status_class = 'status-badge rejete';
                                                    $status_text = 'Rejeté';
                                                    break;
                                            }
                                            echo "<td><span class='$status_class'>$status_text</span></td>";
                                            
                                            // Actions
                                            echo '<td>';
                                            if ($dispo['statut'] === 'en_attente') {
                                                echo '<div class="btn-group">';
                                                echo '<button class="btn btn-sm btn-success approve-btn" 
                                                          data-dispo-id="' . $dispo['id'] . '"
                                                          data-teacher-id="' . $teacher['id'] . '">';
                                                echo '<i class="fas fa-check"></i> Approuver</button>';
                                                echo '<button class="btn btn-sm btn-danger reject-btn"
                                                          data-dispo-id="' . $dispo['id'] . '"
                                                          data-teacher-id="' . $teacher['id'] . '">';
                                                echo '<i class="fas fa-times"></i> Rejeter</button>';
                                                echo '</div>';
                                            }
                                            echo '</td>';
                                            
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-info">';
                                        echo 'Aucune disponibilité enregistrée pour le moment.';
                                        echo '</div>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger">';
                                    echo 'Erreur lors de la récupération des disponibilités : ' . $e->getMessage();
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons d'approbation
        document.querySelectorAll('.approve-btn').forEach(button => {
            button.addEventListener('click', function() {
                const dispoId = this.dataset.dispoId;
                const teacherId = this.dataset.teacherId;
                updateDisponibiliteStatus(dispoId, 'approuve', teacherId);
            });
        });

        // Gestion des boutons de rejet
        document.querySelectorAll('.reject-btn').forEach(button => {
            button.addEventListener('click', function() {
                const dispoId = this.dataset.dispoId;
                const teacherId = this.dataset.teacherId;
                updateDisponibiliteStatus(dispoId, 'rejete', teacherId);
            });
        });

        function updateDisponibiliteStatus(dispoId, status, teacherId) {
            // Afficher un indicateur de chargement
            const button = document.querySelector(`[data-dispo-id="${dispoId}"]`);
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            button.disabled = true;

            fetch('update_disponibilite_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `dispo_id=${dispoId}&status=${status}&teacher_id=${teacherId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface
                    const row = button.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(3)');
                    const actionsCell = row.querySelector('td:nth-child(4)');
                    
                    // Mettre à jour le statut
                    let statusClass = status === 'approuve' ? 'status-badge approuve' : 'status-badge rejete';
                    let statusText = status === 'approuve' ? 'Approuvé' : 'Rejeté';
                    statusCell.innerHTML = `<span class="${statusClass}">${statusText}</span>`;
                    
                    // Supprimer les boutons d'action
                    actionsCell.innerHTML = '';
                    
                    // Afficher un message de succès
                    alert(data.message);
                } else {
                    // Restaurer le bouton
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                // Restaurer le bouton en cas d'erreur
                button.innerHTML = originalContent;
                button.disabled = false;
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur');
            });
        }
    });
    </script>
</body>
</html> 