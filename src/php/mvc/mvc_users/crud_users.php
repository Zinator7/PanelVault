<?php
require_once 'db.php';

/**
 * CRUD — TABLE users
 */

function insert_user($conn, $username, $email, $password) {
    // On échappe les données pour éviter les injections SQL
    $username = mysqli_real_escape_string($conn, $username);
    $email    = mysqli_real_escape_string($conn, $email);
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO `users` (`username`, `email`, `password`) 
            VALUES ('$username', '$email', '$hash')";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    return $res;
}

function select_user($conn, $id) {
    $id  = mysqli_real_escape_string($conn, $id);
    $sql = "SELECT * FROM `users` WHERE id = '$id'";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    $tab = rs_to_tab($res);
    return $tab[0];
}

function select_user_by_email($conn, $email) {
    $email = mysqli_real_escape_string($conn, $email);
    $sql   = "SELECT * FROM `users` WHERE email = '$email'";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    $tab = rs_to_tab($res);
    return isset($tab[0]) ? $tab[0] : false;
}

function list_users($conn) {
    $sql = "SELECT id, username, avatar, level, xp, points, streak 
            FROM `users` 
            ORDER BY points DESC";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    return rs_to_tab($res);
}

function update_user($conn, $id, $username, $avatar) {
    $id       = mysqli_real_escape_string($conn, $id);
    $username = mysqli_real_escape_string($conn, $username);
    $avatar   = mysqli_real_escape_string($conn, $avatar);

    $sql = "UPDATE `users` SET `username`='$username', `avatar`='$avatar' 
            WHERE id = '$id'";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    return $res;
}

function update_xp_points($conn, $id, $xp, $points) {
    $id     = mysqli_real_escape_string($conn, $id);
    $xp     = (int) $xp;
    $points = (int) $points;

    $sql = "UPDATE `users` 
            SET `xp` = xp + $xp, `points` = points + $points 
            WHERE id = '$id'";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    return $res;
}

function update_streak($conn, $id) {
    $user = select_user($conn, $id);
    if (!$user) return false;

    $today     = date('Y-m-d');
    $lastRead  = $user['last_read'];
    $streak    = (int) $user['streak'];
    $streakMax = (int) $user['streak_max'];

    // Déjà lu aujourd'hui
    if ($lastRead === $today) return true;

    // Lu hier → on continue
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($lastRead === $yesterday) {
        $streak++;
    } else {
        $streak = 1; // streak cassé
    }

    $streakMax = max($streak, $streakMax);
    $id        = mysqli_real_escape_string($conn, $id);

    $sql = "UPDATE `users` 
            SET `streak`=$streak, `streak_max`=$streakMax, `last_read`='$today' 
            WHERE id = '$id'";

    global $debug;
    if ($debug) echo $sql;

    return mysqli_query($conn, $sql);
}

function delete_user($conn, $id) {
    $id  = mysqli_real_escape_string($conn, $id);
    $sql = "DELETE FROM `users` WHERE id = '$id'";

    global $debug;
    if ($debug) echo $sql;

    $res = mysqli_query($conn, $sql);
    return $res;
}

/**
 * Connexion : vérifie email + mot de passe
 */
function login_user($conn, $email, $password) {
    $user = select_user_by_email($conn, $email);
    if (!$user) return false;
    if (!password_verify($password, $user['password'])) return false;
    return $user;
}
?>