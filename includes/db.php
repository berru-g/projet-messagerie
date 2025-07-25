<?php

$dbHost = 'localhost';      // Hôte Hostinger
$dbName = 'u667977963_saas';    // Nom DB
$dbUser = 'u667977963_berru_saas';       // Ton user
$dbPass = 'm@bddSQL25'; // Ton password

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
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}*/
?>