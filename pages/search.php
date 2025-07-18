<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
// Attention tout les json ne se mette pas en tableau et le telechargement est uniquement en json, non en pdf pour l'instant
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}
$user = getUserById($_SESSION['user_id']);
$searchResults = [];
$searchQuery = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $searchQuery = trim($_GET['q']);

    if (!empty($searchQuery)) {
        $stmt = $pdo->prepare("
            SELECT uf.*, u.email as owner_email 
            FROM user_files uf
            JOIN users u ON uf.user_id = u.id
            WHERE (uf.file_name LIKE :query OR u.email LIKE :query)
            AND (uf.is_public = TRUE OR uf.user_id = :user_id) 
            ORDER BY uf.upload_date DESC
        ");
        $stmt->execute([
            'query' => '%' . $searchQuery . '%',
            'user_id' => $_SESSION['user_id']
        ]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <h2><?= htmlspecialchars($user['username']) ?> fichiers partagés</h2>

    <div class="search-container mb-4">
        <form method="get" action="search.php">
            <div class="input-group">
                <input type="text" id="file-search" name="q" class="form-control"
                    placeholder="Rechercher par nom de fichier ou email propriétaire..."
                    value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($searchQuery)): ?>
        <div class="search-results">
            <h4>Résultats pour "<?= htmlspecialchars($searchQuery) ?>" <?= count($searchResults) ?> fichiers</h4>

            <?php if (empty($searchResults)): ?>
                <div class="alert alert-info">Aucun fichier trouvé.</div>
            <?php else: ?>
                <div class="file-gallery">
                    <?php foreach ($searchResults as $file): ?>
                        <div class="file-card">
                            <div class="file-icon">
                                <?php switch ($file['file_type']):
                                    case 'csv': ?>
                                        <i class="fas fa-file-csv"></i>
                                        <?php break; ?>
                                    <?php case 'excel': ?>
                                        <i class="fas fa-file-excel"></i>
                                        <?php break; ?>
                                    <?php case 'json': ?>
                                        <i class="fas fa-file-code"></i>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <i class="fas fa-file"></i>
                                <?php endswitch; ?>
                            </div>
                            <div class="file-info">
                                <h5><?= htmlspecialchars($file['file_name']) ?></h5>
                                <small>Propriétaire: <?= htmlspecialchars($file['owner_email']) ?></small>
                                <small><?= date('d/m/Y H:i', strtotime($file['upload_date'])) ?></small>
                                <small class="file-type <?= $file['file_type'] ?>">
                                    <?= strtoupper($file['file_type']) ?>
                                </small>
                            </div>
                            <div class="file-actions">
                                <a href="view_file.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="view_chart.php?id=<?= $file['id'] ?>" class="btn btn-info">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Script pour l'autocomplétion -->
<script>
    $(document).ready(function () {
        $("#file-search").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "../includes/search_autocomplete.php",
                    dataType: "json",
                    data: {
                        term: request.term
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            },
            minLength: 2
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>