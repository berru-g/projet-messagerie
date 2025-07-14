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

// Gestion du partage
document.querySelectorAll('.toggle-share').forEach(button => {
    button.addEventListener('click', function() {
        const fileId = this.getAttribute('data-file-id');
        
        fetch('../includes/toggle_share.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'file_id=' + fileId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const icon = this.querySelector('i');
                if (data.is_public) {
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-success');
                    icon.classList.remove('fa-lock');
                    icon.classList.add('fa-lock-open');
                    this.title = 'Public';
                } else {
                    this.classList.remove('btn-success');
                    this.classList.add('btn-secondary');
                    icon.classList.remove('fa-lock-open');
                    icon.classList.add('fa-lock');
                    this.title = 'Privé';
                }
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la suppression de fichiers
    document.querySelectorAll('.delete-file').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const fileId = this.getAttribute('data-file-id');
            
            if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
                fetch('../includes/delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'file_id=' + fileId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.file-card').remove();
                    } else {
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue');
                });
            }
        });
    });
});