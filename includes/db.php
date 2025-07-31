<?php

$dbHost = 'localhost';     
$dbName = 'u667977963_saas';    
$dbUser = 'u667977963_berru_saas';      
$dbPass = 'm@bddSQL25'; 

try {
    //$dbConfig = require __DIR__ . '/../db_config.php';
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