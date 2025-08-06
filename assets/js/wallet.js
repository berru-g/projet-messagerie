// Configuration
const API_URL = '/wallet/api_wallet.php';
const COINGECKO_API = 'https://api.coingecko.com/api/v3';

// Éléments DOM
const searchInput = document.getElementById("search-crypto");
const cryptoIdInput = document.getElementById("crypto-id");
const cryptoNameInput = document.getElementById("crypto-name");
const autocompleteList = document.getElementById("autocomplete-list");
const walletList = document.getElementById("wallet-list");

// Charger la liste des cryptos au démarrage
async function loadCryptoList() {
    try {
        const response = await fetch(`${COINGECKO_API}/coins/list`);
        if (!response.ok) throw new Error("Erreur de chargement");
        return await response.json();
    } catch (error) {
        console.error("Erreur:", error);
        return [];
    }
}

// Autocomplétion
searchInput.addEventListener("input", async (e) => {
    const query = e.target.value.trim();
    if (query.length < 2) {
        autocompleteList.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`${COINGECKO_API}/search?query=${query}`);
        const data = await response.json();
        displayAutocompleteResults(data.coins.slice(0, 5));
    } catch (error) {
        console.error("Erreur:", error);
    }
});

function displayAutocompleteResults(coins) {
    autocompleteList.innerHTML = '';
    coins.forEach(coin => {
        const li = document.createElement("li");
        li.textContent = `${coin.name} (${coin.symbol.toUpperCase()})`;
        li.onclick = () => {
            searchInput.value = coin.name;
            cryptoIdInput.value = coin.id;
            cryptoNameInput.value = coin.name;
            autocompleteList.innerHTML = '';
        };
        autocompleteList.appendChild(li);
    });
}

// Gestion du portefeuille
async function loadWallet() {
    try {
        const response = await fetch(`${API_URL}?action=get&user_id=${userId}`);
        const holdings = await response.json();
        
        if (!holdings.length) {
            walletList.innerHTML = '<p>Portefeuille vide.</p>';
            updateTotals(0, 0, 0);
            return;
        }

        // Récupérer les prix actuels
        const ids = holdings.map(c => c.crypto_id).join(',');
        const pricesResponse = await fetch(`${COINGECKO_API}/simple/price?ids=${ids}&vs_currencies=usd&include_24hr_change=true`);
        const prices = await pricesResponse.json();

        // Afficher les holdings
        let totalInvested = 0;
        let totalCurrent = 0;
        let total24hChange = 0;

        walletList.innerHTML = holdings.map(crypto => {
            const currentPrice = prices[crypto.crypto_id]?.usd || 0;
            const change24h = prices[crypto.crypto_id]?.usd_24h_change || 0;
            const invested = crypto.purchase_price * crypto.quantity;
            const currentValue = currentPrice * crypto.quantity;
            const profitLoss = currentValue - invested;
            const profitLossPercent = (profitLoss / invested) * 100;

            totalInvested += invested;
            totalCurrent += currentValue;
            total24hChange += (currentValue * (change24h / 100));

            return `
                <div class="crypto-item">
                    <div>
                        <span class="crypto-name">${crypto.crypto_name}</span>
                        <div class="crypto-meta">
                            ${crypto.quantity} @ $${crypto.purchase_price.toFixed(2)}
                        </div>
                    </div>
                    <div>
                        <span style="color: ${profitLoss >= 0 ? '#60d394' : '#ee6055'}">
                            $${currentValue.toFixed(2)} (${profitLossPercent.toFixed(2)}%)
                        </span>
                        <button class="btn-wallet" onclick="deleteCrypto('${crypto.crypto_id}')">Supprimer</button>
                    </div>
                </div>
            `;
        }).join('');

        updateTotals(totalInvested, totalCurrent, total24hChange);
        drawChart(holdings, prices);

    } catch (error) {
        console.error("Erreur:", error);
        walletList.innerHTML = '<p>Erreur de chargement du portefeuille</p>';
    }
}

function updateTotals(invested, current, change24h) {
    document.getElementById("total-invested").textContent = `$${invested.toFixed(2)}`;
    document.getElementById("current-value").textContent = `$${current.toFixed(2)}`;
    document.getElementById("performance-24h").textContent = `${((change24h / current) * 100).toFixed(2)}%`;
}

// Ajouter une crypto
document.getElementById("add-btn").addEventListener("click", async () => {
    const crypto_id = cryptoIdInput.value;
    const crypto_name = cryptoNameInput.value;
    const purchase_price = parseFloat(document.getElementById("purchase-price").value);
    const quantity = parseFloat(document.getElementById("quantity").value);

    if (!crypto_id || !purchase_price || !quantity) {
        alert("Veuillez remplir tous les champs");
        return;
    }

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                user_id: userId,
                crypto_id,
                crypto_name,
                purchase_price,
                quantity
            })
        });

        if (!response.ok) throw new Error("Erreur d'ajout");
        
        // Réinitialiser le formulaire
        searchInput.value = '';
        cryptoIdInput.value = '';
        cryptoNameInput.value = '';
        document.getElementById("purchase-price").value = '';
        document.getElementById("quantity").value = '';
        
        // Recharger le portefeuille
        loadWallet();
    } catch (error) {
        console.error("Erreur:", error);
        alert("Erreur lors de l'ajout de la crypto");
    }
});

// Supprimer une crypto
async function deleteCrypto(id) {
    if (!confirm("Supprimer cette crypto de votre portefeuille ?")) return;
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'delete', 
                user_id: userId, 
                crypto_id: id 
            })
        });
        
        if (!response.ok) throw new Error("Erreur de suppression");
        loadWallet();
    } catch (error) {
        console.error("Erreur:", error);
        alert("Erreur lors de la suppression");
    }
}

// Graphique
function drawChart(holdings, prices) {
    am5.ready(() => {
        const root = am5.Root.new("chartdiv");
        root.setThemes([am5themes_Animated.new(root)]);
        
        const chart = root.container.children.push(
            am5xy.XYChart.new(root, {
                panX: true,
                panY: true,
                wheelX: "panX",
                wheelY: "zoomX"
            })
        );

        const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
            categoryField: "crypto",
            renderer: am5xy.AxisRendererX.new(root, {})
        }));

        const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
            renderer: am5xy.AxisRendererY.new(root, {})
        }));

        const series = chart.series.push(am5xy.ColumnSeries.new(root, {
            name: "Valeur",
            xAxis: xAxis,
            yAxis: yAxis,
            valueYField: "value",
            categoryXField: "crypto"
        }));

        const chartData = holdings.map(item => ({
            crypto: item.crypto_name,
            value: (prices[item.crypto_id]?.usd || 0) * item.quantity
        }));

        xAxis.data.setAll(chartData);
        series.data.setAll(chartData);
    });
}

// Initialisation
loadWallet();

// feed back user
