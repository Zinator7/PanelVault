<?php
include '../../db_connect.php';

function insert_comic($conn, $user_id, $title, $publisher, $cover, $total_pages) {
    $user_id     = (int) $user_id;
    $title       = mysqli_real_escape_string($conn, $title);
    $publisher   = mysqli_real_escape_string($conn, $publisher);
    $cover       = mysqli_real_escape_string($conn, $cover);
    $total_pages = (int) $total_pages;

    $sql = "INSERT INTO `comics` (`user_id`, `title`, `publisher`, `cover`, `total_pages`) VALUES ($user_id, '$title', '$publisher', '$cover', $total_pages)";
    return mysqli_query($conn, $sql);
}

function select_comic($conn, $id) {
    $id  = (int) $id;
    $sql = "SELECT c.*, u.username as uploader FROM `comics` c JOIN `users` u ON c.user_id = u.id WHERE c.id = $id";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}

function list_comics($conn) {
    $sql = "SELECT c.*, u.username as uploader FROM `comics` c JOIN `users` u ON c.user_id = u.id ORDER BY c.uploaded_at DESC";
    $res = mysqli_query($conn, $sql);
    $tab = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function list_comics_by_user($conn, $user_id) {
    $user_id = (int) $user_id;
    $sql     = "SELECT * FROM `comics` WHERE user_id = $user_id ORDER BY uploaded_at DESC";
    $res     = mysqli_query($conn, $sql);
    $tab     = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function list_comics_by_publisher($conn, $publisher) {
    $publisher = mysqli_real_escape_string($conn, $publisher);
    $sql       = "SELECT c.*, u.username as uploader FROM `comics` c JOIN `users` u ON c.user_id = u.id WHERE c.publisher = '$publisher' ORDER BY c.uploaded_at DESC";
    $res       = mysqli_query($conn, $sql);
    $tab       = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function search_comics($conn, $query) {
    $query = mysqli_real_escape_string($conn, $query);
    $sql   = "SELECT c.*, u.username as uploader FROM `comics` c JOIN `users` u ON c.user_id = u.id WHERE c.title LIKE '%$query%' ORDER BY c.uploaded_at DESC";
    $res   = mysqli_query($conn, $sql);
    $tab   = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function update_comic($conn, $id, $title, $publisher, $cover) {
    $id        = (int) $id;
    $title     = mysqli_real_escape_string($conn, $title);
    $publisher = mysqli_real_escape_string($conn, $publisher);
    $cover     = mysqli_real_escape_string($conn, $cover);
    $sql       = "UPDATE `comics` SET `title`='$title', `publisher`='$publisher', `cover`='$cover' WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function delete_comic($conn, $id) {
    $id  = (int) $id;
    $sql = "DELETE FROM `comics` WHERE id = $id";
    return mysqli_query($conn, $sql);
}
?>