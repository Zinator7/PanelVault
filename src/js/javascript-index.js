/* ═══ SCROLL PROGRESS BAR ═══ */
const scrollBar = document.createElement('div');
scrollBar.className = 'scroll-progress';
document.body.prepend(scrollBar);
window.addEventListener('scroll', () => {
  const total = document.documentElement.scrollHeight - window.innerHeight;
  if (total > 0) scrollBar.style.width = (window.scrollY / total * 100) + '%';
}, { passive: true });

/* ═══ BURGER MENU ═══ */
const burger = document.getElementById('burger');
const mobileMenu = document.getElementById('mobileMenu');

if (burger && mobileMenu) {
  burger.addEventListener('click', () => {
    burger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
  });
}

function closeMenu() {
  if (burger) burger.classList.remove('open');
  if (mobileMenu) mobileMenu.classList.remove('open');
}

/* ═══ HEADER SCROLL ═══ */
const hdr = document.getElementById('hdr');
if (hdr) {
  window.addEventListener('scroll', () => {
    hdr.classList.toggle('scrolled', window.scrollY > 40);
  }, { passive: true });
}

/* ═══ PARALLAXE COVERS HERO ═══ */
const coversMosaic = document.querySelector('.covers');
if (coversMosaic) {
  document.addEventListener('mousemove', e => {
    const x = (e.clientX / window.innerWidth  - 0.5) * 16;
    const y = (e.clientY / window.innerHeight - 0.5) * 10;
    coversMosaic.style.transform = `scale(1.04) translate(${x}px, ${y}px)`;
  }, { passive: true });

  const heroEl = document.querySelector('.hero');
  if (heroEl) {
    heroEl.addEventListener('mouseleave', () => {
      coversMosaic.style.transform = 'scale(1.04)';
    });
  }
}

/* ═══ COMPTEURS ANIMÉS ═══ */
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const dur = 2000;
  const start = performance.now();
  function step(now) {
    const p = Math.min((now - start) / dur, 1);
    const ease = 1 - Math.pow(1 - p, 4);
    el.textContent = Math.floor(ease * target).toLocaleString('fr-FR');
    if (p < 1) requestAnimationFrame(step);
    else el.textContent = target.toLocaleString('fr-FR');
  }
  requestAnimationFrame(step);
}

let done1 = false, done2 = false;

const heroStats = document.querySelector('.hero-stats-mini');
if (heroStats) {
  new IntersectionObserver(e => {
    if (e[0].isIntersecting && !done1) {
      done1 = true;
      document.querySelectorAll('.ctr').forEach(animateCounter);
    }
  }, { threshold: 0.3 }).observe(heroStats);
}

const statsSection = document.querySelector('.stats-full');
if (statsSection) {
  new IntersectionObserver(e => {
    if (e[0].isIntersecting && !done2) {
      done2 = true;
      document.querySelectorAll('.ctr2').forEach(animateCounter);
    }
  }, { threshold: 0.3 }).observe(statsSection);
}

/* Compteurs dashboard — stats-grid */
const statsGrid = document.querySelector('.stats-grid');
if (statsGrid) {
  let doneGrid = false;
  new IntersectionObserver(e => {
    if (e[0].isIntersecting && !doneGrid) {
      doneGrid = true;
      statsGrid.querySelectorAll('.ctr').forEach(animateCounter);
    }
  }, { threshold: 0.2 }).observe(statsGrid);
}

/* Compteurs dashboard — library-summary */
const libSummary = document.querySelector('.library-summary');
if (libSummary) {
  let doneLib = false;
  new IntersectionObserver(e => {
    if (e[0].isIntersecting && !doneLib) {
      doneLib = true;
      libSummary.querySelectorAll('.ctr').forEach(animateCounter);
    }
  }, { threshold: 0.2 }).observe(libSummary);
}

/* ═══ SCROLL REVEAL ═══ */
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => {
  revealObserver.observe(el);
});
