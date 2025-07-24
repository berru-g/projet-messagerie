<?php
header('Content-Type: text/plain');
echo "=== SERVEUR ===\n";
echo "PHP: ".phpversion()."\n";
echo "MySQL: ".extension_loaded('pdo_mysql') ? "OK" : "ERREUR";
echo "\n=== PERMISSIONS ===\n";
echo "index.php: ".(file_exists('index.php') ? "EXISTE" : "MANQUANT");
echo "\nDossier: ".substr(sprintf('%o', fileperms('.')), -4);