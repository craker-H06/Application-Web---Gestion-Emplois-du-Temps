<?php
session_start();
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, rediriger vers la page appropriée
if(isset($_SESSION['user_id'])) {
    switch($_SESSION['role']) {
        case 'admin':
            header("Location: admin/dashboard.php");
            break;
        case 'enseignant':
            header("Location: enseignant/dashboard.php");
            break;
        case 'etudiant':
            header("Location: etudiant/dashboard.php");
            break;
        default:
            header("Location: index.php");
    }
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        // Vérifier les identifiants
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            
            // Rediriger vers la page appropriée
            switch($user['role']) {
                case 'admin':
                header("Location: admin/dashboard.php");
                    break;
                case 'enseignant':
                                header("Location: enseignant/dashboard.php");
                    break;
                case 'etudiant':
                    header("Location: etudiant/dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {
        $error = "Une erreur est survenue lors de la connexion.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ASCENCIA business school</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .login-header .logo-container img {
            height: 80px;
            width: auto;
        }
        .login-header .school-name {
            display: flex;
            flex-direction: column;
            align-items: center;
            line-height: 1.2;
        }
        .login-header .main-text {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .login-header .sub-text {
            font-size: 1.1rem;
            font-weight: 400;
            color: #34495e;
            margin: 0;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,.05);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <img src="assets/images/logo.png" alt="Logo ASCENCIA">
                <div class="school-name">
                    <h1 class="main-text">ASCENCIA</h1>
                    <p class="sub-text">business school</p>
                </div>
            </div>
        </div>

                <div class="card">
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="login.php">
                    <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                    <div class="mb-4">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Se connecter
                        </button>
                            </div>
                        </form>
                        <p class="text-center mt-3">Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 