<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un enseignant
// Si pas connecté ou pas enseignant, rediriger vers la page de connexion
if(!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'enseignant') {
    header("Location: ../login.php");
    exit();
}

// Vérifier si l'enseignant a déjà un profil complété ou en attente/approuvé
// Si un profil existe, le rediriger (soit vers l'attente, soit vers le dashboard)
$stmt_profile = $conn->prepare("SELECT status FROM teacher_profiles WHERE user_id = ?");
$stmt_profile->execute([$_SESSION['user_id']]);
$profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);

if ($profile) {
    // Si un profil existe déjà, rediriger selon son statut
     if ($profile['status'] === 'pending') {
         header("Location: waiting_approval.php");
     } elseif ($profile['status'] === 'approved') {
         header("Location: dashboard.php");
     }
     // Pour le statut 'rejected', on peut le laisser ici pour qu'il remplisse à nouveau si nécessaire, ou rediriger vers une page d'information
    exit();
}

// Si aucun profil n'existe, continuer pour afficher le formulaire

// Optionnel : Récupérer les informations de base de l'utilisateur (nom, prenom, email) pour pré-remplir ou afficher
$stmt_user = $conn->prepare("SELECT nom, prenom, email FROM users WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compléter Profil Enseignant - Ascencia</title>
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

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Compléter votre Profil Enseignant</h2>
                    </div>
                    <div class="card-body">
                        <p class="alert alert-info">Veuillez compléter les informations ci-dessous pour finaliser votre inscription.</p>

                        <?php if(isset($_SESSION['errors'])): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach($_SESSION['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php unset($_SESSION['errors']); ?>
                        <?php endif; ?>

                        <form action="save_profile.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">

                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user_info['nom'] ?? ''); ?>" readonly> <!-- Généralement non modifiable ici -->
                            </div>
                             <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user_info['prenom'] ?? ''); ?>" readonly> <!-- Généralement non modifiable ici -->
                            </div>
                             <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" readonly> <!-- Généralement non modifiable ici -->
                            </div>

                            <div class="mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control" id="ville" name="ville" required>
                            </div>
                            <div class="mb-3">
                                <label for="quartier" class="form-label">Quartier</label>
                                <input type="text" class="form-control" id="quartier" name="quartier">
                            </div>
                            <div class="mb-3">
                                <label for="dernier_diplome" class="form-label">Dernier Diplôme Obtenu</label>
                                <input type="text" class="form-control" id="dernier_diplome" name="dernier_diplome" required>
                            </div>
                             <div class="mb-3">
                                <label for="statut_matrimonial" class="form-label">Statut Matrimonial</label>
                                <select class="form-select" id="statut_matrimonial" name="statut_matrimonial">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="celibataire">Célibataire</option>
                                    <option value="marie">Marié(e)</option>
                                    <option value="divorce">Divorcé(e)</option>
                                    <option value="veuf">Veuf/Veuve</option>
                                </select>
                            </div>
                             <div class="mb-3">
                                <label for="nombre_enfants" class="form-label">Nombre d'enfants</label>
                                <input type="number" class="form-control" id="nombre_enfants" name="nombre_enfants" min="0" value="0">
                            </div>
                            <div class="mb-3">
                                <label for="langues_parlees" class="form-label">Langues Parlées (séparées par une virgule)</label>
                                <input type="text" class="form-control" id="langues_parlees" name="langues_parlees">
                            </div>
                            <div class="mb-3">
                                <label for="langues_ecrites" class="form-label">Langues Écrites (séparées par une virgule)</label>
                                <input type="text" class="form-control" id="langues_ecrites" name="langues_ecrites">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Soumettre le Profil</button>
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