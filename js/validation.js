// Validation des formulaires côté client
document.addEventListener('DOMContentLoaded', function() {
    // Validation du formulaire d'inscription
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const email = document.getElementById('email').value;
            
            // Validation du mot de passe
            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }

            // Validation de la confirmation du mot de passe
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }

            // Validation basique de l'email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
        });
    }

    // Validation du formulaire de réservation
    const reservationForm = document.querySelector('.reservation-form');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            const checkIn = new Date(document.getElementById('check-in').value);
            const checkOut = new Date(document.getElementById('check-out').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Vérification des dates
            if (checkIn < today) {
                e.preventDefault();
                alert('La date d\'arrivée ne peut pas être dans le passé.');
                return;
            }

            if (checkOut <= checkIn) {
                e.preventDefault();
                alert('La date de départ doit être postérieure à la date d\'arrivée.');
                return;
            }

            // Vérification du nombre de personnes
            const adults = parseInt(document.getElementById('adults').value);
            const children = parseInt(document.getElementById('children').value);
            
            if (adults < 1) {
                e.preventDefault();
                alert('Il doit y avoir au moins 1 adulte.');
                return;
            }

            if (children < 0) {
                e.preventDefault();
                alert('Le nombre d\'enfants ne peut pas être négatif.');
                return;
            }
        });
    }

    // Animations des champs de formulaire
    const formInputs = document.querySelectorAll('input');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });

        // Initialisation de l'état focused si le champ a une valeur
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
});