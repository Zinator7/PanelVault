<?php
// ════════════════════════════════════════════════
//  UPLOAD COMICS — Ajouter un scan à la bibliothèque
//  PanelVault
//  Formats acceptés : PDF, CBZ, ZIP (images), EPUB
//  Couverture : optionnelle (image JPG/PNG/WEBP)
// ════════════════════════════════════════════════

session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../pages_connexion/login.php");
    exit();
}

include '../db_connect.php';
include '../mvc/mvc_users/crud_users.php';
include '../mvc/mvc_comics/crud_comics.php';

$user    = select_user($conn, $_SESSION['user']['id']);
$user_id = $user['id'];

$niveau         = $user['level'] ?? 1;
$xp_totale      = $user['xp']    ?? 0;
$xp_par_palier  = 1000;
$xp_dans_niveau = $xp_totale - (($niveau - 1) * $xp_par_palier);
$pourcentage_xp = max(0, min(100, ($xp_dans_niveau / $xp_par_palier) * 100));

$flash_type = '';
$flash_msg  = '';

// ─── TRAITEMENT POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title']       ?? '');
    $publisher   = trim($_POST['publisher']   ?? '');
    $total_pages = (int)($_POST['total_pages'] ?? 0);
    $cover_name  = '';
    $file_name   = '';
    $file_type   = '';

    $errors = [];

    if ($title === '') $errors[] = 'Le titre est obligatoire.';

    // ── Dossier de stockage des scans ──
    $upload_dir = __DIR__ . '/../../uploads/comics/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // ══════════════════════════════════════════════
    //  TRAITEMENT DU FICHIER SCAN (obligatoire)
    // ══════════════════════════════════════════════
    $allowed_scan = ['pdf', 'epub', 'cbz', 'zip'];
    $max_scan_size = 500 * 1024 * 1024; // 500 Mo (limité en pratique par php.ini)

    if (!isset($_FILES['scan_file']) || $_FILES['scan_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_err = $_FILES['scan_file']['error'] ?? -1;
        if ($upload_err === UPLOAD_ERR_INI_SIZE || $upload_err === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'Fichier trop lourd pour le serveur. Vérifiez la limite d\'upload de l\'hébergeur.';
        } else {
            $errors[] = 'Veuillez sélectionner un fichier scan (PDF, CBZ, ZIP ou EPUB).';
        }
    } else {
        $scan = $_FILES['scan_file'];
        $scan_ext = strtolower(pathinfo($scan['name'], PATHINFO_EXTENSION));

        if (!in_array($scan_ext, $allowed_scan)) {
            $errors[] = 'Format non supporté. Utilisez PDF, CBZ, ZIP ou EPUB.';
        } elseif ($scan['size'] > $max_scan_size) {
            $errors[] = 'Fichier trop lourd (max 500 Mo).';
        } else {
            $file_type = $scan_ext;
            $file_name = time() . '_' . $user_id . '.' . $scan_ext;
            $dest_path = $upload_dir . $file_name;

            if (!move_uploaded_file($scan['tmp_name'], $dest_path)) {
                $errors[] = 'Erreur lors de l\'enregistrement du fichier. Réessayez.';
                $file_name = '';
                $file_type = '';
            } else {
                // ── Auto-détection des pages pour CBZ/ZIP ──
                if (in_array($file_type, ['cbz', 'zip']) && class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($dest_path) === true) {
                        $count = 0;
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $n   = $zip->getNameIndex($i);
                            $ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                $count++;
                            }
                        }
                        $zip->close();
                        if ($count > 0) $total_pages = $count;
                    }
                }

                // Valide total_pages pour PDF/EPUB (non auto-détecté)
                if (in_array($file_type, ['pdf', 'epub']) && $total_pages <= 0) {
                    $errors[] = 'Veuillez indiquer le nombre de pages pour les fichiers PDF/EPUB.';
                    // Supprime le fichier uploadé car on ne va pas l'enregistrer
                    unlink($dest_path);
                    $file_name = '';
                    $file_type = '';
                }
            }
        }
    }

    // ══════════════════════════════════════════════
    //  TRAITEMENT DE LA COUVERTURE (optionnelle)
    // ══════════════════════════════════════════════
    $cover_dir = __DIR__ . '/../../assets/img/';

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $cov = $_FILES['cover'];
        $cov_ext     = strtolower(pathinfo($cov['name'], PATHINFO_EXTENSION));
        $allowed_img = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $max_img     = 5 * 1024 * 1024; // 5 Mo

        if (!in_array($cov_ext, $allowed_img)) {
            $errors[] = 'Format de couverture non supporté. Utilisez JPG, PNG ou WEBP.';
        } elseif ($cov['size'] > $max_img) {
            $errors[] = 'La couverture est trop lourde (max 5 Mo).';
        } else {
            $cover_name = 'cover_' . time() . '_' . $user_id . '.' . $cov_ext;
            if (!move_uploaded_file($cov['tmp_name'], $cover_dir . $cover_name)) {
                $errors[] = 'Erreur lors de l\'upload de la couverture.';
                $cover_name = '';
            }
        }
    }
    // Si pas de couverture → $cover_name reste '' (pas d'erreur)

    // ── Insertion en base ──
    if (empty($errors) && $file_name !== '') {
        $result = insert_comic($conn, $user_id, $title, $publisher, $cover_name, $total_pages, $file_name, $file_type);
        if ($result) {
            header("Location: bibliotheque.php?uploaded=1");
            exit();
        } else {
            // Rollback : supprime le fichier si l'insertion BDD échoue
            if ($file_name && file_exists($upload_dir . $file_name)) {
                unlink($upload_dir . $file_name);
            }
            $flash_type = 'error';
            $flash_msg  = 'Erreur lors de l\'enregistrement en base de données.';
        }
    } elseif (!empty($errors)) {
        $flash_type = 'error';
        $flash_msg  = implode(' ', $errors);
    }
}

if (isset($_GET['uploaded'])) {
    $flash_type = 'success';
    $flash_msg  = 'Scan ajouté avec succès à la bibliothèque !';
}

$mes_uploads   = list_comics_by_user($conn, $user_id);
$editeurs_connus = ['Marvel', 'DC Comics', 'Image Comics', 'Dark Horse', 'IDW Publishing', 'Boom! Studios', 'Vertigo', 'Manga', 'Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploader un scan — PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/upload.css">
</head>
<body
    <?php if ($flash_type): ?>
        data-flash="<?php echo $flash_type; ?>"
        data-flash-msg="<?php echo htmlspecialchars($flash_msg); ?>"
    <?php endif; ?>
>

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
    <a href="upload_comics.php" class="mm-ghost">Uploader</a>
    <a href="../pages_connexion/logout.php" class="mm-red">Déconnexion →</a>
</div>

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
            <a href="../pages_users/dashboard.php"><span class="icon">🏠</span> Accueil</a>
            <a href="bibliotheque.php"><span class="icon">📚</span> Ma Bibliothèque</a>
            <a href="upload_comics.php" class="active"><span class="icon">📤</span> Uploader</a>
            <a href="../pages_users/profil.php"><span class="icon">👤</span> Mon Profil</a>
            <a href="../pages_users/badge_users.php"><span class="icon">🏅</span> Mes Badges</a>
            <a href="../../../leaderboard.php"><span class="icon">🏆</span> Classement</a>
            <a href="../pages_connexion/logout.php" class="logout-link"><span class="icon">🚪</span> Déconnexion</a>
        </nav>
    </aside>

    <!-- ── CONTENU ── -->
    <div class="dashboard-content">
        <section class="section">

            <p class="s-eyebrow reveal">Bibliothèque</p>
            <h1 class="s-title reveal">Uploader un scan.</h1>

            <div class="upload-container">

                <div class="form-msg" id="formMsg">
                    <span class="form-msg-icon"></span>
                    <span></span>
                </div>

                <form
                    id="uploadForm"
                    method="POST"
                    action="upload_comics.php"
                    enctype="multipart/form-data"
                    class="upload-form"
                    novalidate
                >

                    <!-- ── FICHIER SCAN (obligatoire) ── -->
                    <div class="form-group">
                        <label class="form-label">Fichier du scan *</label>

                        <div class="drop-zone" id="dropZoneScan">
                            <input
                                type="file"
                                name="scan_file"
                                id="scanFile"
                                accept=".pdf,.epub,.cbz,.zip"
                            >
                            <span class="drop-icon" id="scanIcon">📂</span>
                            <p class="drop-title" id="scanTitle">Déposer le fichier ici</p>
                            <p class="drop-desc" id="scanDesc">
                                ou <strong>cliquer pour parcourir</strong>
                            </p>
                            <div class="drop-formats">
                                <span class="drop-format-tag">PDF</span>
                                <span class="drop-format-tag">CBZ</span>
                                <span class="drop-format-tag">ZIP</span>
                                <span class="drop-format-tag">EPUB</span>
                            </div>
                        </div>
                    </div>

                    <!-- ── COUVERTURE (optionnelle) ── -->
                    <div class="form-group">
                        <label class="form-label">
                            Couverture
                            <span style="opacity:0.45; font-weight:400; font-size:0.85em"> — optionnelle</span>
                        </label>

                        <div class="drop-zone" id="dropZone">
                            <input
                                type="file"
                                name="cover"
                                id="coverFile"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                            >
                            <span class="drop-icon" id="dropIcon">🖼️</span>
                            <p class="drop-title" id="dropTitle">Déposer la couverture ici</p>
                            <p class="drop-desc" id="dropDesc">
                                ou <strong>cliquer pour parcourir</strong>
                            </p>
                            <div class="drop-formats">
                                <span class="drop-format-tag">JPG</span>
                                <span class="drop-format-tag">PNG</span>
                                <span class="drop-format-tag">WEBP</span>
                                <span class="drop-format-tag">Max 5 Mo</span>
                            </div>
                        </div>

                        <div class="cover-preview-wrap" id="coverPreviewWrap">
                            <img src="" alt="Aperçu" class="cover-preview" id="coverPreview">
                            <button type="button" class="cover-preview-remove" id="removeCover" title="Supprimer">✕</button>
                        </div>
                    </div>

                    <!-- ── TITRE ── -->
                    <div class="form-group">
                        <label class="form-label" for="title">Titre *</label>
                        <input
                            type="text"
                            class="form-input"
                            id="title"
                            name="title"
                            placeholder="ex: One Piece Tome 1, Batman #1…"
                            required
                            maxlength="200"
                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                        >
                    </div>

                    <!-- ── ÉDITEUR + PAGES ── -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="publisher">Éditeur / Type</label>
                            <select class="form-select" id="publisher" name="publisher">
                                <option value="" <?php echo empty($_POST['publisher']) ? 'selected' : ''; ?>>
                                    — Choisir (optionnel) —
                                </option>
                                <?php foreach ($editeurs_connus as $ed): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($ed); ?>"
                                        <?php echo (($_POST['publisher'] ?? '') === $ed) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($ed); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="pagesGroup">
                            <label class="form-label" for="total_pages">Nombre de pages</label>
                            <input
                                type="number"
                                class="form-input"
                                id="total_pages"
                                name="total_pages"
                                placeholder="ex: 200"
                                min="1"
                                max="9999"
                                value="<?php echo htmlspecialchars((string)($_POST['total_pages'] ?? '')); ?>"
                            >
                            <span class="form-hint" id="pagesHint">Requis pour PDF/EPUB · Auto-détecté pour CBZ/ZIP</span>
                        </div>
                    </div>

                    <div class="upload-submit reveal">
                        <button type="submit" class="btn-red-upload" id="submitBtn">
                            <span class="btn-label">📤 Ajouter à la bibliothèque</span>
                            <span class="btn-spinner"></span>
                        </button>
                        <p class="upload-note">
                            Votre scan sera visible par tous les membres de PanelVault.
                        </p>
                    </div>

                </form>
            </div>

            <!-- ── MES UPLOADS RÉCENTS ── -->
            <?php if (count($mes_uploads) > 0): ?>
                <hr class="sep reveal" style="margin: 52px 0 36px">
                <p class="s-eyebrow reveal">Mes ajouts</p>
                <h2 class="s-title reveal" style="font-size:clamp(24px,3vw,36px); margin-bottom:20px">
                    Scans uploadés.
                </h2>

                <div class="recent-uploads-list reveal">
                    <?php foreach ($mes_uploads as $up):
                        $up_cover  = $up['cover']  ? '../../assets/img/' . $up['cover'] : '';
                        $up_title  = htmlspecialchars($up['title']);
                        $up_pub    = htmlspecialchars($up['publisher'] ?? '');
                        $up_date   = isset($up['uploaded_at']) ? date('d/m/Y', strtotime($up['uploaded_at'])) : '—';
                        $up_pages  = (int)$up['total_pages'];
                        $up_ftype  = strtoupper($up['file_type'] ?? '');
                    ?>
                        <div class="recent-item">
                            <?php if ($up_cover): ?>
                                <img
                                    src="<?php echo $up_cover; ?>"
                                    alt="<?php echo $up_title; ?>"
                                    class="recent-cover"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                >
                                <div class="recent-cover-placeholder" style="display:none">📖</div>
                            <?php else: ?>
                                <div class="recent-cover-placeholder">📖</div>
                            <?php endif; ?>

                            <div class="recent-info">
                                <div class="recent-title"><?php echo $up_title; ?></div>
                                <div class="recent-meta">
                                    <?php if ($up_pub) echo $up_pub . ' · '; ?>
                                    <?php if ($up_pages) echo $up_pages . ' pages · '; ?>
                                    <?php if ($up_ftype) echo '<strong>' . $up_ftype . '</strong> · '; ?>
                                    ajouté le <?php echo $up_date; ?>
                                </div>
                            </div>

                            <div class="recent-actions">
                                <a href="info_comics.php?id=<?php echo (int)$up['id']; ?>" class="recent-action-btn">Voir</a>
                                <a href="read_comics.php?id=<?php echo (int)$up['id']; ?>" class="recent-action-btn">Lire</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>
    </div>

</main>

<footer>
    <a class="logo" href="../../../index.php">Panel<em>Vault</em></a>
    <p>© 2025 PanelVault · Projet étudiant L1 Informatique</p>
</footer>

<script src="../../js/js-upload.js"></script>

</body>
</html>
