document.addEventListener('DOMContentLoaded', function() {
    // Éléments du menu
    const navbar = document.querySelector('.navbar');
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const backToTop = document.querySelector('.back-to-top');

    // Fonction pour basculer le menu
    function toggleMenu() {
        if (!hamburger || !navLinks) return;
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    }

    // Événement pour le bouton hamburger
    if (hamburger) {
        hamburger.addEventListener('click', toggleMenu);
    }

    // Fermer le menu lors d'un clic sur un lien
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                if (navLinks) navLinks.classList.remove('active');
                if (hamburger) hamburger.classList.remove('active');
            }
        });
    });

    // Fermer le menu lors d'un clic en dehors
    document.addEventListener('click', (e) => {
        if (navbar && navLinks && !navbar.contains(e.target) && navLinks.classList.contains('active')) {
            toggleMenu();
        }
    });

    // Cacher/Montrer la navbar au scroll
    let lastScroll = 0;
    const scrollThreshold = 200;

    window.addEventListener('scroll', () => {
        if (!navbar) return;
        const currentScroll = window.pageYOffset;

        // Gestion de la navbar (même logique que index)
        if (currentScroll > lastScroll && currentScroll > scrollThreshold) {
            navbar.classList.add('hidden');
        } else {
            navbar.classList.remove('hidden');
        }
        lastScroll = currentScroll;

        // Gestion du bouton back-to-top (même seuil que index)
        if (backToTop) {
            if (currentScroll > scrollThreshold) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }
    });

    // Gestion du bouton "Retour en haut"
    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});