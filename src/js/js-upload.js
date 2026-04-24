/* ════════════════════════════════════════════════
   JS-UPLOAD.JS — Upload d'un scan + drag & drop
   PanelVault
   ════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

    // ─── ÉLÉMENTS DOM ───
    // Zone scan (obligatoire)
    const dropZoneScan = document.getElementById('dropZoneScan');
    const scanInput    = document.getElementById('scanFile');
    const scanIcon     = document.getElementById('scanIcon');
    const scanTitle    = document.getElementById('scanTitle');
    const scanDesc     = document.getElementById('scanDesc');

    // Zone couverture (optionnelle)
    const dropZone    = document.getElementById('dropZone');
    const coverInput  = document.getElementById('coverFile');
    const previewWrap = document.getElementById('coverPreviewWrap');
    const previewImg  = document.getElementById('coverPreview');
    const removeBtn   = document.getElementById('removeCover');
    const dropIcon    = document.getElementById('dropIcon');
    const dropTitle   = document.getElementById('dropTitle');
    const dropDesc    = document.getElementById('dropDesc');

    // Champ pages + hint
    const pagesInput  = document.getElementById('total_pages');
    const pagesGroup  = document.getElementById('pagesGroup');
    const pagesHint   = document.getElementById('pagesHint');

    // Icônes par extension de fichier scan
    const SCAN_ICONS = { pdf: '📄', epub: '📖', cbz: '🗜️', zip: '🗜️', cbr: '📦' };
    // Formats où les pages sont auto-détectées côté serveur
    const AUTO_PAGES = ['cbz', 'zip'];

    // ── Affiche les infos du fichier scan sélectionné ──
    function showScanInfo(file) {
        const ext    = file.name.split('.').pop().toLowerCase();
        const sizeMB = (file.size / 1024 / 1024).toFixed(1);

        if (scanIcon)  scanIcon.textContent  = SCAN_ICONS[ext] || '📂';
        if (scanTitle) scanTitle.textContent = file.name;
        if (scanDesc)  scanDesc.textContent  = `${sizeMB} Mo · ${ext.toUpperCase()}`;
        if (dropZoneScan) dropZoneScan.classList.add('has-file');

        // Adapte le champ "pages" selon le format
        if (pagesHint) {
            if (AUTO_PAGES.includes(ext)) {
                pagesHint.textContent = '✅ Pages auto-détectées depuis le fichier';
                if (pagesInput) {
                    pagesInput.removeAttribute('required');
                    pagesInput.placeholder = 'Auto-détecté';
                }
            } else {
                pagesHint.textContent = 'Requis pour PDF/EPUB · entrez le nombre exact';
                if (pagesInput) {
                    pagesInput.setAttribute('required', 'required');
                    pagesInput.placeholder = 'ex: 200';
                }
            }
        }
    }

    function clearScanInfo() {
        if (scanIcon)    scanIcon.textContent  = '📂';
        if (scanTitle)   scanTitle.textContent = 'Déposer le fichier ici';
        if (scanDesc)    scanDesc.innerHTML    = 'ou <strong>cliquer pour parcourir</strong>';
        if (dropZoneScan) dropZoneScan.classList.remove('has-file');
        if (scanInput)   scanInput.value       = '';
        if (pagesHint)   pagesHint.textContent = 'Requis pour PDF/EPUB · Auto-détecté pour CBZ/ZIP';
        if (pagesInput) {
            pagesInput.removeAttribute('required');
            pagesInput.placeholder = 'ex: 200';
        }
    }

    // ── Input scan : changement classique ──
    if (scanInput) {
        scanInput.addEventListener('change', () => {
            if (scanInput.files[0]) showScanInfo(scanInput.files[0]);
            else clearScanInfo();
        });
    }

    // ── Drag & Drop sur la zone scan ──
    if (dropZoneScan) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            dropZoneScan.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
        });
        dropZoneScan.addEventListener('dragenter', () => dropZoneScan.classList.add('drag-over'));
        dropZoneScan.addEventListener('dragover',  () => dropZoneScan.classList.add('drag-over'));
        dropZoneScan.addEventListener('dragleave', () => dropZoneScan.classList.remove('drag-over'));
        dropZoneScan.addEventListener('drop', e => {
            dropZoneScan.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && scanInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                scanInput.files = dt.files;
                showScanInfo(file);
            }
        });
    }

    // ══════════════════════════════════════════════
    //  COUVERTURE (optionnelle)
    // ══════════════════════════════════════════════
    function showCoverPreview(file) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            if (previewImg)  previewImg.src = e.target.result;
            if (previewWrap) previewWrap.classList.add('visible');
            if (dropZone)    dropZone.classList.add('has-file');
            if (dropIcon)  dropIcon.textContent = '✅';
            if (dropTitle) dropTitle.textContent = file.name;
            if (dropDesc)  dropDesc.textContent  = (file.size / 1024).toFixed(0) + ' Ko';
        };
        reader.readAsDataURL(file);
    }

    function clearCoverPreview() {
        if (previewImg)  previewImg.src = '';
        if (previewWrap) previewWrap.classList.remove('visible');
        if (dropZone)    dropZone.classList.remove('has-file');
        if (coverInput)  coverInput.value = '';
        if (dropIcon)  dropIcon.textContent = '🖼️';
        if (dropTitle) dropTitle.textContent = 'Déposer la couverture ici';
        if (dropDesc)  dropDesc.innerHTML   = 'ou <strong>cliquer pour parcourir</strong>';
    }

    if (coverInput) {
        coverInput.addEventListener('change', () => {
            if (coverInput.files[0]) showCoverPreview(coverInput.files[0]);
        });
    }
    if (removeBtn) removeBtn.addEventListener('click', clearCoverPreview);

    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            dropZone.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); });
        });
        dropZone.addEventListener('dragenter', () => dropZone.classList.add('drag-over'));
        dropZone.addEventListener('dragover',  () => dropZone.classList.add('drag-over'));
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', e => {
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && coverInput) {
                const dt = new DataTransfer();
                dt.items.add(file);
                coverInput.files = dt.files;
                showCoverPreview(file);
            }
        });
    }

    // ══════════════════════════════════════════════
    //  VALIDATION + SOUMISSION DU FORMULAIRE
    // ══════════════════════════════════════════════
    const form      = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const formMsg   = document.getElementById('formMsg');

    if (form) {
        form.addEventListener('submit', e => {
            // Le fichier scan est obligatoire
            if (!scanInput || scanInput.files.length === 0) {
                e.preventDefault();
                showMessage('error', '📂 Veuillez sélectionner un fichier scan (PDF, CBZ, ZIP ou EPUB).');
                return;
            }

            const ext = scanInput.files[0].name.split('.').pop().toLowerCase();

            // Pour PDF/EPUB, vérifier que le nombre de pages est renseigné
            if (!AUTO_PAGES.includes(ext)) {
                const pages = parseInt(pagesInput ? pagesInput.value : '', 10);
                if (!pages || pages <= 0) {
                    e.preventDefault();
                    showMessage('error', '📋 Veuillez indiquer le nombre de pages pour les fichiers PDF/EPUB.');
                    return;
                }
            }

            // Feedback visuel
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    function showMessage(type, text) {
        if (!formMsg) return;
        formMsg.className = `form-msg show ${type}`;
        formMsg.innerHTML = `<span class="form-msg-icon">${type === 'success' ? '✅' : '❌'}</span> ${text}`;
        formMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Messages flash injectés par PHP
    const flashType = document.body.dataset.flash;
    const flashMsg  = document.body.dataset.flashMsg;
    if (flashType && flashMsg) showMessage(flashType, flashMsg);

    // ─── DROPDOWN HEADER ───
    const trigger  = document.querySelector('.user-trigger');
    const dropdown = document.querySelector('.user-dropdown-menu');
    if (trigger && dropdown) {
        trigger.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
        });
        document.addEventListener('click', () => {
            if (dropdown) dropdown.style.display = 'none';
        });
    }

    // ─── BURGER MENU ───
    const burger     = document.getElementById('burger');
    const mobileMenu = document.getElementById('mobileMenu');
    if (burger && mobileMenu) {
        burger.addEventListener('click', () => {
            burger.classList.toggle('open');
            mobileMenu.classList.toggle('open');
        });
    }
    window.closeMenu = () => {
        if (burger) burger.classList.remove('open');
        if (mobileMenu) mobileMenu.classList.remove('open');
    };

    // ─── SCROLL REVEAL ───
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length && 'IntersectionObserver' in window) {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.1 });
        revealEls.forEach(el => obs.observe(el));
    }

    // ─── HEADER SCROLL ───
    const hdr = document.getElementById('hdr');
    if (hdr) {
        window.addEventListener('scroll', () => {
            hdr.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }
});
