<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
//$comments = getAllComments();
$comments = getParentComments(); // pour afficher le com sous un post ciblé ? 1h pour trouver ce bug gael vas te coucher (ouai je me parle tout seul putain je suis fou ça y'est. ..)
//$userLiked = hasUserLiked($comment['id'], $user['id']);  //bug ici

// Traitement du formulaire de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['content']);
    $file_path = null;
    $file_type = null;

    if (!empty($_FILES['file']['name'])) {
        $upload = uploadFile($_FILES['file']);
        if (!isset($upload['error'])) {
            $file_path = $upload['path'];
            $file_type = $upload['type'];
        }
    }

    if (!empty($content) || $file_path) {
        addComment($_SESSION['user_id'], $content, null, $file_path, $file_type);
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

// Traitement du formulaire de réponse sous un post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply']) && isset($_POST['parent_id'])) {
    $content = trim($_POST['content']);
    $file_path = null;
    $file_type = null;

    if (!empty($_FILES['file']['name'])) {
        $upload = uploadFile($_FILES['file']);
        if (!isset($upload['error'])) {
            $file_path = $upload['path'];
            $file_type = $upload['type'];
        }
    }

    if (!empty($content) || $file_path) {
        $parent_id = intval($_POST['parent_id']);
        addComment($_SESSION['user_id'], $content, $parent_id, $file_path, $file_type);
        header("Location: " . BASE_URL);
        exit;
    }
}


require_once '../includes/header.php';
?>

<div class="container">
    <div class="comment-form">
        <h2>Poster un commentaire <?= htmlspecialchars($user['username']) ?></h2>
        <form method="POST" enctype="multipart/form-data" class="reply-form">
            <textarea name="content" placeholder="Exprimez-vous "></textarea>

            <label for="file-main" class="file-label">
                <i class="fas fa-file-upload"></i> Parcourir
            </label>
            <input type="file" name="file" id="file-main" accept="image/*,video/*">

            <button type="submit" name="comment" class="btn-reply">
                <i class="fas fa-paper-plane"></i> Poster
            </button>
        </form>
    </div>

    <div class="comments">
        <h2>Posts commun</h2>
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <p><strong><?= htmlspecialchars($comment['username']) ?></strong> :</p>

                <?php if (!empty($comment['content'])): ?>
                    <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                <?php endif; ?>

                <?php if (!empty($comment['file_path'])): ?>
                    <?php if ($comment['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($comment['file_path']) ?>" alt="image partagée" style="max-width:300px;">
                    <?php elseif ($comment['file_type'] === 'video'): ?>
                        <video controls style="max-width:300px;">
                            <source src="<?= htmlspecialchars($comment['file_path']) ?>" type="video/mp4">
                            Votre navigateur ne supporte pas la vidéo.
                        </video>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="comment-actions">

                    <a href="?like=<?= $comment['id'] ?>" class="like-btn <?= $userLiked ? 'liked' : 'not-liked' ?>">
                        <i class="fas fa-heart"></i> <?= $comment['like_count'] ?>
                    </a>

                </div>
            </div>

            <!-- Formulaire de réponse -->
            <form method="POST" enctype="multipart/form-data" class="reply-form">
                <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                <textarea name="content" placeholder="Répondre à ce post..."></textarea>

                <label for="file-<?= $comment['id'] ?>" class="file-label">
                    <i class="fas fa-file-upload"></i> Parcourir
                </label>
                <input type="file" name="file" id="file-<?= $comment['id'] ?>" accept="image/*,video/*">

                <button type="submit" name="reply" class="btn-reply">
                    <i class="fas fa-reply"></i> Répondre
                </button>
            </form>

            <!-- Affichage des réponses -->
            <?php foreach (getReplies($comment['id']) as $reply): ?>
                <div class="reply" style="margin-left: 40px; border-left: 2px solid #ccc; padding-left: 10px;">
                    <p><strong><?= htmlspecialchars($reply['username']) ?></strong> a répondu :</p>

                    <?php if (!empty($reply['content'])): ?>
                        <p><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($reply['file_path'])): ?>
                        <?php if ($reply['file_type'] === 'image'): ?>
                            <img src="<?= htmlspecialchars($reply['file_path']) ?>" style="max-width:200px;" alt="image réponse">
                        <?php elseif ($reply['file_type'] === 'video'): ?>
                            <video controls style="max-width:200px;">
                                <source src="<?= htmlspecialchars($reply['file_path']) ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endforeach; ?>
    </div>
</div>


<?php require_once '../includes/footer.php'; ?>