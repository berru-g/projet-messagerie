<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

// Récupérer le fichier depuis l'URL
$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$file = getFileById($file_id);

if (!$file || !canAccessFile($_SESSION['user_id'], $file_id)) {
    die("Accès refusé ou fichier non trouvé");
}

$file_path = '../uploads/' . $file['file_path'];
$file_ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
$user = getUserById($_SESSION['user_id']);
$owner = getUserById($file['owner_id']);


require_once '../includes/header.php';
?>
<div class="data-visualizer-header">
    <h1><i class="fas fa-chart-line"></i> Data Visualizer</h1>
    <p><i class="fas fa-file"></i> <?= htmlspecialchars($file['file_name']) ?></p>
    
    <?php if ($owner): ?>
        <p>
            <i class="fas fa-user"></i>
            <a href="<?= BASE_URL ?>/pages/profile.php?user_id=<?= $owner['id'] ?>" class="owner-link">
                <?= htmlspecialchars($owner['username'] ?? $owner['email']) ?>
            </a>
        </p>
    <?php endif; ?>
    <!--btn retour exactement là où on en etais via historyback protège contre l'injection-->
    <a href="#" class="primary-btn back-btn" data-fallback="search.php">
        <i class="fa-solid fa-reply"></i>
    </a>
</div>

<div class="container mt-5">
    <div id="columnSelector" class="mb-4"></div>
    
    <!-- Remplacement du canvas par le container amCharts -->
    <div id="chartdiv" style="width: 100%; height: 500px;"></div>
    
    <div class="mt-4 d-flex flex-wrap gap-2 align-items-center">
        <select id="chartType" class="form-select w-auto">
            <option value="column">Colonnes</option>
            <option value="bar">Barres</option>
            <option value="line">Lignes</option>
            <option value="pie">Camembert</option>
            <option value="donut">Donut</option>
            <option value="radar">Radar</option>
            <option value="stackedColumn">Colonnes Empilées</option>
            <option value="stackedBar">Barres Empilées</option>
        </select>
        
        <select id="chartTheme" class="form-select w-auto">
            <option value="am4themes_animated">Thème Animé</option>
            <option value="am4themes_dark">Thème Sombre</option>
            <option value="am4themes_material">Thème Material</option>
        </select>
        
        <button id="exportPNG" class="btn btn-primary"><i class="fas fa-image"></i> PNG</button>
        <button id="exportPDF" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF</button>
        <button id="exportCSV" class="btn btn-success"><i class="fas fa-file-csv"></i> CSV</button>
    </div>

    <div class="table-responsive mt-4">
        <table id="dataTable" class="table table-bordered table-striped"></table>
    </div>
</div>

<!-- Scripts amCharts (ajoutez ces lignes dans votre section head ou avant </body>) -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/dark.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/material.js"></script>

<script>
// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Détection automatique du thème
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    am4core.useTheme(prefersDark ? am4themes_dark : am4themes_animated);
    
    // Si vous avez déjà les données en PHP, vous pouvez les injecter directement
    <?php if (!empty($fileData)): ?>
        initChart(<?= json_encode($fileData) ?>);
    <?php endif; ?>
});

// Fonction principale
function initChart(rawData) {
    let currentChart = null;
    let currentTheme = null;
    
    // Traitement des données
    const data = Array.isArray(rawData) ? rawData : parseRawData(rawData);
    const fields = data.length > 0 ? Object.keys(data[0]) : [];
    
    // Mise à jour de l'interface
    updateColumnSelector(fields);
    renderTable(data, fields);
    createChart('column', fields[0], fields.slice(1), data);
    
    // Écouteurs d'événements
    document.getElementById('chartType').addEventListener('change', function() {
        const type = this.value;
        const category = document.getElementById('categoryField').value;
        const series = getSelectedSeries();
        createChart(type, category, series, data);
    });
    
    document.getElementById('chartTheme').addEventListener('change', function() {
        am4core.unuseTheme(currentTheme);
        am4core.useTheme(am4themes[this.value]);
        currentTheme = am4themes[this.value];
    });
    
    // Fonctions internes
    function parseRawData(raw) {
        if (typeof raw === 'string') {
            try {
                return JSON.parse(raw);
            } catch {
                return Papa.parse(raw, { header: true }).data;
            }
        }
        return raw;
    }
    
    function updateColumnSelector(fields) {
        const container = document.getElementById('columnSelector');
        if (fields.length < 2) return;
        
        container.innerHTML = `
            <div class="mb-3">
                <label class="form-label">Axe des X</label>
                <select id="categoryField" class="form-select">
                    ${fields.map(f => `<option value="${f}">${f}</option>`).join('')}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Séries</label>
                ${fields.slice(1).map(f => `
                    <div class="form-check">
                        <input type="checkbox" id="series-${f}" value="${f}" checked class="form-check-input">
                        <label for="series-${f}" class="form-check-label">${f}</label>
                    </div>
                `).join('')}
            </div>
        `;
        
        document.getElementById('categoryField').addEventListener('change', updateChart);
        container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', updateChart);
        });
    }
    
    function getSelectedSeries() {
        return Array.from(document.querySelectorAll('#columnSelector input[type="checkbox"]:checked'))
                   .map(el => el.value);
    }
    
    function updateChart() {
        const type = document.getElementById('chartType').value;
        const category = document.getElementById('categoryField').value;
        const series = getSelectedSeries();
        createChart(type, category, series, data);
    }
    
    function createChart(type, category, series, chartData) {
        if (currentChart) currentChart.dispose();
        if (series.length === 0) return;
        
        const chart = am4core.create('chartdiv', getChartType(type));
        
        // Configuration commune
        chart.data = chartData.map(row => {
            const item = { category: row[category] };
            series.forEach(s => item[s] = parseFloat(row[s]) || 0);
            return item;
        });
        
        // Configuration spécifique
        setupChartByType(chart, type, category, series);
        
        currentChart = chart;
    }
    
    function getChartType(type) {
        const map = {
            pie: am4charts.PieChart,
            donut: am4charts.PieChart,
            radar: am4charts.RadarChart,
            funnel: am4charts.FunnelChart,
            default: am4charts.XYChart
        };
        return map[type] || map.default;
    }
    
    function setupChartByType(chart, type, category, series) {
        switch(type) {
            case 'pie':
            case 'donut':
                setupPieChart(chart, type, category, series[0]);
                break;
            case 'radar':
                setupRadarChart(chart, category, series);
                break;
            default:
                setupXYChart(chart, type, category, series);
        }
    }
    
    function setupXYChart(chart, type, category, series) {
        // Axes
        const xAxis = chart.xAxes.push(type.includes('bar') 
            ? new am4charts.ValueAxis() 
            : new am4charts.CategoryAxis());
        
        xAxis.dataFields.category = 'category';
        if (type.includes('bar')) xAxis.renderer.opposite = true;
        
        const yAxis = chart.yAxes.push(new am4charts.ValueAxis());
        if (type.includes('bar')) yAxis.renderer.opposite = true;
        
        // Series
        series.forEach(s => {
            const series = chart.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = s;
            series.dataFields.categoryX = 'category';
            series.name = s;
            
            if (type.includes('stacked')) series.stacked = true;
            if (type.includes('bar')) {
                series.dataFields.valueX = s;
                series.dataFields.categoryY = 'category';
            }
            if (type === 'line') {
                chart.series.removeIndex(chart.series.indexOf(series));
                const lineSeries = chart.series.push(new am4charts.LineSeries());
                lineSeries.dataFields.valueY = s;
                lineSeries.dataFields.categoryX = 'category';
                lineSeries.name = s;
                lineSeries.strokeWidth = 3;
            }
        });
        
        // Legend
        chart.legend = new am4charts.Legend();
    }
    
    function setupPieChart(chart, type, category, valueField) {
        const series = chart.series.push(new am4charts.PieSeries());
        series.dataFields.value = valueField;
        series.dataFields.category = category;
        series.labels.template.disabled = true;
        series.ticks.template.disabled = true;
        
        if (type === 'donut') {
            series.innerRadius = am4core.percent(50);
        }
    }
    
    function setupRadarChart(chart, category, series) {
        const categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
        categoryAxis.dataFields.category = 'category';
        
        const valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
        
        series.forEach(s => {
            const radarSeries = chart.series.push(new am4charts.RadarSeries());
            radarSeries.dataFields.valueY = s;
            radarSeries.dataFields.categoryX = 'category';
            radarSeries.name = s;
            radarSeries.strokeWidth = 3;
        });
    }
    
    function renderTable(data, fields) {
        const table = document.getElementById('dataTable');
        if (!data.length) return;
        
        // Header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        fields.forEach(f => {
            const th = document.createElement('th');
            th.textContent = f;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        
        // Body
        const tbody = document.createElement('tbody');
        data.forEach(row => {
            const tr = document.createElement('tr');
            fields.forEach(f => {
                const td = document.createElement('td');
                td.textContent = row[f] || '';
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        
        table.innerHTML = '';
        table.appendChild(thead);
        table.appendChild(tbody);
    }
    
    // Gestion des exports
    document.getElementById('exportPNG').addEventListener('click', () => {
        if (currentChart) currentChart.exporting.export('png');
    });
    
    document.getElementById('exportPDF').addEventListener('click', () => {
        if (currentChart) currentChart.exporting.export('pdf');
    });
    
    document.getElementById('exportCSV').addEventListener('click', () => {
        const csv = Papa.unparse(data);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        saveAs(blob, 'export.csv');
    });
}

// Si vous avez besoin de charger des données depuis un fichier
function handleFileUpload(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const data = e.target.result;
        initChart(data);
    };
    reader.readAsText(file);
}
</script>

<!--V1--<div class="data-visualizer-header">
    <h1><i class="fas fa-chart-line"></i> Data Visualizer</h1>
    <p><i class="fas fa-file"></i> <?= htmlspecialchars($file['file_name']) ?></p>
    
    <?php if ($owner): ?>
        <p>
            <i class="fas fa-user"></i>
            <a href="<?= BASE_URL ?>/pages/profile.php?user_id=<?= $owner['id'] ?>" class="owner-link">
                <?= htmlspecialchars($owner['username'] ?? $owner['email']) ?>
            </a>
        </p>
    <?php endif; ?>
    !--btn retour exactement là où on en etais via historyback protège contre l'injection--
    <a href="#" class="primary-btn back-btn" data-fallback="search.php">
        <i class="fa-solid fa-reply"></i>
    </a>
</div>

<div class="container mt-5">
    <div id="columnSelector" class="mb-4"></div>
    <canvas id="myChart" height="120"></canvas>
    <div class="mt-4 d-flex flex-wrap gap-2">
        <select id="chartType" class="form-select w-auto">
            <option value="bar">Barres</option>
            <option value="line">Lignes</option>
            <option value="pie">Camembert</option>
            <option value="doughnut">Donut</option>
            <option value="radar">Radar</option>
        </select>
        <button id="exportPNG" class="btn btn-primary"><i class="fas fa-image"></i> PNG Graphique</button>
        <button id="exportPDF" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> PDF Graphique</button>
    </div>

    <div class="table-responsive mt-4">
        <table id="dataTable" class="table table-bordered table-striped"></table>
    </div>
</div>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartContainer = document.getElementById("myChart");
        const columnSelector = document.getElementById("columnSelector");
        const chartTypeSelect = document.getElementById("chartType");
        const dataTable = document.getElementById("dataTable");

        let chart;
        let currentRows = [];
        let currentLabel = "";
        let currentValue = "";

        // Charger le fichier selon son type
        function loadFile() {
            const fileExt = "<?= $file_ext ?>";
            const filePath = "<?= $file_path ?>";

            if (fileExt === 'csv') {
                fetch(filePath)
                    .then(response => response.text())
                    .then(csv => parseCSV(csv));
            } else if (fileExt === 'json') {
                fetch(filePath)
                    .then(response => response.json())
                    .then(data => parseJSON(data));
            } else if (fileExt === 'xlsx' || fileExt === 'xls') {
                fetch(filePath)
                    .then(response => response.arrayBuffer())
                    .then(buffer => {
                        const wb = XLSX.read(buffer, { type: "array" });
                        const ws = wb.Sheets[wb.SheetNames[0]];
                        const csv = XLSX.utils.sheet_to_csv(ws);
                        parseCSV(csv);
                    });
            }
        }

        function parseJSON(data) {
            try {
                if (!Array.isArray(data)) throw new Error("JSON doit être un tableau de lignes.");

                const headers = Object.keys(data[0]);
                const rows = data.filter(row => row[headers[0]] && row[headers[1]]);
                currentRows = rows;
                currentLabel = headers[0];
                currentValue = headers[1];

                columnSelector.innerHTML = headers.map(h =>
                    `<label class='me-2'><input type='checkbox' value='${h}' ${[headers[0], headers[1]].includes(h) ? 'checked' : ''}> ${h}</label>`
                ).join("");

                renderChart(currentLabel, currentValue, rows);
                renderTable(rows);

                columnSelector.addEventListener("change", updateFromCheckbox);
            } catch (err) {
                alert("Erreur lors de l'analyse du JSON : " + err.message);
            }
        }

        function parseCSV(csv) {
            const data = Papa.parse(csv, { header: true });
            const headers = data.meta.fields;
            const rows = data.data.filter(row => row[headers[0]] && row[headers[1]]);
            currentRows = rows;
            currentLabel = headers[0];
            currentValue = headers[1];

            columnSelector.innerHTML = headers.map(h =>
                `<label class='me-2'><input type='checkbox' value='${h}' ${[headers[0], headers[1]].includes(h) ? 'checked' : ''}> ${h}</label>`
            ).join("");

            renderChart(currentLabel, currentValue, rows);
            renderTable(rows);
            columnSelector.addEventListener("change", updateFromCheckbox);
        }

        function updateFromCheckbox() {
            const selected = [...columnSelector.querySelectorAll("input:checked")].map(el => el.value);
            if (selected.length >= 2) {
                currentLabel = selected[0];
                currentValue = selected[1];
                renderChart(currentLabel, currentValue, currentRows);
                renderTable(currentRows);
            }
        }

        function renderChart(labelKey, valueKey, rows) {
            const labels = rows.map(r => r[labelKey]);
            const values = rows.map(r => parseFloat(r[valueKey]));
            const type = chartTypeSelect.value;

            if (chart) chart.destroy();

            chart = new Chart(chartContainer, {
                type,
                data: {
                    labels,
                    datasets: [{
                        label: `${valueKey} par ${labelKey}`,
                        data: values,
                        backgroundColor: "rgba(54, 162, 235, 0.5)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1
                    }]
                },
                // Dans renderChart(), modifie les options :
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { family: 'Arial', size: 14 } }
                        },
                        tooltip: {
                            backgroundColor: '#ab9ff2',
                            bodyFont: { size: 14 },
                            padding: 12,
                            cornerRadius: 12
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 12 } }
                        },
                        y: {
                            grid: { color: '#ab9ff2' },
                            ticks: { font: { size: 12 } }
                        }
                    },
                    elements: {
                        bar: { borderRadius: 6 },
                        line: { tension: 0.4 }
                    }
                }
            });
        }

        function renderTable(rows) {
            if (!rows.length) return;
            const headers = Object.keys(rows[0]);
            const thead = `<thead><tr>${headers.map(h => `<th>${h}</th>`).join("")}</tr></thead>`;
            const tbody = `<tbody>${rows.map(r => `<tr>${headers.map(h => `<td>${r[h]}</td>`).join("")}</tr>`).join("")}</tbody>`;
            dataTable.innerHTML = thead + tbody;
        }

        // Écouteurs d'événements
        chartTypeSelect.addEventListener("change", () => {
            if (currentLabel && currentValue && currentRows.length > 0) {
                renderChart(currentLabel, currentValue, currentRows);
            }
        });

        document.getElementById("exportPNG").addEventListener("click", () => {
            const url = chart.toBase64Image();
            const a = document.createElement("a");
            a.href = url;
            a.download = "<?= BASE_URL ?>chart.png";
            a.click();
        });

        document.getElementById("exportPDF").addEventListener("click", () => {
            html2canvas(chartContainer).then(canvas => {
                const imgData = canvas.toDataURL("<?= BASE_URL ?>image/png");
                const pdf = new jspdf.jsPDF();
                pdf.addImage(imgData, "PNG", 10, 10, 180, 100);
                pdf.save("chart.pdf");
            });
        });

        // Initialiser le graphique
        loadFile();
    });
    // btn retour exactement là où on en etais via historyback protège contre l'injection
    document.querySelectorAll('.back-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            // Si l'historique contient la page précédente (et n'est pas externe)
            if (document.referrer.includes(window.location.hostname)) {
                history.back();
            } else {
                window.location.href = btn.dataset.fallback;
            }
        });
    });
</script>-->

<?php require_once '../includes/footer.php'; ?>