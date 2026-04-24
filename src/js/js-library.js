/* ════════════════════════════════════════════════
   JS-LIBRARY.JS — Bibliothèque : filtres & recherche
   PanelVault
   ════════════════════════════════════════════════ */

// DOMContentLoaded se déclenche quand le navigateur a fini de construire le DOM
// (tous les éléments HTML sont disponibles). Sans ça, querySelector() renverrait null
// car les éléments n'existeraient pas encore au moment où le JS s'exécute.
document.addEventListener('DOMContentLoaded', () => {

    // --- SÉLECTION DES ÉLÉMENTS ---

    // querySelectorAll() retourne une NodeList (liste) de tous les éléments qui
    // correspondent au sélecteur CSS. Ici : toutes les cartes .comic-card.
    const cards       = document.querySelectorAll('.comic-card');
    const tabs        = document.querySelectorAll('.filter-tab');
    // getElementById() retourne UN seul élément (ou null s'il n'existe pas)
    const searchInput = document.getElementById('libSearch');
    const noResults   = document.getElementById('libNoResults');

    // Variables d'état du module : quel filtre est actif et quel mot recherche-t-on.
    // "let" = variable modifiable, "const" = constante (non réassignable).
    let activeFilter = 'all';
    let searchQuery  = '';

    // ════════════════════════════════════════════════
    //  FONCTION PRINCIPALE : applyFilters()
    //  Parcourt toutes les cartes et décide laquelle
    //  montrer ou cacher selon l'onglet + la recherche.
    // ════════════════════════════════════════════════
    function applyFilters() {
        let visibleCount = 0;

        // forEach() est une méthode de tableau/NodeList qui exécute une fonction
        // pour chaque élément. card => {...} est une "arrow function" (fonction fléchée),
        // c'est une syntaxe courte pour function(card) {...}
        cards.forEach(card => {

            // dataset donne accès aux attributs data-* du HTML.
            // <div data-status="reading"> → card.dataset.status vaut "reading"
            // <div data-mine="1">        → card.dataset.mine   vaut "1"
            const status = card.dataset.status;
            const mine   = card.dataset.mine;
            const titleRaw = card.dataset.title    || ''; // || '' = valeur par défaut si undefined
            const pubRaw   = card.dataset.publisher || '';

            // Vérifie si la carte correspond à l'onglet actif
            let tabOk = false;
            if (activeFilter === 'all')     tabOk = true;              // tout afficher
            if (activeFilter === 'reading') tabOk = (status === 'reading');
            if (activeFilter === 'done')    tabOk = (status === 'done');
            if (activeFilter === 'mine')    tabOk = (mine === '1');    // uploadé par moi

            // ─── RECHERCHE INSENSIBLE À LA CASSE ET AUX ACCENTS ───
            //
            // toLowerCase() : "Batman" → "batman" (ignore la casse)
            //
            // normalize('NFD') : décompose les lettres accentuées en leur base + accent séparé.
            // Exemple : 'é' (1 caractère) devient 'e' + '´' (2 caractères).
            //
            // .replace(/[̀-ͯ]/g, '') : supprime TOUS les caractères "accent seul"
            // (unicode range U+0300 à U+036F = tous les diacritiques combinants).
            // Résultat : 'é' → 'e', 'ç' → 'c', 'à' → 'a', etc.
            //
            // Pourquoi ? Pour que "batman" trouve "Batman" et aussi "Bätman".
            const normalise = str => str.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
            const q        = normalise(searchQuery);
            // includes() retourne true si la chaîne contient q (même partielle)
            const searchOk = q === '' ||
                             normalise(titleRaw).includes(q) ||
                             normalise(pubRaw).includes(q);

            // Une carte est visible si elle passe LES DEUX filtres (onglet ET recherche)
            if (tabOk && searchOk) {
                card.style.display = ''; // '' = remet la valeur CSS par défaut (affiche la carte)
                visibleCount++;
            } else {
                card.style.display = 'none'; // Cache la carte
            }
        });

        // classList.toggle(class, condition) :
        // → ajoute la classe si condition === true
        // → la retire si condition === false
        // Ici, on affiche le message "aucun résultat" seulement si visibleCount === 0
        if (noResults) {
            noResults.classList.toggle('visible', visibleCount === 0);
        }
    }

    // ════════════════════════════════════════════════
    //  ONGLETS DE FILTRE
    // ════════════════════════════════════════════════
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Retire la classe "active" de TOUS les onglets
            tabs.forEach(t => t.classList.remove('active'));
            // L'ajoute seulement sur celui qui vient d'être cliqué
            tab.classList.add('active');
            // data-filter="reading" → dataset.filter vaut "reading"
            activeFilter = tab.dataset.filter;
            applyFilters();
        });
    });

    // ════════════════════════════════════════════════
    //  RECHERCHE EN TEMPS RÉEL AVEC DEBOUNCE
    //
    //  Le DEBOUNCE est une technique pour éviter d'exécuter une fonction
    //  trop souvent. Sans debounce, applyFilters() serait appelée pour
    //  CHAQUE lettre tapée. Avec le debounce, on attend 160ms après la
    //  dernière frappe avant d'agir (comme attendre que l'utilisateur
    //  ait fini de taper un mot).
    //
    //  Fonctionnement :
    //  1. L'utilisateur tape une lettre → on lance un timer de 160ms
    //  2. Il tape une autre lettre → on ANNULE le timer (clearTimeout)
    //     et on en relance un nouveau de 160ms
    //  3. S'il ne tape rien pendant 160ms → le timer se déclenche
    //     et applyFilters() s'exécute UNE seule fois
    // ════════════════════════════════════════════════
    let searchTimer; // Référence au timer (pour pouvoir l'annuler)
    if (searchInput) {
        // L'événement 'input' se déclenche à chaque changement du champ (frappe, coller, etc.)
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer); // Annule le timer précédent s'il existe
            searchTimer = setTimeout(() => {
                // trim() supprime les espaces au début et à la fin de la chaîne
                searchQuery = searchInput.value.trim();
                applyFilters();
            }, 160); // 160ms de délai
        });

        // Vider la recherche avec la touche Échap (UX standard)
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchQuery = '';
                applyFilters();
                searchInput.blur(); // blur() = retire le focus du champ (le "déselectionne")
            }
        });

        // Raccourci Ctrl+K (ou Cmd+K sur Mac) pour ouvrir la recherche
        // — même comportement que GitHub, VS Code, Notion, etc.
        document.addEventListener('keydown', e => {
            // e.ctrlKey = true si Ctrl est appuyé | e.metaKey = true si Cmd (Mac) est appuyé
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault(); // Empêche le comportement par défaut du navigateur (ex: ouvrir la barre d'adresse)
                searchInput.focus();  // focus() = met le curseur dans le champ
                searchInput.select(); // select() = sélectionne tout le texte dans le champ
            }
        });
    }

    // ════════════════════════════════════════════════
    //  INTERSECTION OBSERVER — Barres de progression
    //
    //  L'IntersectionObserver est une API du navigateur qui "surveille"
    //  des éléments et te prévient quand ils entrent ou sortent de l'écran.
    //  C'est bien plus performant que d'écouter l'événement 'scroll' en
    //  permanence, car le navigateur fait le travail en arrière-plan.
    //
    //  Ici, on l'utilise pour animer la barre de progression SEULEMENT
    //  quand la carte est visible à l'écran (pas au chargement de la page
    //  pour des cartes qui sont en bas — on ne les voit pas encore).
    // ════════════════════════════════════════════════
    const progressFills = document.querySelectorAll('.comic-progress-fill');

    // 'IntersectionObserver' in window : vérifie que le navigateur supporte cette API
    // (anciens navigateurs ne l'ont pas — sécurité)
    if (progressFills.length > 0 && 'IntersectionObserver' in window) {

        const observer = new IntersectionObserver(entries => {
            // entries = liste des éléments qui ont changé d'état (entré/sorti du viewport)
            entries.forEach(entry => {
                // isIntersecting = true si l'élément EST VISIBLE dans la fenêtre
                if (entry.isIntersecting) {
                    const fill = entry.target;           // L'élément observé
                    const pct  = fill.dataset.pct || '0'; // Lit data-pct="35"
                    // On applique la width → la transition CSS prend le relais et anime 0% → 35%
                    fill.style.width = pct + '%';
                    // unobserve = arrête de surveiller cet élément (inutile de l'animer 2 fois)
                    observer.unobserve(fill);
                }
            });
        }, {
            threshold: 0.3 // Déclenche quand 30% de l'élément est visible (0 = dès qu'il apparaît, 1 = totalement visible)
        });

        // On commence à surveiller chaque barre de progression
        progressFills.forEach(fill => observer.observe(fill));
    }

    // ════════════════════════════════════════════════
    //  DROPDOWN HEADER (menu utilisateur)
    //  Toggle = afficher/cacher en alternance au clic
    // ════════════════════════════════════════════════
    const trigger  = document.querySelector('.user-trigger');
    const dropdown = document.querySelector('.user-dropdown-menu');
    if (trigger && dropdown) {
        trigger.addEventListener('click', e => {
            e.preventDefault();   // Empêche la navigation vers href="#"
            e.stopPropagation();  // Empêche l'événement de "remonter" au document
                                  // (sinon le listener document.click le fermerait aussitôt)
            const isOpen = dropdown.style.display === 'flex';
            dropdown.style.display = isOpen ? 'none' : 'flex';
        });
        // Ferme le dropdown si on clique ailleurs sur la page
        document.addEventListener('click', () => {
            if (dropdown) dropdown.style.display = 'none';
        });
    }

    // ════════════════════════════════════════════════
    //  MENU HAMBURGER MOBILE
    //  classList.toggle('open') : ajoute 'open' si absent, la retire si présente
    // ════════════════════════════════════════════════
    const burger     = document.getElementById('burger');
    const mobileMenu = document.getElementById('mobileMenu');
    if (burger && mobileMenu) {
        burger.addEventListener('click', () => {
            burger.classList.toggle('open');
            mobileMenu.classList.toggle('open');
        });
    }
    // Exposée sur window pour être appelable depuis les attributs onclick="closeMenu()" du HTML
    window.closeMenu = () => {
        if (burger)     burger.classList.remove('open');
        if (mobileMenu) mobileMenu.classList.remove('open');
    };

    // ════════════════════════════════════════════════
    //  SCROLL REVEAL
    //  Même principe que les barres de progression :
    //  l'élément s'anime en fondu/slide quand il entre dans le viewport.
    //  La classe .reveal (CSS) définit l'état de départ (opacity:0, translateY:32px).
    //  La classe .visible (CSS) définit l'état final (opacity:1, translateY:0).
    // ════════════════════════════════════════════════
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length > 0 && 'IntersectionObserver' in window) {
        const revealObs = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObs.unobserve(entry.target); // Pas besoin de surveiller après l'animation
                }
            });
        }, { threshold: 0.1 }); // Déclenche dès que 10% est visible
        revealEls.forEach(el => revealObs.observe(el));
    }

    // ════════════════════════════════════════════════
    //  HEADER SCROLL
    //  Ajoute la classe "scrolled" au header dès qu'on scrolle de 20px.
    //  Ce qui déclenche le fond noir semi-transparent (cf style.css).
    // ════════════════════════════════════════════════
    const hdr = document.getElementById('hdr');
    if (hdr) {
        // { passive: true } = indication au navigateur que ce listener ne bloquera pas le scroll
        // → améliore les performances (scroll plus fluide, surtout sur mobile)
        window.addEventListener('scroll', () => {
            hdr.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    // Appel initial pour appliquer les filtres dès le chargement
    // (au cas où des paramètres d'URL pré-rempliraient la recherche en V2)
    applyFilters();
});
