<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — PanelVault</title>
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
            <div class="auth-ghost-num">02</div>
            <div class="auth-eyebrow">Rejoins-nous</div>
            <h1 class="auth-title">Inscription</h1>
            <form class="auth-form" action="#" method="POST">
                <div class="field">
                    <label for="username">Pseudo</label>
                    <input type="text" id="username" name="username" placeholder="Choisis ton pseudo" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Ton adresse email" required>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="Crée ton mot de passe" required>
                </div>
                <button class="btn btn-red btn-full" type="submit">Créer mon compte →</button>
            </form>
            <hr class="auth-divider">
            <div class="auth-link">
                <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script src="../../js/auth.js"></script>
    <script src="../../js/javascript-index.js"></script>
</body>
</html>
