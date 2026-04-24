<?php
// On démarre la session pour récupérer les infos de l'utilisateur connecté
session_start();
// On inclut les fichiers nécessaires (Base de données et CRUD)
include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
include '../../php/mvc/mvc_reading/crud_reading.php';
include '../../php/mvc/mvc_badges/crud_badges.php';

// On inclut le fichier CRUD des comics pour les infos de couverture
include '../../php/mvc/mvc_comics/crud_comics.php';
// Sécurité : Si l'utilisateur n'est pas connecté (pas de session), on le redirige
if (isset($_SESSION['user']) == false) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

// On récupère le tableau de l'utilisateur depuis la session
$user = $_SESSION['user'];
// Pour être sûr d'avoir les "vraies" stats à jour (XP, Niveau), on refait un SELECT
// C'est important car les données en session peuvent ne pas être à jour si l'XP a changé
$user = select_user($conn, $user['id']);
$user_id = $user['id'];

// --- CALCULS DES VALEURS RÉELLES ---

// 1. Récupération du Niveau et de l'XP
if (isset($user['level'])) {
    $niveau = $user['level'];
} else {
    $niveau = 1;
}

if (isset($user['xp'])) {
    $xp_totale = $user['xp'];
} else {
    $xp_totale = 0;
}

// 2. Calcul de la barre d'expérience (Progression)
$xp_par_palier = 1000;
$xp_seuil_actuel = ($niveau - 1) * $xp_par_palier;
$xp_dans_niveau = $xp_totale - $xp_seuil_actuel;
$pourcentage_xp = ($xp_dans_niveau / $xp_par_palier) * 100;

// On s'assure que le pourcentage est compris entre 0 et 100
if ($pourcentage_xp > 100) {
    $pourcentage_xp = 100;
}
if ($pourcentage_xp < 0) {
    $pourcentage_xp = 0;
}

// 3. Récupération du Streak (Série de lecture)
if (isset($user['streak'])) {
    $streak = $user['streak'];
} else {
    $streak = 0;
}

// 4. Récupération des lectures en cours (Historique réel)
// On utilise la fonction du CRUD reading
$lectures_en_cours = list_reading($conn, $user_id);

// 5. Récupération du nombre de badges débloqués
$mes_badges = list_user_badges($conn, $user_id);
$nb_badges = count($mes_badges);

// 6. Récupération du nombre total de comics lus (terminés)
$lectures_terminees = list_completed($conn, $user_id);
$nb_comics_lus = count($lectures_terminees);

// Nombre total en bibliothèque
$nb_total_bibli = $nb_comics_lus + count($lectures_en_cours);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body> 
    <header id="hdr">
        <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
        <nav>
            <a href="../../../index.php#features">Fonctionnalités</a>
            <a href="../../../index.php#how">Comment ça marche</a>
            <a href="../../../leaderboard.php">Classement</a>
        </nav>
        <div class="h-btns"> 
            <?php 
            // On vérifie si l'utilisateur est connecté pour afficher les bons boutons
            if (isset($_SESSION['user']) == true) { 
            ?>
                <!-- Si l'utilisateur est connecté, on affiche son mini-profil et un menu déroulant -->
                <div class="user-dropdown-wrapper">
                    <a href="#" class="user-trigger">
                        <div class="profile-avatar-mini">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?> <!-- Initiales du pseudo -->
                        </div>
                        <span class="username-display"><?php echo htmlspecialchars($user['username']); ?></span>
                    </a>
                    <div class="user-dropdown-menu">
                        <a href="profil.php">Mon Profil</a>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="badge_users.php">Mes Badges</a>
                        <a href="#">Paramètres</a>
                        <hr>
                        <a href="../pages_connexion/logout.php">Déconnexion</a>
                    </div>
                </div>
            <?php 
            } else { 
            ?>
                <!-- Si l'utilisateur n'est PAS connecté, on affiche les boutons de connexion/inscription -->
                <a href="../pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
                <a href="../pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
            <?php } ?>
            <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </header>

    <!-- MENU MOBILE -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="../../../index.php#features" onclick="closeMenu()">Fonctionnalités</a>
        <a href="../../../index.php#how" onclick="closeMenu()">Comment ça marche</a>
        <a href="../../../leaderboard.php">Classement</a>
        <hr style="border-color:var(--border);border-width:0.5px;"/>
        <?php if (isset($_SESSION['user']) == true) { ?>
            <a href="profil.php" class="mm-ghost">Mon Profil</a>
            <a href="dashboard.php" class="mm-ghost">Dashboard</a>
            <a href="badge_users.php" class="mm-ghost">Mes Badges</a>
            <a href="#" class="mm-ghost">Paramètres</a>
            <hr style="border-color:var(--border);border-width:0.5px;"/>
            <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
        <?php } else { ?>
            <a href="../pages_connexion/login.php" class="mm-ghost">Connexion</a>
            <a href="../pages_connexion/register.php" class="mm-red">S'inscrire →</a>
        <?php } ?>
    </div>

    <main class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-profile">
                <div class="profile-avatar"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
                <div class="profile-details">
                    <span class="profile-name"><?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="profile-level">Lvl. <?php echo $niveau; ?></span>
                    <div class="xp-bar-wrap">
                        <div class="xp-bar" style="--xp-w: <?php echo $pourcentage_xp; ?>%"></div>
                    </div>
                    <span class="xp-next-level"><?php echo $xp_dans_niveau; ?> / <?php echo $xp_par_palier; ?> XP → Lvl. <?php echo ($niveau + 1); ?></span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="active">
                    <span class="icon">🏠</span> Accueil
                </a>
                <a href="#">
                    <span class="icon">📚</span> Ma Bibliothèque
                </a>
                <a href="profil.php">
                    <span class="icon">👤</span> Mon Profil
                </a>
                <a href="badge_users.php">
                    <span class="icon">🏅</span> Mes Badges
                </a>
                <a href="../../../leaderboard.php">
                    <span class="icon">🏆</span> Classement
                </a>
                <a href="#">
                    <span class="icon">⚙️</span> Paramètres
                </a>
                <a href="../pages_connexion/logout.php" class="logout-link">
                    <span class="icon">🚪</span> Déconnexion
                </a>
            </nav>
        </aside>

        <!-- Contenu principal -->
        <div class="dashboard-content">
            <section class="section">
                <p class="s-eyebrow reveal">Bienvenue</p>
                <h1 class="s-title reveal">Votre Tableau de Bord.</h1>

                <div class="stats-grid">
                    <div class="stat-card reveal">
                        <span class="stat-icon">✨</span>
                        <span class="stat-value"><span class="ctr" data-type="xp" data-target="<?php echo $xp_totale; ?>">0</span> XP</span>
                        <span class="stat-label">Total XP</span>
                    </div>
                    <div class="stat-card reveal">
                        <span class="stat-icon">📖</span>
                        <span class="stat-value"><span class="ctr" data-type="read" data-target="<?php echo $nb_comics_lus; ?>">0</span></span>
                        <span class="stat-label">Comics lus</span>
                    </div>
                    <div class="stat-card reveal">
                        <span class="stat-icon">🔥</span>
                        <span class="stat-value"><span class="ctr" data-type="streak" data-target="<?php echo $streak; ?>">0</span> jours</span>
                        <span class="stat-label">Streak actuel</span>
                    </div>
                    <div class="stat-card reveal">
                        <span class="stat-icon">🏅</span>
                        <span class="stat-value"><span class="ctr" data-type="badges" data-target="<?php echo $nb_badges; ?>">0</span></span>
                        <span class="stat-label">Badges débloqués</span>
                    </div>
                </div>
            </section>

            <hr class="sep reveal"/>

            <section class="section">
                <p class="s-eyebrow reveal">Activité</p>
                <h2 class="s-title reveal">Vos Derniers Comics Lus.</h2>

                <div class="last-read-grid">
                    <?php if (count($lectures_en_cours) > 0) { ?>
                        <?php foreach ($lectures_en_cours as $index => $lecture) { 
                            // On calcule le pourcentage de lecture
                            $progression = ($lecture['current_page'] / $lecture['total_pages']) * 100;
                        ?>
                            <div class="read-item reveal">
                                <img src="../../assets/img/<?php echo $lecture['cover']; ?>" alt="<?php echo $lecture['title']; ?>" class="read-cover">
                                <div class="read-info">
                                    <span class="read-title"><?php echo htmlspecialchars($lecture['title']); ?></span> <!-- Titre du comic -->
                                    <div class="read-pb-wrap">
                                        <div class="read-pb" style="--pb-w: <?php echo $progression; ?>%"></div>
                                    </div>
                                    <span class="read-progress">Page <?php echo $lecture['current_page']; ?> / <?php echo $lecture['total_pages']; ?></span>
                                    <span class="read-date">Dernière lecture : <?php echo date('d/m/Y', strtotime($lecture['last_read_at'])); ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="stat-card" style="grid-column: 1 / -1; padding: 40px;">
                            <p style="color: var(--muted);">Vous n'avez aucune lecture en cours pour le moment.</p>
                            <a href="#" class="btn btn-red" style="margin-top: 15px;">Uploader votre scan →</a>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <hr class="sep reveal"/>

            <section class="section">
                <p class="s-eyebrow reveal">Organisation</p>
                <h2 class="s-title reveal">Votre Bibliothèque.</h2>

                <div class="library-summary">
                    <div class="summary-card reveal">
                        <span class="summary-icon">📚</span>
                        <span class="summary-value"><span class="ctr" data-target="<?php echo $nb_total_bibli; ?>">0</span></span>
                        <span class="summary-label">Comics au total</span>
                    </div>
                    <div class="summary-card reveal">
                        <span class="summary-icon">🗂️</span>
                        <span class="summary-value"><span class="ctr" data-target="<?php echo count($lectures_en_cours); ?>">0</span></span>
                        <span class="summary-label">Séries différentes</span>
                    </div>
                    <div class="summary-card cta-upload reveal">
                        <span class="summary-icon">📤</span>
                        <span class="summary-value">Ajouter des comics</span>
                        <span class="summary-label">Importez vos scans</span>
                        <a href="#" class="btn btn-red">Uploader →</a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <a class="logo" href="#">Panel<em>Vault</em></a>
        <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
    </footer>

    <script src="../../js/javascript-index.js"></script>
    <script src="../../js/js-profil.js"></script> <!-- Inclusion du nouveau JS -->
</body>
</html>
