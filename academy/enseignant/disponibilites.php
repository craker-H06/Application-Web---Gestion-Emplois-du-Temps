<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté et est un enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Traitement du formulaire de disponibilités
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- DEBUG POST --- Entrée dans le bloc POST
    error_log("DEBUG POST: Entrée dans le bloc de traitement POST.");

    // Ajouter du logging pour inspecter les données POST reçues
    error_log("DEBUG POST: $ _POST data received: " . print_r($_POST, true));

    $date = null;
    // Lire la date depuis le champ 'selected_date' du formulaire en priorité
    if (isset($_POST['selected_date']) && !empty($_POST['selected_date'])) {
        $date = clean_input($_POST['selected_date']);
        error_log("DEBUG POST: $date extracted: " . $date);
    } elseif (isset($_POST['date']) && !empty($_POST['date'])) { // Fallback just in case
         $date = clean_input($_POST['date']);
         error_log("DEBUG POST: Warning: Received date from unexpected 'date' key instead of 'selected_date'. Date: " . $date);
    }

    if ($date) {
        error_log("DEBUG POST: Date is valid. Processing periods.");
        // Modification pour récupérer les disponibilités du formulaire
        $periodes = [];
        if (isset($_POST['disponibilites']) && is_array($_POST['disponibilites'])) {
            foreach ($_POST['disponibilites'] as $periode_item) {
                if (isset($periode_item['periode'])) {
                    $periodes[] = ['periode' => $periode_item['periode']];
                }
            }
        }
        error_log("DEBUG POST: Periodes processed: " . print_r($periodes, true));
        
        try {
            error_log("DEBUG POST: Starting DB transaction.");
            $conn->beginTransaction();

            // Supprimer les anciennes disponibilités pour cette date
            $stmt_delete = $conn->prepare("DELETE FROM disponibilites WHERE user_id = ? AND date_dispo = ?");
            error_log("DEBUG POST: Executing DELETE for user " . $_SESSION['user_id'] . " and date " . $date);
            $stmt_delete->execute([$_SESSION['user_id'], $date]);
            error_log("DEBUG POST: DELETE executed. Row count: " . $stmt_delete->rowCount());
            
            // Insérer les nouvelles disponibilités si des périodes sont sélectionnées
            if (!empty($periodes)) {
                error_log("DEBUG POST: Periods found. Starting INSERT loop.");
                 $stmt_insert = $conn->prepare("INSERT INTO disponibilites (user_id, date_dispo, periode, statut) VALUES (?, ?, ?, 'en_attente')");
                 foreach ($periodes as $periode_item) { // Renommer $periode pour éviter conflit
                     // Assurer que la clé 'periode' existe et n'est pas nulle
                     if (isset($periode_item['periode']) && !empty($periode_item['periode'])) {
                         $periode_key = $periode_item['periode'];
                         error_log("DEBUG POST: Inserting availability for date " . $date . " periode " . $periode_key);
                         $stmt_insert->execute([$_SESSION['user_id'], $date, $periode_key]);
                         error_log("DEBUG POST: INSERT executed for periode " . $periode_key);
                     } else {
                         error_log("DEBUG POST: Warning: Skipping availability insertion due to missing or empty periode key for item: " . print_r($periode_item, true) . " for date: " . $date);
                     }
                 }
            }
           
            $conn->commit();
            error_log("DEBUG POST: DB transaction committed. Redirecting.");
            $success_message = "Disponibilités enregistrées avec succès.";
            
            // Rediriger pour éviter la soumission multiple du formulaire
            header("Location: disponibilites.php?success=1");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Une erreur est survenue lors de l'enregistrement des disponibilités : " . $e->getMessage();
             // Log l'erreur complète côté serveur pour le débogage
            error_log("DEBUG POST: Erreur PDO/Exception lors de l'enregistrement: " . $e->getMessage());
        }
    } else {
        $error_message = "Aucune date sélectionnée ou date invalide pour enregistrer les disponibilités.";
         error_log("DEBUG POST: Erreur: Aucune date ou date invalide reçue.");
    }
} else if (isset($_GET['success'])) {
    error_log("DEBUG: Page loaded with success=1 parameter.");
    // Afficher le message de succès si présent dans l'URL après redirection
    $success_message = "Vos disponibilités ont été mises à jour avec succès.";
}

// Récupérer les disponibilités actuelles
try {
    /* --- DEBUG --- Afficher l'ID utilisateur utilisé
    echo "<pre>Debug User ID: " . $_SESSION['user_id'] . " (variable $user_id: " . $user_id . ")</pre>";
    ------------- Fin DEBUG --------- */

    $query_dispo = "SELECT id, date_dispo, periode, statut FROM disponibilites WHERE user_id = ? ORDER BY date_dispo DESC, periode ASC";
    
    /* --- DEBUG --- Afficher la requête SQL préparée
    echo "<pre>Debug Query:\n" . htmlspecialchars($query_dispo) . "</pre>";
    echo "<pre>Debug Parameters:\n" . print_r([$user_id], true) . "</pre>";
    ------------- Fin DEBUG --------- */

    $stmt_dispo = $conn->prepare($query_dispo);
    $stmt_dispo->execute([$user_id]);
    
    /* --- DEBUG --- Afficher le rowCount et les erreurs de l'instruction
    echo "<pre>Debug Statement Info:\n";
    echo "RowCount: " . $stmt_dispo->rowCount() . "\n";
    print_r($stmt_dispo->errorInfo());
    echo "</pre>";
    ------------- Fin DEBUG --------- */

    $disponibilites = $stmt_dispo->fetchAll(PDO::FETCH_ASSOC);

    /* --- DEBUG --- Afficher le contenu de la variable $disponibilites
    echo "<pre>Debug Disponibilités Fetched:\n";
    print_r($disponibilites);
    echo "</pre>";
    ------------- Fin DEBUG --------- */

    // Initialiser le tableau des disponibilités par date
    $disponibilites_par_date = [];

    // Créer un tableau associatif des disponibilités par date pour le calendrier
    foreach ($disponibilites as $dispo) {
        $date_formattee = $dispo['date_dispo']; // Format YYYY-MM-DD pour la clé
        if (!isset($disponibilites_par_date[$date_formattee])) {
            $disponibilites_par_date[$date_formattee] = [];
        }
        $disponibilites_par_date[$date_formattee][] = $dispo['periode'];
    }

} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des disponibilités : " . $e->getMessage();
    $disponibilites = [];
    $disponibilites_par_date = [];
    error_log("Erreur PDO dans enseignant/disponibilites.php (récupération) : " . $e->getMessage());
}

// Calculer les statistiques
$total_disponibilites = count($disponibilites);
$disponibilites_par_periode = [
    'periode1' => 0,
    'periode2' => 0,
    'periode3' => 0
];
foreach ($disponibilites as $dispo) {
     // Assurer que la clé existe avant d'y accéder
    if (isset($disponibilites_par_periode[$dispo['periode']])) {
        $disponibilites_par_periode[$dispo['periode']]++;
    } else {
         error_log("Warning: Periode inconnue rencontrée : " . $dispo['periode']);
    }
}

// Périodes disponibles (pour le calendrier et l'affichage)
$periodes_config = [
    'periode1' => '8h30 - 12h30',
    'periode2' => '12h30 - 17h30',
    'periode3' => '18h00 - 22h00'
];

// Obtenir la date actuelle
$today = new DateTime();
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)$today->format('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)$today->format('Y');

// Créer un objet DateTime pour le premier jour du mois
$firstDayOfMonth = new DateTime("$currentYear-$currentMonth-01");
$lastDayOfMonth = new DateTime($firstDayOfMonth->format('Y-m-t'));

// Obtenir le jour de la semaine du premier jour (0 = Dimanche, 6 = Samedi)
$firstDayOfWeek = (int)$firstDayOfMonth->format('w');

// Calculer le nombre de jours dans le mois
$daysInMonth = (int)$lastDayOfMonth->format('d');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Disponibilités - ASCENCIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .calendar-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 10px;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .calendar-day {
            aspect-ratio: 1;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: white;
        }
        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .calendar-day.past {
            background-color: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        .calendar-day.selected {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        .calendar-day.has-disponibilite {
            background-color: #e8f5e9;
            border-color: #4caf50;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .periodes-container {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .periode-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .periode-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .periode-item.selected {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .periode-time {
            font-weight: bold;
            color: #2c3e50;
            margin-right: 15px;
        }
        .periode-label {
            color: #6c757d;
        }
        .btn-save {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        .month-navigation {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .month-navigation button {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .month-navigation button:hover {
            background: rgba(255,255,255,0.3);
        }
        .current-month {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-input {
            border-radius: 20px;
            padding: 10px 20px;
            border: 1px solid #e9ecef;
            width: 100%;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .disponibilites-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .disponibilites-list::-webkit-scrollbar {
            width: 8px;
        }
        .disponibilites-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        .disponibilites-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .disponibilites-list::-webkit-scrollbar-thumb:hover {
            background: #555;
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

        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-title">Total des disponibilités</div>
                <div class="stat-value"><?php echo $total_disponibilites; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sun"></i>
                </div>
                <div class="stat-title">Période matin (8h30-12h30)</div>
                <div class="stat-value"><?php echo $disponibilites_par_periode['periode1']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cloud-sun"></i>
                </div>
                <div class="stat-title">Période après-midi (12h30-17h30)</div>
                <div class="stat-value"><?php echo $disponibilites_par_periode['periode2']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="stat-title">Période soir (18h00-22h00)</div>
                <div class="stat-value"><?php echo $disponibilites_par_periode['periode3']; ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="month-navigation">
                            <button onclick="changeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="current-month">
                                <?php echo $firstDayOfMonth->format('F Y'); ?>
                            </span>
                            <button onclick="changeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <?php
                        $daysOfWeek = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                        foreach ($daysOfWeek as $day) {
                            echo "<div class='calendar-day-header'>$day</div>";
                        }

                        // Remplir les jours vides au début
                        for ($i = 0; $i < $firstDayOfWeek; $i++) {
                            echo "<div class='calendar-day'></div>";
                        }

                        // Remplir les jours du mois
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = new DateTime("$currentYear-$currentMonth-$day");
                            $isPast = $date < $today;
                            $isSelected = false;
                            $hasDisponibilite = false;

                            // Vérifier si la date a des disponibilités
                            foreach ($disponibilites as $dispo) {
                                if ($dispo['date_dispo'] == $date->format('Y-m-d')) {
                                    $hasDisponibilite = true;
                                    break;
                                }
                            }

                            $classes = ['calendar-day'];
                            if ($isPast) $classes[] = 'past';
                            if ($isSelected) $classes[] = 'selected';
                            if ($hasDisponibilite) $classes[] = 'has-disponibilite';

                            echo "<div class='" . implode(' ', $classes) . "' 
                                      data-date='" . $date->format('Y-m-d') . "'
                                      onclick='selectDate(this)'>";
                            echo $day;
                            if ($hasDisponibilite) {
                                echo "<i class='fas fa-check-circle position-absolute top-0 end-0 m-2 text-success'></i>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <div id="periodesContainer" class="periodes-container">
                        <h4 class="mb-4">Sélectionnez les périodes disponibles pour le <span id="selectedDate"></span></h4>
                        <form id="disponibilitesForm" method="POST">
                            <input type="hidden" name="selected_date" id="selectedDateInput">
                            <?php foreach ($periodes_config as $key => $value): ?>
                                <div class="periode-item" onclick="togglePeriode(this, '<?php echo $key; ?>')">
                                    <div class="periode-time"><?php echo $value; ?></div>
                                    <div class="periode-label"><?php echo ucfirst($key); ?></div>
                                    <input type="checkbox" name="disponibilites[<?php echo $key; ?>][periode]" 
                                           value="<?php echo $key; ?>" style="display: none;">
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-save">
                                    <i class="fas fa-save"></i> Enregistrer les disponibilités
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i> Mes disponibilités
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="search-container">
                            <input type="text" class="search-input" id="searchDisponibilites" 
                                   placeholder="Rechercher une date...">
                        </div>
                        <div class="disponibilites-list">
                            <?php if (empty($disponibilites)): ?>
                                <p class="text-muted">Aucune disponibilité enregistrée.</p>
                            <?php else: ?>
                                <?php
                                $currentDate = null;
                                foreach ($disponibilites as $dispo):
                                    // Utiliser directement date_dispo du tableau (format YYYY-MM-DD)
                                    $dispo_date_str = $dispo['date_dispo'];
                                    $date_obj = new DateTime($dispo_date_str);

                                    if ($currentDate !== $dispo_date_str):
                                        if ($currentDate !== null) echo '</div><!-- fin groupe date -->';
                                        $currentDate = $dispo_date_str;
                                ?>
                                    <div class="disponibilite-date mb-3">
                                        <h6 class="text-primary mb-2">
                                            <?php echo $date_obj->format('d/m/Y'); // Afficher au format JJ/MM/YYYY ?>
                                        </h6>
                                <?php endif; ?>
                                        <div class="periode-item">
                                            <?php
                                            // Utiliser la fonction get_period_name pour obtenir le nom
                                            echo get_period_name($dispo['periode']);
                                            ?>
                                             <span class="badge 
                                                <?php 
                                                switch($dispo['statut']) {
                                                    case 'en_attente': echo 'bg-warning'; break;
                                                    case 'approuve': echo 'bg-success'; break;
                                                    case 'rejete': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                                ms-2">
                                                <?php
                                                switch($dispo['statut']) {
                                                    case 'en_attente': echo 'En attente'; break;
                                                    case 'approuve': echo 'Approuvé'; break;
                                                    case 'rejete': echo 'Rejeté'; break;
                                                    default: echo 'Inconnu';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                <?php endforeach; ?>
                                <?php if ($currentDate !== null) echo '</div><!-- fin groupe date -->'; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Conserver l'état sélectionné après rechargement si la date est dans l'URL (après redirection)
        const urlParams = new URLSearchParams(window.location.search);
        const successDate = urlParams.get('date'); // Vérifier si une date est passée après succès
        if (urlParams.has('success')) {
             // Si succès, tenter de sélectionner la date qui était dans le formulaire POST
             // Note: La date POST n'est pas directement dans l'URL après redirection GET. 
             // Il faudrait la passer explicitement si on voulait la resélectionner.
             // Pour l'instant, on ne resélectionne pas automatiquement.
        }

        function selectDate(element) {
            if (element.classList.contains('past')) return;

            // Mettre à jour la date sélectionnée
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });
            element.classList.add('selected');

            // Afficher les périodes
            const date = element.dataset.date; // Format YYYY-MM-DD
            document.getElementById('selectedDate').textContent = new Date(date + 'T12:00:00').toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('selectedDateInput').value = date;
            document.getElementById('periodesContainer').style.display = 'block';

            // Réinitialiser les périodes et présélectionner celles existantes pour cette date
            const disponibilitesExistantes = <?php echo json_encode($disponibilites_par_date); ?>;

            document.querySelectorAll('.periode-item').forEach(item => {
                 const checkbox = item.querySelector('input[type="checkbox"]');
                 const periodeKey = checkbox.value; // Récupérer la clé de la période (periode1, periode2, ...)

                 item.classList.remove('selected');
                 checkbox.checked = false;

                 // Vérifier si cette période existe pour la date sélectionnée
                 if (disponibilitesExistantes[date] && disponibilitesExistantes[date].includes(periodeKey)) {
                      item.classList.add('selected');
                      checkbox.checked = true;
                 }
            });

        }

        function togglePeriode(element, periodeKey) {
            element.classList.toggle('selected');
            const checkbox = element.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = element.classList.contains('selected');
                // Forcer la mise à jour de l'état checked
                checkbox.dispatchEvent(new Event('change'));
            }
        }

        function changeMonth(delta) {
            const urlParams = new URLSearchParams(window.location.search);
            let month = parseInt(urlParams.get('month') || <?php echo $currentMonth; ?>);
            let year = parseInt(urlParams.get('year') || <?php echo $currentYear; ?>);

            month += delta;
            if (month > 12) {
                month = 1;
                year++;
            } else if (month < 1) {
                month = 12;
                year--;
            }

            // Assurer que l'URL conserve le paramètre success s'il existe (pour afficher le message après redirection)
            const newUrlParams = new URLSearchParams();
            newUrlParams.set('month', month);
            newUrlParams.set('year', year);
            if (urlParams.has('success')) {
                 newUrlParams.set('success', 1);
            }
            // Optionnel: Si vous vouliez resélectionner la date après le changement de mois, vous pourriez ajouter la date aux params ici
            // if (currentlySelectedDate) { newUrlParams.set('date', currentlySelectedDate); }

            window.location.href = `?${newUrlParams.toString()}`;
        }

        // Fonction de recherche
        document.getElementById('searchDisponibilites').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const disponibilites = document.querySelectorAll('.disponibilite-date'); // Chaque groupe de date
            
            disponibilites.forEach(dispoGroup => {
                const dateElement = dispoGroup.querySelector('h6');
                const dateText = dateElement.textContent.toLowerCase();
                
                if (dateText.includes(searchText)) {
                    dispoGroup.style.display = 'block';
                } else {
                    dispoGroup.style.display = 'none';
                }
            });
        });

         // S'assurer que le conteneur des périodes est caché au chargement initial sauf si une date est présélectionnée (ce qui n'est pas le cas ici)
        // document.getElementById('periodesContainer').style.display = 'none'; // Cette ligne est déjà dans le CSS display: none

        // Amélioration: Si la page est chargée avec un message de succès, on pourrait scrollé vers la liste des dispos
        if (urlParams.has('success')) {
             const listElement = document.querySelector('.disponibilites-list');
             if (listElement) {
                 listElement.scrollIntoView({ behavior: 'smooth' });
             }
        }

    </script>
</body>
</html> 