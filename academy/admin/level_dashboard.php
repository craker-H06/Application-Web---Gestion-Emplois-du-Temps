<?php
ob_start(); // Activer la mise en tampon de sortie
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
    // Rediriger si l'ID du niveau est manquant ou invalide
    header("Location: dashboard.php");
    exit();
}

// Récupérer les informations du niveau
$stmt = $conn->prepare("SELECT * FROM levels WHERE id = ?");
$stmt->execute([$level_id]);
$level = $stmt->fetch(PDO::FETCH_ASSOC);

// Rediriger si le niveau n'existe pas
if (!$level) {
    header("Location: dashboard.php");
    exit();
}

// Le nom du niveau est maintenant disponible dans $level['name']

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                // Logique pour ajouter un utilisateur
                header("Location: manage_users.php?level_id=" . $level_id);
                exit();
            
            case 'edit_user':
                if (isset($_POST['user_id'])) {
                    header("Location: edit_user.php?id=" . $_POST['user_id'] . "&level_id=" . $level_id);
                    exit();
                }
                break;
            
            case 'delete_user':
                if (isset($_POST['user_id'])) {
                    // Logique pour supprimer un utilisateur
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND level_id = ?");
                    $stmt->execute([$_POST['user_id'], $level_id]);
                    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
                    header("Location: level_dashboard.php?level_id=" . $level_id);
                    exit();
                }
                break;

            case 'add_subject':
                header("Location: manage_subjects.php?level_id=" . $level_id);
                exit();

            case 'edit_subject':
                if (isset($_POST['subject_id'])) {
                    header("Location: edit_subject.php?id=" . $_POST['subject_id'] . "&level_id=" . $level_id);
                    exit();
                }
                break;

            case 'delete_subject':
                if (isset($_POST['subject_id'])) {
                    $stmt = $conn->prepare("DELETE FROM matieres WHERE id = ? AND level_id = ?");
                    $stmt->execute([$_POST['subject_id'], $level_id]);
                    $_SESSION['success'] = "Matière supprimée avec succès.";
                    header("Location: level_dashboard.php?level_id=" . $level_id);
                    exit();
                }
                break;
        }
    }
}

// Créer la table schedule si elle n'existe pas
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        level_id INT NOT NULL,
        matiere_id INT NOT NULL,
        jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi') NOT NULL,
        heure_debut TIME NOT NULL,
        heure_fin TIME NOT NULL,
        salle_id INT NOT NULL,
        enseignant_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
        FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
        FOREIGN KEY (salle_id) REFERENCES salles(id) ON DELETE CASCADE,
        FOREIGN KEY (enseignant_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Si la table existe déjà, on continue
    if ($e->getCode() != '42S01') {
        throw $e;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Niveau <?php echo htmlspecialchars($level['name']); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
     <style>
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
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
                        <span class="nav-link text-light">Connecté(e) en tant que : <?php echo htmlspecialchars($_SESSION['prenom']) . ' ' . htmlspecialchars($_SESSION['nom']); ?></span>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Retour au Dashboard Principal</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h1>Dashboard - <?php echo htmlspecialchars($level['name']); ?></h1>

        <!-- Navigation par onglets pour les sections de gestion -->
        <ul class="nav nav-tabs" id="levelTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="?level_id=<?php echo $level_id; ?>&tab=users" class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'users') ? 'active' : ''; ?>" id="users-tab" role="tab" aria-controls="users" aria-selected="<?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'users') ? 'true' : 'false'; ?>">Utilisateurs</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?level_id=<?php echo $level_id; ?>&tab=matieres" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'matieres') ? 'active' : ''; ?>" id="matieres-tab" role="tab" aria-controls="matieres" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'matieres') ? 'true' : 'false'; ?>">Matières du Niveau</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?level_id=<?php echo $level_id; ?>&tab=attributions" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'attributions') ? 'active' : ''; ?>" id="attributions-tab" role="tab" aria-controls="attributions" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'attributions') ? 'true' : 'false'; ?>">Attributions du Niveau</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?level_id=<?php echo $level_id; ?>&tab=emplois-temps" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'emplois-temps') ? 'active' : ''; ?>" id="emplois-temps-tab" role="tab" aria-controls="emplois-temps" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'emplois-temps') ? 'true' : 'false'; ?>">Emplois du Temps du Niveau</a>
            </li>
            <?php if (strtolower($level['name']) === 'licence'): ?>
                <li class="nav-item" role="presentation">
                    <a href="?level_id=<?php echo $level_id; ?>&tab=specializations" class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'specializations') ? 'active' : ''; ?>" id="specializations-tab" role="tab" aria-controls="specializations" aria-selected="<?php echo (isset($_GET['tab']) && $_GET['tab'] === 'specializations') ? 'true' : 'false'; ?>">Spécialités (Licence)</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content" id="levelTabsContent">
            <!-- Contenu de l'onglet Utilisateurs -->
            <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'users') ? 'show active' : ''; ?>" id="users" role="tabpanel" aria-labelledby="users-tab">
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h2>Gestion des Utilisateurs de ce Niveau</h2>
                    <a href="manage_users.php?level_id=<?php echo $level_id; ?>" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Ajouter un Utilisateur
                    </a>
                </div>
                <?php
                // Récupérer les enseignants de ce niveau
                $sql_enseignants = "SELECT u.* FROM users u WHERE u.level_id = ? AND u.role = 'enseignant' ORDER BY u.nom, u.prenom";
                $stmt_enseignants = $conn->prepare($sql_enseignants);
                $stmt_enseignants->execute([$level_id]);
                $level_enseignants = $stmt_enseignants->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($level_enseignants): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Actions</th> <!-- Colonne Actions pour Enseignants -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($level_enseignants as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                        <td class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="edit_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Modifier
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3" role="alert">
                        Aucun enseignant trouvé pour ce niveau pour l'instant.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenu de l'onglet Matières -->
            <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'matieres') ? 'show active' : ''; ?>" id="matieres" role="tabpanel" aria-labelledby="matieres-tab">
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h2>Gestion des Matières</h2>
                    <a href="manage_subjects.php?level_id=<?php echo $level_id; ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Ajouter une Matière
                    </a>
                </div>
                <?php
                // Récupérer les matières de ce niveau
                $stmt = $conn->prepare("SELECT * FROM matieres WHERE level_id = ? ORDER BY libelle");
                $stmt->execute([$level_id]);
                $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($subjects): ?>
                    <div class="table-responsive mt-3">
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
                                        <td class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="edit_subject">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Modifier
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette matière ?');">
                                                <input type="hidden" name="action" value="delete_subject">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3" role="alert">
                        Aucune matière trouvée pour ce niveau pour l'instant.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenu de l'onglet Attributions -->
            <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'attributions') ? 'show active' : ''; ?>" id="attributions" role="tabpanel" aria-labelledby="attributions-tab">
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h2>Attribution des Matières aux Enseignants</h2>
                    <a href="wilfried.php?level_id=<?php echo $level_id; ?>" class="btn btn-primary">
                        <i class="bi bi-link"></i> Gérer les Attributions
                    </a>
                </div>
                <?php
                // Récupérer les attributions de matières
                $stmt = $conn->prepare("
                    SELECT u.nom, u.prenom, GROUP_CONCAT(m.libelle SEPARATOR ', ') as matieres
                    FROM users u
                    LEFT JOIN teacher_subjects ts ON u.id = ts.user_id
                    LEFT JOIN matieres m ON ts.matiere_id = m.id
                    WHERE u.level_id = ? AND u.role = 'enseignant'
                    GROUP BY u.id
                    ORDER BY u.nom, u.prenom
                ");
                $stmt->execute([$level_id]);
                $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($assignments): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Enseignant</th>
                                    <th>Matières attribuées</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['prenom'] . ' ' . $assignment['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['matieres'] ?: 'Aucune matière attribuée'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3" role="alert">
                        Aucune attribution de matière trouvée.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenu de l'onglet Emplois du Temps -->
            <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'emplois-temps') ? 'show active' : ''; ?>" id="emplois-temps" role="tabpanel" aria-labelledby="emplois-temps-tab">
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h2>Emploi du Temps</h2>
                    <a href="schedule.php?level_id=<?php echo $level_id; ?>" class="btn btn-primary">
                        <i class="bi bi-calendar-plus"></i> Gérer l'Emploi du Temps
                    </a>
                </div>
                <?php
                // Récupérer l'emploi du temps
                $stmt = $conn->prepare("
                    SELECT s.*, m.libelle as matiere, u.nom as enseignant_nom, u.prenom as enseignant_prenom, 
                           sl.nom as salle_nom
                    FROM schedule s
                    JOIN matieres m ON s.matiere_id = m.id
                    JOIN users u ON s.enseignant_id = u.id
                    JOIN salles sl ON s.salle_id = sl.id
                    WHERE s.level_id = ?
                    ORDER BY FIELD(s.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), 
                             s.heure_debut
                ");
                $stmt->execute([$level_id]);
                $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($schedule): ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Jour</th>
                                    <th>Créneau</th>
                                    <th>Matière</th>
                                    <th>Enseignant</th>
                                    <th>Salle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($schedule as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['jour_semaine']); ?></td>
                                        <td><?php echo htmlspecialchars($course['heure_debut']); ?> - <?php echo htmlspecialchars($course['heure_fin']); ?></td>
                                        <td><?php echo htmlspecialchars($course['matiere']); ?></td>
                                        <td><?php echo htmlspecialchars($course['enseignant_prenom'] . ' ' . $course['enseignant_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($course['salle_nom']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3" role="alert">
                        Aucun cours programmé pour ce niveau.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenu de l'onglet Spécialités (seulement pour la Licence) -->
             <?php if (strtolower($level['name']) === 'licence'): ?>
                <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'specializations') ? 'show active' : ''; ?>" id="specializations" role="tabpanel" aria-labelledby="specializations-tab">
                     <h2 class="mt-3">Spécialités de la Licence</h2>
                     <?php
                        // --- Logique et affichage des spécialités de la Licence ---
                        // Lister les spécialités, permettre d'en ajouter/modifier/supprimer
                        // Afficher les utilisateurs par spécialité au sein de cet onglet, ou lier vers une vue plus détaillée.
                        // Récupérer les spécialités pour le niveau Licence
                        $stmt_specializations = $conn->prepare("SELECT * FROM specializations WHERE level_id = ? ORDER BY name");
                        $stmt_specializations->execute([$level['id']]); // Utiliser l'ID du niveau actuel (qui sera celui de la Licence)
                        $licence_specializations = $stmt_specializations->fetchAll(PDO::FETCH_ASSOC);
                     ?>

                     <?php if ($licence_specializations): ?>
                         <div class="table-responsive">
                             <table class="table table-striped">
                                 <thead>
                                     <tr>
                                         <th>Nom de la Spécialité</th>
                                         <!-- Ajoutez d'autres colonnes si nécessaire -->
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach($licence_specializations as $specialization): ?>
                                         <tr>
                                             <td><?php echo htmlspecialchars($specialization['name']); ?></td>
                                             <td>
                                                 <!-- Actions spécialité (modifier, supprimer, etc.) -->
                                                 <button class="btn btn-sm btn-warning">Modifier</button>
                                                 <button class="btn btn-sm btn-danger">Supprimer</button>
                                             </td>
                                         </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                     <?php else: ?>
                          <div class="alert alert-info mt-3" role="alert">
                            Aucune spécialité configurée pour la Licence pour l'instant.
                         </div>
                     <?php endif; ?>
                     <!-- Bouton Ajouter Spécialité (à implémenter) -->
                     <button class="btn btn-success mt-3">Ajouter Spécialité</button>
                </div>
             <?php endif; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer les onglets Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Récupérer l'onglet actif depuis l'URL si présent
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            
            if (activeTab) {
                // Désactiver tous les onglets
                document.querySelectorAll('.nav-link').forEach(tab => {
                    tab.classList.remove('active');
                    tab.setAttribute('aria-selected', 'false');
                });
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Activer l'onglet spécifié
                const tabToActivate = document.querySelector(`#${activeTab}-tab`);
                const paneToActivate = document.querySelector(`#${activeTab}`);
                
                if (tabToActivate && paneToActivate) {
                    tabToActivate.classList.add('active');
                    tabToActivate.setAttribute('aria-selected', 'true');
                    paneToActivate.classList.add('show', 'active');
                }
            }
        });
    </script>
</body>
</html>

<!-- Modal Ajouter Utilisateur -->
<div class="modal fade" id="ajouterUtilisateurModal" tabindex="-1" aria-labelledby="ajouterUtilisateurModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterUtilisateurModalLabel">Ajouter un utilisateur pour <?php echo htmlspecialchars($level['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterUtilisateurForm" action="manage_users.php" method="POST">
                    <input type="hidden" name="action" value="add_user">
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
                     <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <!-- Option 'admin' n'est généralement pas ajoutée via ce formulaire de niveau -->
                            <option value="enseignant">Enseignant</option>
                             <!-- Ajoutez l'option 'etudiant' si ce rôle existe dans votre ENUM -->
                            <option value="etudiant">Étudiant</option>
                        </select>
                    </div>

                    <?php if (strtolower($level['name']) === 'licence'): ?>
                         <div class="mb-3" id="specialization-group">
                            <label for="specialization" class="form-label">Spécialité</label>
                            <select class="form-select" id="specialization" name="specialization_id">
                                <option value="">-- Sélectionner une spécialité --</option>
                                <?php
                                // Récupérer les spécialités pour la Licence (déjà fait plus haut dans l'onglet Spécialités, on peut réutiliser la variable)
                                // Assurez-vous que $licence_specializations est disponible ici, ou refaites la requête si ce modal est utilisé indépendamment des onglets.
                                // Pour l'instant, je suppose que $licence_specializations est disponible.
                                if (isset($licence_specializations)) {
                                     foreach($licence_specializations as $specialization): ?>
                                        <option value="<?php echo $specialization['id']; ?>"><?php echo htmlspecialchars($specialization['name']); ?></option>
                                    <?php endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterUtilisateurForm" class="btn btn-primary">Ajouter Utilisateur</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Matière -->
<div class="modal fade" id="ajouterMatiereModal" tabindex="-1" aria-labelledby="ajouterMatiereModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterMatiereModalLabel">Ajouter une matière pour <?php echo htmlspecialchars($level['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterMatiereForm" action="manage_matieres.php" method="POST">
                    <input type="hidden" name="action" value="add_matiere">
                     <!-- Champ caché pour passer l'ID du niveau -->
                    <input type="hidden" name="level_id" value="<?php echo $level_id; ?>">

                    <div class="mb-3">
                        <label for="code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="nom_matiere" class="form-label">Nom de la matière</label>
                        <input type="text" class="form-control" id="nom_matiere" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="description_matiere" class="form-label">Description</label>
                        <textarea class="form-control" id="description_matiere" name="description" rows="3"></textarea>
                    </div>
                    <!-- Champ pour le nombre d'heures -->
                    <div class="mb-3">
                        <label for="nombre_heures" class="form-label">Nombre d'heures</label>
                        <input type="number" class="form-control" id="nombre_heures" name="nombre_heures" min="0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterMatiereForm" class="btn btn-primary">Ajouter Matière</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Associer le bouton "Ajouter Utilisateur" à l'ouverture du modal
    document.addEventListener('DOMContentLoaded', function () {
        const addUserButton = document.querySelector('#users button.btn-success.mt-3'); // Sélectionne le bouton dans l'onglet utilisateurs
        if (addUserButton) {
            addUserButton.setAttribute('data-bs-toggle', 'modal');
            addUserButton.setAttribute('data-bs-target', '#ajouterUtilisateurModal');
        }

        // Associer le bouton "Ajouter Matière" à l'ouverture du modal
         const addMatiereButton = document.querySelector('#matieres button.btn-success.mt-3'); // Sélectionne le bouton dans l'onglet matières
         if (addMatiereButton) {
             addMatiereButton.setAttribute('data-bs-toggle', 'modal');
             addMatiereButton.setAttribute('data-bs-target', '#ajouterMatiereModal');
         }

        // Afficher/Masquer le champ Spécialité en fonction du rôle (si étudiant est ajouté)
        const roleSelect = document.getElementById('role');
        const specializationGroup = document.getElementById('specialization-group');

        if (roleSelect && specializationGroup) {
            roleSelect.addEventListener('change', function() {
                // Adapter la logique si vous avez d'autres rôles et leurs besoins de spécialité
                if (this.value === 'etudiant' && "<?php echo strtolower($level['name']); ?>" === 'licence') {
                    specializationGroup.style.display = 'block';
                } else {
                    specializationGroup.style.display = 'none';
                }
            });
            // Initialiser l'affichage basé sur la valeur par défaut
             roleSelect.dispatchEvent(new Event('change'));
        }

    });
</script> 