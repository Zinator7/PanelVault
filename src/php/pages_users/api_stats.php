<?php
session_start();
header('Content-Type: application/json');

include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
include '../../php/mvc/mvc_reading/crud_reading.php';
include '../../php/mvc/mvc_badges/crud_badges.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_session = $_SESSION['user'];
$user_id      = (int) $user_session['id'];
$user         = select_user($conn, $user_id);

// Calculs des stats
$niveau       = isset($user['level']) ? (int) $user['level'] : 1;
$xp_totale    = isset($user['xp'])    ? (int) $user['xp']    : 0;
$xp_par_palier     = 1000;
$xp_dans_le_niveau = $xp_totale - (($niveau - 1) * $xp_par_palier);
$pourcentage       = max(0, min(100, ($xp_dans_le_niveau / $xp_par_palier) * 100));

$badges_possedes = list_user_badges($conn, $user_id);
$nb_badges       = count($badges_possedes);
$tous_badges     = list_badges($conn);
$nb_total        = count($tous_badges);

$comics_finis    = list_completed($conn, $user_id);
$nb_lus          = count($comics_finis);

echo json_encode([
    'xp' => $xp_totale,
    'level' => $niveau,
    'badges' => $nb_badges,
    'read' => $nb_lus,
    'percent' => $nb_total > 0 ? round(($nb_badges / $nb_total) * 100) : 0,
    'streak' => isset($user['streak']) ? (int)$user['streak'] : 0
]);