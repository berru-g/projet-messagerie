<?php

// Chemin absolu pour être sûr
$envPath = __DIR__.'/.env';

// Vérifie si le fichier .env existe
if (!file_exists($envPath)) {
    die("Fichier .env introuvable à l'emplacement : ".$envPath);
}

// Charge le fichier en forçant le mode scalar (valeurs simples)
$env = parse_ini_file($envPath, false, INI_SCANNER_TYPED);

// Vérifie les clés obligatoires
$requiredKeys = ['dbHost', 'dbName', 'dbUser', 'dbPass', 'dbCharset'];
foreach ($requiredKeys as $key) {
    if (!isset($env[$key])) {
        die("Clé $key manquante dans le .env");
    }
}

try {
    $pdo = new PDO(
        "mysql:host={$env['dbHost']};dbname={$env['dbName']};charset={$env['dbCharset']}",
        $env['dbUser'],
        $env['dbPass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // TEST : Si tu arrives ici, la connexion marche
    echo "Connexion réussie !";
    
} catch (PDOException $e) {
    die("Erreur de connexion MySQL : " . $e->getMessage());
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