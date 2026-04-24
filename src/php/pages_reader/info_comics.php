<?php
// ════════════════════════════════════════════════
//  INFO COMICS — Fiche détail d'un comic
//  PanelVault
// ════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

include '../db_connect.php';
include '../mvc/mvc_users/crud_users.php';
include '../mvc/mvc_comics/crud_comics.php';
include '../mvc/mvc_reading/crud_reading.php';
include '../mvc/mvc_badges/crud_badges.php';

// Validation de l'ID passé en GET — on caste en entier pour éviter les injections
$comic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($comic_id <= 0) {
    // ID invalide → retour bibliothèque
    header("Location: bibliotheque.php");
    exit();
}

// Données utilisateur
$user    = select_user($conn, $_SESSION['user']['id']);
$user_id = $user['id'];

// Données sidebar
$niveau           = $user['level'] ?? 1;
$xp_totale        = $user['xp']    ?? 0;
$xp_par_palier    = 1000;
$xp_dans_niveau   = $xp_totale - (($niveau - 1) * $xp_par_palier);
$pourcentage_xp   = max(0, min(100, ($xp_dans_niveau / $xp_par_palier) * 100));

// Données du comic
$comic = select_comic($conn, $comic_id);

if (!$comic) {
    // Comic inexistant → retour bibliothèque
    header("Location: bibliotheque.php");
    exit();
}

// Progression de lecture de l'utilisateur pour CE comic
$progress = select_progress($conn, $user_id, $comic_id);

if ($progress) {
    $current_page = (int)$progress['current_page'];
    $completed    = (int)$progress['completed'];
    $last_read    = $progress['last_read_at'] ?? null;
    $pct          = ($comic['total_pages'] > 0)
        ? round(($current_page / $comic['total_pages']) * 100)
        : 0;
} else {
    // L'utilisateur n'a jamais ouvert ce comic
    $current_page = 0;
    $completed    = 0;
    $last_read    = null;
    $pct          = 0;
}

$title       = htmlspecialchars($comic['title']);
$publisher   = htmlspecialchars($comic['publisher'] ?? 'Inconnu');
$uploader    = htmlspecialchars($comic['uploader'] ?? 'Inconnu');
$total_pages = (int)$comic['total_pages'];
$cover       = $comic['cover'] ?? '';
$cover_url   = '../../assets/img/' . $cover;
$upload_date = isset($comic['uploaded_at'])
    ? date('d/m/Y', strtotime($comic['uploaded_at']))
    : '—';

// Label du bouton principal selon l'état de lecture
if ($completed) {
    $btn_label = '✓ Relire depuis le début';
    $btn_page  = 1;
} elseif ($current_page > 0) {
    $btn_label = '▶ Continuer — page ' . $current_page;
    $btn_page  = $current_page;
} else {
    $btn_label = '▶ Commencer la lecture';
    $btn_page  = 1;
}

$status_label = $completed ? 'Terminé' : ($current_page > 0 ? 'En cours' : 'Non commencé');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> — PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/library.css">
</head>
<body>

<div class="scroll-progress" id="scrollProg"></div>

<!-- ══ HEADER ══ -->
<header id="hdr">
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <nav>
        <a href="../../../index.php#features">Fonctionnalités</a>
        <a href="../../../leaderboard.php">Classement</a>
    </nav>
    <div class="h-btns">
        <div class="user-dropdown-wrapper">
            <a href="#" class="user-trigger">
                <div class="profile-avatar-mini">
                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                </div>
                <span class="username-display"><?php echo htmlspecialchars($user['username']); ?></span>
            </a>
            <div class="user-dropdown-menu">
                <a href="../pages_users/profil.php">Mon Profil</a>
                <a href="../pages_users/dashboard.php">Dashboard</a>
                <a href="bibliotheque.php">Ma Bibliothèque</a>
                <a href="../pages_users/badge_users.php">Mes Badges</a>
                <hr>
                <a href="../pages_connexion/logout.php">Déconnexion</a>
            </div>
        </div>
        <button class="burger" id="burger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="mobile-menu" id="mobileMenu">
    <a href="../../../leaderboard.php" onclick="closeMenu()">Classement</a>
    <hr style="border-color:var(--border);border-width:0.5px"/>
    <a href="bibliotheque.php" class="mm-ghost">Ma Bibliothèque</a>
    <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
</div>

<!-- ══ LAYOUT ══ -->
<main class="dashboard-layout">

    <!-- ── SIDEBAR ── -->
    <aside class="dashboard-sidebar">
        <div class="user-profile">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            </div>
            <div class="profile-details">
                <span class="profile-name"><?php echo htmlspecialchars($user['username']); ?></span>
                <span class="profile-level">Lvl. <?php echo $niveau; ?></span>
                <div class="xp-bar-wrap">
                    <div class="xp-bar" style="--xp-w: <?php echo $pourcentage_xp; ?>%"></div>
                </div>
                <span class="xp-next-level">
                    <?php echo $xp_dans_niveau; ?> / <?php echo $xp_par_palier; ?> XP → Lvl. <?php echo $niveau + 1; ?>
                </span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="../pages_users/dashboard.php">
                <span class="icon">🏠</span> Accueil
            </a>
            <a href="bibliotheque.php" class="active">
                <span class="icon">📚</span> Ma Bibliothèque
            </a>
            <a href="upload_comics.php">
                <span class="icon">📤</span> Uploader
            </a>
            <a href="../pages_users/profil.php">
                <span class="icon">👤</span> Mon Profil
            </a>
            <a href="../pages_users/badge_users.php">
                <span class="icon">🏅</span> Mes Badges
            </a>
            <a href="../../../leaderboard.php">
                <span class="icon">🏆</span> Classement
            </a>
            <a href="../pages_connexion/logout.php" class="logout-link">
                <span class="icon">🚪</span> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- ── CONTENU ── -->
    <div class="dashboard-content">
        <section class="section">

            <!-- Lien retour discret -->
            <a href="bibliotheque.php" class="back-link reveal">
                ← Retour à la bibliothèque
            </a>

            <!-- HERO: cover + infos côte à côte -->
            <div class="info-hero reveal">

                <!-- Background flou décoratif (image de la cover floutée) -->
                <?php if ($cover): ?>
                    <div class="info-hero-bg" style="background-image:url('<?php echo $cover_url; ?>')"></div>
                <?php endif; ?>

                <!-- Colonne gauche: grande cover -->
                <div class="info-cover-col">
                    <?php if ($cover): ?>
                        <img
                            src="<?php echo $cover_url; ?>"
                            alt="<?php echo $title; ?>"
                            class="info-cover"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                        >
                        <div class="info-cover-placeholder" style="display:none">📖</div>
                    <?php else: ?>
                        <div class="info-cover-placeholder">📖</div>
                    <?php endif; ?>
                </div>

                <!-- Colonne droite: toutes les infos -->
                <div class="info-content-col">

                    <!-- Tag éditeur -->
                    <span class="info-publisher-tag"><?php echo $publisher; ?></span>

                    <!-- Titre principal -->
                    <h1 class="info-title"><?php echo $title; ?></h1>

                    <!-- Métadonnées -->
                    <div class="info-meta">
                        <div class="info-meta-row">
                            <span class="info-meta-icon">📄</span>
                            <strong><?php echo $total_pages; ?></strong>&nbsp;pages au total
                        </div>
                        <div class="info-meta-row">
                            <span class="info-meta-icon">👤</span>
                            Uploadé par&nbsp;<strong><?php echo $uploader; ?></strong>
                        </div>
                        <div class="info-meta-row">
                            <span class="info-meta-icon">📅</span>
                            Ajouté le&nbsp;<strong><?php echo $upload_date; ?></strong>
                        </div>
                        <div class="info-meta-row">
                            <span class="info-meta-icon"><?php echo $completed ? '✅' : ($current_page > 0 ? '🔖' : '⭕'); ?></span>
                            Statut :&nbsp;<strong><?php echo $status_label; ?></strong>
                        </div>
                        <?php if ($last_read): ?>
                            <div class="info-meta-row">
                                <span class="info-meta-icon">🕐</span>
                                Dernière lecture :&nbsp;<strong><?php echo date('d/m/Y', strtotime($last_read)); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Section progression -->
                    <div class="info-progress-section">
                        <div class="info-progress-label">
                            <span>
                                <?php if ($completed): ?>
                                    Lecture terminée !
                                <?php elseif ($current_page > 0): ?>
                                    Page <strong><?php echo $current_page; ?></strong> sur <?php echo $total_pages; ?>
                                <?php else: ?>
                                    Pas encore commencé
                                <?php endif; ?>
                            </span>
                            <strong><?php echo $pct; ?>%</strong>
                        </div>
                        <div class="info-progress-bar-wrap">
                            <!-- La width est animée en CSS (transition) — on la fixe directement ici -->
                            <div
                                class="info-progress-bar <?php echo $completed ? 'done' : ''; ?>"
                                style="width: <?php echo $pct; ?>%"
                            ></div>
                        </div>
                        <span class="info-progress-note">
                            <?php if ($completed): ?>
                                ✓ Vous avez lu ce comic en entier. Bravo !
                            <?php elseif ($current_page > 0): ?>
                                Continuez où vous en étiez — page <?php echo $current_page; ?>.
                            <?php else: ?>
                                Commencez ce comic et gagnez de l'XP !
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="info-actions">
                        <!-- Bouton principal: Lire / Continuer / Relire -->
                        <a
                            href="read_comics.php?id=<?php echo $comic_id; ?>&page=<?php echo $btn_page; ?>"
                            class="btn btn-red"
                        >
                            <?php echo $btn_label; ?>
                        </a>
                        <!-- Relire depuis le début (si déjà commencé) -->
                        <?php if ($current_page > 0 && !$completed): ?>
                            <a href="read_comics.php?id=<?php echo $comic_id; ?>&page=1" class="btn btn-ghost">
                                ↺ Depuis le début
                            </a>
                        <?php endif; ?>
                    </div>

                </div><!-- /info-content-col -->
            </div><!-- /info-hero -->

            <hr class="sep reveal" style="margin: 8px 0 32px">

            <!-- GRILLE DE STATS DÉTAILLÉES -->
            <div class="info-details-grid reveal">
                <div class="info-detail-card">
                    <span class="info-detail-icon">📄</span>
                    <span class="info-detail-value"><?php echo $total_pages; ?></span>
                    <span class="info-detail-label">Pages totales</span>
                </div>
                <div class="info-detail-card">
                    <span class="info-detail-icon">🔖</span>
                    <span class="info-detail-value"><?php echo $current_page; ?></span>
                    <span class="info-detail-label">Page actuelle</span>
                </div>
                <div class="info-detail-card">
                    <span class="info-detail-icon"><?php echo $completed ? '✅' : '⏳'; ?></span>
                    <span class="info-detail-value"><?php echo $pct; ?><em style="font-size:0.6em;color:var(--muted)">%</em></span>
                    <span class="info-detail-label">Progression</span>
                </div>
            </div>

        </section>
    </div><!-- /dashboard-content -->

</main>

<footer>
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
</footer>

<!-- Réutilise js-library pour le dropdown/burger/reveal -->
<script src="../../js/js-library.js"></script>

</body>
</html>
