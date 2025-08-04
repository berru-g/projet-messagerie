<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);
/* Un seul accé autorisé ...
if ($user['username'] !== 'berru' || $user['email'] !== 'g.leberruyer@gmail.com') {
    http_response_code(403);
    exit("⛔ Accès interdit.");
}*/
// redirection
if ($user['username'] !== 'admin' || $user['email'] !== 'g.leberruyer@gmail.com') {
    header("Location: " . BASE_URL . "/pages/profile.php");
    exit;
}

// Initialisation de $pdo comme dans votre profil.php
$pdo = getDB(); // Assurez-vous que getDB() retourne bien votre connexion PDO

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout/modification d'une crypto
    if (isset($_POST['add_crypto'])) {
        $cryptoId = trim($_POST['crypto_id']);
        $cryptoName = trim($_POST['crypto_name']);
        $purchasePrice = floatval($_POST['purchase_price']);
        $quantity = floatval($_POST['quantity']);
        
        try {
            // Vérification de l'existence
            $stmt = $pdo->prepare("SELECT id FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
            $stmt->execute([$userId, $cryptoId]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Mise à jour
                $stmt = $pdo->prepare("UPDATE user_crypto_holdings SET quantity = ?, purchase_price = ?, crypto_name = ? WHERE id = ?");
                $stmt->execute([$quantity, $purchasePrice, $cryptoName, $exists['id']]);
            } else {
                // Insertion
                $stmt = $pdo->prepare("INSERT INTO user_crypto_holdings (user_id, crypto_id, crypto_name, purchase_price, quantity) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $cryptoId, $cryptoName, $purchasePrice, $quantity]);
            }
            
            $_SESSION['success'] = "Portefeuille mis à jour avec succès";
            header("Location: " . BASE_URL . "/pages/wallet.php");
            exit;
            
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la mise à jour du portefeuille";
        }
    }
    // Suppression d'une crypto
    elseif (isset($_POST['delete_crypto'])) {
        $cryptoId = $_POST['crypto_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
            $stmt->execute([$userId, $cryptoId]);
            
            $_SESSION['success'] = "Crypto supprimée du portefeuille";
            header("Location: " . BASE_URL . "/pages/wallet.php");
            exit;
            
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de la suppression";
        }
    }
}

// Récupération des cryptos de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM user_crypto_holdings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userCryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $userCryptos = [];
}

require_once '../includes/header.php';
?>
<style>
    /* Ajoutez ceci à votre fichier CSS */
.crypto-form {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.button {
    background-color: #ab9ff2;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.button:hover {
    background-color: #9285d8;
}

.button-delete {
    background-color: #f56545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
}

.button-delete:hover {
    background-color: #d9534f;
}

.user-cryptos {
    margin-bottom: 30px;
}

#user-crypto-list {
    list-style-type: none;
    padding: 0;
}

#user-crypto-list li {
    background-color: #f8f9fa;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.crypto-item {
    background-color: #f8f9fa;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    align-items: center;
}

@media (max-width: 768px) {
    .crypto-item {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container wallet-container">
    <h2>Mon Portefeuille Crypto</h2>
    
    <!-- Messages d'erreur/succès -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Ajouter/Modifier une crypto</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label for="crypto_id">ID Crypto (ex: bitcoin):</label>
                    <input type="text" class="form-control" id="crypto_id" name="crypto_id" required>
                </div>
                <div class="form-group">
                    <label for="crypto_name">Nom complet (ex: Bitcoin):</label>
                    <input type="text" class="form-control" id="crypto_name" name="crypto_name" required>
                </div>
                <div class="form-group">
                    <label for="purchase_price">Prix d'achat ($):</label>
                    <input type="number" step="0.000001" class="form-control" id="purchase_price" name="purchase_price" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantité:</label>
                    <input type="number" step="0.000001" class="form-control" id="quantity" name="quantity" required>
                </div>
                <button type="submit" name="add_crypto" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <!-- Liste des cryptos -->
    <div class="card">
        <div class="card-header">
            <h3>Vos actifs cryptos</h3>
        </div>
        <div class="card-body">
            <?php if (empty($userCryptos)): ?>
                <p>Aucune crypto enregistrée dans votre portefeuille.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Crypto</th>
                                <th>Quantité</th>
                                <th>Prix d'achat</th>
                                <th>Valeur actuelle</th>
                                <th>Profit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="crypto-list">
                            <!-- Rempli par JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <h4 id="portfolio-total">Total: Chargement...</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fonction pour charger les données des cryptos
async function loadWalletData() {
    try {
        // Récupérer les cryptos de l'utilisateur depuis PHP
        const userCryptos = <?= json_encode($userCryptos) ?>;
        
        if (userCryptos.length === 0) {
            document.getElementById('portfolio-total').textContent = 'Total: 0.00 $';
            return;
        }
        
        // Récupérer les prix depuis CoinGecko
        const cryptoIds = userCryptos.map(c => c.crypto_id).join(',');
        const response = await fetch(`https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=${cryptoIds}`);
        const prices = await response.json();
        
        let totalValue = 0;
        let totalInvestment = 0;
        let html = '';
        
        userCryptos.forEach(crypto => {
            const priceData = prices.find(p => p.id === crypto.crypto_id);
            if (priceData) {
                const currentPrice = priceData.current_price;
                const holdings = parseFloat(crypto.quantity);
                const value = currentPrice * holdings;
                const investment = crypto.purchase_price * holdings;
                const profit = value - investment;
                const profitPercent = (profit / investment) * 100;
                
                totalValue += value;
                totalInvestment += investment;
                
                html += `
                    <tr>
                        <td>
                            <img src="${priceData.image}" alt="${crypto.crypto_name}" style="width: 20px; height: 20px; margin-right: 5px;">
                            ${crypto.crypto_name} (${priceData.symbol.toUpperCase()})
                        </td>
                        <td>${holdings}</td>
                        <td>${parseFloat(crypto.purchase_price).toFixed(6)} $</td>
                        <td>${value.toFixed(2)} $</td>
                        <td style="color: ${profit >= 0 ? 'green' : 'red'}">
                            ${profit.toFixed(2)} $ (${profitPercent.toFixed(2)}%)
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="crypto_id" value="${crypto.crypto_id}">
                                <button type="submit" name="delete_crypto" class="btn btn-sm btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                `;
            }
        });
        
        const totalProfit = totalValue - totalInvestment;
        const totalProfitPercent = (totalProfit / totalInvestment) * 100;
        
        document.getElementById('crypto-list').innerHTML = html;
        document.getElementById('portfolio-total').innerHTML = `
            Total: ${totalValue.toFixed(2)} $<br>
            Investi: ${totalInvestment.toFixed(2)} $<br>
            Profit: <span style="color: ${totalProfit >= 0 ? 'green' : 'red'}">
                ${totalProfit.toFixed(2)} $ (${totalProfitPercent.toFixed(2)}%)
            </span>
        `;
        
    } catch (error) {
        console.error("Erreur:", error);
        document.getElementById('portfolio-total').textContent = 'Erreur de chargement';
    }
}

// Chargement initial
loadWalletData();

// Actualisation toutes les 5 minutes
setInterval(loadWalletData, 300000);
</script>

<?php require_once '../includes/footer.php'; ?>
