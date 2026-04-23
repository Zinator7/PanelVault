document.addEventListener('DOMContentLoaded', () => {
    // On sélectionne le déclencheur du menu déroulant (le mini-profil)
    const userDropdownTrigger = document.querySelector('.user-trigger');
    // On sélectionne le menu déroulant lui-même
    const userDropdownMenu = document.querySelector('.user-dropdown-menu');

    // On vérifie si les éléments existent avant d'ajouter des écouteurs d'événements
    if (userDropdownTrigger && userDropdownMenu) {
        let timeout; // Variable pour gérer le délai de fermeture

        // Quand la souris entre sur le déclencheur
        userDropdownTrigger.addEventListener('mouseenter', () => {
            clearTimeout(timeout); // On annule toute fermeture en attente
            userDropdownMenu.style.display = 'flex'; // On affiche le menu
        });

        // Quand la souris quitte le déclencheur
        userDropdownTrigger.addEventListener('mouseleave', () => {
            // On met un petit délai avant de cacher le menu, pour laisser le temps de passer la souris dessus
            timeout = setTimeout(() => {
                userDropdownMenu.style.display = 'none';
            }, 200); 
        });

        // Quand la souris entre sur le menu déroulant (pour éviter qu'il se ferme si on le survole)
        userDropdownMenu.addEventListener('mouseenter', () => {
            clearTimeout(timeout); // On annule la fermeture
        });

        // Quand la souris quitte le menu déroulant
        userDropdownMenu.addEventListener('mouseleave', () => {
            timeout = setTimeout(() => {
                userDropdownMenu.style.display = 'none';
            }, 200);
        });
    }
});