<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php'; // Cette ligne doit venir AVANT d'utiliser isLoggedIn()

// Le reste de votre code...
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <header class="fixed-header">
        <div class="header-content">
            <div class="profile-dropdown">
                <button class="profile-btn">
                    <i class="fas fa-user"></i>
                </button>
                <div class="dropdown-content">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user-circle"></i> Profil</a>
                        <a href="<?= BASE_URL ?>/pages/change-password.php"><i class="fas fa-key"></i> Changer mot de passe</a>
                        <a href="<?= BASE_URL ?>/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/pages/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                        <a href="<?= BASE_URL ?>/pages/register.php"><i class="fas fa-user-plus"></i> Inscription</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <h1><?= SITE_NAME ?></h1>
            
            <div class="menu-dropdown">
                <button class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="dropdown-content">
                    <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Accueil</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= BASE_URL ?>/pages/profile.php"><i class="fas fa-user"></i> Profil</a>
                        <a href="#comments"><i class="fas fa-comment"></i> Commentaires</a>
                        <a href="#likes"><i class="fas fa-heart"></i> Likes</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main>