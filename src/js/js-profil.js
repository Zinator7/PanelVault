document.addEventListener('DOMContentLoaded', () => {

    // ─── DROPDOWN HEADER ───────────────────────────────────────────────────────
    const trigger = document.querySelector('.user-trigger');
    const menu    = document.querySelector('.user-dropdown-menu');
    if (trigger && menu) {
        let timeout;
        trigger.addEventListener('mouseenter', () => { clearTimeout(timeout); menu.style.display = 'flex'; });
        trigger.addEventListener('mouseleave', () => { timeout = setTimeout(() => menu.style.display = 'none', 200); });
        menu.addEventListener('mouseenter',    () => clearTimeout(timeout));
        menu.addEventListener('mouseleave',    () => { timeout = setTimeout(() => menu.style.display = 'none', 200); });
    }

    // ─── REVEAL (apparition au scroll) ────────────────────────────────────────
    // IntersectionObserver : dès qu'un élément .reveal entre dans la fenêtre,
    // on lui ajoute la classe .visible qui déclenche l'animation CSS
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                revealObserver.unobserve(e.target); // on arrête d'observer une fois visible
            }
        });
    }, { threshold: 0.08 });

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    // ─── COMPTEURS ANIMÉS (.ctr) ───────────────────────────────────────────────
    // Chaque .ctr a un data-target="X" ; on anime de 0 vers X avec une ease-out
    function animateCounter(el) {
        const target   = parseInt(el.dataset.target) || 0;
        const duration = 1200;
        const start    = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    // On déclenche les compteurs quand la stat-card devient visible
    const ctrObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.querySelectorAll('.ctr').forEach(animateCounter);
                ctrObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.3 });

    document.querySelectorAll('.stat-card').forEach(card => ctrObserver.observe(card));

    // ─── ANNEAU SVG (progression XP autour de l'avatar) ───────────────────────
    // Le PHP injecte data-progress="0.XX" (valeur entre 0 et 1)
    // On modifie stroke-dashoffset pour remplir l'anneau proportionnellement
    const ringFg = document.querySelector('.ring-fg');
    if (ringFg) {
        const progress     = parseFloat(ringFg.dataset.progress) || 0;
        const circumference = 390; // 2 * PI * 62 (rayon du cercle SVG)
        const offset       = circumference - progress * circumference;
        setTimeout(() => { ringFg.style.strokeDashoffset = offset; }, 400);
    }

    // ─── CALENDRIER STREAK ────────────────────────────────────────────────────
    // window.streakData est injecté par PHP : { "2025-04-10": 2, "2025-04-11": 1, ... }
    const grid = document.getElementById('streakGrid');
    if (grid && window.streakData) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // On génère 91 jours (13 semaines) du plus ancien au plus récent
        for (let i = 90; i >= 0; i--) {
            const d   = new Date(today);
            d.setDate(d.getDate() - i);
            const key   = d.toISOString().split('T')[0]; // format YYYY-MM-DD
            const count = window.streakData[key] || 0;

            const div = document.createElement('div');
            div.className = 'streak-day';

            // Intensité de la couleur selon le nombre de lectures ce jour-là
            if      (count >= 4) div.classList.add('l4');
            else if (count >= 3) div.classList.add('l3');
            else if (count >= 2) div.classList.add('l2');
            else if (count >= 1) div.classList.add('l1');

            if (i === 0) div.classList.add('today'); // bord rouge pour aujourd'hui
            div.title = count > 0 ? `${key} — ${count} lecture(s)` : key;
            grid.appendChild(div);
        }
    }

    // ─── BARRES HISTORIQUE DE LECTURE ─────────────────────────────────────────
    // Les barres .history-pb ont transform:scaleX(0) par défaut.
    // Quand l'item devient visible, la classe .visible déclenche la transition CSS.
    // Le .reveal observer s'en charge déjà, mais on cible aussi l'item parent.
    const histObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                histObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.history-item').forEach(item => histObserver.observe(item));

});
