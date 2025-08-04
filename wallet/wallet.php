<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

//$user = getUserById($_SESSION['user_id']);
$userId = $_SESSION['user_id']; // l'utilisateur connecté
$user = getUserById($userId);   // ses infos
// Récupérer les cryptos de l'utilisateur
$db = getDB();
$stmt = $db->prepare("SELECT * FROM user_crypto_holdings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$userCryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire d'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_crypto'])) {
        $cryptoId = trim($_POST['crypto_id']);
        $cryptoName = trim($_POST['crypto_name']);
        $purchasePrice = (float)$_POST['purchase_price'];
        $quantity = (float)$_POST['quantity'];
        
        // Vérifier si la crypto existe déjà pour cet utilisateur
        $stmt = $db->prepare("SELECT id FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
        $stmt->execute([$user['id'], $cryptoId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Mise à jour si existe déjà
            $stmt = $db->prepare("UPDATE user_crypto_holdings SET quantity = ?, purchase_price = ? WHERE id = ?");
            $stmt->execute([$quantity, $purchasePrice, $exists['id']]);
        } else {
            // Insertion si nouvelle
            $stmt = $db->prepare("INSERT INTO user_crypto_holdings (user_id, crypto_id, crypto_name, purchase_price, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $cryptoId, $cryptoName, $purchasePrice, $quantity]);
        }
        
        // Redirection pour éviter le rechargement du formulaire
        header("Location: " . BASE_URL . "/pages/profile.php");
        exit;
    } elseif (isset($_POST['delete_crypto'])) {
        $cryptoId = $_POST['crypto_id'];
        $stmt = $db->prepare("DELETE FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
        $stmt->execute([$user['id'], $cryptoId]);
        
        header("Location: " . BASE_URL . "/pages/profile.php");
        exit;
    }
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
<div class="container profile-container">
    <h2><?= htmlspecialchars($user['username']) ?></h2>

    <div class="profile-info">
        <div id="portfolio-total">0.00 $</div>
    </div>

    <!-- Formulaire pour ajouter/modifier une crypto -->
    <div class="crypto-form">
        <h3>Ajouter/Modifier une crypto</h3>
        <form method="POST">
            <div class="form-group">
                <label for="crypto_id">ID Crypto (ex: bitcoin):</label>
                <input type="text" id="crypto_id" name="crypto_id" required>
            </div>
            <div class="form-group">
                <label for="crypto_name">Nom complet (ex: Bitcoin):</label>
                <input type="text" id="crypto_name" name="crypto_name" required>
            </div>
            <div class="form-group">
                <label for="purchase_price">Prix d'achat ($):</label>
                <input type="number" step="0.000001" id="purchase_price" name="purchase_price" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantité:</label>
                <input type="number" step="0.000001" id="quantity" name="quantity" required>
            </div>
            <button type="submit" name="add_crypto" class="button">Enregistrer</button>
        </form>
    </div>

    <!-- Liste des cryptos de l'utilisateur -->
    <div class="user-cryptos">
        <h3>Vos cryptos</h3>
        <?php if (empty($userCryptos)): ?>
            <p>Aucune crypto enregistrée.</p>
        <?php else: ?>
            <ul id="user-crypto-list">
                <?php foreach ($userCryptos as $crypto): ?>
                    <li>
                        <?= htmlspecialchars($crypto['crypto_name']) ?> - 
                        Quantité: <?= $crypto['quantity'] ?> - 
                        Prix d'achat: <?= $crypto['purchase_price'] ?>$
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="crypto_id" value="<?= $crypto['crypto_id'] ?>">
                            <button type="submit" name="delete_crypto" class="button-delete">Supprimer</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div id="crypto-prices-container">
        <div class="address-container">
            <button class="copy-button" data-target="sol-address">SOL <span class="address"
                    id="sol-address">D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC</span></button>
            <button class="copy-button" data-target="eth-address">ETH <span class="address"
                    id="eth-address">0x026e9B43BAB0881FD55625ac1dB6dC418162eDAd</span></button>
        </div>
        <div id="crypto-prices"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    // Fonction pour charger les données des cryptos de l'utilisateur
    async function loadUserCryptos() {
        try {
            const response = await fetch('https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&per_page=250');
            const allCryptos = await response.json();
            
            // Récupérer les cryptos de l'utilisateur via AJAX ou les passer en JSON depuis PHP
            const userCryptos = <?= json_encode($userCryptos) ?>;
            
            if (userCryptos.length === 0) {
                document.getElementById('portfolio-total').innerHTML = '<h3 style="color:#ab9ff2">0.00 $</h3>';
                return;
            }
            
            // Créer une liste des IDs de cryptos à suivre
            const cryptoIds = userCryptos.map(crypto => crypto.crypto_id).join(',');
            
            // Récupérer les prix des cryptos de l'utilisateur
            const priceResponse = await fetch(`https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=${cryptoIds}`);
            const priceData = await priceResponse.json();
            
            const container = document.getElementById('crypto-prices');
            container.innerHTML = '';
            
            let totalPortfolioValue = 0;
            let totalInvestment = 0;
            let totalProfit = 0;
            
            // Afficher chaque crypto avec ses données
            userCryptos.forEach(userCrypto => {
                const cryptoData = priceData.find(c => c.id === userCrypto.crypto_id);
                
                if (cryptoData) {
                    const currentPrice = cryptoData.current_price;
                    const change24h = cryptoData.price_change_percentage_24h.toFixed(2);
                    const holdings = parseFloat(userCrypto.quantity);
                    const totalValue = (currentPrice * holdings).toFixed(2);
                    const investment = (userCrypto.purchase_price * holdings).toFixed(2);
                    const profit = (totalValue - investment).toFixed(2);
                    const profitPercentage = ((profit / investment) * 100).toFixed(2);
                    
                    totalPortfolioValue += parseFloat(totalValue);
                    totalInvestment += parseFloat(investment);
                    totalProfit += parseFloat(profit);
                    
                    const cryptoElement = document.createElement('div');
                    cryptoElement.id = userCrypto.crypto_id;
                    cryptoElement.classList.add('crypto-item');
                    cryptoElement.innerHTML = `
                        <p style="display: flex; align-items: center;">
                            <img src="${cryptoData.image}" alt="${userCrypto.crypto_id} logo" style="width: 30px; height: 30px; margin-right: 10px;">
                            <span>${userCrypto.crypto_name} (${cryptoData.symbol.toUpperCase()})</span>
                        </p>
                        <p class="price" style="color:#333;">${currentPrice}$</p>
                        <p class="holdings" style="color:grey;">Quantité: ${holdings}</p>
                        <p class="value" style="color:grey;">Valeur: ${totalValue}$</p>
                        <p class="investment">Investi: ${investment}$</p>
                        <p class="profit" style="color: ${profit >= 0 ? '#3ad38b' : '#f56545'}">
                            Profit: ${profit}$ (${profitPercentage}%)
                        </p>
                        <p class="change">24h: ${change24h}%</p>
                    `;
                    container.appendChild(cryptoElement);
                    
                    const changeElement = cryptoElement.querySelector('.change');
                    changeElement.style.color = change24h < 0 ? '#f56545' : '#3ad38b';
                }
            });
            
            // Afficher le total du portefeuille
            const totalProfitPercentage = ((totalProfit / totalInvestment) * 100).toFixed(2);
            document.getElementById('portfolio-total').innerHTML = `
                <h3 style="color:#ab9ff2">
                    Total: ${totalPortfolioValue.toFixed(2)}$<br>
                    Investi: ${totalInvestment.toFixed(2)}$<br>
                    Profit: <span style="color: ${totalProfit >= 0 ? '#3ad38b' : '#f56545'}">
                        ${totalProfit.toFixed(2)}$ (${totalProfitPercentage}%)
                    </span>
                </h3>
            `;
            
        } catch (error) {
            console.error('Erreur lors de la récupération des données:', error);
        }
    }
    
    // Charger les cryptos au démarrage
    loadUserCryptos();
    
    // Recharger toutes les 5 minutes
    setInterval(loadUserCryptos, 300000);

    // Gestion du menu hamburger
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', () => {
            Swal.fire({
                title: '0x',
                html: '<ul><li><a href="https://accounts.binance.com/register?ref=">Binance</a>.com</li><li><a href="https://shop.ledger.com/?r=">Ledger</a>/live</li><li><a href="https://app.uniswap.org">Uniswap</a>.org<li><a href="#">Phantom</a>/app</li><li><a href="https://solscan.io/account/D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC#portfolio">Solscan</a>.io</li><li><a href="https://pump.fun/profile/D6khWo">Pump</a>.fun</li><li><a href="https://jup.ag">jup</a>.ag</li></ul>',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'custom-swal-popup',
                    closeButton: 'custom-swal-close-button',
                    content: 'custom-swal-content',
                }
            });
        });
    }

    // Gestion des boutons de copie
    document.querySelectorAll('.copy-button').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const addressElement = document.getElementById(targetId);
            const address = addressElement.textContent;

            navigator.clipboard.writeText(address).then(() => {
                Toastify({
                    text: "✅ Adresse copiée !",
                    duration: 2000,
                    gravity: "center",
                    position: "center",
                    backgroundColor: "",
                }).showToast();
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>