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

require_once '../includes/header.php';
?>

<div class="data-visualizer-header">
    <h1><i class="fas fa-chart-line"></i> Visualisation Graphique</h1>
    <p>Data : <?= htmlspecialchars($file['file_name']) ?></p>
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

<!-- Mêmes dépendances que data-to-chart.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    // Même code JS que data-to-chart.php mais avec chargement automatique
    document.addEventListener('DOMContentLoaded', function () {
        const fileExt = "<?= $file_ext ?>";
        const filePath = "<?= $file_path ?>";

        // Fonction pour charger le fichier via AJAX
        function loadFile() {
            if (fileExt === 'csv') {
                fetch(filePath)
                    .then(response => response.text())
                    .then(parseCSV);
            } else if (fileExt === 'json') {
                fetch(filePath)
                    .then(response => response.json())
                    .then(data => parseJSON(JSON.stringify(data)));
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

        // Initialiser le graphique
        loadFile();

        // ... (le reste du code JavaScript de data-to-chart.php)
        const chartContainer = document.getElementById("myChart");
        const columnSelector = document.getElementById("columnSelector");
        const chartTypeSelect = document.getElementById("chartType");
        const dropZone = document.getElementById("dropZone");
        const fileInput = document.getElementById("dataUpload");
        const dataTable = document.getElementById("dataTable");

        let chart;
        let currentRows = [];
        let currentLabel = "";
        let currentValue = "";

        dropZone.addEventListener("click", () => fileInput.click());
        dropZone.addEventListener("dragover", e => { e.preventDefault(); dropZone.classList.add("dragover"); });
        dropZone.addEventListener("dragleave", () => dropZone.classList.remove("dragover"));
        dropZone.addEventListener("drop", e => {
            e.preventDefault();
            dropZone.classList.remove("dragover");
            handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener("change", e => handleFile(e.target.files[0]));

        function handleFile(file) {
            const reader = new FileReader();
            const ext = file.name.split(".").pop().toLowerCase();

            if (ext === "csv") {
                reader.onload = e => parseCSV(e.target.result);
                reader.readAsText(file);
            } else if (["xls", "xlsx"].includes(ext)) {
                reader.onload = e => {
                    const wb = XLSX.read(e.target.result, { type: "binary" });
                    const ws = wb.Sheets[wb.SheetNames[0]];
                    const csv = XLSX.utils.sheet_to_csv(ws);
                    parseCSV(csv);
                };
                reader.readAsBinaryString(file);
            } else if (ext === "json") {
                reader.onload = e => parseJSON(e.target.result);
                reader.readAsText(file);
            }
        }

        function parseJSON(jsonString) {
            try {
                const data = JSON.parse(jsonString);
                if (!Array.isArray(data)) throw new Error("JSON doit être un tableau de lignes.");

                const headers = Object.keys(data[0]);
                const rows = data.filter(row => row[headers[0]] && row[headers[1]]);
                currentRows = rows;
                currentLabel = headers[0];
                currentValue = headers[1];

                columnSelector.innerHTML = headers.map(h => `<label class='me-2'><input type='checkbox' value='${h}' ${[headers[0], headers[1]].includes(h) ? 'checked' : ''}> ${h}</label>`).join("");
                renderChart(currentLabel, currentValue, rows);
                renderTable(rows);
                /*columnSelector.addEventListener("change", updateFromCheckbox);*/
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

            columnSelector.innerHTML = headers.map(h => `<label class='me-2'><input type='checkbox' value='${h}' ${[headers[0], headers[1]].includes(h) ? 'checked' : ''}> ${h}</label>`).join("");
            renderChart(currentLabel, currentValue, rows);
            renderTable(rows);
            columnSelector.addEventListener("change", updateFromCheckbox);
        }

        /*function updateFromCheckbox() {
            const selected = [...columnSelector.querySelectorAll("input:checked")].map(el => el.value);
            if (selected.length >= 2) {
                currentLabel = selected[0];
                currentValue = selected[1];
                renderChart(currentLabel, currentValue, currentRows);
                renderTable(currentRows);
            }
        }*/

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
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: "top" },
                        title: { display: true, text: "Visualisation des données" }
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

        chartTypeSelect.addEventListener("change", () => {
            if (currentLabel && currentValue && currentRows.length > 0) {
                renderChart(currentLabel, currentValue, currentRows);
            }
        });

        document.getElementById("exportPNG").addEventListener("click", () => {
            const url = chart.toBase64Image();
            const a = document.createElement("a");
            a.href = url;
            a.download = "chart.png";
            a.click();
        });

        document.getElementById("exportPDF").addEventListener("click", () => {
            html2canvas(chartContainer).then(canvas => {
                const imgData = canvas.toDataURL("image/png");
                const pdf = new jspdf.jsPDF();
                pdf.addImage(imgData, "PNG", 10, 10, 180, 100);
                pdf.save("chart.pdf");
            });
        });

        document.getElementById("exportTablePNG").addEventListener("click", () => {
            html2canvas(dataTable).then(canvas => {
                const link = document.createElement("a");
                link.href = canvas.toDataURL("image/png");
                link.download = "tableau.png";
                link.click();
            });
        });

        document.getElementById("exportTablePDF").addEventListener("click", () => {
            html2canvas(dataTable).then(canvas => {
                const imgData = canvas.toDataURL("image/png");
                const pdf = new jspdf.jsPDF();
                pdf.addImage(imgData, "PNG", 10, 10, 190, 0);
                pdf.save("tableau.pdf");
            });
        });
    });
    // Remplacer toute la logique des checkboxes par ceci :

    function setupColumnSelectors(headers) {
        const labelSelect = document.getElementById('labelColumn');
        const valueSelect = document.getElementById('valueColumn');

        // Vider et remplir les selects
        labelSelect.innerHTML = '<option value="">Choisir la colonne Label</option>';
        valueSelect.innerHTML = '<option value="">Choisir la colonne Valeur</option>';

        headers.forEach(header => {
            labelSelect.appendChild(new Option(header, header));
            valueSelect.appendChild(new Option(header, header));
        });

        // Sélection automatique des premières colonnes
        if (headers.length >= 2) {
            labelSelect.value = headers[0];
            valueSelect.value = headers[1];
            updateChartFromSelects();
        }

        // Écouteurs d'événements
        labelSelect.addEventListener('change', updateChartFromSelects);
        valueSelect.addEventListener('change', updateChartFromSelects);
    }

    function updateChartFromSelects() {
        const labelColumn = document.getElementById('labelColumn').value;
        const valueColumn = document.getElementById('valueColumn').value;

        if (labelColumn && valueColumn) {
            currentLabel = labelColumn;
            currentValue = valueColumn;
            renderChart(currentLabel, currentValue, currentRows);
        }
    }

    // Dans parseCSV/parseJSON, remplacer la partie checkboxes par :
    setupColumnSelectors(headers);
</script>

<?php require_once '../includes/footer.php'; ?>