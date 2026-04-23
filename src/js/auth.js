/**
 * PanelVault - Auth Dynamics
 * Gère l'animation sobre du background parallaxe
 */

const authBg = document.querySelector('.auth-bg');

if (authBg) {
    document.addEventListener('mousemove', (e) => {
        // Sensibilité du mouvement (plus le chiffre est bas, plus c'est sobre)
        const sensitivity = 15;
        const x = (e.clientX / window.innerWidth - 0.5) * sensitivity;
        const y = (e.clientY / window.innerHeight - 0.5) * sensitivity;

        // On garde le scale(1.1) du CSS pour éviter les fuites de bords
        authBg.style.transform = `scale(1.1) translate(${x}px, ${y}px)`;
    });
}