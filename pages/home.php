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
<!-- presentation du tool 
<section class="data-tools-showcase">
    <div class="dt-container">
        <h2 class="dt-title">
            <span class="dt-icon"><i class="fas fa-chart-network"></i></span>
            Transformez vos données en insights
        </h2>

        <div class="dt-grid">
            <div class="dt-card">
                <div class="dt-card-icon csv">
                    <i class="fa fa-file-csv"></i>
                </div>
                <h3>CSV Transformer</h3>
                <p>Conversion vers multiples formats</p>
                <ul class="dt-features">
                    <li><i class="fas fa-chart-bar"></i> Graphiques dynamiques</li>
                    <li><i class="fas fa-table"></i> Tableaux interactifs</li>
                    <li><i class="fas fa-file-export"></i> Exports PNG/PDF</li>
                </ul>
            </div>

            <div class="dt-card">
                <div class="dt-card-icon excel">
                    <i class="fa fa-file-excel"></i>
                </div>
                <h3>Excel Magic</h3>
                <p>Analyse avancée</p>
                <ul class="dt-features">
                    <li><i class="fas fa-project-diagram"></i> Visualisations 3D</li>
                    <li><i class="fas fa-bolt"></i> Traitement rapide</li>
                    <li><i class="fas fa-cloud-upload"></i> Intégration cloud</li>
                </ul>
            </div>

            <div class="dt-card">
                <div class="dt-card-icon json">
                    <i class="fa fa-file-code"></i>
                </div>
                <h3>JSON Explorer</h3>
                <p>Analyse de structures</p>
                <ul class="dt-features">
                    <li><i class="fas fa-sitemap"></i> Arborescence</li>
                    <li><i class="fas fa-filter"></i> Filtres intelligents</li>
                    <li><i class="fas fa-share-alt"></i> Partage configurable</li>
                </ul>
            </div>
        </div>

        <div class="dt-cta">
            <p>Explorez notre galerie publique ou uploader vos propres fichiers</p>
            <div class="dt-buttons">
                <a href="<?= safe_url('/pages/search.php') ?>" class="dt-btn primary">
                    <i class="fas fa-rocket"></i> Commencer
                </a>
                <a href="#" class="dt-btn secondary">
                    <i class="fas fa-book-open"></i> Tutoriels
                </a>
            </div>
        </div>
    </div>
</section>-->
<!--<?= safe_url('/pages/search.php') ?>--remplace--<?= BASE_URL ?>-->


<div class="container" id="mur">
    <div class="comment-form">
        <h2>Poster un commentaire <?= htmlspecialchars($user['username']) ?></h2>
        <form method="POST" enctype="multipart/form-data" class="reply-form">
            <textarea name="content" placeholder="Exprimez-vous "></textarea>

            <label for="file-main" class="file-label">
                <i class="fas fas fa-image"></i> Parcourir
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

                <?php if (!empty($comment['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($comment['profile_picture']) ?>?<?= time() ?>"
                        alt="Photo de <?= htmlspecialchars($comment['username']) ?>" class="profile-picture-thumbnail">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>

                <span class="comment-date">
                    <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                </span>

                <p><strong><?= htmlspecialchars($comment['username']) ?></strong> :</p>

                <?php if (!empty($comment['content'])): ?>
                    <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                <?php endif; ?>

                <?php if (!empty($comment['file_path'])): ?>
                    <?php if ($comment['file_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($comment['file_path']) ?>" alt="image partagée" class="img-partage">
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
                    <i class="fas fas fa-image"></i> Parcourir
                </label>
                <input type="file" name="file" id="file-<?= $comment['id'] ?>" accept="image/*,video/*">

                <button type="submit" name="reply" class="btn-reply">
                    <i class="fas fa-reply"></i> Répondre
                </button>
            </form>

            <!-- Affichage des réponses -->
            <?php foreach (getReplies($comment['id']) as $reply): ?>
                <div class="reply" style="margin-left: 40px; border-left: 2px solid #ccc; padding-left: 10px;">
                    <?php if (!empty($reply['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($reply['profile_picture']) ?>?<?= time() ?>"
                            alt="Photo de <?= htmlspecialchars($reply['username']) ?>" class="profile-picture-thumbnail">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
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