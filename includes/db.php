<?php

try {
    $dbConfig = require __DIR__ . '/../db_config.php';
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8",
        $dbUser,
        $dbPass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // En production, on ne montre pas le message exact
    error_log("Erreur de connexion BDD : " . $e->getMessage()); // Log serveur
    http_response_code(500); // Code erreur HTTP
    die("Erreur interne. Merci de réessayer plus tard.");
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