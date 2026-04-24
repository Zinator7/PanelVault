<?php
session_start();

include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';
include '../../php/mvc/mvc_reading/crud_reading.php';
include '../../php/mvc/mvc_badges/crud_badges.php';

// CORRIGÉ : !isset() est la bonne façon d'écrire "si la variable n'existe pas"
// L'ancien code utilisait == false ce qui est moins lisible
if (!isset($_SESSION['user'])) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

$user_session = $_SESSION['user'];
// CORRIGÉ : on force l'id à être un entier avec (int) pour éviter les injections SQL
$user_id      = (int) $user_session['id'];
// On recharge l'utilisateur depuis la DB pour avoir des stats toujours à jour
$user         = select_user($conn, $user_id);

// --- NIVEAU & XP ---
// isset() + opérateur ternaire ?: donne une valeur par défaut si la clé n'existe pas
$niveau       = isset($user['level']) ? (int) $user['level'] : 1;
$xp_totale    = isset($user['xp'])    ? (int) $user['xp']    : 0;
$xp_par_palier     = 1000;                                     // 1000 XP pour passer un niveau
$xp_seuil_actuel   = ($niveau - 1) * $xp_par_palier;          // XP cumulée au début du niveau actuel
$xp_dans_le_niveau = $xp_totale - $xp_seuil_actuel;           // XP gagnée dans le niveau en cours
// max(0, min(100, ...)) remplace les deux if séparés de l'ancien code — même résultat, plus court
$pourcentage = max(0, min(100, ($xp_dans_le_niveau / $xp_par_palier) * 100));

// --- STREAK ---
// CORRIGÉ : $streak_actuel et $streak_max n'étaient jamais définis dans l'ancien code
// → provoquait des erreurs PHP "Undefined variable" à l'affichage
$streak_actuel = isset($user['streak'])     ? (int) $user['streak']     : 0;
$streak_max    = isset($user['streak_max']) ? (int) $user['streak_max'] : 0;

// --- DATE D'INSCRIPTION ---
$date_membre = "Inconnu";
// !empty() vérifie à la fois que la clé existe ET qu'elle n'est pas vide
if (!empty($user['created_at'])) {
    $date_membre = date_format(new DateTime($user['created_at']), 'd M. Y');
}

// --- LECTURES & BADGES ---
$badges_possedes = list_user_badges($conn, $user_id);
$nb_badges       = count($badges_possedes);

$comics_en_cours     = list_reading($conn, $user_id);
$comics_finis        = list_completed($conn, $user_id);
$nb_lus              = count($comics_finis);
// CORRIGÉ : $nb_series_terminees n'était jamais défini → erreur PHP
$nb_series_terminees = $nb_lus; // une série terminée = un comic avec completed = 1

// --- TEMPS DE LECTURE ---
// CORRIGÉ : $temps_lecture_heures n'était jamais défini → erreur PHP
// get_total_reading_time() additionne les pages des comics finis et divise par 60
$temps_lecture_heures = get_total_reading_time($conn, $user_id);

// --- CLASSEMENT ---
// CORRIGÉ : $top_classement_text n'était jamais défini → erreur PHP
// list_users() retourne les users triés par XP décroissant
// On cherche la position (index) de l'utilisateur dans ce tableau
$tous_users = list_users($conn);
$nb_total   = count($tous_users);
$rang       = $nb_total; // valeur par défaut = dernier si non trouvé
foreach ($tous_users as $i => $u) {
    // $i commence à 0, donc le premier est rang 1
    if ((int) $u['id'] === $user_id) { $rang = $i + 1; break; }
}
// On génère un texte lisible selon la position
if ($rang === 1) {
    $top_classement_text = '#1 du classement';
} elseif ($nb_total > 1 && ($rang / $nb_total) <= 0.10) {
    $top_classement_text = 'Top 10%';
} elseif ($nb_total > 1 && ($rang / $nb_total) <= 0.25) {
    $top_classement_text = 'Top 25%';
} else {
    $top_classement_text = '#' . $rang . ' / ' . $nb_total;
}

// --- ACTIVITÉ POUR LE CALENDRIER STREAK ---
// NOUVEAU : on récupère les jours où l'utilisateur a lu quelque chose sur 91 jours (13 semaines)
// DATE(last_read_at) extrait uniquement la date (sans l'heure) pour regrouper par jour
// COUNT(*) compte le nombre de comics lus ce jour-là (intensité de la couleur)
$streak_activity = [];
$sql_cal = "SELECT DATE(last_read_at) as d, COUNT(*) as n
            FROM reading_progress
            WHERE user_id = $user_id AND last_read_at >= DATE_SUB(NOW(), INTERVAL 91 DAY)
            GROUP BY DATE(last_read_at)";
$res_cal = mysqli_query($conn, $sql_cal);
// On construit un tableau associatif : ["2025-04-10" => 2, "2025-04-11" => 1, ...]
while ($row = mysqli_fetch_assoc($res_cal)) {
    $streak_activity[$row['d']] = (int) $row['n'];
}

// --- TOUS LES BADGES (pour aperçu verrouillé) ---
// NOUVEAU : si l'utilisateur n'a aucun badge, on charge tous les badges du jeu
// pour les afficher en version "verrouillée" → donne des objectifs visuels
// Si l'utilisateur a déjà des badges, on n'a pas besoin de cette liste → tableau vide
$tous_badges = ($nb_badges === 0) ? list_badges($conn) : [];
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

  <!-- MENU MOBILE -->
  <div class="mobile-menu" id="mobileMenu">
    <a href="../../../index.php#features" onclick="closeMenu()">Fonctionnalités</a>
    <a href="../../../index.php#how" onclick="closeMenu()">Comment ça marche</a>
    <a href="../../../leaderboard.php">Classement</a>
    <hr style="border-color:var(--border);border-width:0.5px;"/>
    <?php if (isset($_SESSION['user']) == true) { ?>
        <a href="profil.php" class="mm-ghost">Mon Profil</a>
        <a href="dashboard.php" class="mm-ghost">Dashboard</a>
        <a href="#" class="mm-ghost">Paramètres</a>
        <hr style="border-color:var(--border);border-width:0.5px;"/>
        <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
    <?php } else { ?>
        <a href="../pages_connexion/login.php" class="mm-ghost">Connexion</a>
        <a href="../pages_connexion/register.php" class="mm-red">S'inscrire →</a>
    <?php } ?>
  </div>

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
        <!-- CORRIGÉ : était "03" (fausse donnée template) → affiche maintenant le vrai niveau -->
        <span class="profile-banner-ghost">LVL <?php echo $niveau; ?></span>
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
            <!-- CORRIGÉ : on n'affiche la pill streak que si streak > 0, sinon "0 jours" est inutile -->
            <?php if ($streak_actuel > 0): ?>
            <span class="profile-pill accent">🔥 <?php echo $streak_actuel; ?> jours de streak</span>
            <?php endif; ?>
            <!-- CORRIGÉ : $top_classement_text était undefined → maintenant calculé depuis list_users() -->
            <span class="profile-pill accent">⭐ <?php echo $top_classement_text; ?></span>
            <span class="profile-pill">📖 <?php echo $nb_lus; ?> comics lus</span>
            <!-- AJOUT : pluriel conditionnel "badge" vs "badges" -->
            <span class="profile-pill">🏅 <?php echo $nb_badges; ?> badge<?php echo $nb_badges > 1 ? 's' : ''; ?></span>
            <!-- CORRIGÉ : $temps_lecture_heures était undefined → calculé via get_total_reading_time() -->
            <?php if ($temps_lecture_heures > 0): ?>
            <span class="profile-pill">⏱️ <?php echo $temps_lecture_heures; ?>h de lecture</span>
            <?php endif; ?>
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
            <span class="stat-value"><span class="ctr" data-target="<?php echo $streak_actuel; ?>">0</span> j.</span>
            <span class="stat-label">Streak actuel</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">🏅</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $nb_badges; ?>">0</span></span> <!-- Badges débloqués -->
            <span class="stat-label">Badges débloqués</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">⏱️</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $temps_lecture_heures; ?>">0</span> h</span>
            <span class="stat-label">Temps de lecture</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">✅</span>
            <span class="stat-value"><span class="ctr" data-target="<?php echo $nb_series_terminees; ?>">0</span></span>
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
              <div class="lp-meta-row">🏆 <span><?php echo $top_classement_text; ?></span></div>
              <div class="lp-meta-row">🔥 <span>Meilleur streak : <strong><?php echo $streak_max; ?> jours</strong></span></div>
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
                <span class="streak-count-num">🔥 <?php echo $streak_actuel; ?></span>
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
            <?php if ($nb_badges > 0): ?>
                <!-- CAS 1 : l'utilisateur a des badges → on les affiche normalement -->
                <?php foreach ($badges_possedes as $badge): ?>
                    <div class="badge-card reveal">
                        <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                        <span class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></span>
                        <span class="badge-desc"><?php echo htmlspecialchars($badge['description']); ?></span>
                        <!-- AJOUT : date réelle d'obtention depuis la colonne earned_at de user_badges -->
                        <span class="badge-desc" style="font-size:11px;color:var(--muted);margin-top:6px;">
                            Obtenu le <?php echo date('d M. Y', strtotime($badge['earned_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php elseif (count($tous_badges) > 0): ?>
                <!-- CAS 2 : l'utilisateur n'a aucun badge MAIS il en existe dans la DB -->
                <!-- NOUVEAU : on affiche les 6 premiers en version "verrouillée" (classe .locked) -->
                <!-- Objectif : montrer à l'utilisateur ce qu'il peut débloquer -->
                <?php foreach (array_slice($tous_badges, 0, 6) as $badge): ?>
                    <div class="badge-card locked reveal">
                        <span class="badge-icon">🔒</span>
                        <span class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></span>
                        <span class="badge-desc"><?php echo htmlspecialchars($badge['description']); ?></span>
                        <span class="badge-locked-hint">Non débloqué</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- CAS 3 : aucun badge dans la DB du tout -->
                <p style="grid-column:1/-1;text-align:center;color:var(--muted);padding:40px 20px;">
                    Aucun badge disponible pour le moment.
                </p>
            <?php endif; ?>
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

  <!-- NOUVEAU : on passe les données PHP au JS via une variable globale window.streakData -->
  <!-- json_encode() convertit le tableau PHP en JSON lisible par JavaScript -->
  <!-- Exemple : window.streakData = {"2025-04-10": 2, "2025-04-22": 1} -->
  <script>window.streakData = <?php echo json_encode($streak_activity); ?>;</script>

</body>
</html>
