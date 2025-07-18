<?php
require_once  '../includes/config.php';
require_once  '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
$userId = $_SESSION['user_id'];

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

require_once  '../includes/header.php';
?>

<div class="container profile-container">
    <h2>Mon Profil</h2>
    
    <div class="profile-info">
        <p><strong>Nom d'utilisateur:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Membre depuis:</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
        <p style="text-align:right;"><a href="<?= BASE_URL ?>/pages/mon-dashboard.php" style="text-align:right;text-decoration:none;color:#ab9ff2;"><i class="fa-solid fa-chart-line"></i> Full Stats</a></p>
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

<?php require_once  '../includes/footer.php'; ?>