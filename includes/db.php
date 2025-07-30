<?php
// Chemin absolu pour éviter tout problème
$env_file = __DIR__ . '/.env';

// Charge le .env si existe, sinon utilise les valeurs par défaut
if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
} else {
    die("Fichier .env manquant !");
}

// Configuration avec fallback sécurisé
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? '';
$dbPass = $env['DB_PASS'] ?? '';

// Vérification des credentials
if (empty($dbName) || empty($dbUser)) {
    die("Configuration DB incomplète dans .env");
}

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Optionnel : message de test (à retirer en production)
    // echo "Connexion réussie !";
    
} catch (PDOException $e) {
    // Message générique en production
    die("Erreur de connexion à la base de données");
    // Pour le debug : die("Erreur DB: " . $e->getMessage());
}

/*
require_once  'config.php';

$host = 'localhost';
$dbname = 'messagerie_collegues';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}*/
?>