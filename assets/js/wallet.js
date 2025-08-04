// wallet.js
const API_URL = '/api/api_wallet.php';
const COINGECKO_URL = 'https://api.coingecko.com/api/v3/simple/price';

let holdings = [];

// Auto-complete
document.getElementById('search-crypto').addEventListener('input', async (e) => {
  const query = e.target.value;
  if (query.length < 2) return;

  const res = await fetch(`https://api.coingecko.com/api/v3/search?query=${query}`);
  const data = await res.json();
  const list = document.getElementById('autocomplete-list');
  list.innerHTML = '';
  data.coins.slice(0, 5).forEach(c => {
    const li = document.createElement('li');
    li.textContent = c.name;
    li.addEventListener('click', () => {
      document.getElementById('crypto-id').value = c.id;
      document.getElementById('crypto-name').value = c.name;
      document.getElementById('search-crypto').value = c.name;
      list.innerHTML = '';
    });
    list.appendChild(li);
  });
});

// Ajouter une crypto
document.getElementById('add-btn').addEventListener('click', async () => {
  const crypto_id = document.getElementById('crypto-id').value;
  const crypto_name = document.getElementById('crypto-name').value;
  const purchase_price = parseFloat(document.getElementById('purchase-price').value);
  const quantity = parseFloat(document.getElementById('quantity').value);

  if (!crypto_id || !purchase_price || !quantity) return;

  await fetch(API_URL, {
    method: 'POST',
    body: JSON.stringify({
      action: 'add',
      user_id: userId,
      crypto_id, crypto_name,
      purchase_price, quantity
    })
  });

  loadWallet();
});

// Charger portefeuille
async function loadWallet() {
  const res = await fetch(`${API_URL}?action=get&user_id=${userId}`);
  holdings = await res.json();
  const ids = holdings.map(c => c.crypto_id).join(',');
  const prices = await fetch(`${COINGECKO_URL}?ids=${ids}&vs_currencies=usd&include_24hr_change=true`).then(r => r.json());

  let html = '';
  let totalInvested = 0, totalCurrent = 0, total24hChange = 0;

  holdings.forEach(c => {
    const current = prices[c.crypto_id]?.usd ?? 0;
    const change24h = prices[c.crypto_id]?.usd_24h_change ?? 0;
    const value = c.quantity * current;
    const invested = c.quantity * c.purchase_price;
    totalInvested += invested;
    totalCurrent += value;
    total24hChange += (value * (change24h / 100));

    html += `<div class="wallet-item">
      <strong>${c.crypto_name}</strong><br>
      Investi: $${invested.toFixed(2)} | Actuel: $${value.toFixed(2)}<br>
      <button onclick="deleteCrypto('${c.crypto_id}')">Supprimer</button>
    </div>`;
  });

  document.getElementById('wallet-list').innerHTML = html;
  document.getElementById('total-invested').textContent = `$${totalInvested.toFixed(2)}`;
  document.getElementById('current-value').textContent = `$${totalCurrent.toFixed(2)}`;
  document.getElementById('performance-24h').textContent = `${((total24hChange / totalCurrent) * 100).toFixed(2)}%`;

  drawPieChart(holdings, prices);
  drawGrowthChart();
}

// Supprimer une crypto
async function deleteCrypto(id) {
  await fetch(API_URL, {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', user_id: userId, crypto_id: id })
  });
  loadWallet();
}

// Graphique Pie
function drawPieChart(holdings, prices) {
  am5.ready(() => {
    let root = am5.Root.new("chartdiv");
    root.setThemes([am5themes_Animated.new(root)]);
    let chart = root.container.children.push(am5percent.PieChart.new(root, { layout: root.verticalLayout }));
    let series = chart.series.push(am5percent.PieSeries.new(root, {
      valueField: "value",
      categoryField: "name"
    }));
    const data = holdings.map(h => ({
      name: h.crypto_name,
      value: (prices[h.crypto_id]?.usd ?? 0) * h.quantity
    }));
    series.data.setAll(data);
  });
}

// Graphique de croissance
async function drawGrowthChart() {
  const res = await fetch(`${API_URL}?action=history&user_id=${userId}`);
  const history = await res.json();

  am5.ready(() => {
    let root = am5.Root.new("growthchart");
    root.setThemes([am5themes_Animated.new(root)]);
    let chart = root.container.children.push(am5xy.XYChart.new(root, { panX: true, panY: true, wheelX: "panX", wheelY: "zoomX" }));
    let xAxis = chart.xAxes.push(am5xy.DateAxis.new(root, {
      maxDeviation: 0.2,
      groupData: false,
      baseInterval: { timeUnit: "day", count: 1 },
      renderer: am5xy.AxisRendererX.new(root, {})
    }));
    let yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
      renderer: am5xy.AxisRendererY.new(root, {})
    }));
    let series = chart.series.push(am5xy.LineSeries.new(root, {
      name: "Capital",
      xAxis: xAxis,
      yAxis: yAxis,
      valueYField: "total",
      valueXField: "date"
    }));
    series.data.setAll(history.map(h => ({
      date: new Date(h.date).getTime(),
      total: parseFloat(h.total)
    })));
    series.appear(1000);
    chart.appear(1000, 100);
  });
}

// Init
loadWallet();
