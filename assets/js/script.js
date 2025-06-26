// Script pour le menu déroulant sur mobile
document.addEventListener('DOMContentLoaded', function() {
    // Scroll vers le bas de la page pour voir les nouveaux messages
    window.scrollTo(0, document.body.scrollHeight);
    
    // Gestion des likes sans rechargement de page
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            
            fetch(url)
                .then(response => response.text())
                .then(() => {
                    window.location.reload();
                });
        });
    });
});