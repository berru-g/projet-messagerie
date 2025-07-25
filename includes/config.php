<?php
// Configuration de base
session_start();
define('BASE_URL', 'https://bisque-mallard-923914.hostingersite.com/');
define('SITE_NAME', 'fileshare');

// Détecte les tentatives d'injection dans les URLs
if (preg_match('/[\'"]|(--)|(\/\*)|(\\\\)/i', $_SERVER['QUERY_STRING'])) {
    header("HTTP/1.1 403 Forbidden");
    error_log("Tentative d'injection détectée: ".$_SERVER['REQUEST_URI']);
    die('Requête suspecte bloquée');
}

?>