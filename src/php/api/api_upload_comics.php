<?php
header('Content-Type: application/json');
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit();
}

if (!isset($_FILES['comic_file']) && !isset($_FILES['scan_file'])) { 
    echo json_encode(['error' => 'Aucun fichier reçu']);
    exit();
}

$file = $_FILES['comic_file'] ?? $_FILES['scan_file'];
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
    $title = mysqli_real_escape_string($conn, pathinfo($file['name'], PATHINFO_FILENAME));
    $userId = (int)$_SESSION['user']['id'];
    $totalPages = isset($_POST['total_pages']) ? (int)$_POST['total_pages'] : 0;
    
    // On insère en base. Note: tu devras peut-être adapter tes colonnes (ex: file_path)
    $sql = "INSERT INTO comics (title, file_path, file_type, user_id, total_pages, created_at) 
            VALUES ('$title', '$newName', '$ext', $userId, $totalPages, NOW())";
    
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