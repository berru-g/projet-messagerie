<?php
// Charge les variables d'environnement
$env = parse_ini_file(__DIR__ . '/.env');

if (!$env) {
    die("Erreur : Fichier .env introuvable ou invalide");
}

// Configuration avec valeurs par défaut sécurisées
$dbConfig = [
    'host' => $env['DB_HOST'] ?? 'localhost',
    'name' => $env['DB_NAME'] ?? '',
    'user' => $env['DB_USER'] ?? '',
    'pass' => $env['DB_PASS'] ?? ''
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4",
        $dbConfig['user'],
        $dbConfig['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] DB Error: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
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