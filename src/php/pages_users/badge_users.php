<?php
session_start();

include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
include '../../php/mvc/mvc_badges/crud_badges.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

$user_session = $_SESSION['user'];
$user_id      = (int) $user_session['id'];
$user         = select_user($conn, $user_id);

// --- NIVEAU & XP (sidebar) ---
$niveau            = isset($user['level']) ? (int) $user['level'] : 1;
$xp_totale         = isset($user['xp'])    ? (int) $user['xp']    : 0;
$xp_par_palier     = 1000;
$xp_seuil_actuel   = ($niveau - 1) * $xp_par_palier;
$xp_dans_le_niveau = $xp_totale - $xp_seuil_actuel;
$pourcentage       = max(0, min(100, $xp_dans_le_niveau / $xp_par_palier * 100));

// --- BADGES ---
$badges_possedes = list_user_badges($conn, $user_id);
$tous_badges     = list_badges($conn);
$nb_badges       = count($badges_possedes);
$nb_total_badges = count($tous_badges);
$nb_locked       = $nb_total_badges - $nb_badges;
$progress_pct    = $nb_total_badges > 0 ? round(($nb_badges / $nb_total_badges) * 100) : 0;

// Map id → earned_at pour lookup O(1)
$unlocked_ids  = [];
$earned_at_map = [];
foreach ($badges_possedes as $b) {
    $unlocked_ids[]               = (int) $b['id'];
    $earned_at_map[(int)$b['id']] = $b['earned_at'];
}

// Badge featured = le plus récemment obtenu (list_user_badges trie DESC earned_at)
$featured_badge = $nb_badges > 0 ? $badges_possedes[0] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Badges — PanelVault</title>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="../../css/badges.css">
</head>
<body>

  <header id="hdr" class="scrolled">
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <nav>
      <a href="../../../index.php#features">Fonctionnalités</a>
      <a href="../../../index.php#how">Comment ça marche</a>
      <a href="../../../leaderboard.php">Classement</a>
    </nav>
    <div class="h-btns">
      <?php if (isset($_SESSION['user'])): ?>
        <div class="user-dropdown-wrapper">
          <a href="#" class="user-trigger">
            <div class="profile-avatar-mini"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
            <span class="username-display"><?php echo htmlspecialchars($user['username']); ?></span>
          </a>
          <div class="user-dropdown-menu">
            <a href="profil.php">Mon Profil</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="#">Paramètres</a>
            <hr>
            <a href="../pages_connexion/logout.php">Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="../pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
        <a href="../pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
      <?php endif; ?>
      <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </header>

  <div class="mobile-menu" id="mobileMenu">
    <a href="../../../index.php#features">Fonctionnalités</a>
    <a href="../../../index.php#how">Comment ça marche</a>
    <a href="../../../leaderboard.php">Classement</a>
    <hr style="border-color:var(--border);border-width:0.5px;"/>
    <?php if (isset($_SESSION['user'])): ?>
      <a href="profil.php" class="mm-ghost">Mon Profil</a>
      <a href="dashboard.php" class="mm-ghost">Dashboard</a>
      <a href="#" class="mm-ghost">Paramètres</a>
      <hr style="border-color:var(--border);border-width:0.5px;"/>
      <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
    <?php else: ?>
      <a href="../pages_connexion/login.php" class="mm-ghost">Connexion</a>
      <a href="../pages_connexion/register.php" class="mm-red">S'inscrire →</a>
    <?php endif; ?>
  </div>

  <main class="dashboard-layout">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="dashboard-sidebar">
      <div class="user-profile">
        <div class="profile-avatar"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
        <div class="profile-details">
          <span class="profile-name"><?php echo htmlspecialchars($user['username']); ?></span>
          <span class="profile-level">Lvl. <?php echo $niveau; ?></span>
          <div class="xp-bar-wrap">
            <div class="xp-bar" style="--xp-w: <?php echo $pourcentage; ?>%"></div>
          </div>
          <span class="xp-next-level"><?php echo $xp_dans_le_niveau; ?> / <?php echo $xp_par_palier; ?> XP → Lvl. <?php echo ($niveau + 1); ?></span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <a href="dashboard.php"><span class="icon">🏠</span> Accueil</a>
        <a href="#"><span class="icon">📚</span> Ma Bibliothèque</a>
        <a href="profil.php"><span class="icon">👤</span> Mon Profil</a>
        <a href="badge_users.php" class="active"><span class="icon">🏅</span> Mes Badges</a>
        <a href="../../../leaderboard.php"><span class="icon">🏆</span> Classement</a>
        <a href="#"><span class="icon">⚙️</span> Paramètres</a>
        <a href="../pages_connexion/logout.php" class="logout-link"><span class="icon">🚪</span> Déconnexion</a>
      </nav>
    </aside>

    <!-- ═══ CONTENU PRINCIPAL ═══ -->
    <div class="dashboard-content">

      <!-- ═══ PAGE HEADER ═══ -->
      <div class="badges-page-header">
        <div class="badges-header-text">
          <p class="s-eyebrow">Récompenses</p>
          <h1 class="badges-main-title">Mes Badges.</h1>
          <p class="badges-subtitle">
            <?php if ($nb_badges > 0): ?>
              Tu as débloqué <strong><?php echo $nb_badges; ?></strong> badge<?php echo $nb_badges > 1 ? 's' : ''; ?> sur <strong><?php echo $nb_total_badges; ?></strong>.
            <?php else: ?>
              Aucun badge débloqué pour l'instant — commence à lire pour en gagner.
            <?php endif; ?>
          </p>
        </div>

        <!-- Barre de progression globale -->
        <div class="badges-global-progress">
          <div class="bgp-labels">
            <span><?php echo $nb_badges; ?> / <?php echo $nb_total_badges; ?> débloqués</span>
            <span class="bgp-pct"><?php echo $progress_pct; ?>%</span>
          </div>
          <div class="bgp-bar-wrap">
            <div class="bgp-bar" style="--bgp-w: <?php echo $progress_pct; ?>%"></div>
          </div>
          <div class="bgp-sublabels">
            <span><?php echo $nb_locked; ?> badge<?php echo $nb_locked > 1 ? 's' : ''; ?> restant<?php echo $nb_locked > 1 ? 's' : ''; ?></span>
            <?php if ($nb_badges >= $nb_total_badges && $nb_total_badges > 0): ?>
              <span class="bgp-complete">Collection complète ✓</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ═══ FEATURED (dernier badge obtenu) ═══ -->
      <?php if ($featured_badge): ?>
      <div class="badges-featured-wrap">
        <div class="badge-featured reveal">
          <div class="bf-glow"></div>
          <div class="bf-icon"><?php echo $featured_badge['icon']; ?></div>
          <div class="bf-info">
            <p class="bf-eyebrow">Dernier badge obtenu</p>
            <h2 class="bf-name"><?php echo htmlspecialchars($featured_badge['name']); ?></h2>
            <p class="bf-desc"><?php echo htmlspecialchars($featured_badge['description']); ?></p>
            <p class="bf-date">Obtenu le <?php echo date('d M. Y', strtotime($featured_badge['earned_at'])); ?></p>
          </div>
          <div class="bf-shine"></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ═══ FILTRES + GRILLE ═══ -->
      <section class="section badges-section">

        <!-- Filtres -->
        <div class="badge-filters reveal">
          <button class="badge-filter active" data-filter="all">
            Tous <span class="bf-count"><?php echo $nb_total_badges; ?></span>
          </button>
          <button class="badge-filter" data-filter="unlocked">
            Débloqués <span class="bf-count"><?php echo $nb_badges; ?></span>
          </button>
          <button class="badge-filter" data-filter="locked">
            Verrouillés <span class="bf-count"><?php echo $nb_locked; ?></span>
          </button>
        </div>

        <!-- Grille de badges -->
        <div class="badges-v2-grid" id="badgesGrid">
          <?php if ($nb_total_badges > 0): ?>
            <?php foreach ($tous_badges as $badge):
              $is_unlocked = in_array((int)$badge['id'], $unlocked_ids);
              $earned      = $is_unlocked ? ($earned_at_map[(int)$badge['id']] ?? null) : null;
            ?>
              <div class="badge-v2 <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?> reveal"
                   data-status="<?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">

                <?php if ($is_unlocked): ?>
                  <div class="bv2-shine"></div>
                <?php else: ?>
                  <div class="bv2-lock-overlay">🔒</div>
                <?php endif; ?>

                <div class="bv2-icon-wrap">
                  <span class="bv2-icon"><?php echo $badge['icon']; ?></span>
                </div>

                <div class="bv2-body">
                  <span class="bv2-name"><?php echo htmlspecialchars($badge['name']); ?></span>
                  <span class="bv2-desc"><?php echo htmlspecialchars($badge['description']); ?></span>
                  <?php if ($is_unlocked && $earned): ?>
                    <span class="bv2-date">✓ <?php echo date('d M. Y', strtotime($earned)); ?></span>
                  <?php else: ?>
                    <span class="bv2-locked-label">Non débloqué</span>
                  <?php endif; ?>
                </div>

              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="badges-empty">Aucun badge disponible pour le moment.</p>
          <?php endif; ?>
        </div>

        <p class="badges-filter-empty" id="filterEmpty" style="display:none;">
          Aucun badge dans cette catégorie.
        </p>

      </section>

    </div>
  </main>

  <footer>
    <a class="logo" href="#">Panel<em>Vault</em></a>
    <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
  </footer>

  <script>
  document.addEventListener('DOMContentLoaded', () => {

    // Dropdown header
    const trigger = document.querySelector('.user-trigger');
    const menu    = document.querySelector('.user-dropdown-menu');
    if (trigger && menu) {
      let t;
      trigger.addEventListener('mouseenter', () => { clearTimeout(t); menu.style.display = 'flex'; });
      trigger.addEventListener('mouseleave', () => { t = setTimeout(() => menu.style.display = 'none', 200); });
      menu.addEventListener('mouseenter',    () => clearTimeout(t));
      menu.addEventListener('mouseleave',    () => { t = setTimeout(() => menu.style.display = 'none', 200); });
    }

    // Reveal scroll
    const ro = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); ro.unobserve(e.target); } });
    }, { threshold: 0.06 });
    document.querySelectorAll('.reveal').forEach(el => ro.observe(el));

    // Filtres
    const filters  = document.querySelectorAll('.badge-filter');
    const cards    = document.querySelectorAll('.badge-v2');
    const emptyMsg = document.getElementById('filterEmpty');

    filters.forEach(btn => {
      btn.addEventListener('click', () => {
        filters.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        let visible = 0;
        cards.forEach(card => {
          const show = f === 'all' || card.dataset.status === f;
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });
        emptyMsg.style.display = visible === 0 ? 'block' : 'none';
      });
    });

  });
  </script>

</body>
</html>
