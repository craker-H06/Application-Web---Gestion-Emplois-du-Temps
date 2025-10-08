<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si les paramètres nécessaires sont présents
if (!isset($_GET['id']) || !isset($_GET['level_id'])) {
    header("Location: dashboard.php");
    exit();
}

$user_id = intval($_GET['id']);
$level_id = intval($_GET['level_id']);

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND level_id = ?");
$stmt->execute([$user_id, $level_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: manage_users.php?level_id=" . $level_id);
    exit();
}

// Récupérer les spécialisations si c'est le niveau Licence
$specializations = [];
if (strtolower($user['role']) === 'licence') {
    $stmt = $conn->query("SELECT * FROM specializations ORDER BY name");
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $specialization_id = isset($_POST['specialization_id']) ? $_POST['specialization_id'] : null;

        try {
            $sql = "UPDATE users SET nom = ?, prenom = ?, email = ?, role = ?, specialization_id = ?";
            $params = [$nom, $prenom, $email, $role, $specialization_id];

            // Si un nouveau mot de passe est fourni, l'ajouter à la mise à jour
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['success'] = "Utilisateur modifié avec succès.";
            header("Location: manage_users.php?level_id=" . $level_id);
            exit();
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification de l'utilisateur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Utilisateur</title>
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
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-secondary {
            background: #95a5a6;
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #dc2626;
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
                        <a class="nav-link" href="manage_users.php?level_id=<?php echo $level_id; ?>">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
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
                    <i class="bi bi-person-gear"></i> 
                    Modifier un Utilisateur
                </h1>
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

        <div class="row justify-content-center">
            <div class="col-md-8">
        <div class="card">
                    <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit_user">
                    
                            <div class="mb-4">
                        <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control form-control-lg" id="nom" name="nom" 
                                       value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                    </div>

                            <div class="mb-4">
                        <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control form-control-lg" id="prenom" name="prenom" 
                                       value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                    </div>

                            <div class="mb-4">
                        <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                            <div class="mb-4">
                        <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password">
                    </div>

                            <div class="mb-4">
                        <label for="role" class="form-label">Rôle</label>
                                <select class="form-select form-select-lg" id="role" name="role" required>
                            <option value="enseignant" <?php echo $user['role'] === 'enseignant' ? 'selected' : ''; ?>>Enseignant</option>
                            <option value="etudiant" <?php echo $user['role'] === 'etudiant' ? 'selected' : ''; ?>>Étudiant</option>
                        </select>
                    </div>

                    <?php if (strtolower($user['role']) === 'licence'): ?>
                                <div class="mb-4">
                            <label for="specialization_id" class="form-label">Spécialité</label>
                                    <select class="form-select form-select-lg" id="specialization_id" name="specialization_id">
                                <option value="">Sélectionnez une spécialité</option>
                                <?php foreach($specializations as $specialization): ?>
                                            <option value="<?php echo $specialization['id']; ?>" 
                                                    <?php echo $user['specialization_id'] == $specialization['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($specialization['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                            <div class="d-grid gap-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                                <a href="manage_users.php?level_id=<?php echo $level_id; ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 