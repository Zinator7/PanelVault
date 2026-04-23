<?php
require_once 'db.php';

function insert_progress($conn, $user_id, $comic_id, $current_page, $total_pages) {
    $user_id      = (int) $user_id;
    $comic_id     = (int) $comic_id;
    $current_page = (int) $current_page;
    $completed    = ($current_page >= $total_pages) ? 1 : 0;

    $sql = "INSERT INTO `reading_progress` (`user_id`, `comic_id`, `current_page`, `completed`) VALUES ($user_id, $comic_id, $current_page, $completed)
            ON DUPLICATE KEY UPDATE `current_page`=$current_page, `completed`=$completed, `last_read_at`=CURRENT_TIMESTAMP";
    return mysqli_query($conn, $sql);
}

function select_progress($conn, $user_id, $comic_id) {
    $user_id  = (int) $user_id;
    $comic_id = (int) $comic_id;
    $sql      = "SELECT * FROM `reading_progress` WHERE user_id = $user_id AND comic_id = $comic_id";
    $res      = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}

function list_reading($conn, $user_id) {
    $user_id = (int) $user_id;
    $sql     = "SELECT rp.*, c.title, c.cover, c.total_pages, c.publisher FROM `reading_progress` rp JOIN `comics` c ON rp.comic_id = c.id WHERE rp.user_id = $user_id AND rp.completed = 0 ORDER BY rp.last_read_at DESC";
    $res     = mysqli_query($conn, $sql);
    $tab     = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function list_completed($conn, $user_id) {
    $user_id = (int) $user_id;
    $sql     = "SELECT rp.*, c.title, c.cover, c.total_pages, c.publisher FROM `reading_progress` rp JOIN `comics` c ON rp.comic_id = c.id WHERE rp.user_id = $user_id AND rp.completed = 1 ORDER BY rp.last_read_at DESC";
    $res     = mysqli_query($conn, $sql);
    $tab     = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function delete_progress($conn, $user_id, $comic_id) {
    $user_id  = (int) $user_id;
    $comic_id = (int) $comic_id;
    $sql      = "DELETE FROM `reading_progress` WHERE user_id = $user_id AND comic_id = $comic_id";
    return mysqli_query($conn, $sql);
}
?>