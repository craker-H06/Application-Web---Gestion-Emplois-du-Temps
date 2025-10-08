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

// Traitement du formulaire d'ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $level_id = $_POST['level_id'];
        $specialization_id = isset($_POST['specialization_id']) ? $_POST['specialization_id'] : null;

        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé.";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (nom, prenom, email, password, role, level_id, specialization_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $password, $role, $level_id, $specialization_id]);
                
                $_SESSION['success'] = "Utilisateur ajouté avec succès.";
                header("Location: level_dashboard.php?level_id=" . $level_id);
                exit();
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
        }
    }
}

// Récupérer les spécialisations si c'est le niveau Licence
$specializations = [];
if (strtolower($level['name']) === 'licence') {
    $stmt = $conn->query("SELECT * FROM specializations ORDER BY name");
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - <?php echo htmlspecialchars($level['name']); ?></title>
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
        .form-control, .form-select {
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
        .alert {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
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
        <h1>Gestion des Utilisateurs - <?php echo htmlspecialchars($level['name']); ?></h1>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Colonne des Enseignants -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Enseignants du Niveau <?php echo htmlspecialchars($level['name']); ?></h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="bi bi-person-plus"></i> Ajouter un Enseignant
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // Récupérer les enseignants de ce niveau
                        $stmt = $conn->prepare("
                            SELECT u.*, 
                                   COALESCE(s.name, 'Non assigné') as specialite
                            FROM users u 
                            LEFT JOIN specializations s ON u.specialization_id = s.id
                            WHERE u.level_id = ? AND u.role = 'enseignant' 
                            ORDER BY u.nom, u.prenom
                        ");
                        $stmt->execute([$level_id]);
                        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if ($teachers): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Email</th>
                                            <th>Spécialité</th>
                                            <th>Date d'inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($teachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['prenom']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['specialite']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($teacher['date_creation'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $teacher['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $teacher['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                Aucun enseignant trouvé pour ce niveau.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne des Étudiants -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Étudiants du Niveau <?php echo htmlspecialchars($level['name']); ?></h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus"></i> Ajouter un Étudiant
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        // Récupérer les étudiants de ce niveau
                        $stmt = $conn->prepare("
                            SELECT u.*, 
                                   COALESCE(s.name, 'Non assigné') as specialite
                            FROM users u 
                            LEFT JOIN specializations s ON u.specialization_id = s.id
                            WHERE u.level_id = ? AND u.role = 'etudiant' 
                            ORDER BY u.nom, u.prenom
                        ");
                        $stmt->execute([$level_id]);
                        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if ($students): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Email</th>
                                            <th>Spécialité</th>
                                            <th>Date d'inscription</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($student['prenom']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['specialite']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($student['date_creation'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $student['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $student['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                Aucun étudiant trouvé pour ce niveau.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Enseignant -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Enseignant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <input type="hidden" name="role" value="enseignant">
                        <input type="hidden" name="level_id" value="<?php echo $level_id; ?>">
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Ajouter l'enseignant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Étudiant -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un Étudiant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <input type="hidden" name="role" value="etudiant">
                        <input type="hidden" name="level_id" value="<?php echo $level_id; ?>">
                        
                        <div class="mb-3">
                            <label for="nom_student" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom_student" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="prenom_student" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom_student" name="prenom" required>
                        </div>

                        <div class="mb-3">
                            <label for="email_student" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_student" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_student" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password_student" name="password" required>
                        </div>

                        <?php if (strtolower($level['name']) === 'licence'): ?>
                            <div class="mb-3">
                                <label for="specialization_id" class="form-label">Spécialité</label>
                                <select class="form-select" id="specialization_id" name="specialization_id">
                                    <option value="">Sélectionnez une spécialité</option>
                                    <?php foreach($specializations as $specialization): ?>
                                        <option value="<?php echo $specialization['id']; ?>">
                                            <?php echo htmlspecialchars($specialization['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Ajouter l'étudiant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            window.location.href = `edit_user.php?id=${userId}&level_id=<?php echo $level_id; ?>`;
        }

        function deleteUser(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_user.php';
                
                const userIdInput = document.createElement('input');
                userIdInput.type = 'hidden';
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                
                const levelIdInput = document.createElement('input');
                levelIdInput.type = 'hidden';
                levelIdInput.name = 'level_id';
                levelIdInput.value = '<?php echo $level_id; ?>';
                
                form.appendChild(userIdInput);
                form.appendChild(levelIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 