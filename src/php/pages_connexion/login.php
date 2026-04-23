<?php
session_start();
include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
$error ="";
if(isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = login_user($conn, $email, $password);
    if($user) {
        $_SESSION['user'] = $user;
        header("Location: ../pages_users/profil.php");
        exit();
    }else{
        $error = "Connexion Impossible";
    }
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body>
    <header id="hdr">
        <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
        <div class="h-btns">
            <a href="../../../index.php" class="btn btn-ghost">← Retour à l'accueil</a>
        </div>
    </header>

    <div class="auth-wrap">
        <div class="auth-bg">
            <div class="auth-bg-panel ab1"></div>
            <div class="auth-bg-panel ab2"></div>
            <div class="auth-bg-panel ab3"></div>
        </div>
        <div class="auth-noise"></div>
        <div class="auth-overlay"></div>
        <div class="auth-card">
            <div class="auth-ghost-num">01</div>
            <div class="auth-eyebrow">Ravi de te revoir</div>
            <h1 class="auth-title">Connexion</h1>
            <?php if ($error): ?>
                <div style="color: var(--red); background: rgba(232, 50, 47, 0.1); padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; border: 1px solid var(--red);">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" action="#" method="POST">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Ton adresse email" required>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Ton mot de passe" required>
                </div>
                <button class="btn btn-red btn-full" type="submit">Se connecter →</button>
            </form>
            <hr class="auth-divider">
            <div class="auth-link">
                <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
            </div>
        </div>
    </div>

    <script src="../../js/auth.js"></script>
    <script src="../../js/javascript-index.js"></script>
</body>
</html>
