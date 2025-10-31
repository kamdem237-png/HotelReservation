document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des dates minimales pour le formulaire de recherche
    const checkInInput = document.getElementById('check-in');
    const checkOutInput = document.getElementById('check-out');
    
    if (checkInInput && checkOutInput) {
        // Définir la date minimale à aujourd'hui
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        // Formatage des dates pour l'input
        const formatDate = (date) => {
            const d = new Date(date);
            let month = '' + (d.getMonth() + 1);
            let day = '' + d.getDate();
            const year = d.getFullYear();
            
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            
            return [year, month, day].join('-');
        };
        
        checkInInput.min = formatDate(today);
        checkOutInput.min = formatDate(tomorrow);
        
        // Mise à jour de la date minimale de départ lors du changement de la date d'arrivée
        checkInInput.addEventListener('change', function() {
            const nextDay = new Date(this.value);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.min = formatDate(nextDay);
            
            if (checkOutInput.value && new Date(checkOutInput.value) <= new Date(this.value)) {
                checkOutInput.value = formatDate(nextDay);
            }
        });
    }
    
    // Animation des cartes de chambres au scroll
    const roomCards = document.querySelectorAll('.room-card');
    
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    roomCards.forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });
    
    // Filtrage des chambres en fonction de la capacité
    const adultsInput = document.getElementById('adults');
    const childrenInput = document.getElementById('children');
    
    function updateRoomVisibility() {
        const totalGuests = parseInt(adultsInput.value) + parseInt(childrenInput.value);
        
        roomCards.forEach(card => {
            const capacity = parseInt(card.dataset.capacity);
            if (capacity < totalGuests) {
                card.classList.add('hidden');
            } else {
                card.classList.remove('hidden');
            }
        });
    }
    
    if (adultsInput && childrenInput) {
        adultsInput.addEventListener('change', updateRoomVisibility);
        childrenInput.addEventListener('change', updateRoomVisibility);
    }
});