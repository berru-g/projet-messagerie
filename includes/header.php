<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php'; // Cette ligne doit venir AVANT d'utiliser isLoggedIn()

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    
</head>
<body>
    <header class="fixed-header">
        <div class="header-content">
            <div class="profile-dropdown">
                <button class="profile-btn">
                    <i class="fas fa-user-circle"></i>
                </button>
                <div class="dropdown-content">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['username']) ?></a>
                        <a href="<?= BASE_URL ?>/pages/change-password.php"><i class="fas fa-key"></i> Changer mot de passe</a>
                        <a href="<?= BASE_URL ?>/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <h1><?= SITE_NAME ?></h1>
            
            <div class="menu-dropdown">
                <button class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="dropdown-content">
                    <a href="<?= BASE_URL ?>"><i class="fa-solid fa-comments"></i> Comment</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/pages/view_file.php"><i class="fa-solid fa-magnifying-glass"></i> Search</a>
                        <a href="<?= BASE_URL ?>/pages/gallery.php"><i class="fas fa-download"></i> Upload</a>
                        <a href="<?= BASE_URL ?>/pages/facture.php"><i class="fa-solid fa-receipt"></i> Create Invoice</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main>