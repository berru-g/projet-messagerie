<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
// Visibilité public d'un profil
$profileUserId = $_GET['user_id'] ?? $_SESSION['user_id']; // Prend l'ID de l'URL ou celui en session
$profileUser = getUserById($profileUserId);

// Remplace ensuite tous les $user par $profileUser dans ce fichier
// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mise à jour du site web
    if (isset($_POST['website_url'])) {
        $websiteUrl = filter_var($_POST['website_url'], FILTER_SANITIZE_URL);

        $stmt = $pdo->prepare("UPDATE users SET website_url = ? WHERE id = ?");
        $stmt->execute([$websiteUrl, $userId]);
        $user['website_url'] = $websiteUrl;
    }

    // Upload de la photo de profil
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExt = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;

        // Vérification du type de fichier
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($fileExt), $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                // Suppression de l'ancienne photo si elle existe
                if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }

                // Mise à jour en base de données
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$targetPath, $userId]);
                $user['profile_picture'] = $targetPath;
            }
        }
    }
}


// Nombre total de fichiers privée
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_files WHERE user_id = ?");
$stmt->execute([$userId]);
$fileCount = $stmt->fetchColumn();

// Nombre de fichiers publics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_files WHERE user_id = ? AND is_public = 1");
$stmt->execute([$userId]);
$publicFileCount = $stmt->fetchColumn();

// Nombre d'images partagées 
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_files_img WHERE user_id = ?");
$stmt->execute([$user['id']]);
$imageCount = $stmt->fetchColumn();


// Nombre de commentaires
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$userId]);
$commentCount = $stmt->fetchColumn();

// Nombre de likes reçus
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM likes 
    WHERE comment_id IN (SELECT id FROM comments WHERE user_id = ?)
");
$stmt->execute([$userId]);
$likesReceived = $stmt->fetchColumn();

require_once '../includes/header.php';
?>

<div class="profile-app">
    <!-- Header du profil -->
    <div class="profile-header">
        <div class="profile-avatar-section">
            <div class="avatar-edit">
                <img src="<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'https://ui-avatars.com/api/?name='.urlencode($user['username']).'&background=ab9ff2&color=fff' ?>" 
                     alt="Avatar de <?= htmlspecialchars($user['username']) ?>" 
                     class="avatar-image">
                
                <form method="post" enctype="multipart/form-data" class="avatar-form">
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" hidden>
                    <button type="button" class="btn-primary" onclick="document.getElementById('profile_picture').click()">
                        <i class="fas fa-camera"></i> Changer
                    </button>
                    <button type="submit" class="btn-ghost" id="submit-btn" style="display:none;">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </form>
            </div>
            
            <div class="profile-identity">
                <h1 class="profile-title"><?= htmlspecialchars($user['username']) ?></h1>
                
                <!-- Lien cliquable seulement en mode affichage -->
                <?php if (!empty($user['website_url'])): ?>
                <div class="website-display">
                    <a href="<?= htmlspecialchars($user['website_url']) ?>" target="_blank" class="website-link">
                        <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($user['website_url']) ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <span class="meta-item">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-calendar-alt"></i> Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section édition -->
    <div class="profile-edit-section">
        <h3 class="section-title"><i class="fas fa-user-cog"></i> Personnalisation</h3>
        
        <form method="post" class="website-form">
            <div class="form-group">
                <label for="website_url">Votre site web :</label>
                <div class="input-group">
                    <input type="url" name="website_url" id="website_url"
                           value="<?= !empty($user['website_url']) ? htmlspecialchars($user['website_url']) : '' ?>"
                           placeholder="https://example.com">
                    <button type="submit" class="btn-primary">Mettre à jour</button>
                </div>
            </div>
        </form>
    </div>

    <script>
// Gestion de l'affichage du bouton Enregistrer
document.getElementById('profile_picture').addEventListener('change', function() {
    if(this.files.length > 0) {
        document.getElementById('submit-btn').style.display = 'inline-block';
    }
});
</script>


    <!-- Stats sous forme de cartes -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-purple">
                <i class="fas fa-image"></i>
            </div>
            <div class="stat-info">
                <span class="stat-count"><?= $imageCount ?></span>
                <span class="stat-label">Images</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-blue">
                <i class="fas fa-file-upload"></i>
            </div>
            <div class="stat-info">
                <span class="stat-count"><?= $fileCount ?></span>
                <span class="stat-label">Fichiers</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-green">
                <i class="fas fa-globe"></i>
            </div>
            <div class="stat-info">
                <span class="stat-count"><?= $publicFileCount ?></span>
                <span class="stat-label">Publics</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-orange">
                <i class="fas fa-comment"></i>
            </div>
            <div class="stat-info">
                <span class="stat-count"><?= $commentCount ?></span>
                <span class="stat-label">Commentaires</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-red">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-info">
                <span class="stat-count"><?= $likesReceived ?></span>
                <span class="stat-label">Likes reçus</span>
            </div>
        </div>
        
        <div class="stat-card clickable" onclick="location.href='<?= BASE_URL ?>/pages/mon-dashboard.php'">
            <div class="stat-icon bg-gradient">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Voir toutes</span>
                <span class="stat-link">les statistiques <i class="fas fa-arrow-right"></i></span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="action-buttons">
        <a href="change-password.php" class="btn-primary">
            <i class="fas fa-key"></i> Changer le mot de passe
        </a>
        <a href="<?= BASE_URL ?>/pages/mon-dashboard.php" class="btn-ghost">
            <i class="fas fa-chart-pie"></i> Tableau de bord complet
        </a>
    </div>
</div>

<style>
.profile-app {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
    font-family: 'Segoe UI', system-ui, sans-serif;
}

.profile-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.profile-avatar-section {
    display: flex;
    align-items: flex-start;
    gap: 2rem;
}

.avatar-edit {
    text-align: center;
    flex-shrink: 0;
}

.avatar-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.avatar-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.profile-identity {
    flex: 1;
}

.profile-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
    color: #333;
    font-weight: 600;
}

.profile-meta {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    color: #666;
    font-size: 0.95rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.website-display {
    margin: 0.5rem 0;
}

.website-display a {
    color: #4d8af0;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    word-break: break-all;
}

.website-display a:hover {
    text-decoration: underline;
}

.profile-edit-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.section-title {
    color: #555;
    font-size: 1.2rem;
    margin: 0 0 1.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
@media (max-width: 768px) {
    .profile-avatar-section {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .avatar-form {
        flex-direction: row;
        justify-content: center;
    }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}

.stat-card.clickable {
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.bg-purple { background: #ab9ff2; }
.bg-blue { background: #4d8af0; }
.bg-green { background: #3bb873; }
.bg-orange { background: #ff914d; }
.bg-red { background: #ff5a5f; }
.bg-gradient { background: linear-gradient(135deg, #ab9ff2, #4d8af0); }

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-count {
    font-size: 1.3rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 0.8rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-link {
    font-size: 0.8rem;
    color: #ab9ff2;
    margin-top: 0.2rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

/* Boutons modernes */
.btn-primary {
    background: #ab9ff2;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #8a7bd9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(171, 159, 242, 0.3);
}

.btn-ghost {
    background: transparent;
    color: #ab9ff2;
    border: 1px solid #ab9ff2;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-ghost:hover {
    background: rgba(171, 159, 242, 0.1);
}

.input-group {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    border: 1px solid #e0e0e0;
}

.input-icon {
    color: #666;
    margin-right: 0.5rem;
}

.avatar-form {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.website-display {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.website-link {
    color: #4d8af0;
    text-decoration: none;
    word-break: break-all;
}

.website-link:hover {
    text-decoration: underline;
}

.profile-edit-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.profile-edit-section h3 {
    color: #555;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #555;
}

.input-group input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .profile-avatar-section {
        flex-direction: column;
        text-align: center;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>