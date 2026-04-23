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
            <a href="../pages_connexion/login.php" class="btn btn-ghost">Connexion</a>
            <a href="../pages_connexion/register.php" class="btn btn-red">S'inscrire</a>
            <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </header>

    <!-- MENU MOBILE -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="../../../index.php#features" onclick="closeMenu()">Fonctionnalités</a>
        <a href="../../../index.php#how" onclick="closeMenu()">Comment ça marche</a>
        <a href="../../../leaderboard.php">Classement</a>
        <hr style="border-color:var(--border);border-width:0.5px;"/>
        <a href="../pages_connexion/login.php" class="mm-ghost">Connexion</a>
        <a href="../pages_connexion/register.php" class="mm-red">S'inscrire →</a>
    </div>

    <main class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-profile">
                <div class="profile-avatar">ZN</div>
                <div class="profile-details">
                    <span class="profile-name">ZinatoR</span>
                    <span class="profile-level">Lvl. 28</span>
                    <div class="xp-bar-wrap">
                        <div class="xp-bar" style="--xp-w: 68%"></div>
                    </div>
                    <span class="xp-next-level">6 210 / 9 100 XP → Lvl. 29</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="active">
                    <span class="icon">🏠</span> Accueil
                </a>
                <a href="#">
                    <span class="icon">📚</span> Ma Bibliothèque
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

        <!-- Contenu principal -->
        <div class="dashboard-content">
            <section class="section">
                <p class="s-eyebrow reveal">Bienvenue</p>
                <h1 class="s-title reveal">Votre Tableau de Bord.</h1>

                <div class="stats-grid">
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
                        <span class="stat-value"><span class="ctr" data-target="7">0</span> jours</span>
                        <span class="stat-label">Streak actuel</span>
                    </div>
                    <div class="stat-card reveal">
                        <span class="stat-icon">🏅</span>
                        <span class="stat-value"><span class="ctr" data-target="12">0</span></span>
                        <span class="stat-label">Badges débloqués</span>
                    </div>
                </div>
            </section>

            <hr class="sep reveal"/>

            <section class="section">
                <p class="s-eyebrow reveal">Activité</p>
                <h2 class="s-title reveal">Vos Derniers Comics Lus.</h2>

                <div class="last-read-grid">
                    <div class="read-item reveal">
                        <img src="../../assets/img/spiderman.jpg" alt="Spider-Man #12" class="read-cover">
                        <div class="read-info">
                            <span class="read-title">Spider-Man #12</span>
                            <div class="read-pb-wrap">
                                <div class="read-pb" style="--pb-w: 75%"></div>
                            </div>
                            <span class="read-progress">Page 24 / 32</span>
                            <span class="read-date">Lu le 22/04/2026</span>
                        </div>
                    </div>
                    <div class="read-item reveal">
                        <img src="../../assets/img/Batman.jpg" alt="Batman: The Long Halloween" class="read-cover">
                        <div class="read-info">
                            <span class="read-title">Batman: The Long Halloween</span>
                            <div class="read-pb-wrap">
                                <div class="read-pb complete" style="--pb-w: 100%"></div>
                            </div>
                            <span class="read-progress" style="color: #4ade80">Terminé ✓</span>
                            <span class="read-date">Lu le 18/04/2026</span>
                        </div>
                    </div>
                    <div class="read-item reveal">
                        <img src="../../assets/img/ironman.jpg" alt="Iron Man: Extremis" class="read-cover">
                        <div class="read-info">
                            <span class="read-title">Iron Man: Extremis</span>
                            <div class="read-pb-wrap">
                                <div class="read-pb" style="--pb-w: 25%"></div>
                            </div>
                            <span class="read-progress">Page 12 / 48</span>
                            <span class="read-date">Lu le 15/04/2026</span>
                        </div>
                    </div>
                </div>
            </section>

            <hr class="sep reveal"/>

            <section class="section">
                <p class="s-eyebrow reveal">Organisation</p>
                <h2 class="s-title reveal">Votre Bibliothèque.</h2>

                <div class="library-summary">
                    <div class="summary-card reveal">
                        <span class="summary-icon">📚</span>
                        <span class="summary-value"><span class="ctr" data-target="35">0</span></span>
                        <span class="summary-label">Comics au total</span>
                    </div>
                    <div class="summary-card reveal">
                        <span class="summary-icon">🗂️</span>
                        <span class="summary-value"><span class="ctr" data-target="12">0</span></span>
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
</body>
</html>
