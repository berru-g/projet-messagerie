<?php
require_once  '../includes/config.php';
require_once  '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

require_once  '../includes/header.php';
?>

<div class="container profile-container">
    <h2><?= htmlspecialchars($user['username']) ?></h2>
    
    <div class="profile-info">
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    </div>
    
    <div class="data-table-generator">
        <h3>Générateur de tableau à partir de fichier</h3>
        
        <div class="form-group">
            <label for="dataFile">Téléverser un fichier (JSON, CSV ou XLSX):</label>
            <input type="file" class="form-control-file" id="dataFile" accept=".json,.csv,.xlsx,.xls">
        </div>
        
        <div id="tableResult" class="table-responsive mt-4">
            <p class="text-muted">Aucune donnée à afficher. Veuillez téléverser un fichier.</p>
        </div>
        
        <button id="downloadPdf" class="btn btn-success mt-3" disabled>
            <i class="fas fa-file-pdf"></i> Télécharger en PDF
        </button>
    </div>
</div>

<!-- Bibliothèques -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
const { jsPDF } = window.jspdf;
let tableData = [];

document.getElementById('dataFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = e.target.result;
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (extension === 'xlsx' || extension === 'xls') {
                processExcel(data);
            } else if (extension === 'csv') {
                processCSV(data);
            } else if (extension === 'json') {
                processJSON(data);
            } else {
                throw new Error("Format non supporté");
            }
        } catch (error) {
            showError(error.message);
        }
    };

    if (file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
        reader.readAsArrayBuffer(file);
    } else {
        reader.readAsText(file);
    }
});

// Traitement Excel
function processExcel(data) {
    const workbook = XLSX.read(data, { type: 'array' });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
    tableData = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });
    
    if (tableData.length > 0) {
        displayTable(tableData);
    } else {
        throw new Error("Le fichier Excel est vide");
    }
}

// Traitement CSV
function processCSV(csvText) {
    const lines = csvText.split('\n').filter(line => line.trim() !== '');
    if (lines.length === 0) throw new Error("Fichier CSV vide");
    
    // Détection du délimiteur
    const delimiters = [',', ';', '\t'];
    let delimiter = ',';
    for (const d of delimiters) {
        if (lines[0].includes(d)) {
            delimiter = d;
            break;
        }
    }
    
    // Extraction des en-têtes
    const headers = lines[0].split(delimiter).map(h => h.trim());
    
    // Traitement des lignes
    tableData = [];
    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(delimiter).map(v => v.trim());
        if (values.length === 0 || values.every(v => v === '')) continue;
        
        const row = {};
        headers.forEach((header, index) => {
            row[header] = index < values.length ? values[index] : '';
        });
        tableData.push(row);
    }
    
    if (tableData.length > 0) {
        displayTable(tableData);
    } else {
        throw new Error("Aucune donnée valide dans le CSV");
    }
}

// Traitement JSON
function processJSON(jsonText) {
    const data = JSON.parse(jsonText);
    
    // Conversion en format tableau
    if (Array.isArray(data)) {
        tableData = data;
    } else if (typeof data === 'object') {
        // Si objet unique, on le convertit en tableau d'un élément
        tableData = [data];
    } else {
        // Si valeur simple, on crée un tableau spécial
        tableData = [{ "Valeur": data }];
    }
    
    if (tableData.length > 0) {
        displayTable(tableData);
    } else {
        throw new Error("Le JSON ne contient pas de données valides");
    }
}

// Affichage du tableau
function displayTable(data) {
    if (!data || data.length === 0) {
        document.getElementById('tableResult').innerHTML = 
            '<p class="text-muted">Aucune donnée à afficher.</p>';
        return;
    }
    
    const headers = Object.keys(data[0]);
    let html = '<table class="table table-striped table-bordered"><thead><tr>';
    
    headers.forEach(header => {
        html += `<th>${header}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    data.forEach(row => {
        html += '<tr>';
        headers.forEach(header => {
            let value = row[header];
            if (value === undefined || value === null) value = '';
            if (typeof value === 'object') value = JSON.stringify(value);
            html += `<td>${value}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    
    document.getElementById('tableResult').innerHTML = html;
    document.getElementById('downloadPdf').disabled = false;
    
    // Mise à jour du gestionnaire PDF
    document.getElementById('downloadPdf').onclick = function() {
        downloadAsPDF(data, headers);
    };
}

// Export PDF
function downloadAsPDF(data, headers) {
    const doc = new jsPDF();
    
    const body = data.map(row => {
        return headers.map(header => {
            let value = row[header];
            if (value === undefined || value === null) return '';
            if (typeof value === 'object') return JSON.stringify(value);
            return value;
        });
    });
    
    doc.autoTable({
        head: [headers],
        body: body,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [34, 139, 34] }
    });
    
    doc.save('export-donnees.pdf');
}

// Affichage des erreurs
function showError(message) {
    document.getElementById('tableResult').innerHTML = 
        `<div class="alert alert-danger">Erreur: ${message}</div>`;
    document.getElementById('downloadPdf').disabled = true;
}
</script>

<?php require_once  '../includes/footer.php'; ?>