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
?>