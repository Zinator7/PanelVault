/* Burger menu */
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

/* Header scroll */
const hdr = document.getElementById('hdr');
if (hdr) {
  window.addEventListener('scroll', () => {
    hdr.classList.toggle('scrolled', window.scrollY > 40);
  });
}

/* Compteurs animés */
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

/* Scroll reveal */
new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('visible'); }
  });
}, { threshold: 0.1 }).observe(document.body);

document.querySelectorAll('.reveal').forEach(el => {
  new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) {
      entries[0].target.classList.add('visible');
    }
  }, { threshold: 0.1 }).observe(el);
});
