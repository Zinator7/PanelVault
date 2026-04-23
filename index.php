<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PanelVault — Lis. Collectionne. Domine.</title>
  <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="src/css/style.css"/>
</head>
<body>

<!-- HEADER -->
<header id="hdr">
  <a class="logo" href="#">Panel<em>Vault</em></a>
  <nav>
    <a href="#features">Fonctionnalités</a>
    <a href="#how">Comment ça marche</a>
    <a href="leaderboard.php">Classement</a>
  </nav>
  <div class="h-btns">
    <a href="src/php/pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
    <a href="src/php/pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
    <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
  </div>
</header>

<!-- MENU MOBILE -->
<div class="mobile-menu" id="mobileMenu">
  <a href="#features" onclick="closeMenu()">Fonctionnalités</a>
  <a href="#how" onclick="closeMenu()">Comment ça marche</a>
  <a href="leaderboard.php" onclick="closeMenu()">Classement</a>
  <hr style="border-color:var(--border);border-width:0.5px;"/>
  <a href="src/php/pages_connexion/login.php" class="mm-ghost">Connexion</a>
  <a href="src/php/pages_connexion/register.php" class="mm-red">S'inscrire →</a>
</div>

<!-- HERO -->
<div class="hero">

  <!-- Mosaïque covers -->
  <div class="covers">
    <div class="cover c1">
      <div class="cover-art"></div>
      <div class="cover-inner">
        <div class="cover-title"></div>
        <div class="cover-num">#1</div>
      </div>
    </div>
    <div class="cover c2">
      <div class="cover-art"></div>
      <div class="cover-inner">
        <div class="cover-title"></div>
        <div class="cover-num">#2</div>
      </div>
    </div>
    <div class="cover c3">
      <div class="cover-art"></div>
      <div class="cover-inner">
        <div class="cover-title"></div>
        <div class="cover-num">#3</div>
      </div>
    </div>
    <div class="cover c4">
      <div class="cover-art"></div>
      <div class="cover-inner">
        <div class="cover-title">Spider-Man</div>
        <div class="cover-num">#4</div>
      </div>
    </div>
    <div class="cover c5">
      <div class="cover-art"></div>
      <div class="cover-inner">
        <div class="cover-title">Superman</div>
        <div class="cover-num">#5</div>
      </div>
    </div>
  </div>

  <!-- Overlay gradient -->
  <div class="hero-overlay"></div>

  <!-- Texte hero -->
  <div class="hero-content">
    <!-- Ticker géant -->
    <div class="ticker-wrap">
      <div class="ticker">
        <div class="ticker-item">
          Lis <span class="ticker-dot"></span>
          <span class="outline">Collectionne</span>
          <span class="ticker-dot"></span>
          Domine
          <span class="ticker-dot"></span>
          Lis
          <span class="ticker-dot"></span>
          <span class="outline">Collectionne</span>
          <span class="ticker-dot"></span>
          Domine
          <span class="ticker-dot"></span>
        </div>
        <div class="ticker-item" aria-hidden="true">
          Lis <span class="ticker-dot"></span>
          <span class="outline">Collectionne</span>
          <span class="ticker-dot"></span>
          Domine
          <span class="ticker-dot"></span>
          Lis
          <span class="ticker-dot"></span>
          <span class="outline">Collectionne</span>
          <span class="ticker-dot"></span>
          Domine
          <span class="ticker-dot"></span>
        </div>
      </div>
    </div>

    <div class="hero-bottom">
      <p class="hero-tagline">
        Importe tes scans, <strong>suis ta progression</strong>,<br>
        débloque des badges et grimpe dans le<br>
        <strong>classement de la communauté.</strong>
      </p>
      <div class="hero-cta">
        <div class="hero-stats-mini">
          <div class="hsm">
            <div class="hsm-n"><span class="ctr" data-target="1240">0</span><em>+</em></div>
            <div class="hsm-l">Utilisateurs</div>
          </div>
          <div class="hsm">
            <div class="hsm-n"><span class="ctr" data-target="58300">0</span><em>+</em></div>
            <div class="hsm-l">Comics lus</div>
          </div>
        </div>
        <a href="src/php/pages_connexion/register.php" class="btn btn-red btn-red-lg">Créer mon compte →</a>
      </div>
    </div>
  </div>
</div>

<!-- BAND ROUGE -->
<div class="band">
  <div class="band-inner">
    <div class="band-item">Lecteur de scans</div>
    <div class="band-item">Système de badges</div>
    <div class="band-item">Streaks quotidiens</div>
    <div class="band-item">Classement live</div>
    <div class="band-item">Profil complet</div>
    <div class="band-item">Upload de comics</div>
    <div class="band-item">Lecteur de scans</div>
    <div class="band-item">Système de badges</div>
    <div class="band-item">Streaks quotidiens</div>
    <div class="band-item">Classement live</div>
    <div class="band-item">Profil complet</div>
    <div class="band-item">Upload de comics</div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <p class="s-eyebrow reveal">Fonctionnalités</p>
  <h2 class="s-title reveal">Ce qu'on<br>t'offre.</h2>

  <div class="feat-grid">
    <div class="feat reveal">
      <div class="f-num">01</div>
      <span class="f-icon">📖</span>
      <div class="f-name">Lecteur de scans</div>
      <div class="f-desc">Importe tes dossiers d'images (JPG, PNG, WebP). Navigation fluide, zoom, plein écran et raccourcis clavier.</div>
    </div>
    <div class="feat reveal">
      <div class="f-num">02</div>
      <span class="f-icon">🔥</span>
      <div class="f-name">Streaks & XP</div>
      <div class="f-desc">Lis chaque jour pour maintenir ton streak. Plus il est long, plus tu accumules de points bonus.</div>
    </div>
    <div class="feat reveal">
      <div class="f-num">03</div>
      <span class="f-icon">🏅</span>
      <div class="f-name">Badges</div>
      <div class="f-desc">Débloque des achievements en lisant, uploadant et restant actif. Affiche-les sur ton profil.</div>
    </div>
    <div class="feat reveal">
      <div class="f-num">04</div>
      <span class="f-icon">📤</span>
      <div class="f-name">Upload de scans</div>
      <div class="f-desc">Partage tes comics avec la communauté. Chaque upload te rapporte des points et de la visibilité.</div>
    </div>
    <div class="feat reveal">
      <div class="f-num">05</div>
      <span class="f-icon">🏆</span>
      <div class="f-name">Classement</div>
      <div class="f-desc">Classe-toi parmi les meilleurs lecteurs chaque semaine. Le podium se reset tous les lundis.</div>
    </div>
    <div class="feat reveal">
      <div class="f-num">06</div>
      <span class="f-icon">👤</span>
      <div class="f-name">Profil & Dashboard</div>
      <div class="f-desc">Visualise ton historique, ta progression, tes badges et tes statistiques de lecture.</div>
    </div>
  </div>
</section>

<hr class="sep"/>

<!-- STATS -->
<div class="stats-full reveal">
  <div class="sf">
    <div class="sf-n"><span class="ctr2" data-target="1240">0</span><em>+</em></div>
    <div class="sf-l">Utilisateurs actifs</div>
  </div>
  <div class="sf">
    <div class="sf-n"><span class="ctr2" data-target="58300">0</span><em>+</em></div>
    <div class="sf-l">Comics lus</div>
  </div>
  <div class="sf">
    <div class="sf-n"><span class="ctr2" data-target="4200">0</span><em>+</em></div>
    <div class="sf-l">Scans uploadés</div>
  </div>
</div>

<hr class="sep"/>

<!-- HOW -->
<section class="section" id="how">
  <p class="s-eyebrow reveal">Comment ça marche</p>
  <h2 class="s-title reveal">Trois étapes.<br>C'est tout.</h2>

  <div class="steps-row">
    <div class="reveal">
      <div class="step-n">01</div>
      <div class="step-line"></div>
      <div class="step-t">Crée ton compte</div>
      <div class="step-d">Pseudo, email, mot de passe. Ton profil est prêt en 30 secondes.</div>
    </div>
    <div class="reveal">
      <div class="step-n">02</div>
      <div class="step-line"></div>
      <div class="step-t">Importe tes scans</div>
      <div class="step-d">Glisse-dépose un dossier d'images. Le lecteur les organise automatiquement.</div>
    </div>
    <div class="reveal">
      <div class="step-n">03</div>
      <div class="step-line"></div>
      <div class="step-t">Lis & grimpe</div>
      <div class="step-d">Chaque page lue te rapporte de l'XP. Reviens chaque jour pour garder ton streak.</div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<div class="cta-wrap reveal">
  <div class="cta-title">Prêt à commencer<br>ta collection ?</div>
  <a href="src/php/pages_connexion/register.php" class="btn-white">Créer mon compte →</a>
</div>

<!-- FOOTER -->
<footer>
  <a class="logo" href="#">Panel<em>Vault</em></a>
  <p>© 2025 PanelVault · Projet étudiant L1 Informatique · Gratuit & sans pub</p>
</footer>

<script src="src/js/javascript-index.js"></script>
</body>
</html>