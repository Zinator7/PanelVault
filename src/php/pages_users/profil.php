<?php
session_start();
include '../../php/db_connect.php';
include '../../php/mvc/mvc_users/crud_users.php';

if(!isset($_SESSION['user'])){
    header("Location: ../pages_connexion/login.php");
    exit();
}
$user = $_SESSION['user'];

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
      <a href="../pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
      <a href="../pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
      <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </header>

  <div class="mobile-menu" id="mobileMenu">
    <a href="../../../index.php#features" onclick="closeMenu()">Fonctionnalités</a>
    <a href="../../../index.php#how" onclick="closeMenu()">Comment ça marche</a>
    <a href="../../../leaderboard.php">Classement</a>
    <hr style="border-color:var(--border);border-width:0.5px;"/>
    <a href="../pages_connexion/login.php" class="mm-ghost">Connexion</a>
    <a href="../pages_connexion/register.php" class="mm-red">S'inscrire →</a>
  </div>

  <main class="dashboard-layout">

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="dashboard-sidebar">
      <div class="user-profile">
        <div class="profile-avatar"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
        <div class="profile-details">
          <span class="profile-name"><?= htmlspecialchars($user['username']) ?></span>
          <span class="profile-level">Lvl. <?= $user['level'] ?? 1 ?></span>
          <div class="xp-bar-wrap">
            <div class="xp-bar" style="--xp-w: 68%"></div>
          </div>
          <span class="xp-next-level">6 210 / 9 100 XP → Lvl. 29</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <a href="dashboard.php">
          <span class="icon">🏠</span> Accueil
        </a>
        <a href="#">
          <span class="icon">📚</span> Ma Bibliothèque
        </a>
        <a href="profil.php" class="active">
          <span class="icon">👤</span> Mon Profil
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
        <a href="#" class="logout-link">
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
          <div class="profile-avatar-lg"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
          <svg class="profile-ring" viewBox="0 0 136 136">
            <circle class="ring-bg" cx="68" cy="68" r="62"/>
            <circle class="ring-fg" cx="68" cy="68" r="62" data-progress="0.6824"/>
          </svg>
          <span class="profile-level-badge">LVL <?= $user['level'] ?? 1 ?></span>
        </div>

        <!-- Info -->
        <div class="profile-info">
          <h1 class="profile-username"><?= htmlspecialchars($user['username']) ?></h1>
          <p class="profile-tagline">Lecteur passionné · Collectionneur de scans rares · Membre depuis le 12 janv. 2025</p>
          <div class="profile-pills">
            <span class="profile-pill accent">🔥 7 jours de streak</span>
            <span class="profile-pill accent">⭐ Top 12 Classement</span>
            <span class="profile-pill">📖 94 comics lus</span>
            <span class="profile-pill">🏅 12 badges</span>
            <span class="profile-pill">⏱️ 52h de lecture</span>
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
            <span class="stat-value"><span class="ctr" data-target="6210">0</span> XP</span>
            <span class="stat-label">Total XP</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">📖</span>
            <span class="stat-value"><span class="ctr" data-target="94">0</span></span>
            <span class="stat-label">Comics lus</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">🔥</span>
            <span class="stat-value"><span class="ctr" data-target="7">0</span> j.</span>
            <span class="stat-label">Streak actuel</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">🏅</span>
            <span class="stat-value"><span class="ctr" data-target="12">0</span></span>
            <span class="stat-label">Badges débloqués</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">⏱️</span>
            <span class="stat-value"><span class="ctr" data-target="52">0</span> h</span>
            <span class="stat-label">Temps de lecture</span>
          </div>
          <div class="stat-card reveal">
            <span class="stat-icon">✅</span>
            <span class="stat-value"><span class="ctr" data-target="8">0</span></span>
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
              <span class="lp-lvl current">28</span>
              <span class="lp-lvl sep">/</span>
              <span class="lp-lvl next">29 →</span>
            </div>

            <div class="lp-bar-wrap">
              <div class="lp-bar" style="--lp-w: 68%"></div>
            </div>

            <div class="lp-xp-row">
              <span><strong>6 210 XP</strong> obtenus</span>
              <span>encore <strong>2 890 XP</strong></span>
            </div>

            <div class="lp-meta">
              <div class="lp-meta-row">📅 <span>Membre depuis <strong>12 janv. 2025</strong></span></div>
              <div class="lp-meta-row">🏆 <span>Classé <strong>#12</strong> cette semaine</span></div>
              <div class="lp-meta-row">🔥 <span>Meilleur streak : <strong>21 jours</strong></span></div>
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
                <span class="streak-count-num">🔥 7</span>
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

          <!-- Unlocked -->
          <div class="badge-card reveal">
            <span class="badge-icon">👣</span>
            <span class="badge-name">Premier Pas</span>
            <span class="badge-desc">Premier comic lu</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">📚</span>
            <span class="badge-name">Dévoreur</span>
            <span class="badge-desc">10 comics lus</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">🔥</span>
            <span class="badge-name">Lecteur Assidu</span>
            <span class="badge-desc">7 jours de streak</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">🦇</span>
            <span class="badge-name">Batmaniac</span>
            <span class="badge-desc">5 comics Batman terminés</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">🌙</span>
            <span class="badge-name">Noctambule</span>
            <span class="badge-desc">Lu après minuit</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">🏃</span>
            <span class="badge-name">Marathonien</span>
            <span class="badge-desc">5 comics en un jour</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">⭐</span>
            <span class="badge-name">Légende</span>
            <span class="badge-desc">5 000 XP atteints</span>
          </div>

          <div class="badge-card reveal">
            <span class="badge-icon">🗂️</span>
            <span class="badge-name">Collectionneur</span>
            <span class="badge-desc">25 comics en bibliothèque</span>
          </div>

          <!-- Locked -->
          <div class="badge-card locked reveal">
            <span class="badge-icon">💀</span>
            <span class="badge-name">Immortel</span>
            <span class="badge-desc">365 jours de streak</span>
            <span class="badge-locked-hint">🔒 Encore 358 jours</span>
          </div>

          <div class="badge-card locked reveal">
            <span class="badge-icon">🌟</span>
            <span class="badge-name">Dieu de la Lecture</span>
            <span class="badge-desc">1 000 comics lus</span>
            <span class="badge-locked-hint">🔒 Encore 906 comics</span>
          </div>

          <div class="badge-card locked reveal">
            <span class="badge-icon">💎</span>
            <span class="badge-name">Ultra Collector</span>
            <span class="badge-desc">100 séries différentes</span>
            <span class="badge-locked-hint">🔒 Encore 88 séries</span>
          </div>

          <div class="badge-card locked reveal">
            <span class="badge-icon">⏰</span>
            <span class="badge-name">Centenaire</span>
            <span class="badge-desc">100h de lecture totale</span>
            <span class="badge-locked-hint">🔒 Encore 48h</span>
          </div>

        </div>
      </section>

      <!-- ═══ HISTORIQUE ═══ -->
      <hr class="sep reveal"/>

      <section class="section">
        <p class="s-eyebrow reveal">Activité</p>
        <h2 class="s-title reveal">Historique de Lecture.</h2>

        <div class="history-list">

          <div class="history-item reveal">
            <img src="../../assets/img/spiderman.jpg" alt="Spider-Man #12" class="history-cover">
            <div class="history-info">
              <span class="history-title">Spider-Man #12</span>
              <div class="history-meta">
                <span>Page 24 / 32</span>
                <span>22 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb" style="--pb-w: 75%"></div>
            </div>
            <span class="history-status active">En cours · 75%</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/Batman.jpg" alt="Batman: The Long Halloween" class="history-cover">
            <div class="history-info">
              <span class="history-title">Batman: The Long Halloween</span>
              <div class="history-meta">
                <span>32 pages</span>
                <span>18 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb complete" style="--pb-w: 100%"></div>
            </div>
            <span class="history-status done">Terminé ✓</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/ironman.jpg" alt="Iron Man: Extremis" class="history-cover">
            <div class="history-info">
              <span class="history-title">Iron Man: Extremis</span>
              <div class="history-meta">
                <span>Page 12 / 48</span>
                <span>15 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb" style="--pb-w: 25%"></div>
            </div>
            <span class="history-status active">En cours · 25%</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/xmen.jpg" alt="X-Men: Days of Future Past" class="history-cover">
            <div class="history-info">
              <span class="history-title">X-Men: Days of Future Past</span>
              <div class="history-meta">
                <span>28 pages</span>
                <span>10 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb complete" style="--pb-w: 100%"></div>
            </div>
            <span class="history-status done">Terminé ✓</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/superman.jpg" alt="Superman: Red Son" class="history-cover">
            <div class="history-info">
              <span class="history-title">Superman: Red Son</span>
              <div class="history-meta">
                <span>Page 20 / 40</span>
                <span>6 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb" style="--pb-w: 50%"></div>
            </div>
            <span class="history-status active">En cours · 50%</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/Batman.jpg" alt="Batman: Year One" class="history-cover">
            <div class="history-info">
              <span class="history-title">Batman: Year One</span>
              <div class="history-meta">
                <span>36 pages</span>
                <span>1 avr. 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb complete" style="--pb-w: 100%"></div>
            </div>
            <span class="history-status done">Terminé ✓</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/spiderman.jpg" alt="Spider-Man: Kraven's Last Hunt" class="history-cover">
            <div class="history-info">
              <span class="history-title">Spider-Man: Kraven's Last Hunt</span>
              <div class="history-meta">
                <span>44 pages</span>
                <span>26 mars 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb complete" style="--pb-w: 100%"></div>
            </div>
            <span class="history-status done">Terminé ✓</span>
          </div>

          <div class="history-item reveal">
            <img src="../../assets/img/ironman.jpg" alt="Iron Man: Armor Wars" class="history-cover">
            <div class="history-info">
              <span class="history-title">Iron Man: Armor Wars</span>
              <div class="history-meta">
                <span>Page 6 / 40</span>
                <span>20 mars 2026</span>
              </div>
            </div>
            <div class="history-pb-wrap">
              <div class="history-pb" style="--pb-w: 15%"></div>
            </div>
            <span class="history-status active">Commencé · 15%</span>
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
  <script>
  (function () {

    /* ── Anneau SVG ── */
    const ring = document.querySelector('.ring-fg');
    if (ring) {
      const progress = parseFloat(ring.dataset.progress || '0');
      setTimeout(() => { ring.style.strokeDashoffset = 390 * (1 - progress); }, 300);
    }

    /* ── Compteurs profil-stats-grid ── */
    const profileGrid = document.querySelector('.profile-stats-grid');
    if (profileGrid) {
      let done = false;
      new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && !done) {
          done = true;
          profileGrid.querySelectorAll('.ctr').forEach(el => {
            const target = parseInt(el.dataset.target, 10);
            const start  = performance.now();
            const dur    = 2000;
            (function step(now) {
              const p    = Math.min((now - start) / dur, 1);
              const ease = 1 - Math.pow(1 - p, 4);
              el.textContent = Math.floor(ease * target).toLocaleString('fr-FR');
              if (p < 1) requestAnimationFrame(step);
              else el.textContent = target.toLocaleString('fr-FR');
            })(performance.now());
          });
        }
      }, { threshold: 0.2 }).observe(profileGrid);
    }

    /* ── Barres de progression historique ── */
    const histObserver = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          histObserver.unobserve(e.target);
        }
      });
    }, { threshold: 0.15 });
    document.querySelectorAll('.history-item').forEach(el => histObserver.observe(el));

    /* ── Calendrier de streak ── */
    const grid = document.getElementById('streakGrid');
    if (grid) {
      const data = [
        0,0,0,1,0,2,1,
        0,1,1,0,2,1,0,
        2,1,0,1,2,0,0,
        0,0,2,3,1,2,1,
        1,2,0,0,1,0,2,
        2,1,3,2,1,0,1,
        0,1,2,1,3,2,1,
        2,3,1,2,3,2,1,
        1,2,3,2,1,3,2,
        3,2,3,1,2,3,2,
        2,3,2,3,4,3,2,
        3,4,3,4,3,4,3,
        4,4,4,3,4,4,4
      ];
      data.forEach((level, i) => {
        const day = document.createElement('div');
        let cls = 'streak-day';
        if (level > 0) cls += ` l${level}`;
        if (i === data.length - 1) cls += ' today';
        day.className = cls;
        grid.appendChild(day);
      });
    }

  })();
  </script>

</body>
</html>
