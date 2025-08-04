<!-- wallet.php -->
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$userId = intval($_SESSION['user_id']);
$user = getUserById($userId);
if (!$user) die("Utilisateur non trouvé");

require_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Wallet Crypto</title>
  <link rel="stylesheet" href="/assets/css/wallet.css">
  <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
  <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
  <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
</head>
<body>
  <div class="container">
    <h1>Mon Portefeuille Crypto</h1>
    <div class="wallet-summary">
  <p>Total investi : <span id="total-invested">$0.00</span></p>
  <p>Valeur actuelle : <span id="current-value">$0.00</span></p>
  <p>Variation 24h : <span id="performance-24h">0.00%</span></p>
</div>

<div id="growthchart" style="width: 100%; height: 400px;"></div>


    <div class="form-card">
      <label>Rechercher une crypto :</label>
      <input type="text" id="search-crypto" placeholder="ex: bitcoin">
      <ul id="autocomplete-list"></ul>
      <input type="hidden" id="crypto-id">
      <input type="text" id="crypto-name" placeholder="Nom complet">
      <input type="number" id="purchase-price" placeholder="Prix d'achat ($)">
      <input type="number" id="quantity" placeholder="Quantité">
      <button id="add-btn">Ajouter</button>
    </div>

    <div id="wallet-list"></div>
    <div id="chartdiv" style="width: 100%; height: 400px;"></div>
  </div>

  <script>const userId = <?= $userId ?>;</script>
  <script src="../assets/js/wallet.js"></script>
  <script>
  const apiUrl = "/pages/api_wallet.php";

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
    am5.ready(function() {
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

