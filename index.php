<?php
// Activer toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== POINT 1 ===\n"; // Affiche un message de test

// Inclusion de votre premier fichier (ex: config.php)
require 'config.php';
echo "=== POINT 2 ===\n";

echo "point 3 visible, l'erreur vient de config.php";
require_once __DIR__ . '/../includes/config.php';
require_once  'pages/home.php';
exit;
?>