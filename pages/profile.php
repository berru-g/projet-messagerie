<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

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

<div class="container profile-container">
    <h2>Mon Profil</h2>

    <div class="profile-info">
        <!-- Photo de profil -->
        <div class="profile-picture-container">
            
            <img src="<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'https://via.placeholder.com/150' ?>"
                alt="Photo de profil" class="profile-picture">
            <form method="post" enctype="multipart/form-data" class="profile-picture-form">
                <input type="file" name="profile_picture" accept="image/*" style="background-color:white;border:none;">
                <button type="submit" class="btn-small">Mettre à jour</button>
            </form>
        </div>

        <p><strong><?= htmlspecialchars($user['username']) ?></strong></p>
        <?php if (!empty($user['website_url'])): ?>
            <p>🔗<strong><a href="<?= htmlspecialchars($user['website_url']) ?>"
                    target="_blank"><?= htmlspecialchars($user['website_url']) ?></a></strong></p>
        <?php endif; ?>
        <p>✉️ <?= htmlspecialchars($user['email']) ?> </p>
        <p><strong>🏆 </strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
        <p style="text-align:right;"><a href="<?= BASE_URL ?>/pages/mon-dashboard.php"
                style="text-align:right;text-decoration:none;color:#ab9ff2;"><i class="fa-solid fa-chart-line"></i> Full
                Stats</a></p>

        <!-- Formulaire pour le site web -->
        <form method="post" class="website-form">
            <div class="form-group">
                <label for="website_url">Site web:</label>
                <input type="url" name="website_url" id="website_url"
                    value="<?= !empty($user['website_url']) ? htmlspecialchars($user['website_url']) : '' ?>"
                    placeholder="https://example.com">
                <button type="submit" class="btn-small">Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="profile-stats">
        <p><i class="fas fa-image"></i> <?= $imageCount ?></p>
        <p><i class="fas fa-file-upload"></i> <?= $fileCount ?></p>
        <p><i class="fas fa-globe"></i> <?= $publicFileCount ?></p>
        <p><i class="fas fa-comments"></i> <?= $commentCount ?></p>
        <p><i class="fas fa-heart"></i> <?= $likesReceived ?></p>
    </div>


    <div class="profile-actions">

        <a href="change-password.php" class="btn">Changer mon mot de passe</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>