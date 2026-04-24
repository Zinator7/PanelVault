<?php
// ════════════════════════════════════════════════
//  GET_CBZ_PAGE — Sert une image extraite d'un fichier CBZ/ZIP
//  PanelVault
//
//  Paramètres GET :
//    id   = comic_id (int)
//    page = numéro de page 1-indexed (int)
//
//  Retourne : image brute (jpeg/png/webp/gif) avec header Content-Type
// ════════════════════════════════════════════════

session_start();

// Seuls les utilisateurs connectés peuvent accéder aux scans
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit();
}

include '../db_connect.php';

$comic_id = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($comic_id <= 0 || $page_num <= 0) {
    http_response_code(400);
    exit();
}

// Récupère les infos du fichier depuis la BDD
$res   = mysqli_query($conn, "SELECT file_path, file_type FROM comics WHERE id = $comic_id");
$comic = mysqli_fetch_assoc($res);

if (!$comic || !$comic['file_path'] || !in_array($comic['file_type'], ['cbz', 'zip'])) {
    http_response_code(404);
    exit();
}

// __DIR__ = src/php/api → ../../uploads/comics/ = src/uploads/comics/
$scan_path = __DIR__ . '/../../uploads/comics/' . basename($comic['file_path']);

if (!file_exists($scan_path)) {
    http_response_code(404);
    exit();
}

$zip = new ZipArchive();
if ($zip->open($scan_path) !== true) {
    http_response_code(500);
    exit();
}

// Collecte tous les fichiers image dans l'archive
$images = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    // Ignore les dossiers et fichiers cachés macOS
    if (str_starts_with(basename($name), '.')) continue;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $images[] = $name;
    }
}

// natsort = tri naturel : "page_2.jpg" avant "page_10.jpg"
natsort($images);
$images = array_values($images);

$idx = $page_num - 1; // 0-indexed
if ($idx < 0 || $idx >= count($images)) {
    $zip->close();
    http_response_code(404);
    exit();
}

$img_data = $zip->getFromName($images[$idx]);
$zip->close();

if ($img_data === false) {
    http_response_code(500);
    exit();
}

$ext  = strtolower(pathinfo($images[$idx], PATHINFO_EXTENSION));
$mime = match($ext) {
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
    default => 'image/jpeg',
};

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
header('Content-Length: ' . strlen($img_data));
echo $img_data;
