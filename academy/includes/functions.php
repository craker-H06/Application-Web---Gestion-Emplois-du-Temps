<?php
// Fonction pour vérifier si l'utilisateur est connecté
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est un administrateur
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour rediriger l'utilisateur
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fonction pour nettoyer les entrées utilisateur
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour formater une date
function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

// Fonction pour vérifier si une date est passée
function is_past_date($date) {
    return strtotime($date) < time();
}

// Fonction pour obtenir le nom de la période
function get_period_name($period) {
    switch ($period) {
        case '8h30-12h30':
            return 'Matin (8h30-12h30)';
        case '12h30-17h30':
            return 'Après-midi (12h30-17h30)';
        case '18h00-22h00':
            return 'Soirée (18h00-22h00)';
        default:
            return $period;
    }
}
?> 