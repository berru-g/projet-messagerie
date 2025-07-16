// JS complet pour :
// - gestion drag & drop + clic
// - parsing CSV / JSON / Excel
// - affichage chart auto avec Chart.js
// - export PDF avec html2canvas + jsPDF

// Librairies externes nécessaires :
// Chart.js, PapaParse, SheetJS (xlsx), html2canvas, jsPDF

let chart;

// Références DOM
const dropZone = document.getElementById("dropZone");
const fileInput = document.getElementById("dataUpload");
const clearBtn = document.getElementById("clearFile");

// Gérer clic sur zone => ouverture fichier
dropZone.addEventListener("click", () => fileInput.click());

// Drag & drop
["dragover", "dragenter"].forEach(evt => dropZone.addEventListener(evt, e => {
  e.preventDefault();
  dropZone.style.background = "#d0eaff";
}));

["dragleave", "drop"].forEach(evt => dropZone.addEventListener(evt, e => {
  e.preventDefault();
  dropZone.style.background = "#edf6fd";
}));

// Drop fichier => traitement
dropZone.addEventListener("drop", e => {
  e.preventDefault();
  if (e.dataTransfer.files.length > 0) {
    handleFile(e.dataTransfer.files[0]);
  }
});

// Choix via input
fileInput.addEventListener("change", e => {
  if (fileInput.files.length > 0) {
    handleFile(fileInput.files[0]);
  }
});

// Clear
clearBtn.addEventListener("click", () => location.reload());

// Traitement fichier principal
function handleFile(file) {
  const name = file.name.toLowerCase();
  if (name.endsWith(".csv")) parseCSV(file);
  else if (name.endsWith(".json")) parseJSON(file);
  else if (name.endsWith(".xls") || name.endsWith(".xlsx")) parseExcel(file);
  else alert("Format non supporté");
}

// CSV => Tableau => Chart
function parseCSV(file) {
  Papa.parse(file, {
    header: true,
    complete: result => drawChartFromData(result.data),
    error: err => alert("Erreur CSV : " + err)
  });
}

function parseJSON(file) {
  const reader = new FileReader();
  reader.onload = e => {
    try {
      const data = JSON.parse(e.target.result);
      drawChartFromData(data);
    } catch (e) {
      alert("Erreur JSON : " + e);
    }
  };
  reader.readAsText(file);
}

function parseExcel(file) {
  const reader = new FileReader();
  reader.onload = e => {
    const data = new Uint8Array(e.target.result);
    const workbook = XLSX.read(data, { type: "array" });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(firstSheet);
    drawChartFromData(json);
  };
  reader.readAsArrayBuffer(file);
}

// Génération auto du graphique avec Chart.js
function drawChartFromData(data) {
  const keys = Object.keys(data[0]);
  if (keys.length < 2) return alert("Il faut au moins 2 colonnes");

  const labels = data.map(row => row[keys[0]]);
  const values = data.map(row => parseFloat(row[keys[1]]));

  const ctx = document.createElement("canvas");
  ctx.id = "chartCanvas";
  document.body.appendChild(ctx);

  if (chart) chart.destroy();

  chart = new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [{
        label: keys[1],
        data: values,
        backgroundColor: "#3b82f6"
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: "Visualisation automatique",
          font: { size: 18 }
        }
      }
    }
  });

  showDownloadPDF(ctx);
}

// Bouton téléchargement PDF
function showDownloadPDF(canvasElem) {
  const btn = document.createElement("button");
  btn.innerText = "Télécharger en PDF";
  btn.style.marginTop = "2rem";
  btn.style.display = "block";
  btn.style.marginInline = "auto";
  btn.onclick = () => {
    html2canvas(canvasElem).then(canvas => {
      const imgData = canvas.toDataURL("image/png");
      const pdf = new jsPDF();
      pdf.addImage(imgData, "PNG", 10, 10, 180, 100);
      pdf.save("chart.pdf");
    });
  };
  document.body.appendChild(btn);
}
