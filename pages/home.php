<?php
require_once  '../includes/config.php';
require_once  '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
$comments = getAllComments();

// Traitement du formulaire de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['content']);
    if (!empty($content)) {
        addComment($_SESSION['user_id'], $content);
        header("Location: " . BASE_URL);
        exit;
    }
}

// Traitement des likes
if (isset($_GET['like'])) {
    $comment_id = intval($_GET['like']);
    likeComment($_SESSION['user_id'], $comment_id);
    header("Location: " . BASE_URL);
    exit;
}

require_once  '../includes/header.php';
?>

<div class="container">
    <div class="comment-form">
        <h2>Poster un commentaire</h2>
        <form method="POST">
            <textarea name="content" placeholder="Quoi de neuf ?" required></textarea>
            <button type="submit" name="comment">Publier</button>
        </form>
    </div>

    <div class="comments-section">
        <h2>Messagerie</h2>
        <?php if (empty($comments)): ?>
            <p>Aucun commentaire pour le moment.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="username"><?= htmlspecialchars($comment['username']) ?></span>
                        <span class="date"><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></span>
                    </div>
                    <div class="comment-content">
                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                    </div>
                    <div class="comment-actions">
                        <a href="?like=<?= $comment['id'] ?>" class="like-btn <?= hasLiked($_SESSION['user_id'], $comment['id']) ? 'liked' : '' ?>">
                            <i class="fas fa-heart"></i> <?= $comment['like_count'] ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once  '../includes/footer.php'; ?>