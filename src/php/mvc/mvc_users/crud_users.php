<?php
include '../../db_connect.php';
function insert_user($conn, $username, $email, $password) {
    $username = mysqli_real_escape_string($conn, $username);
    $email    = mysqli_real_escape_string($conn, $email);
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO `users` (`username`, `email`, `password`) VALUES ('$username', '$email', '$hash')";
    return mysqli_query($conn, $sql);
}

function select_user($conn, $id) {
    $id  = (int) $id;
    $sql = "SELECT * FROM `users` WHERE id = $id";
    $res = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}

function select_user_by_email($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $sql   = "SELECT * FROM `users` WHERE email = '$email'";
    $res   = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($res);
}

function list_users($conn) {
    // On ordonne par XP en premier, puis par points en cas d'égalité
    $sql = "SELECT id, username, avatar, level, xp, points, streak, streak_max FROM `users` ORDER BY xp DESC, points DESC";
    $res = mysqli_query($conn, $sql);
    $tab = [];
    while ($row = mysqli_fetch_assoc($res)) $tab[] = $row;
    return $tab;
}

function update_user($conn, $id, $username, $avatar) {
    $id       = (int) $id;
    $username = mysqli_real_escape_string($conn, $username);
    $avatar   = mysqli_real_escape_string($conn, $avatar);
    $sql      = "UPDATE `users` SET `username`='$username', `avatar`='$avatar' WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function update_xp_points($conn, $id, $xp, $points) {
    $id     = (int) $id;
    $xp     = (int) $xp;
    $points = (int) $points;
    $sql    = "UPDATE `users` SET `xp` = xp + $xp, `points` = points + $points WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function update_streak($conn, $id) {
    $user = select_user($conn, $id);
    if (!$user) return false;

    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $streak    = (int) $user['streak'];
    $streakMax = (int) $user['streak_max'];

    if ($user['last_read'] === $today) return true;
    $streak = ($user['last_read'] === $yesterday) ? $streak + 1 : 1;
    $streakMax = max($streak, $streakMax);

    $id  = (int) $id;
    $sql = "UPDATE `users` SET `streak`=$streak, `streak_max`=$streakMax, `last_read`='$today' WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function delete_user($conn, $id) {
    $id  = (int) $id;
    $sql = "DELETE FROM `users` WHERE id = $id";
    return mysqli_query($conn, $sql);
}

function login_user($conn, $email, $password) {
    $user = select_user_by_email($conn, $email);
    if (!$user) return false;
    if (!password_verify($password, $user['password'])) return false;
    return $user;
}
?>