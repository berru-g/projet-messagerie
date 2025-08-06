<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$userId = intval($_SESSION['user_id']);
$user = getUserById($userId);
if (!$user)
    die("Utilisateur non trouvé");

require_once '../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600&display=swap');


    .wallet {
        max-width: 1000px;
        margin: 40px auto;
        padding: 20px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 16px;
        box-shadow: 0 0 30px rgba(0, 255, 231, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 255, 229, 0.605);
    }


    .form-wallet {
        display: grid;
        margin: 0 auto;
        gap: 12px;
        background: #F4F4F4;
        padding: 20px;
        border-radius: 12px;
        box-shadow: inset 0 0 10px rgba(0, 255, 231, 0.05);
        margin-bottom: 30px;
        max-width: 300px;
    }

    .form-wallet input[type="text"],
    input[type="number"] {
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: #F1F1F1;
        color: #444;
        font-size: 1em;
        outline: none;
        transition: 0.2s ease;
    }

    input:focus {
        box-shadow: 0 0 0 2px #00ffe7;
    }

    btn-wallet {
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(145deg, #00ffe7, #9f7bff);
        color: #F1F1F1;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    btn-wallet:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 255, 231, 0.3);
    }

    #autocomplete-list {
        background: #1e2228;
        border-radius: 8px;
        margin-top: -8px;
        overflow: hidden;
    }

    #autocomplete-list li {
        padding: 10px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    #autocomplete-list li:hover {
        background: #2a2f37;
    }

    #wallet-list {
        margin-top: 30px;
    }

    .crypto-item {
        display: flex;
        justify-content: space-between;
        background: #161b22;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 12px;
        align-items: center;
        transition: 0.2s ease;
    }

    .crypto-item:hover {
        box-shadow: 0 0 10px rgba(0, 255, 231, 0.05);
    }

    .crypto-name {
        font-weight: 600;
        color: #00ffe7;
    }

    .crypto-meta {
        font-size: 0.9em;
        color: #aaa;
        margin-top: 5px;
    }

    .crypto-actions btn-wallet {
        margin-left: 10px;
        background: none;
        color: #ff7070;
        border: 1px solid #ff7070;
    }

    .crypto-actions btn-wallet:hover {
        background: #ff7070;
        color: #0d1117;
    }
</style>


<body>
    <div class="wallet">
        <h3>Mon Portefeuille Crypto</h3>
        <div class="wallet-summary">
            <p>Total investi : <span id="total-invested">$0.00</span></p>
            <p>Valeur actuelle : <span id="current-value">$0.00</span></p>
            <p>Variation 24h : <span id="performance-24h">0.00%</span></p>
        </div>

        <div id="growthchart" style="width: 100%; height: 400px;"></div>


        <div class="form-wallet">
            <label>Ajouter une crypto :</label>
            <input type="text" id="search-crypto" placeholder="ex: bitcoin">
            <ul id="autocomplete-list"></ul>
            <input type="hidden" id="crypto-id">
            <input type="text" id="crypto-name" placeholder="Nom complet">
            <input type="number" id="purchase-price" placeholder="Prix d'achat ($)">
            <input type="number" id="quantity" placeholder="Quantité">
            <button class="btn-wallet" id="add-btn">Ajouter</button>
        </div>

        <div id="wallet-list"></div>
        <div id="chartdiv" style="width: 100%; height: 400px;"></div>
    </div>

    <script>const userId = <?= $userId ?>;</script>
    <script src="../assets/js/wallet.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <script>
        const apiUrl = "/wallet/api_wallet.php";

        async function fetchWallet() {
            const res = await fetch(`${apiUrl}?action=get&user_id=${userId}`);
            const data = await res.json();
            renderWallet(data);
            renderChart(data);
        }

        async function addCrypto() {
            const payload = {
                action: 'add',
                user_id: userId,
                crypto_id: document.getElementById('crypto-id').value,
                crypto_name: document.getElementById('crypto-name').value,
                purchase_price: document.getElementById('purchase-price').value,
                quantity: document.getElementById('quantity').value
            };

            await fetch(apiUrl, {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            fetchWallet();
        }

        async function deleteCrypto(id) {
            await fetch(apiUrl, {
                method: 'POST',
                body: JSON.stringify({ action: 'delete', user_id: userId, crypto_id: id })
            });
            fetchWallet();
        }

        function renderWallet(data) {
            const container = document.getElementById('wallet-list');
            if (!data.length) return container.innerHTML = '<p>Portefeuille vide.</p>';

            container.innerHTML = '<table><tr><th>Nom</th><th>Prix d\'achat</th><th>Quantité</th><th>Total Investi</th><th></th></tr>' +
                data.map(item => `
        <tr>
          <td>${item.crypto_name}</td>
          <td>${item.purchase_price} $</td>
          <td>${item.quantity}</td>
          <td>${(item.purchase_price * item.quantity).toFixed(2)} $</td>
          <td><button onclick="deleteCrypto('${item.crypto_id}')">🗑️</button></td>
        </tr>`).join('') + '</table>';
        }

        async function searchCrypto(query) {
            const res = await fetch(`https://api.coingecko.com/api/v3/search?query=${query}`);
            const data = await res.json();
            const list = document.getElementById('autocomplete-list');
            list.innerHTML = '';
            data.coins.slice(0, 5).forEach(coin => {
                const li = document.createElement('li');
                li.textContent = coin.name;
                li.onclick = () => {
                    document.getElementById('crypto-id').value = coin.id;
                    document.getElementById('crypto-name').value = coin.name;
                    list.innerHTML = '';
                };
                list.appendChild(li);
            });
        }

        function renderChart(data) {
            am5.ready(function () {
                const root = am5.Root.new("chartdiv");
                root.container.children.clear();

                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(
                    am5xy.XYChart.new(root, { panX: true, panY: true, wheelX: "panX", wheelY: "zoomX" })
                );

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "crypto",
                    renderer: am5xy.AxisRendererX.new(root, {})
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: "Investissement",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "total",
                    categoryXField: "crypto"
                }));

                const chartData = data.map(item => ({
                    crypto: item.crypto_name,
                    total: item.purchase_price * item.quantity
                }));

                xAxis.data.setAll(chartData);
                series.data.setAll(chartData);
            });
        }

        document.getElementById('add-btn').onclick = addCrypto;
        document.getElementById('search-crypto').addEventListener('input', e => {
            const val = e.target.value;
            if (val.length > 1) searchCrypto(val);
        });

        fetchWallet();
    </script>
</body>

</html>