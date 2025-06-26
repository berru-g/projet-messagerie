<?php
require_once  '../includes/config.php';
require_once  '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

require_once  '../includes/header.php';
?>

<div class="container profile-container">
    <h2>Mon Profil</h2>
    
    <div class="profile-info">
        <p><strong>Nom d'utilisateur:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Membre depuis:</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
    </div>
    
    <div class="profile-actions">
        <a href="change-password.php" class="btn">Changer mon mot de passe</a>
    </div>
</div>

<?php require_once  '../includes/footer.php'; ?>