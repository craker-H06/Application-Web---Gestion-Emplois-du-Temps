<?php
session_start();
// Vérifier si l'utilisateur est connecté et est un enseignant
// Si pas connecté ou pas enseignant, rediriger vers la page de connexion
if(!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'enseignant') {
    header("Location: ../login.php");
    exit();
}

// Optionnel : Vérifier si le profil est *réellement* en attente
// Si le statut a changé (approuvé/refusé), rediriger vers la page appropriée
require_once '../config/database.php';
$stmt_profile = $conn->prepare("SELECT status FROM teacher_profiles WHERE user_id = ?");
$stmt_profile->execute([$_SESSION['user_id']]);
$profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);

// Si le profil n'existe pas ou n'est pas 'pending', rediriger
if (!$profile || $profile['status'] !== 'pending') {
    if ($profile && $profile['status'] === 'approved') {
         header("Location: dashboard.php");
    } else {
        // Si pas de profil ou statut != pending/approved, rediriger vers la page de complétion
        header("Location: complete_profile.php");
    }
    exit();
}

// Si le statut est bien 'pending', afficher la page d'attente

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En Attente d'Approbation - Ascencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
     <style>
        .navbar-brand img {
            height: 30px; /* Ajustez la hauteur selon vos besoins */
            margin-right: 10px;
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
             <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-header">
                        <h2>Profil En Attente d'Approbation</h2>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Votre profil a été soumis avec succès et est en attente de validation par l'administrateur.</p>
                        <p class="card-text">Vous serez notifié(e) une fois que votre accès au tableau de bord enseignant sera approuvé.</p>
                        <p class="card-text">Merci de votre patience.</p>
                         <a href="../logout.php" class="btn btn-primary mt-3">Se Déconnecter</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 