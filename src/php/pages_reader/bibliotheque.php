<?php
// ════════════════════════════════════════════════
//  BIBLIOTHÈQUE — Hub principal de lecture
//  PanelVault
// ════════════════════════════════════════════════

session_start();
// session_start() DOIT être appelé avant tout output HTML.
// Elle recharge les variables de $_SESSION stockées côté serveur.
// Sans elle, $_SESSION est vide même si l'utilisateur est connecté.

// --- SÉCURITÉ : vérification de connexion ---
// isset() retourne true si la variable existe ET n'est pas null.
// Si l'utilisateur n'est pas connecté, on le redirige et on arrête le script.
if (!isset($_SESSION['user'])) {
    header("Location: ../pages_connexion/login.php");
    exit(); // IMPORTANT: exit() après header() sinon le reste du script s'exécute quand même
}

include '../db_connect.php';
include '../mvc/mvc_users/crud_users.php';
include '../mvc/mvc_comics/crud_comics.php';
include '../mvc/mvc_reading/crud_reading.php';
include '../mvc/mvc_badges/crud_badges.php';

// On refait un SELECT en base plutôt que de lire la session directement.
// Pourquoi ? La session peut être "périmée" : si l'XP a changé depuis la connexion,
// la session garde l'ancienne valeur. La BDD, elle, est toujours à jour.
$user    = select_user($conn, $_SESSION['user']['id']);
$user_id = $user['id'];

// --- CALCUL XP POUR LA SIDEBAR ---

// L'opérateur ?? s'appelle "null coalescing" (fusion de null).
// $user['level'] ?? 1  signifie : si $user['level'] existe et n'est pas null → on le prend,
// sinon → on utilise 1 (valeur par défaut).
$niveau           = $user['level'] ?? 1;
$xp_totale        = $user['xp']    ?? 0;

$xp_par_palier    = 1000; // XP nécessaire pour passer un niveau
// XP déjà accumulée dans le niveau courant (pas depuis le début)
// Exemple: niveau 3 = 2000 XP déjà dépassés → on soustrait les niveaux précédents
$xp_dans_niveau   = $xp_totale - (($niveau - 1) * $xp_par_palier);
// max() et min() servent à "borner" la valeur entre 0 et 100
// pour éviter des cas impossibles comme -5% ou 105%
$pourcentage_xp   = max(0, min(100, ($xp_dans_niveau / $xp_par_palier) * 100));

// --- RÉCUPÉRATION DES DONNÉES ---

// list_comics() retourne TOUS les comics de la bibliothèque (pas seulement ceux de l'utilisateur)
$all_comics = list_comics($conn);

// list_reading() → comics "en cours" (completed = 0 en BDD)
// list_completed() → comics terminés (completed = 1 en BDD)
$en_cours = list_reading($conn, $user_id);
$termines  = list_completed($conn, $user_id);

// --- LE "PROGRESS MAP" : tableau de lookup indexé par comic_id ---
//
// PROBLÈME : pour chaque carte de la grille, on a besoin de savoir si
// l'utilisateur a lu ce comic. Si on cherchait dans $en_cours à chaque fois,
// on ferait une double boucle → O(n²) : très lent si beaucoup de comics.
//
// SOLUTION : on construit un tableau associatif $progress_map[comic_id] = {...}
// Ainsi, pour n'importe quel comic, on accède à sa progression en O(1)
// (temps constant, comme un dictionnaire/hashmap).
//
// Exemple du résultat :
// $progress_map[42] = ['status' => 'reading', 'current_page' => 7, 'percent' => 35]
// $progress_map[17] = ['status' => 'done',    'current_page' => 48, 'percent' => 100]
$progress_map = [];

foreach ($en_cours as $l) {
    // On évite la division par zéro avec le ternaire (condition ? siVrai : siFaux)
    $pct = ($l['total_pages'] > 0)
        ? round(($l['current_page'] / $l['total_pages']) * 100)
        : 0;

    // On indexe par comic_id — c'est la clé d'accès rapide
    $progress_map[$l['comic_id']] = [
        'status'       => 'reading',
        'current_page' => $l['current_page'],
        'total_pages'  => $l['total_pages'],
        'percent'      => $pct,
    ];
}

foreach ($termines as $l) {
    // Pour un comic terminé, current_page = total_pages et percent = 100
    $progress_map[$l['comic_id']] = [
        'status'       => 'done',
        'current_page' => $l['total_pages'],
        'total_pages'  => $l['total_pages'],
        'percent'      => 100,
    ];
}

// --- COMPTEURS POUR LES ONGLETS ---
$nb_total   = count($all_comics);
$nb_reading = count($en_cours);
$nb_done    = count($termines);

// Pour compter les comics que CET utilisateur a uploadés,
// on parcourt tous les comics et on compare le user_id.
// (int) force la conversion en entier pour éviter "2" !== 2 (string vs int)
$nb_mine = 0;
foreach ($all_comics as $c) {
    if ((int)$c['user_id'] === $user_id) $nb_mine++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Bibliothèque — PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/library.css">
</head>
<body>

<!-- Barre de progression rouge qui avance pendant le scroll (gérée par JS) -->
<div class="scroll-progress" id="scrollProg"></div>

<!-- ══ HEADER ══ -->
<header id="hdr">
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <nav>
        <a href="../../../index.php#features">Fonctionnalités</a>
        <a href="../../../index.php#how">Comment ça marche</a>
        <a href="../../../leaderboard.php">Classement</a>
    </nav>
    <div class="h-btns">
        <!-- Menu déroulant utilisateur (dropdown) -->
        <div class="user-dropdown-wrapper">
            <a href="#" class="user-trigger">
                <div class="profile-avatar-mini">
                    <?php
                    // substr($str, 0, 2) = les 2 premiers caractères du pseudo
                    // strtoupper() = met en majuscules → "ab" devient "AB"
                    echo strtoupper(substr($user['username'], 0, 2));
                    ?>
                </div>
                <?php
                // htmlspecialchars() convertit les caractères spéciaux HTML en entités.
                // Exemple : <script> devient &lt;script&gt;
                // C'est INDISPENSABLE pour éviter les attaques XSS (Cross-Site Scripting) :
                // un utilisateur malveillant ne pourrait pas injecter du JS dans son pseudo.
                ?>
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
        <!-- Bouton hamburger ☰ (visible seulement sur mobile via CSS) -->
        <button class="burger" id="burger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- Menu mobile qui apparaît quand on clique sur le hamburger -->
<div class="mobile-menu" id="mobileMenu">
    <a href="../../../index.php#features" onclick="closeMenu()">Fonctionnalités</a>
    <a href="../../../leaderboard.php" onclick="closeMenu()">Classement</a>
    <hr style="border-color:var(--border);border-width:0.5px"/>
    <a href="../pages_users/profil.php" class="mm-ghost">Mon Profil</a>
    <a href="bibliotheque.php" class="mm-ghost">Ma Bibliothèque</a>
    <a href="upload_comics.php" class="mm-ghost">Uploader</a>
    <hr style="border-color:var(--border);border-width:0.5px"/>
    <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
</div>

<!-- Layout principal: sidebar à gauche + contenu à droite (flexbox, cf dashboard.css) -->
<main class="dashboard-layout">

    <!-- ══ SIDEBAR ══ -->
    <aside class="dashboard-sidebar">
        <div class="user-profile">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            </div>
            <div class="profile-details">
                <span class="profile-name"><?php echo htmlspecialchars($user['username']); ?></span>
                <span class="profile-level">Lvl. <?php echo $niveau; ?></span>
                <div class="xp-bar-wrap">
                    <?php
                    // --xp-w est une variable CSS personnalisée (custom property).
                    // On l'injecte depuis PHP pour que la barre se remplisse à la bonne largeur.
                    // La propriété width: var(--xp-w) dans le CSS lira cette valeur.
                    ?>
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
            <!-- La classe "active" met en rouge le lien de la page courante (cf dashboard.css) -->
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

    <!-- ══ CONTENU PRINCIPAL ══ -->
    <div class="dashboard-content">
        <section class="section">

            <!-- En-tête avec titre + barre de recherche -->
            <div class="lib-hero reveal">
                <div class="lib-hero-left">
                    <p class="s-eyebrow">Collection</p>
                    <h1 class="s-title" style="margin-bottom:0">Ma Bibliothèque.</h1>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <!-- Champ de recherche : la logique de filtrage est dans js-library.js -->
                    <div class="lib-search">
                        <span class="lib-search-icon">🔍</span>
                        <input
                            type="text"
                            id="libSearch"
                            placeholder="Rechercher… (Ctrl+K)"
                            autocomplete="off"
                        >
                    </div>
                    <a href="upload_comics.php" class="btn btn-red">+ Ajouter</a>
                </div>
            </div>

            <!-- Bande de statistiques (les chiffres s'animent via la classe .ctr) -->
            <div class="lib-stats reveal">
                <div class="lib-stat">
                    <div class="lib-stat-n">
                        <?php
                        // data-target="42" est un attribut HTML personnalisé (data attribute).
                        // Le JS le lit via element.dataset.target et anime le compteur de 0 → 42.
                        // La valeur initiale "0" sera remplacée par JS à l'animation.
                        ?>
                        <span class="ctr" data-target="<?php echo $nb_total; ?>">0</span>
                    </div>
                    <div class="lib-stat-l">Comics disponibles</div>
                </div>
                <div class="lib-stat">
                    <div class="lib-stat-n" style="color:var(--red)">
                        <span class="ctr" data-target="<?php echo $nb_reading; ?>">0</span>
                    </div>
                    <div class="lib-stat-l">En cours</div>
                </div>
                <div class="lib-stat">
                    <div class="lib-stat-n" style="color:#4ade80">
                        <span class="ctr" data-target="<?php echo $nb_done; ?>">0</span>
                    </div>
                    <div class="lib-stat-l">Terminés</div>
                </div>
            </div>

            <!-- Onglets de filtre -->
            <?php
            // data-filter="reading" est lu par JS pour savoir quel filtre appliquer.
            // Quand on clique un onglet, JS ajoute/retire la classe "active" et
            // cache/affiche les cartes qui ne correspondent pas.
            ?>
            <div class="lib-filters reveal">
                <button class="filter-tab active" data-filter="all">
                    Tous <span class="filter-count"><?php echo $nb_total; ?></span>
                </button>
                <button class="filter-tab" data-filter="reading">
                    En cours <span class="filter-count"><?php echo $nb_reading; ?></span>
                </button>
                <button class="filter-tab" data-filter="done">
                    Terminés <span class="filter-count"><?php echo $nb_done; ?></span>
                </button>
                <button class="filter-tab" data-filter="mine">
                    Mes uploads <span class="filter-count"><?php echo $nb_mine; ?></span>
                </button>
            </div>

            <!-- Grille de toutes les cartes comics -->
            <div class="comics-grid">

                <?php if (count($all_comics) === 0): ?>
                    <!-- État vide: aucun comic en base, on affiche un appel à l'action -->
                    <div class="lib-empty">
                        <span class="lib-empty-icon">📭</span>
                        <h2 class="lib-empty-title">Bibliothèque vide</h2>
                        <p class="lib-empty-desc">
                            Aucun comic n'a encore été ajouté.<br>
                            Soyez le premier à uploader un scan !
                        </p>
                        <a href="upload_comics.php" class="btn btn-red">Uploader un comic →</a>
                    </div>

                <?php else: ?>

                    <?php foreach ($all_comics as $comic):
                        // On caste en int pour être sûr que c'est un nombre (pas une string "42")
                        $cid      = (int)$comic['id'];
                        // htmlspecialchars() protège contre le XSS à l'affichage
                        $title    = htmlspecialchars($comic['title']);
                        // ?? 'Inconnu' : si publisher est null en BDD, on affiche 'Inconnu'
                        $pub      = htmlspecialchars($comic['publisher'] ?? 'Inconnu');
                        $cover    = $comic['cover'] ?? '';
                        $uploader = htmlspecialchars($comic['uploader'] ?? '');

                        // Opérateur ternaire : condition ? valeur_si_vrai : valeur_si_faux
                        // On convertit en string '1' ou '0' car les data-attributes HTML sont toujours des strings
                        $isMine = ((int)$comic['user_id'] === $user_id) ? '1' : '0';

                        // Consultation du progress_map construit plus haut.
                        // $progress_map[$cid] renvoie le tableau si la clé existe, null sinon.
                        // L'opérateur ?? null donne null si la clé n'existe pas (comic jamais ouvert).
                        $prog   = $progress_map[$cid] ?? null;
                        $status = $prog ? $prog['status'] : 'new'; // 'reading' | 'done' | 'new'
                        $pct    = $prog ? $prog['percent'] : 0;

                        // Tableau associatif utilisé comme "switch" concis pour choisir
                        // la classe CSS et le label du badge selon le statut
                        $badgeClass = ['reading' => 'badge-reading', 'done' => 'badge-done', 'new' => 'badge-new'][$status];
                        $badgeLabel = ['reading' => 'En cours', 'done' => 'Terminé', 'new' => 'Nouveau'][$status];

                        $coverUrl   = '../../assets/img/' . $cover;
                        // Si l'utilisateur a déjà lu, on reprend à la page sauvegardée, sinon page 1
                        $resumePage = $prog ? $prog['current_page'] : 1;
                    ?>

                    <?php
                    // LES DATA-ATTRIBUTES SUR LA CARTE :
                    // data-status, data-mine, data-title, data-publisher sont injectés par PHP.
                    // Le JavaScript les lit via card.dataset.status, card.dataset.mine, etc.
                    // pour savoir quelle carte afficher/cacher selon le filtre et la recherche.
                    // C'est le "pont" entre PHP (serveur) et JavaScript (navigateur).
                    ?>
                    <div
                        class="comic-card reveal"
                        data-status="<?php echo $status; ?>"
                        data-mine="<?php echo $isMine; ?>"
                        data-title="<?php echo $title; ?>"
                        data-publisher="<?php echo $pub; ?>"
                    >
                        <!-- aspect-ratio 2:3 est géré par CSS pour conserver le format portrait -->
                        <div class="comic-cover-wrap">
                            <?php if ($cover): ?>
                                <img
                                    src="<?php echo $coverUrl; ?>"
                                    alt="<?php echo $title; ?>"
                                    loading="lazy"
                                    <?php
                                    // onerror="..." est exécuté si le navigateur ne peut pas charger l'image.
                                    // On cache l'img cassée et on affiche le div placeholder emoji à la place.
                                    // this = l'élément <img>, nextElementSibling = le div qui le suit dans le HTML.
                                    ?>
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                >
                                <div class="comic-cover-placeholder" style="display:none">📖</div>
                            <?php else: ?>
                                <div class="comic-cover-placeholder">📖</div>
                            <?php endif; ?>

                            <!-- Badge de statut positionné en absolu en haut à droite via CSS -->
                            <span class="comic-badge <?php echo $badgeClass; ?>">
                                <?php echo $badgeLabel; ?>
                            </span>

                            <!-- Overlay invisible par défaut, visible au :hover via CSS (opacity 0→1) -->
                            <div class="comic-overlay">
                                <a
                                    href="read_comics.php?id=<?php echo $cid; ?>&page=<?php echo $resumePage; ?>"
                                    class="btn btn-red"
                                >
                                    <?php
                                    // Ternaire en ligne : si en cours → "Continuer", sinon → "Lire"
                                    echo ($status === 'reading') ? '▶ Continuer' : '▶ Lire';
                                    ?>
                                </a>
                                <a href="info_comics.php?id=<?php echo $cid; ?>" class="btn-info">
                                    ℹ Infos
                                </a>
                            </div>
                        </div>

                        <!-- Zone texte sous la cover -->
                        <div class="comic-info">
                            <div class="comic-title"><?php echo $title; ?></div>
                            <div class="comic-publisher"><?php echo $pub; ?></div>
                            <?php
                            // data-pct="35" est lu par IntersectionObserver dans js-library.js.
                            // Quand la carte entre dans le viewport, JS fait fill.style.width = "35%"
                            // ce qui déclenche la transition CSS (width 0 → 35%, cf library.css).
                            // La width commence à 0 (pas d'animation immédiate au chargement).
                            ?>
                            <div class="comic-progress-bar">
                                <div
                                    class="comic-progress-fill <?php echo ($status === 'done') ? 'done' : ''; ?>"
                                    data-pct="<?php echo $pct; ?>"
                                    style="width:0%"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>

                    <!-- Affiché uniquement par JS quand la recherche ne trouve rien (class "visible") -->
                    <div class="lib-no-results" id="libNoResults">
                        <p>Aucun comic ne correspond à votre recherche.</p>
                    </div>

                <?php endif; ?>

            </div><!-- /comics-grid -->

        </section>
    </div><!-- /dashboard-content -->

</main>

<footer>
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
</footer>

<script src="../../js/js-library.js"></script>

</body>
</html>
