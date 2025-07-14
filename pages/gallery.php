<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

// Gestion de l'upload de fichiers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $allowedTypes = [
        'text/csv' => 'csv',
        'application/vnd.ms-excel' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        'application/json' => 'json'
    ];
    
    $fileType = $_FILES['uploaded_file']['type'];
    $fileExtension = pathinfo($_FILES['uploaded_file']['name'], PATHINFO_EXTENSION);
    
    // Vérification du type de fichier
    if (array_key_exists($fileType, $allowedTypes)) {
        $uploadDir = '../uploads/' . $user['id'] . '/';
        
        // Créer le répertoire si inexistant
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $filePath)) {
            // Enregistrement en base de données
            $stmt = $pdo->prepare("INSERT INTO user_files (user_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $user['id'],
                $_FILES['uploaded_file']['name'],
                $filePath,
                $allowedTypes[$fileType]
            ]);
            
            $_SESSION['success_message'] = "Fichier uploadé avec succès!";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'upload du fichier.";
        }
    } else {
        $_SESSION['error_message'] = "Type de fichier non supporté. Formats acceptés: CSV, Excel, JSON.";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Récupération des fichiers de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM user_files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$user['id']]);
$userFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container profile-container">
    <h2><?= htmlspecialchars($user['username']) ?></h2>
    
    <div class="profile-info">
        <h3>Ma galerie de fichiers</h3>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="file-gallery">
            <!-- Case pour uploader un nouveau fichier -->
            <div class="file-card upload-card">
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <label for="file-upload" class="upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Ajouter un fichier</span>
                        <input type="file" id="file-upload" name="uploaded_file" accept=".csv,.xlsx,.xls,.json" required>
                    </label>
                    <div class="file-types">
                        <span class="badge badge-csv">CSV</span>
                        <span class="badge badge-excel">Excel</span>
                        <span class="badge badge-json">JSON</span>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Uploader</button>
                </form>
            </div>
            
            <!-- Affichage des fichiers existants -->
            <?php foreach ($userFiles as $file): ?>
                <div class="file-card">
                    <div class="file-icon">
                        <?php switch($file['file_type']):
                            case 'csv': ?>
                                <i class="fas fa-file-csv"></i>
                                <?php break; ?>
                            <?php case 'excel': ?>
                                <i class="fas fa-file-excel"></i>
                                <?php break; ?>
                            <?php case 'json': ?>
                                <i class="fas fa-file-code"></i>
                                <?php break; ?>
                            <?php case 'googlesheet': ?>
                                <i class="fab fa-google-drive"></i>
                                <?php break; ?>
                            <?php default: ?>
                                <i class="fas fa-file"></i>
                        <?php endswitch; ?>
                    </div>
                    <div class="file-info">
                        <h5><?= htmlspecialchars($file['file_name']) ?></h5>
                        <small><?= date('d/m/Y H:i', strtotime($file['upload_date'])) ?></small>
                        <small class="file-type <?= $file['file_type'] ?>"><?= strtoupper($file['file_type']) ?></small>
                    </div>
                    <div class="file-actions">
                        <a href="<?= str_replace('../', BASE_URL.'/', $file['file_path']) ?>" download class="btn btn-sm btn-success">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-danger delete-file" data-file-id="<?= $file['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
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
</script>

<?php require_once '../includes/footer.php'; ?>