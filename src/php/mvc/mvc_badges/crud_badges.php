<?php
include '../../db_connect.php';

function insert_badge($conn, $slug, $name, $description, $icon) {
    $slug        = mysqli_real_escape_string($conn, $slug);
    $name        = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $icon        = mysqli_real_escape_string($conn, $icon);
    $sql         = "INSERT INTO `badges` (`slug`, `name`, `description`, `icon`) VALUES ('$slug', '$name', '$description', '$icon')";
    return mysqli_query($conn, $sql);
}

function select_badge($conn, $id) {
    $id  = (int) $id;
    $sql = "SELECT * FROM `badges` WHERE id = $id";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}

function list_badges($conn) {
    $sql = "SELECT * FROM `badges` ORDER BY id ASC";
    $res = mysqli_query($conn, $sql);
    $tab = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function delete_badge($conn, $id) {
    $id  = (int) $id;
    $sql = "DELETE FROM `badges` WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function give_badge($conn, $user_id, $badge_id) {
    $user_id  = (int) $user_id;
    $badge_id = (int) $badge_id;
    $sql      = "INSERT IGNORE INTO `user_badges` (`user_id`, `badge_id`) VALUES ($user_id, $badge_id)";
    return mysqli_query($conn, $sql);
}

function list_user_badges($conn, $user_id) {
    $user_id = (int) $user_id;
    $sql     = "SELECT b.*, ub.earned_at FROM `user_badges` ub JOIN `badges` b ON ub.badge_id = b.id WHERE ub.user_id = $user_id ORDER BY ub.earned_at DESC";
    $res     = mysqli_query($conn, $sql);
    $tab     = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function user_has_badge($conn, $user_id, $badge_id) {
    $user_id  = (int) $user_id;
    $badge_id = (int) $badge_id;
    $sql      = "SELECT COUNT(*) as total FROM `user_badges` WHERE user_id = $user_id AND badge_id = $badge_id";
    $res      = mysqli_query($conn, $sql);
    $row      = mysqli_fetch_assoc($res);
    return $row['total'] > 0;
}

function remove_badge($conn, $user_id, $badge_id) {
    $user_id  = (int) $user_id;
    $badge_id = (int) $badge_id;
    $sql      = "DELETE FROM `user_badges` WHERE user_id = $user_id AND badge_id = $badge_id";
    return mysqli_query($conn, $sql);
}
?>