<?php
// On démarre la session pour récupérer les données de l'utilisateur
session_start();

// On inclut les fichiers de connexion et les fonctions CRUD (style L1 : include)
include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
include '../../php/mvc/mvc_reading/crud_reading.php';
include '../../php/mvc/mvc_badges/crud_badges.php';

// Vérification de sécurité : si on n'est pas connecté, on redirige vers le login
if (isset($_SESSION['user']) == false) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

// On récupère l'utilisateur en session
$user_session = $_SESSION['user'];
$user_id = $user_session['id'];

// Pour être sûr d'avoir les "vraies" stats à jour (XP, Niveau), on refait un SELECT
$user = select_user($conn, $user_id);

// --- CALCULS DES STATISTIQUES (Version explicite L1) ---

// 1. Niveau et XP
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

// 2. Barre de progression (1000 XP par palier)
$xp_par_palier = 1000;
$xp_seuil_actuel = ($niveau - 1) * $xp_par_palier;
$xp_dans_le_niveau = $xp_totale - $xp_seuil_actuel;

// Calcul du pourcentage pour le CSS (--xp-w) et l'anneau SVG
$pourcentage = ($xp_dans_le_niveau / $xp_par_palier) * 100;
if ($pourcentage > 100) {
    $pourcentage = 100;
}
if ($pourcentage < 0) {
    $pourcentage = 0;
}

// 3. Date d'inscription
$date_membre = "Inconnu";
if (isset($user['created_at'])) {
    $date_objet = new DateTime($user['created_at']);
    $date_membre = date_format($date_objet, 'd M. Y');
}

// 4. Données réelles des autres tables
$badges_possedes = list_user_badges($conn, $user_id);
$nb_badges = count($badges_possedes);

$comics_en_cours = list_reading($conn, $user_id);
$comics_finis = list_completed($conn, $user_id);
$nb_lus = count($comics_finis);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil — PanelVault</title>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../../css/style.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="../../css/profile.css">
  <!-- On inclut le nouveau fichier JS pour le profil -->
  <script src="../../js/js-profil.js" defer></script>
</head>
<body>

  <header id="hdr">
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <nav>
      <a href="../../../index.php#features">Fonctionnalités</a> <!-- Lien vers l'accueil -->
      <a href="../../../index.php#how">Comment ça marche</a> <!-- Lien vers l'accueil -->
      <a href="../../../leaderboard.php">Classement</a> <!-- Lien vers le classement -->
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
                <a href="#">Paramètres</a>
                <hr>
                <a href="../pages_connexion/logout.php">Déconnexion</a>
            </div>
        </div>
      <?php 
      } else { 
      ?>
        <!-- Si l'utilisateur n'est PAS connecté, on affiche les boutons de connexion/inscription -->
        <!-- Si non connecté : Boutons classiques -->
        <a href="../pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
        <a href="../pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
      <?php } ?>
      <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </header>

  <main class="dashboard-layout">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="dashboard-sidebar">
      <div class="user-profile">
        <div class="profile-avatar"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
        <div class="profile-details">
          <span class="profile-name"><?php echo htmlspecialchars($user['username']); ?></span> <!-- Pseudo de l'utilisateur -->
          <span class="profile-level">Lvl. <?php echo $niveau; ?></span> <!-- Niveau de l'utilisateur -->
          <div class="xp-bar-wrap">
            <!-- La barre d'XP se remplit en fonction du pourcentage calculé -->
            <div class="xp-bar" style="--xp-w: <?php echo $pourcentage; ?>%"></div> 
          </div>
          <!-- Affichage de l'XP actuelle dans le niveau et l'XP nécessaire pour le prochain niveau -->
          <span class="xp-next-level"><?php echo $xp_dans_le_niveau; ?> / <?php echo $xp_par_palier; ?> XP → Lvl. <?php echo ($niveau + 1); ?></span> 
        </div>
      </div>
      <nav class="sidebar-nav">
        <a href="dashboard.php">
          <span class="icon">🏠</span> Accueil
        </a>
        <a href="#">
          <span class="icon">📚</span> Ma Bibliothèque <!-- Lien vers la bibliothèque -->
        </a>
        <a href="profil.php" class="active">
          <span class="icon">👤</span> Mon Profil <!-- Lien actif vers le profil -->
        </a> 
        <a href="#">
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

    <!-- ═══ CONTENU PRINCIPAL ═══ -->
    <div class="dashboard-content">

      <!-- BANNER -->
      <div class="profile-banner">
        <div class="profile-banner-covers">
          <div class="pbc" style="background-image:url('../../assets/img/Batman.jpg')"></div>
          <div class="pbc" style="background-image:url('../../assets/img/xmen.jpg')"></div>
          <div class="pbc" style="background-image:url('../../assets/img/ironman.jpg')"></div>
          <div class="pbc" style="background-image:url('../../assets/img/spiderman.jpg')"></div>
          <div class="pbc" style="background-image:url('../../assets/img/superman.jpg')"></div>
        </div>
        <div class="profile-banner-overlay"></div>
        <span class="profile-banner-ghost">03</span>
      </div>

      <!-- PROFILE HERO -->
      <div class="profile-hero">

        <!-- Avatar + ring -->
        <div class="profile-avatar-wrap">
          <div class="profile-avatar-lg"><?php echo strtoupper(substr($user['username'], 0, 2)); ?></div>
          <svg class="profile-ring" viewBox="0 0 136 136"> <!-- Anneau de progression autour de l'avatar -->
            <circle class="ring-bg" cx="68" cy="68" r="62"/>
            <circle class="ring-fg" cx="68" cy="68" r="62" data-progress="<?php echo ($pourcentage / 100); ?>"/> <!-- data-progress pour JS -->
          </svg>
          <span class="profile-level-badge">LVL <?php echo $niveau; ?></span>
        </div>

        <!-- Info -->
        <div class="profile-info">
          <h1 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h1> <!-- Pseudo de l'utilisateur -->
          <p class="profile-tagline">Lecteur passionné · Membre depuis le <?php echo $date_membre; ?></p> <!-- Date d'inscription -->
          <div class="profile-pills">
            <span class="profile-pill accent">🔥 <?php echo $streak_actuel; ?> jours de streak</span> <!-- Streak actuel -->
            <span class="profile-pill accent">⭐ <?php echo $top_classement_text; ?></span> <!-- Rang de l'utilisateur -->
            <span class="profile-pill">📖 <?php echo $nb_lus; ?> comics lus</span> <!-- Nombre de comics lus -->
            <span class="profile-pill">🏅 <?php echo $nb_badges; ?> badges</span> <!-- Nombre de badges débloqués -->
            <span class="profile-pill">⏱️ <?php echo $temps_lecture_heures; ?>h de lecture</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="profile-actions">
          <a href="#" class="btn btn-red">Modifier le profil →</a>
          <a href="#" class="btn btn-ghost">Partager</a>
        </div>

      </div>

      <!-- ═══ STATS ═══ -->
      <hr class="sep reveal"/>

      <section class="section">
        <p class="s-eyebrow reveal">Statistiques</p>
        <h2 class="s-title reveal">En Chiffres.</h2>

        <div class="profile-stats-grid">
          <div class="stat-card reveal">
            <span class="stat-icon">✨</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $xp_totale; ?>">0</span> XP</span> <!-- XP totale -->
            <span class="stat-label">Total XP</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">📖</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $nb_lus; ?>">0</span></span> <!-- Comics lus -->
            <span class="stat-label">Comics lus</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">🔥</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $streak_actuel; ?>">0</span> j.</span> <!-- Streak actuel -->
            <span class="stat-label">Streak actuel</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">🏅</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $nb_badges; ?>">0</span></span> <!-- Badges débloqués -->
            <span class="stat-label">Badges débloqués</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">⏱️</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $temps_lecture_heures; ?>">0</span> h</span> <!-- Temps de lecture -->
            <span class="stat-label">Temps de lecture</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">✅</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $nb_series_terminees; ?>">0</span></span> <!-- Séries terminées -->
            <span class="stat-label">Séries terminées</span>
          </div>
        </div>
      </section>

      <!-- ═══ PROGRESSION + STREAK ═══ -->
      <hr class="sep reveal"/>

      <section class="section">
        <p class="s-eyebrow reveal">Progression</p>
        <h2 class="s-title reveal">Niveau & Activité.</h2>

        <div class="progression-grid">

          <!-- Level card -->
          <div class="level-progress-card reveal">
            <div class="lp-header-text">
              <p class="lp-label">Expérience</p>
              <h3 class="lp-title">Progression de Niveau</h3>
            </div>

            <div class="lp-levels-row">
              <span class="lp-lvl current"><?php echo $niveau; ?></span>
              <span class="lp-lvl sep">/</span>
              <span class="lp-lvl next"><?php echo ($niveau + 1); ?> →</span>
            </div>

            <div class="lp-bar-wrap">
              <div class="lp-bar" style="--lp-w: <?php echo $pourcentage; ?>%"></div>
            </div>

            <div class="lp-xp-row">
              <span><strong><?php echo $xp_dans_le_niveau; ?> XP</strong> obtenus</span>
              <span>encore <strong><?php echo ($xp_par_palier - $xp_dans_le_niveau); ?> XP</strong></span>
            </div>

            <div class="lp-meta">
              <div class="lp-meta-row">📅 <span>Membre depuis <strong><?php echo $date_membre; ?></strong></span></div>
              <div class="lp-meta-row">🏆 <span><?php echo $top_classement_text; ?> cette semaine</span></div>
              <div class="lp-meta-row">🔥 <span>Meilleur streak : <strong><?php if(isset($user['streak_max'])) { echo $user['streak_max']; } else { echo 0; } ?> jours</strong></span></div>
            </div>
          </div>

          <!-- Streak calendar -->
          <div class="streak-wrap reveal">
            <div class="streak-header">
              <div class="streak-header-left">
                <p class="streak-label">Activité</p>
                <h3 class="streak-title">Calendrier de Lecture</h3>
              </div>
              <div class="streak-count">
                <span class="streak-count-num">🔥 <?php echo $streak_actuel; ?></span> <!-- Streak actuel -->
                <span class="streak-count-label">jours consécutifs</span>
              </div>
            </div>

            <div class="streak-grid" id="streakGrid">
              <!-- Générée par JS -->
            </div>

            <div class="streak-footer">
              <span>13 dernières semaines</span>
              <div class="streak-legend-scale">
                <span style="margin-right:4px">Moins</span>
                <div class="sls sls-0"></div>
                <div class="sls sls-1"></div>
                <div class="sls sls-2"></div>
                <div class="sls sls-3"></div>
                <div class="sls sls-4"></div>
                <span style="margin-left:4px">Plus</span>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ═══ BADGES ═══ -->
      <hr class="sep reveal"/>

      <section class="section">
        <p class="s-eyebrow reveal">Récompenses</p>
        <h2 class="s-title reveal">Vos Badges.</h2>

        <div class="badges-grid">
            <?php 
            // On affiche les badges que l'utilisateur possède réellement
            if (count($badges_possedes) > 0) { 
                foreach ($badges_possedes as $badge) { 
            ?>
                    <div class="badge-card reveal">
                        <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                        <span class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></span>
                        <span class="badge-desc"><?php echo htmlspecialchars($badge['description']); ?></span>
                    </div>
            <?php 
                }
            } else { 
            ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--muted); padding: 20px;">
                    Vous n'avez pas encore débloqué de badges. Continuez à lire pour en gagner !
                </p>
            <?php } ?>
        </div>
      </section>

      <!-- ═══ HISTORIQUE ═══ -->
      <hr class="sep reveal"/>

      <section class="section">
        <p class="s-eyebrow reveal">Activité</p>
        <h2 class="s-title reveal">Historique de Lecture.</h2>

        <div class="history-list">
            <?php
            // On fusionne les comics en cours et les comics finis pour l'historique
            // On peut trier cet historique par date de dernière lecture si besoin
            $historique_complet = array_merge($comics_en_cours, $comics_finis);

            // On trie l'historique par date de dernière lecture (du plus récent au plus ancien)
            usort($historique_complet, function($a, $b) {
                return strtotime($b['last_read_at']) - strtotime($a['last_read_at']);
            });
            
            if (count($historique_complet) > 0) {
                foreach ($historique_complet as $item) { 
                    $progression = ($item['current_page'] / $item['total_pages']) * 100;
                    // On s'assure que la progression est entre 0 et 100
                    if ($progression > 100) { $progression = 100; }
                    if ($progression < 0) { $progression = 0; }
            ?>
                    <div class="history-item reveal">
                        <img src="../../assets/img/<?php echo htmlspecialchars($item['cover']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="history-cover">
                        <div class="history-info">
                            <span class="history-title"><?php echo htmlspecialchars($item['title']); ?></span>
                            <div class="history-meta">
                                <span>Page <?php echo $item['current_page']; ?> / <?php echo $item['total_pages']; ?></span>
                                <span><?php echo date('d M. Y', strtotime($item['last_read_at'])); ?></span>
                            </div>
                        </div>
                        <div class="history-pb-wrap">
                            <div class="history-pb <?php if($item['completed'] == 1) { echo 'complete'; } ?>" style="--pb-w: <?php echo $progression; ?>%"></div>
                        </div>
                        <span class="history-status <?php if($item['completed'] == 1) { echo 'done'; } else { echo 'active'; } ?>">
                            <?php 
                            if($item['completed'] == 1) { 
                                echo 'Terminé ✓'; 
                            } else { 
                                echo 'En cours · ' . round($progression) . '%'; 
                            } 
                            ?>
                        </span>
                    </div>
                <?php 
                }
            } else { 
            ?>
                <p style="text-align: center; color: var(--muted); padding: 20px;">Aucune activité de lecture enregistrée pour le moment.</p>
            <?php } ?>
        </div>
      </section>

    </div>
  </main>

  <footer>
    <a class="logo" href="#">Panel<em>Vault</em></a>
    <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
  </footer>

</body>
</html>
