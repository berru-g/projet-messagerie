<?php
require_once  'db.php';

// Fonction pour vérifier si un utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour obtenir les informations de l'utilisateur
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour ajouter un commentaire
function addComment($user_id, $content) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, content, created_at) VALUES (?, ?, NOW())");
    return $stmt->execute([$user_id, $content]);
}

// Fonction pour liker un commentaire
function likeComment($user_id, $comment_id) {
    global $pdo;
    
    // Vérifier si l'utilisateur a déjà liké ce commentaire
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);
    
    if ($stmt->rowCount() > 0) {
        // Retirer le like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND comment_id = ?");
        return $stmt->execute([$user_id, $comment_id]);
    } else {
        // Ajouter le like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, comment_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $comment_id]);
    }
}

// Fonction pour obtenir tous les commentaires avec leurs likes
function getAllComments() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, u.username, COUNT(l.id) as like_count 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN likes l ON c.id = l.comment_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour vérifier si l'utilisateur courant a liké un commentaire
function hasLiked($user_id, $comment_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);
    return $stmt->rowCount() > 0;
}

// fonction de visualisation des uploads
function displayCsvFile($filePath) {
    $html = '<table class="table table-bordered table-striped">';
    
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $firstRow = true;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $html .= '<tr>';
            foreach ($data as $cell) {
                if ($firstRow) {
                    $html .= '<th>' . htmlspecialchars($cell) . '</th>';
                } else {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
            }
            $html .= '</tr>';
            $firstRow = false;
        }
        fclose($handle);
    }
    
    $html .= '</table>';
    return $html;
}

function displayExcelFile($filePath) {
    require_once '../vendor/autoload.php';
    $html = '<table class="table table-bordered table-striped">';
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        foreach ($worksheet->getRowIterator() as $row) {
            $html .= '<tr>';
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $value = $cell->getFormattedValue();
                if ($row->getRowIndex() == 1) {
                    $html .= '<th>' . htmlspecialchars($value) . '</th>';
                } else {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }
    } catch (Exception $e) {
        return '<div class="alert alert-danger">Erreur Excel: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    $html .= '</table>';
    return $html;
}

function displayJsonFile($filePath) {
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<div class="alert alert-danger">Erreur JSON: ' . json_last_error_msg() . '</div>';
    }
    
    return '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
}

?>

