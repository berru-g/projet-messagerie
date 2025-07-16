<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
$userId = $user['id'];

$stats = [];

// Fichiers
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_files WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['total_files'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_files WHERE user_id = ? AND is_public = 1");
$stmt->execute([$userId]);
$stats['public_files'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT file_type, COUNT(*) as count FROM user_files WHERE user_id = ? GROUP BY file_type");
$stmt->execute([$userId]);
$stats['file_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Interactions (commentaires & likes faits par l'utilisateur)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['comments_made'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['likes_made'] = $stmt->fetchColumn();

require_once '../includes/header.php';
?>

<div class="container">
    <h2>📈 Mes Statistiques</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-file-alt"></i> Mes Fichiers</h3>
            <canvas id="filesChart" height="100"></canvas>
            <p>Total : <?= $stats['total_files'] ?></p>
            <p>Publics : <?= $stats['public_files'] ?></p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-bolt"></i> Mes Interactions</h3>
            <canvas id="interactionsChart" height="100"></canvas>
            <p>Commentaires : <?= $stats['comments_made'] ?></p>
            <p>Likes : <?= $stats['likes_made'] ?></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const filesChart = new Chart(document.getElementById('filesChart'), {
        type: 'pie',
        data: {
            labels: [
                <?php foreach ($stats['file_types'] as $type): ?>
                    '<?= ucfirst($type['file_type']) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($stats['file_types'] as $type): ?>
                        <?= $type['count'] ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: ['#4caf50', '#03a9f4', '#ff9800', '#e91e63', '#9c27b0']
            }]
        },
        options: {
            animation: { duration: 1000 }
        }
    });

    const interactionsChart = new Chart(document.getElementById('interactionsChart'), {
        type: 'bar',
        data: {
            labels: ['Commentaires', 'Likes'],
            datasets: [{
                label: 'Interactions',
                data: [<?= $stats['comments_made'] ?>, <?= $stats['likes_made'] ?>],
                backgroundColor: ['#2196f3', '#ff5722']
            }]
        },
        options: {
            responsive: true,
            animation: { duration: 1000 },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}
.stat-card {
    background: #f5f7fa;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-card h3 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.stat-card p {
    margin: 0.4rem 0;
    color: #555;
    font-size: 0.95rem;
}
canvas {
    margin-bottom: 1rem;
}
@media (max-width: 600px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
