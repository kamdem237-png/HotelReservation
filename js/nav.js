document.addEventListener('DOMContentLoaded', function() {
    // Éléments du menu
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const navbar = document.querySelector('.navbar');

    // Fonction pour basculer le menu
    function toggleMenu() {
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
            hamburger.classList.remove('active');
            navLinks.classList.remove('active');
        });
    });

    // Fermer le menu lors d'un clic en dehors
    document.addEventListener('click', (e) => {
        if (!navbar.contains(e.target) && navLinks.classList.contains('active')) {
            toggleMenu();
        }
    });

    // Cacher/Montrer la navbar au scroll
    let lastScroll = 0;
    const scrollThreshold = 10;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            navbar.classList.remove('hidden');
            return;
        }

        if (Math.abs(currentScroll - lastScroll) < scrollThreshold) {
            return;
        }
        
        if (currentScroll > lastScroll && !navbar.classList.contains('hidden')) {
            // Scroll vers le bas
            navbar.classList.add('hidden');
        } else if (currentScroll < lastScroll && navbar.classList.contains('hidden')) {
            // Scroll vers le haut
            navbar.classList.remove('hidden');
        }
        
        lastScroll = currentScroll;
    });

    // Gestion du bouton "Retour en haut"
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});