<?php
// db.php - Version sécurisée
$dbHost = 'mysql.hostinger.com';      // Hôte Hostinger
$dbName = 'u123456789_messagerie';    // Nom DB
$dbUser = 'u123456789_tonuser';       // Ton user
$dbPass = 'tonMotDePasseUltraSecret'; // Ton password

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8",
        $dbUser,
        $dbPass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

/*
require_once  'config.php';

$host = 'localhost';
$dbname = 'messagerie_collegues';
$username = '#';
$password = '#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}*/
?>