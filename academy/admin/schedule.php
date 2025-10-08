<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Récupérer tous les niveaux
$stmt = $conn->query("SELECT * FROM levels ORDER BY name");
$levels = $stmt->fetchAll();

// Récupérer tous les enseignants
$stmt = $conn->query("SELECT u.*, l.name as level_name 
                     FROM users u 
                     LEFT JOIN levels l ON u.level_id = l.id 
                     WHERE u.role = 'enseignant' 
                     ORDER BY u.prenom, u.nom");
$teachers = $stmt->fetchAll();

// Récupérer toutes les matières
$stmt = $conn->query("SELECT * FROM matieres ORDER BY libelle");
$subjects = $stmt->fetchAll();

// Traitement du formulaire d'ajout/modification d'emploi de temps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Supprimer l'ancien emploi du temps pour cette combinaison
        $stmt = $conn->prepare("DELETE FROM schedule WHERE level_id = ? AND user_id = ? AND matiere_id = ? AND day_of_week = ? AND time_slot = ?");
        $stmt->execute([
            $_POST['level_id'],
            $_POST['teacher_id'],
            $_POST['matiere_id'],
            $_POST['day_of_week'],
            $_POST['time_slot']
        ]);

        // Insérer le nouvel emploi du temps
        $stmt = $conn->prepare("INSERT INTO schedule (level_id, user_id, matiere_id, day_of_week, time_slot) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['level_id'],
            $_POST['teacher_id'],
            $_POST['matiere_id'],
            $_POST['day_of_week'],
            $_POST['time_slot']
        ]);
            
        $conn->commit();
        $success_message = "L'emploi du temps a été mis à jour avec succès.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Une erreur est survenue : " . $e->getMessage();
    }
}

// Récupérer l'emploi du temps actuel
$stmt = $conn->query("SELECT s.*, l.name as level_name, 
                             CONCAT(u.prenom, ' ', u.nom) as teacher_name,
                             m.libelle as subject_name
    FROM schedule s
                      JOIN levels l ON s.level_id = l.id
                      JOIN users u ON s.user_id = u.id
    JOIN matieres m ON s.matiere_id = m.id
                      ORDER BY l.name, s.day_of_week, s.time_slot");
$schedule = $stmt->fetchAll();

// Créer un tableau associatif pour faciliter l'accès aux emplois du temps
$schedule_map = [];
foreach ($schedule as $item) {
    $key = $item['level_id'] . '_' . $item['day_of_week'] . '_' . $item['time_slot'];
    $schedule_map[$key] = $item;
}

// Jours de la semaine
$days = [
    'Lundi',
    'Mardi',
    'Mercredi',
    'Jeudi',
    'Vendredi',
    'Samedi'
];

// Créneaux horaires
$time_slots = [
    '8h30-10h30',
    '10h45-12h45',
    '14h00-16h00',
    '16h15-18h15'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des emplois de temps - ASCENCIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .schedule-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .schedule-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .schedule-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .schedule-table td {
            padding: 15px;
            text-align: center;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .schedule-cell {
            min-height: 100px;
            position: relative;
        }
        .schedule-cell:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }
        .schedule-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .schedule-subject {
            font-weight: bold;
            color: #2c3e50;
        }
        .schedule-teacher {
            font-style: italic;
            color: #6c757d;
        }
        .modal-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
        }
        .btn-primary {
            background: #3498db;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .level-selector {
            margin-bottom: 20px;
        }
        .level-selector .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .level-selector .btn.active {
            background: #3498db;
            color: white;
        }
        .schedule-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: none;
        }
        .schedule-cell:hover .schedule-actions {
            display: block;
        }
        .schedule-actions button {
            padding: 2px 8px;
            font-size: 0.8rem;
            margin-left: 5px;
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
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                                            <?php endif; ?>

        <div class="schedule-container">
            <div class="schedule-header">
                <h2 class="mb-0">
                    <i class="fas fa-clock"></i> Gestion des emplois de temps
                </h2>
                            </div>

            <div class="level-selector">
                <h5 class="mb-3">Sélectionnez un niveau :</h5>
                <div class="btn-group" role="group">
                    <?php foreach ($levels as $level): ?>
                        <button type="button" class="btn btn-outline-primary level-btn" 
                                data-level-id="<?php echo $level['id']; ?>">
                            <?php echo htmlspecialchars($level['name']); ?>
                        </button>
                                    <?php endforeach; ?>
                </div>
            </div>

                        <div class="table-responsive">
                <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>Horaire</th>
                            <?php foreach ($days as $day): ?>
                                            <th><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                        <?php foreach ($time_slots as $time_slot): ?>
                                        <tr>
                                <th><?php echo $time_slot; ?></th>
                                <?php foreach ($days as $day_index => $day): ?>
                                    <td class="schedule-cell" 
                                        data-day="<?php echo $day_index; ?>"
                                        data-time="<?php echo $time_slot; ?>">
                                        <?php
                                        $key = "1_" . $day_index . "_" . $time_slot;
                                        if (isset($schedule_map[$key])):
                                            $item = $schedule_map[$key];
                                                    ?>
                                            <div class="schedule-info">
                                                <div class="schedule-subject">
                                                    <?php echo htmlspecialchars($item['subject_name']); ?>
                                                            </div>
                                                <div class="schedule-teacher">
                                                    <?php echo htmlspecialchars($item['teacher_name']); ?>
                                                            </div>
                                                        </div>
                                            <div class="schedule-actions">
                                                <button class="btn btn-sm btn-primary edit-schedule" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#scheduleModal"
                                                        data-level="<?php echo $item['level_id']; ?>"
                                                        data-teacher="<?php echo $item['user_id']; ?>"
                                                        data-subject="<?php echo $item['matiere_id']; ?>"
                                                        data-day="<?php echo $day_index; ?>"
                                                        data-time="<?php echo $time_slot; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-schedule"
                                                        data-level="<?php echo $item['level_id']; ?>"
                                                        data-day="<?php echo $day_index; ?>"
                                                        data-time="<?php echo $time_slot; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="schedule-actions">
                                                <button class="btn btn-sm btn-success add-schedule"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#scheduleModal"
                                                        data-day="<?php echo $day_index; ?>"
                                                        data-time="<?php echo $time_slot; ?>">
                                                    <i class="fas fa-plus"></i>
                                                            </button>
                                            </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
    </div>

    <!-- Modal pour ajouter/modifier un emploi du temps -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gérer l'emploi du temps</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm" method="POST">
                        <input type="hidden" name="day_of_week" id="dayOfWeek">
                        <input type="hidden" name="time_slot" id="timeSlot">
                        <input type="hidden" name="matiere_id" id="subject_id">
                        
                        <div class="mb-3">
                            <label for="level_id" class="form-label">Niveau</label>
                            <select class="form-select" id="level_id" name="level_id" required>
                                <option value="">Sélectionnez un niveau</option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>">
                                        <?php echo htmlspecialchars($level['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Enseignant</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Sélectionnez un enseignant</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']); ?>
                                        <?php if ($teacher['level_name']): ?>
                                            (<?php echo htmlspecialchars($teacher['level_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Matière</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Sélectionnez une matière</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['libelle']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" form="scheduleForm" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des boutons de niveau
        document.querySelectorAll('.level-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.level-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                // TODO: Filtrer l'emploi du temps par niveau
            });
        });

        // Gestion du modal d'ajout/modification
        document.querySelectorAll('.add-schedule, .edit-schedule').forEach(button => {
            button.addEventListener('click', function() {
                const day = this.dataset.day;
                const time = this.dataset.time;
                
                document.getElementById('dayOfWeek').value = day;
                document.getElementById('timeSlot').value = time;

                if (this.classList.contains('edit-schedule')) {
                    document.getElementById('level_id').value = this.dataset.level;
                    document.getElementById('teacher_id').value = this.dataset.teacher;
                    document.getElementById('subject_id').value = this.dataset.subject;
                } else {
                    document.getElementById('scheduleForm').reset();
                }
            });
        });

        // Gestion de la suppression
        document.querySelectorAll('.delete-schedule').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer cet emploi du temps ?')) {
                    // TODO: Implémenter la suppression
                }
            });
        });
    </script>
</body>
</html> 