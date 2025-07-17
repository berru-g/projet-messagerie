<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

// Fonction révisée avec debug
function getAgriculturalPrices() {
    $cacheFile = '../cache/agricultural_prices.json';
    
    // Testez d'abord sans cache
    $apiUrl = "https://data.economie.gouv.fr/api/records/1.0/search/?" . http_build_query([
        'dataset' => 'prix-des-fruits-et-legumes',  // Nouveau dataset actif
        'rows' => 50,
        'sort' => '-date_publication',
        'facet' => ['produit', 'zone_geographique']
    ]);

    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 15
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        error_log("Erreur de connexion à l'API");
        return [];
    }

    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erreur JSON: " . json_last_error_msg());
        return [];
    }

    // Structure actuelle vérifiée (novembre 2023)
    $prices = [];
    foreach ($data['records'] ?? [] as $record) {
        $fields = $record['fields'] ?? [];
        $prices[] = [
            'produit' => $fields['produit'] ?? 'Produit inconnu',
            'marche' => $fields['zone_geographique'] ?? 'Non spécifié',
            'prix' => $fields['prix_moyen'] ?? $fields['valeur'] ?? 0,
            'unite' => $fields['unite'] ?? 'kg',
            'date' => $fields['date_publication'] ?? date('Y-m-d')
        ];
    }

    return $prices;
}

$pricesData = getAgriculturalPrices();
$markets = array_unique(array_column($pricesData, 'marche'));
sort($markets);

require_once '../includes/header.php';
?>

<div class="container">
    <!-- [Votre en-tête existant] -->
    
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h3>Prix agricoles</h3>
        </div>
        <div class="card-body">
            <!-- Outils de recherche -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="Filtrer les produits...">
                </div>
                <div class="col-md-4">
                    <select id="marketSelect" class="form-select">
                        <option value="">Tous marchés</option>
                        <?php foreach ($markets as $market): ?>
                            <option><?= htmlspecialchars($market) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="refreshBtn" class="btn btn-primary w-100">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <!-- Tableau -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Zone</th>
                            <th>Prix</th>
                            <th>Date</th>
                            <th>Unité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pricesData)): ?>
                            <?php foreach ($pricesData as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['produit']) ?></td>
                                    <td><?= htmlspecialchars($item['marche']) ?></td>
                                    <td><?= number_format($item['prix'], 2) ?> €</td>
                                    <td><?= date('d/m/Y', strtotime($item['date'])) ?></td>
                                    <td><?= htmlspecialchars($item['unite']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Aucune donnée disponible. Essayez de rafraîchir.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Script minimal fonctionnel
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const marketSelect = document.getElementById('marketSelect');
    const rows = document.querySelectorAll('tbody tr');
    
    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase();
        const market = marketSelect.value;
        
        rows.forEach(row => {
            const product = row.cells[0].textContent.toLowerCase();
            const zone = row.cells[1].textContent;
            const matchesSearch = product.includes(searchTerm);
            const matchesMarket = !market || zone === market;
            
            row.style.display = (matchesSearch && matchesMarket) ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterRows);
    marketSelect.addEventListener('change', filterRows);
    
    document.getElementById('refreshBtn').addEventListener('click', function() {
        location.reload();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>