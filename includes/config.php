<?php
// Configuration de base
session_start();
define('BASE_URL', 'https://moccasin-trout-851542.hostingersite.com/');
define('SITE_NAME', 'FileShare');

// Détecte les tentatives d'injection dans les URLs
if (preg_match('/[\'"]|(--)|(\/\*)|(\\\\)/i', $_SERVER['QUERY_STRING'])) {
    header("HTTP/1.1 403 Forbidden");
    error_log("Tentative d'injection détectée: ".$_SERVER['REQUEST_URI']);
    die('Requête suspecte bloquée');
}

?>