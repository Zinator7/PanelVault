<?php
header('Content-Type: application/json');
include 'db_connect.php';

// Nombre total d'utilisateurs inscrits
$res_u = mysqli_query($conn, "SELECT COUNT(*) as c FROM users");
$total_users = mysqli_fetch_assoc($res_u)['c'] ?? 0;

// Nombre total de comics dans la base
$res_c = mysqli_query($conn, "SELECT COUNT(*) as c FROM comics");
$total_comics = mysqli_fetch_assoc($res_c)['c'] ?? 0;

// Nombre total de lectures terminées sur tout le site
$res_r = mysqli_query($conn, "SELECT COUNT(*) as c FROM reading_progress WHERE completed = 1");
$total_completed = mysqli_fetch_assoc($res_r)['c'] ?? 0;

echo json_encode([
    'users'     => (int)$total_users,
    'comics'    => (int)$total_comics,
    'completed' => (int)$total_completed
]);
?>