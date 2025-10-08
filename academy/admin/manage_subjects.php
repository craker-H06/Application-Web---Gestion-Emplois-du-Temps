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

// Traitement du formulaire d'ajout de matière
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_subject') {
        $code = $_POST['code'];
        $libelle = $_POST['libelle'];
        $description = $_POST['description'];
        $nombre_heures = $_POST['nombre_heures'];

        try {
            $stmt = $conn->prepare("INSERT INTO matieres (code, libelle, description, nombre_heures, level_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $libelle, $description, $nombre_heures, $level_id]);
            
            $_SESSION['success'] = "Matière ajoutée avec succès.";
            header("Location: level_dashboard.php?level_id=" . $level_id);
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de la matière : " . $e->getMessage();
        }
    }
}

// Récupérer toutes les matières du niveau
$stmt = $conn->prepare("SELECT * FROM matieres WHERE level_id = ? ORDER BY code");
$stmt->execute([$level_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matières - <?php echo htmlspecialchars($level['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar {
            padding: 0.3rem 1rem;
            background-color: #0d6efd !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 1rem;
            font-weight: 500;
        }
        .navbar-brand img {
            height: 25px;
            width: auto;
            margin-right: 8px;
        }
        .nav-link {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            color: rgba(255,255,255,0.9) !important;
        }
        .nav-link:hover {
            color: #fff !important;
        }
        .container {
            max-width: 1200px;
            padding: 1rem;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            font-weight: 500;
        }
        .card {
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            margin-bottom: 1rem;
            background-color: #fff;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 0.75rem 1rem;
        }
        .card-title {
            color: #333;
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }
        .card-body {
            padding: 1rem;
        }
        .form-label {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 0.3rem;
        }
        .form-control {
            font-size: 0.9rem;
            padding: 0.4rem 0.75rem;
        }
        .btn {
            font-size: 0.9rem;
            padding: 0.4rem 0.75rem;
        }
        .table {
            font-size: 0.9rem;
        }
        .table th {
            font-weight: 500;
            color: #555;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
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
                        <a class="nav-link" href="level_dashboard.php?level_id=<?php echo $level_id; ?>">Retour au Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Gestion des Matières - <?php echo htmlspecialchars($level['name']); ?></h1>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ajouter une Matière</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_subject">
                            
                            <div class="mb-3">
                                <label for="code" class="form-label">Code</label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>

                            <div class="mb-3">
                                <label for="libelle" class="form-label">Libellé</label>
                                <input type="text" class="form-control" id="libelle" name="libelle" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_heures" class="form-label">Nombre d'heures</label>
                                <input type="number" class="form-control" id="nombre_heures" name="nombre_heures" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Ajouter la matière
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Liste des Matières</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Libellé</th>
                                        <th>Description</th>
                                        <th>Heures</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['libelle']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['description']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['nombre_heures']); ?></td>
                                            <td>
                                                <a href="edit_subject.php?id=<?php echo $subject['id']; ?>&level_id=<?php echo $level_id; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="delete_subject.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');">
                                                    <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                    <input type="hidden" name="level_id" value="<?php echo $level_id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 