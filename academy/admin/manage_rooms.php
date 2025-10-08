<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Traitement de l'ajout/modification/suppression de salle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $conn->prepare("INSERT INTO salles (nom, capacite, type) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['nom'], $_POST['capacite'], $_POST['type']]);
                    $_SESSION['success'] = "Salle ajoutée avec succès";
                    break;

                case 'edit':
                    $stmt = $conn->prepare("UPDATE salles SET nom = ?, capacite = ?, type = ? WHERE id = ?");
                    $stmt->execute([$_POST['nom'], $_POST['capacite'], $_POST['type'], $_POST['id']]);
                    $_SESSION['success'] = "Salle modifiée avec succès";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM salles WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = "Salle supprimée avec succès";
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }
        header("Location: manage_rooms.php");
        exit();
    }
}

// Récupérer toutes les salles
$stmt = $conn->query("SELECT * FROM salles ORDER BY nom");
$salles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Salles - Ascencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 1rem 0;
        }
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .room-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .room-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        .modal-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .alert {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        .stats-card {
            background: linear-gradient(45deg, #3498db, #2c3e50);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .room-type-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            background: #e9ecef;
            color: #495057;
        }
        .capacity-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Retour au Dashboard
            </a>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="mb-0">
                <i class="bi bi-building"></i> Gestion des Salles
            </h1>
        </div>
    </div>

    <div class="container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h5><i class="bi bi-building"></i> Total des Salles</h5>
                    <h3><?php echo count($salles); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h5><i class="bi bi-people"></i> Capacité Totale</h5>
                    <h3><?php echo array_sum(array_column($salles, 'capacite')); ?> places</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h5><i class="bi bi-diagram-3"></i> Types de Salles</h5>
                    <h3><?php echo count(array_unique(array_column($salles, 'type'))); ?></h3>
                </div>
            </div>
        </div>

        <!-- Bouton d'ajout -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="bi bi-plus-circle"></i> Ajouter une Salle
            </button>
        </div>

        <!-- Liste des salles -->
        <div class="row">
            <?php foreach($salles as $salle): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="room-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($salle['nom']); ?></h5>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" onclick="editRoom(<?php echo htmlspecialchars(json_encode($salle)); ?>)">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" onclick="deleteRoom(<?php echo $salle['id']; ?>)">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <span class="room-type-badge">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($salle['type']); ?>
                            </span>
                            <span class="capacity-badge">
                                <i class="bi bi-people"></i> <?php echo $salle['capacite']; ?> places
                            </span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> Ajoutée le <?php echo date('d/m/Y', strtotime($salle['created_at'])); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une Salle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoomForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nom de la salle</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacité</label>
                            <input type="number" class="form-control" name="capacite" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de salle</label>
                            <select class="form-control" name="type" required>
                                <option value="Amphithéâtre">Amphithéâtre</option>
                                <option value="Salle de cours">Salle de cours</option>
                                <option value="Laboratoire">Laboratoire</option>
                                <option value="Salle informatique">Salle informatique</option>
                                <option value="Salle de réunion">Salle de réunion</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="addRoomForm" class="btn btn-primary">Ajouter</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la Salle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRoomForm" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de la salle</label>
                            <input type="text" class="form-control" name="nom" id="edit_nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacité</label>
                            <input type="number" class="form-control" name="capacite" id="edit_capacite" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de salle</label>
                            <select class="form-control" name="type" id="edit_type" required>
                                <option value="Amphithéâtre">Amphithéâtre</option>
                                <option value="Salle de cours">Salle de cours</option>
                                <option value="Laboratoire">Laboratoire</option>
                                <option value="Salle informatique">Salle informatique</option>
                                <option value="Salle de réunion">Salle de réunion</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="editRoomForm" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de suppression -->
    <form id="deleteRoomForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('edit_id').value = room.id;
            document.getElementById('edit_nom').value = room.nom;
            document.getElementById('edit_capacite').value = room.capacite;
            document.getElementById('edit_type').value = room.type;
            new bootstrap.Modal(document.getElementById('editRoomModal')).show();
        }

        function deleteRoom(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cette salle ?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteRoomForm').submit();
            }
        }
    </script>
</body>
</html> 