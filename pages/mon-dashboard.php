<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

// Récupération des stats globales
$stats = [];

// Utilisateurs
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['new_users_last_30'] = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();

// Fichiers
$stats['total_files'] = $pdo->query("SELECT COUNT(*) FROM user_files")->fetchColumn();
$stats['public_files'] = $pdo->query("SELECT COUNT(*) FROM user_files WHERE is_public = 1")->fetchColumn();
$stats['file_types'] = $pdo->query("SELECT file_type, COUNT(*) as count FROM user_files GROUP BY file_type")->fetchAll(PDO::FETCH_ASSOC);

// Activités
$stats['comments_count'] = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$stats['likes_count'] = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();

// Top utilisateurs
$stats['top_uploaders'] = $pdo->query("SELECT u.username, COUNT(f.id) as uploads FROM users u JOIN user_files f ON u.id = f.user_id GROUP BY u.id ORDER BY uploads DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$stats['top_commented'] = $pdo->query("SELECT u.username, COUNT(c.id) as comments FROM users u JOIN comments c ON u.id = c.user_id GROUP BY u.id ORDER BY comments DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container">
    <h2>📊 Statistiques Globales</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Utilisateurs</h3>
            <canvas id="usersChart" height="100"></canvas>
            <p>Total : <?= $stats['total_users'] ?></p>
            <p>Nouveaux (30j) : <?= $stats['new_users_last_30'] ?></p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-file-upload"></i> Fichiers</h3>
            <canvas id="filesChart" height="100"></canvas>
            <p>Total : <?= $stats['total_files'] ?></p>
            <p>Publics : <?= $stats['public_files'] ?></p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-comments"></i> Interactions</h3>
            <canvas id="interactionsChart" height="100"></canvas>
            <p>Commentaires : <?= $stats['comments_count'] ?></p>
            <p>Likes : <?= $stats['likes_count'] ?></p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-trophy"></i> Top Uploaders</h3>
            <ul>
                <?php foreach ($stats['top_uploaders'] as $user): ?>
                    <li><?= htmlspecialchars($user['username']) ?> : <?= $user['uploads'] ?> fichiers</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-fire"></i> Plus actifs (posts)</h3>
            <ul>
                <?php foreach ($stats['top_commented'] as $user): ?>
                    <li><?= htmlspecialchars($user['username']) ?> : <?= $user['comments'] ?> commentaires</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const usersChart = new Chart(document.getElementById('usersChart'), {
        type: 'doughnut',
        data: {
            labels: ['Nouveaux (30j)', 'Anciens'],
            datasets: [{
                label: 'Utilisateurs',
                data: [<?= $stats['new_users_last_30'] ?>, <?= $stats['total_users'] - $stats['new_users_last_30'] ?>],
                backgroundColor: ['#4caf50', '#90caf9']
            }]
        },
        options: { animation: { duration: 1000 } }
    });

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
                backgroundColor: ['#ff9800', '#03a9f4', '#8bc34a', '#e91e63']
            }]
        },
        options: { animation: { duration: 1000 } }
    });

    const interactionsChart = new Chart(document.getElementById('interactionsChart'), {
        type: 'bar',
        data: {
            labels: ['Commentaires', 'Likes'],
            datasets: [{
                label: 'Interactions',
                data: [<?= $stats['comments_count'] ?>, <?= $stats['likes_count'] ?>],
                backgroundColor: ['#ff5722', '#3f51b5']
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
.stat-card p, .stat-card li {
    margin: 0.4rem 0;
    color: #555;
    font-size: 0.95rem;
}
.stat-card ul {
    padding-left: 1.2rem;
    margin-top: 0.5rem;
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
