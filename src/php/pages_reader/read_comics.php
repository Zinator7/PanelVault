<?php
// ════════════════════════════════════════════════
//  READ COMICS — Lecteur de scans plein écran
//  PanelVault
//  Supporte : PDF (PDF.js), CBZ/ZIP (images via API), EPUB (message)
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

$user    = select_user($conn, $_SESSION['user']['id']);
$user_id = $user['id'];

$comic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($comic_id <= 0) {
    header("Location: bibliotheque.php");
    exit();
}

$comic = select_comic($conn, $comic_id);
if (!$comic) {
    header("Location: bibliotheque.php");
    exit();
}

$total_pages = (int)$comic['total_pages'];
$file_type   = $comic['file_type'] ?? '';
$file_path   = $comic['file_path'] ?? '';

// URL relative du fichier scan (pour PDF.js)
// Depuis read_comics.php → ../../uploads/comics/
$file_url = $file_path ? '../../uploads/comics/' . rawurlencode($file_path) : '';

$progress = select_progress($conn, $user_id, $comic_id);

if (isset($_GET['page'])) {
    $start_page = max(1, min(max($total_pages, 1), (int)$_GET['page']));
} elseif ($progress) {
    $start_page = max(1, (int)$progress['current_page']);
} else {
    $start_page = 1;
}

$title     = htmlspecialchars($comic['title']);
$publisher = htmlspecialchars($comic['publisher'] ?? '');
$info_url  = 'info_comics.php?id=' . $comic_id;

// Label du format pour affichage
$format_label = match($file_type) {
    'pdf'  => 'PDF',
    'cbz'  => 'CBZ',
    'zip'  => 'ZIP',
    'epub' => 'EPUB',
    default => '',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecture — <?php echo $title; ?> · PanelVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../../css/reader.css">
    <?php if ($file_type === 'pdf'): ?>
    <!-- PDF.js via CDN — chargé uniquement pour les fichiers PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <?php endif; ?>
    <style>
        :root {
            --red:    #E8322F;
            --ink:    #0D0C0B;
            --cream:  #F0EBE1;
            --muted:  rgba(240,235,225,0.5);
            --border: rgba(240,235,225,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Instrument Sans', sans-serif; background: var(--ink); color: var(--cream); }

        /* Affichage des pages réelles */
        .reader-pdf-canvas {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            border-radius: 2px;
        }
        .reader-page-img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            user-select: none;
            border-radius: 2px;
        }
        .reader-page-img.anim-right { animation: slideRight 0.18s ease; }
        .reader-page-img.anim-left  { animation: slideLeft  0.18s ease; }
        @keyframes slideRight { from { opacity:0; transform:translateX(16px); } to { opacity:1; transform:none; } }
        @keyframes slideLeft  { from { opacity:0; transform:translateX(-16px); } to { opacity:1; transform:none; } }

        /* États de chargement / erreur */
        .reader-loading,
        .reader-error,
        .reader-epub-msg {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 260px;
            gap: 0.9rem;
            opacity: 0.7;
            text-align: center;
            padding: 2rem;
        }
        .reader-loading span,
        .reader-error span { font-size: 1rem; }
        .reader-epub-msg .epub-big { font-size: 3.5rem; }
        .reader-epub-msg a {
            margin-top: 0.5rem;
            color: var(--red);
            text-decoration: underline;
            font-size: 0.9rem;
        }

        /* Spinner */
        .reader-spinner {
            width: 32px; height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--red);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Format badge dans la topbar */
        .reader-format-tag {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            background: var(--red);
            color: #fff;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        /* Viewer doit occuper toute la hauteur disponible */
        .reader-page-viewer {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }
    </style>
</head>
<body class="reader-page">

<div
    class="reader-layout"
    id="readerContainer"
    data-comic-id="<?php echo $comic_id; ?>"
    data-current-page="<?php echo $start_page; ?>"
    data-total-pages="<?php echo max($total_pages, 1); ?>"
    data-info-url="<?php echo $info_url; ?>"
    data-file-type="<?php echo htmlspecialchars($file_type); ?>"
    data-file-url="<?php echo htmlspecialchars($file_url); ?>"
>

    <!-- ── BARRE SUPÉRIEURE ── -->
    <div class="reader-topbar">
        <a href="<?php echo $info_url; ?>" class="reader-back" title="Retour (Échap)">
            <span class="reader-back-icon">←</span>
            <span>Retour</span>
        </a>
        <div class="reader-divider"></div>
        <div class="reader-title-bar">
            <div class="reader-comic-title">
                <?php echo $title; ?>
                <?php if ($format_label): ?>
                    <span class="reader-format-tag"><?php echo $format_label; ?></span>
                <?php endif; ?>
            </div>
            <div class="reader-page-counter" id="pageNum">
                Page <?php echo $start_page; ?> / <?php echo max($total_pages, 1); ?>
            </div>
        </div>
        <div class="reader-topbar-right">
            <button class="reader-icon-btn" id="btnFullscreen" title="Plein écran (F)">⛶</button>
            <a href="bibliotheque.php" class="reader-icon-btn" title="Bibliothèque">📚</a>
        </div>
    </div>

    <!-- ── BARRE DE PROGRESSION ── -->
    <div class="reader-progress-line">
        <div
            class="reader-progress-fill-line"
            id="progressLine"
            style="width: <?php echo $total_pages > 0 ? round(($start_page / $total_pages) * 100) : 0; ?>%"
        ></div>
    </div>

    <!-- ── ZONE DE LECTURE ── -->
    <div class="reader-main">

        <div class="reader-click-zone reader-click-zone-prev <?php echo $start_page > 1 ? 'enabled' : ''; ?>">
            <div class="click-zone-arrow">‹</div>
        </div>

        <div class="reader-page-viewer" id="pageViewer">
            <!-- Rempli dynamiquement par js-reader.js selon le format -->
            <div class="reader-loading">
                <div class="reader-spinner"></div>
                <span>Chargement…</span>
            </div>
        </div>

        <div class="reader-click-zone reader-click-zone-next <?php echo ($start_page < $total_pages || $total_pages <= 0) ? 'enabled' : ''; ?>">
            <div class="click-zone-arrow">›</div>
        </div>

    </div>

    <!-- ── BARRE DE NAVIGATION INFÉRIEURE ── -->
    <div class="reader-bottombar">
        <button class="reader-nav-btn" id="btnPrev" <?php echo $start_page <= 1 ? 'disabled' : ''; ?>>
            ← Préc.
        </button>

        <div class="reader-slider-wrap">
            <span class="slider-page-label" id="sliderLabel">
                <?php echo $start_page; ?> / <?php echo max($total_pages, 1); ?>
            </span>
            <input
                type="range"
                class="reader-page-slider"
                id="pageSlider"
                min="1"
                max="<?php echo max($total_pages, 1); ?>"
                value="<?php echo $start_page; ?>"
                step="1"
            >
        </div>

        <button class="reader-nav-btn" id="btnNext" <?php echo ($total_pages > 0 && $start_page >= $total_pages) ? 'disabled' : ''; ?>>
            Suiv. →
        </button>

        <span class="reader-hint">← → · Échap = retour</span>
    </div>

</div>

<script src="../../js/js-reader.js"></script>

</body>
</html>
