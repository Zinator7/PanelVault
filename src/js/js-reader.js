/* ════════════════════════════════════════════════
   JS-READER.JS — Lecteur de scans plein écran
   PanelVault
   Supporte : PDF (PDF.js), CBZ/ZIP (images via API), EPUB (message)
   ════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

    const readerEl = document.getElementById('readerContainer');
    if (!readerEl) return;

    let currentPage  = parseInt(readerEl.dataset.currentPage, 10) || 1;
    let totalPages   = parseInt(readerEl.dataset.totalPages,  10) || 1;
    const comicId    = readerEl.dataset.comicId  || '';
    const infoUrl    = readerEl.dataset.infoUrl  || '#';
    const fileType   = readerEl.dataset.fileType || '';
    const fileUrl    = readerEl.dataset.fileUrl  || '';

    const pageNumDisplay = document.getElementById('pageNum');
    const pageSlider     = document.getElementById('pageSlider');
    const sliderLabel    = document.getElementById('sliderLabel');
    const btnPrev        = document.getElementById('btnPrev');
    const btnNext        = document.getElementById('btnNext');
    const progressLine   = document.getElementById('progressLine');
    const pageViewer     = document.getElementById('pageViewer');

    // ── État PDF.js ──
    let pdfDoc       = null;
    let pdfRendering = false;
    let pdfPending   = null;

    // ════════════════════════════════════════════════
    //  INITIALISATION selon le format du fichier
    // ════════════════════════════════════════════════
    if (fileType === 'pdf' && fileUrl) {
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            showSpinner();
            pdfjsLib.getDocument(fileUrl).promise
                .then(pdf => {
                    pdfDoc     = pdf;
                    totalPages = pdf.numPages;
                    // Synchronise le slider avec le vrai nombre de pages du PDF
                    if (pageSlider) pageSlider.max = totalPages;
                    updateUI(null);
                })
                .catch(() => showError('Impossible de charger le PDF.'));
        } else {
            showError('PDF.js non chargé — rechargez la page.');
        }
    } else {
        updateUI(null);
    }

    // ════════════════════════════════════════════════
    //  Helpers affichage
    // ════════════════════════════════════════════════
    function showSpinner() {
        if (!pageViewer) return;
        pageViewer.innerHTML = `
            <div class="reader-loading">
                <div class="reader-spinner"></div>
                <span>Chargement…</span>
            </div>`;
    }

    function showError(msg) {
        if (!pageViewer) return;
        pageViewer.innerHTML = `<div class="reader-error"><span>❌ ${msg}</span></div>`;
    }

    // ════════════════════════════════════════════════
    //  updateUI — Synchronise toute l'interface après un changement de page
    // ════════════════════════════════════════════════
    function updateUI(direction) {
        const pct = totalPages > 0 ? Math.round((currentPage / totalPages) * 100) : 0;

        if (pageNumDisplay) pageNumDisplay.textContent = `Page ${currentPage} / ${totalPages}`;
        if (pageSlider) {
            pageSlider.value = currentPage;
            pageSlider.max   = totalPages;
        }
        if (sliderLabel) sliderLabel.textContent = `${currentPage} / ${totalPages}`;
        if (progressLine) progressLine.style.width = pct + '%';

        if (btnPrev) btnPrev.disabled = (currentPage <= 1);
        if (btnNext) btnNext.disabled = (currentPage >= totalPages);

        const zPrev = document.querySelector('.reader-click-zone-prev');
        const zNext = document.querySelector('.reader-click-zone-next');
        if (zPrev) zPrev.classList.toggle('enabled', currentPage > 1);
        if (zNext) zNext.classList.toggle('enabled', currentPage < totalPages);

        renderPage(currentPage, direction);
    }

    // ════════════════════════════════════════════════
    //  renderPage — Dispatch selon le format
    // ════════════════════════════════════════════════
    function renderPage(pageNum, direction) {
        if (fileType === 'pdf' && pdfDoc) {
            renderPdfPage(pageNum);
        } else if (fileType === 'cbz' || fileType === 'zip') {
            renderCbzPage(pageNum, direction);
        } else if (fileType === 'epub') {
            renderEpubMessage();
        } else {
            renderPlaceholder(pageNum, direction);
        }
    }

    // ════════════════════════════════════════════════
    //  renderPdfPage — Rendu d'une page PDF via PDF.js
    //
    //  PDF.js fonctionne ainsi :
    //  1. pdfDoc.getPage(n) retourne une Promise<PDFPageProxy>
    //  2. page.getViewport({ scale }) calcule les dimensions de la page
    //  3. page.render({ canvasContext, viewport }) dessine sur un <canvas>
    //
    //  Le "queue" (pdfPending) évite de lancer deux rendus simultanés :
    //  si l'utilisateur navigue vite, on annule le rendu en attente et
    //  on lance directement le bon.
    // ════════════════════════════════════════════════
    function renderPdfPage(pageNum) {
        if (!pdfDoc) return;

        if (pdfRendering) {
            pdfPending = pageNum;
            return;
        }
        pdfRendering = true;
        showSpinner();

        pdfDoc.getPage(pageNum).then(page => {
            // Calcule un scale qui tient dans le viewer sans dépasser 2×
            const container = pageViewer;
            const maxW = (container ? container.clientWidth  : 0) || 800;
            const maxH = (container ? container.clientHeight : 0) || 900;
            const rawVp  = page.getViewport({ scale: 1 });
            const scaleW = maxW / rawVp.width;
            const scaleH = maxH / rawVp.height;
            const scale  = Math.min(scaleW, scaleH, 2.5);

            const viewport = page.getViewport({ scale });
            const canvas   = document.createElement('canvas');
            canvas.width   = viewport.width;
            canvas.height  = viewport.height;
            canvas.className = 'reader-pdf-canvas';

            return page.render({
                canvasContext: canvas.getContext('2d'),
                viewport
            }).promise.then(() => {
                if (pageViewer) {
                    pageViewer.innerHTML = '';
                    pageViewer.appendChild(canvas);
                }
                pdfRendering = false;
                if (pdfPending !== null) {
                    const next = pdfPending;
                    pdfPending = null;
                    renderPdfPage(next);
                }
            });
        }).catch(() => {
            showError('Erreur de rendu de la page.');
            pdfRendering = false;
        });
    }

    // ════════════════════════════════════════════════
    //  renderCbzPage — Charge une image depuis l'API PHP
    //
    //  get_cbz_page.php extrait l'image du ZIP et la retourne en HTTP.
    //  On crée un <img> côté JS et on attend onload avant de l'afficher
    //  pour éviter un flash de "image cassée".
    // ════════════════════════════════════════════════
    function renderCbzPage(pageNum, direction) {
        if (!pageViewer) return;

        showSpinner();

        const animClass = direction === 'prev' ? 'anim-left' : 'anim-right';
        const img = new Image();
        img.className = `reader-page-img ${animClass}`;
        img.alt = `Page ${pageNum}`;
        // Cache-bust minimal : évite que le navigateur réutilise une image corrompue en cache
        img.src = `../api/get_cbz_page.php?id=${comicId}&page=${pageNum}`;

        img.onload = () => {
            pageViewer.innerHTML = '';
            pageViewer.appendChild(img);
        };
        img.onerror = () => {
            showError(`Page ${pageNum} introuvable dans l'archive.`);
        };
    }

    // ── EPUB : lecteur à venir ──
    function renderEpubMessage() {
        if (!pageViewer) return;
        pageViewer.innerHTML = `
            <div class="reader-epub-msg">
                <span class="epub-big">📖</span>
                <p>Lecture EPUB disponible bientôt</p>
                <p style="opacity:0.55;font-size:0.88rem">
                    En attendant, téléchargez le fichier pour le lire dans votre lecteur.
                </p>
            </div>`;
    }

    // ── Placeholder (aucun fichier attaché) ──
    function renderPlaceholder(pageNum, direction) {
        if (!pageViewer) return;
        const animClass = direction === 'prev' ? 'anim-left' : 'anim-right';
        pageViewer.innerHTML = `
            <div class="reader-placeholder-page ${animClass}">
                <span class="placeholder-icon">📄</span>
                <span class="placeholder-page-num">${String(pageNum).padStart(2, '0')}</span>
                <span class="placeholder-label">Page ${pageNum} sur ${totalPages}</span>
            </div>`;
    }

    // ════════════════════════════════════════════════
    //  goTo — Navigation centrale (appelée par tous les contrôles)
    // ════════════════════════════════════════════════
    function goTo(page, direction) {
        const newPage = Math.max(1, Math.min(totalPages, page));
        if (newPage === currentPage && direction) return;
        currentPage = newPage;
        updateUI(direction || 'next');
        saveProgress();
    }

    // Boutons Préc. / Suiv.
    if (btnPrev) btnPrev.addEventListener('click', () => goTo(currentPage - 1, 'prev'));
    if (btnNext) btnNext.addEventListener('click', () => goTo(currentPage + 1, 'next'));

    // Zones de clic latérales
    const clickPrev = document.querySelector('.reader-click-zone-prev');
    const clickNext = document.querySelector('.reader-click-zone-next');
    if (clickPrev) clickPrev.addEventListener('click', () => goTo(currentPage - 1, 'prev'));
    if (clickNext) clickNext.addEventListener('click', () => goTo(currentPage + 1, 'next'));

    // Slider
    if (pageSlider) {
        pageSlider.addEventListener('input', () => {
            const val = parseInt(pageSlider.value, 10);
            if (sliderLabel) sliderLabel.textContent = `${val} / ${totalPages}`;
        });
        pageSlider.addEventListener('change', () => {
            goTo(parseInt(pageSlider.value, 10), null);
        });
    }

    // Raccourcis clavier
    document.addEventListener('keydown', e => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
        switch (e.key) {
            case 'ArrowRight': case 'ArrowDown': e.preventDefault(); goTo(currentPage + 1, 'next'); break;
            case 'ArrowLeft':  case 'ArrowUp':   e.preventDefault(); goTo(currentPage - 1, 'prev'); break;
            case 'Home': e.preventDefault(); goTo(1, 'prev'); break;
            case 'End':  e.preventDefault(); goTo(totalPages, 'next'); break;
            case 'Escape': if (infoUrl !== '#') window.location.href = infoUrl; break;
        }
    });

    // ════════════════════════════════════════════════
    //  saveProgress — Sauvegarde AJAX avec debounce 800 ms
    // ════════════════════════════════════════════════
    let saveTimer;
    function saveProgress() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            if (!comicId) return;
            fetch('../api/api_save_progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    comic_id:     comicId,
                    current_page: currentPage,
                    total_pages:  totalPages
                })
            }).catch(() => {});
        }, 800);
    }

    // Bouton plein écran
    const btnFullscreen = document.getElementById('btnFullscreen');
    if (btnFullscreen) {
        btnFullscreen.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen();
            }
        });
        document.addEventListener('fullscreenchange', () => {
            btnFullscreen.textContent = document.fullscreenElement ? '✕' : '⛶';
        });
    }
});
