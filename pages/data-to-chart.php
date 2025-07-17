<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
  header("Location: " . BASE_URL . "/pages/login.php");
  exit;
}

$user = getUserById($_SESSION['user_id']);

require_once '../includes/header.php';
?>

<div class="data-visualizer-header">
  <h1><i class="fas fa-chart-line"></i> Data Visualizer</h1>
  <p>Transformez vos fichiers en insights visuels en 3 étapes</p>
  <div class="steps">
    <div class="step active">1 <span>Importer</span></div>
    <div class="step">2 <span>Visualiser</span></div>
    <div class="step">3 <span>Configurer</span></div>
  </div>
</div>

<div class="upload-container">
  <div class="upload-dropzone" id="dropZone">
    <i class="fas fa-cloud-upload-alt"></i>
    <p><strong>Déposez votre fichier ici</strong></p>
    <p>ou cliquez pour parcourir</p>
    <div class="file-types mt-2">
      <span class="badge badge-csv">.csv</span>
      <span class="badge badge-excel">.xlsx</span>
      <span class="badge badge-json">.json</span>
    </div>
    <input type="file" id="dataUpload" accept=".csv,.json,.xls,.xlsx" hidden>
  </div>
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

  <div class="mt-4 d-flex flex-wrap gap-2">

    <button id="exportTablePNG" class="btn btn-outline-primary"><i class="fas fa-image"></i> PNG Tableau</button>
    <button id="exportTablePDF" class="btn btn-outline-secondary"><i class="fas fa-file-pdf"></i> PDF Tableau</button>
  </div>

  <div class="mt-4">
    <h5>📁 Format de fichier idéal (CSV/Excel)</h5>
    <p>Assurez-vous que votre fichier respecte ce format de base :</p>
    <pre>
Produit,Quantité
Pommes,120
Bananes,90
Poires,60
    </pre>
    <p>💡 Colonne 1 : catégorie ou nom / Colonne 2 : valeur numérique</p>
  </div>
</div>

<link rel="stylesheet" href="../assets/css/data-to-chart.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
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

    columnSelector.innerHTML = headers.map(h => `<label class='me-2'><input type='checkbox' value='${h}' ${[headers[0], headers[1]].includes(h) ? 'checked' : ''}> ${h}</label>`).join("");
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
</script>

<?php require_once '../includes/footer.php'; ?>