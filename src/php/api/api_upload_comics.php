<?php
header('Content-Type: application/json');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit();
}

// Vérification de la taille via Content-Length si $_FILES est vide
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(['error' => 'Le fichier dépasse la limite autorisée par le serveur.']);
    exit();
}

if (!isset($_FILES['comic_file'])) { 
    echo json_encode(['error' => 'Aucun fichier de scan n\'a été envoyé.']); 
    exit(); 
}

if ($_FILES['comic_file']['error'] !== 0) {
    $errCodes = [1 => 'Fichier trop lourd (php.ini)', 2 => 'Fichier trop lourd (HTML)', 3 => 'Upload partiel', 4 => 'Aucun fichier'];
    $msg = $errCodes[$_FILES['comic_file']['error']] ?? 'Erreur inconnue';
    echo json_encode(['error' => 'Erreur upload : ' . $msg]); 
    exit(); 
}

$file = $_FILES['comic_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'epub', 'cbz', 'zip', 'jpg', 'png', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['error' => 'Format non supporté (.' . $ext . ')']);
    exit();
}

// Sécurité : nom de fichier unique
$newName = uniqid('comic_') . '.' . $ext;
$uploadDir = '../../uploads/comics/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
    // Gestion de la couverture si fournie
    $coverName = "";
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === 0) {
        $cExt = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
        if (in_array($cExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $coverName = uniqid('cover_') . '.' . $cExt;
            move_uploaded_file($_FILES['cover_file']['tmp_name'], '../../assets/img/' . $coverName);
        }
    }

    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
    $publisher = mysqli_real_escape_string($conn, $_POST['publisher'] ?? '');
    $userId = (int)$_SESSION['user']['id'];
    $totalPages = isset($_POST['total_pages']) ? (int)$_POST['total_pages'] : 0;
    
    $sql = "INSERT INTO comics (title, publisher, cover, file_path, file_type, user_id, total_pages, created_at) 
            VALUES ('$title', '$publisher', '$coverName', '$newName', '$ext', $userId, $totalPages, NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Erreur BDD : ' . mysqli_error($conn)]);
    }
} else {
    // Aide au diagnostic si l'upload échoue
    $maxSize = ini_get('upload_max_filesize');
    echo json_encode([
        'error' => 'Échec du transfert. Vérifiez que le fichier ne dépasse pas ' . $maxSize,
        'debug_code' => $file['error']
    ]);
}
?>