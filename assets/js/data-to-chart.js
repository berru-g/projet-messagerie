// Initialisation
const { jsPDF } = window.jspdf;
let myChart = null;
let rawData = [];
let currentPage = 1;
const rowsPerPage = 10;
let colorTheme = 'default';

// Thèmes de couleurs
const colorThemes = {
    default: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'],
    pastel: ['#a8d8ea', '#aa96da', '#fcbad3', '#ffffd2', '#ffaaa5', '#ff8b94', '#a6e3e9'],
    vibrant: ['#ff595e', '#ffca3a', '#8ac926', '#1982c4', '#6a4c93', '#ff9f1c', '#2ec4b6'],
    monochrome: ['#495057', '#6c757d', '#adb5bd', '#dee2e6', '#e9ecef', '#f8f9fa', '#212529']
};

// Écouteurs d'événements
document.addEventListener('DOMContentLoaded', function () {

    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('dropZone');

    if (!fileInput || !dropZone) {
        console.error("fileInput ou dropZone introuvable !");
        return;
    }
    
    console.log("Initialisation du script"); // Vérifiez que ce message apparaît

    fileInput.addEventListener('click', (e) => {
        console.log("Input file cliqué", e); // Vérifiez quand vous cliquez
        e.stopPropagation(); // Empêche la propagation à la dropzone
    });

    dropZone.addEventListener('click', (e) => {
        console.log("Dropzone cliquée", e); // Vérifiez quand vous cliquez
    });

    fileInput.addEventListener('change', function (e) {
        console.log("Fichier sélectionné", e.target.files); // Vérifiez les fichiers
    });
    // Meilleure gestion du clic sur la dropzone
    dropZone.style.position = 'relative';

    // Assurez-vous que l'input file couvre toute la zone
    fileInput.style.position = 'absolute';
    fileInput.style.width = '100%';
    fileInput.style.height = '100%';
    fileInput.style.top = '0';
    fileInput.style.left = '0';
    fileInput.style.opacity = '0';
    fileInput.style.cursor = 'pointer';

    // Feedback visuel lors du clic
    dropZone.addEventListener('mousedown', () => {
        dropZone.style.transform = 'scale(0.98)';
        dropZone.style.opacity = '0.9';
    });

    dropZone.addEventListener('mouseup', () => {
        dropZone.style.transform = '';
        dropZone.style.opacity = '';
    });

    dropZone.addEventListener('mouseleave', () => {
        dropZone.style.transform = '';
        dropZone.style.opacity = '';
    });
    

    // Drag and drop
    dropZone.addEventListener('dragover', handleDragOver);
    dropZone.addEventListener('drop', handleDrop);

    // Boutons
    document.getElementById('dataUpload').addEventListener('change', handleFileUpload);
    document.getElementById('cancelUpload').addEventListener('click', resetUpload);
    document.getElementById('updateChart').addEventListener('click', updateChart);
    document.getElementById('exportBtn').addEventListener('click', exportVisualization);
    document.getElementById('copyLinkBtn').addEventListener('click', copyShareLink);
    document.getElementById('embedBtn').addEventListener('click', generateEmbedCode);

    // Recherche tableau
    document.getElementById('tableSearch').addEventListener('input', filterTable);

    // Changement de thème
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', changeColorTheme);
    });

    // Bouton d'exemple
    document.querySelectorAll('[data-sample]').forEach(btn => {
        btn.addEventListener('click', loadSampleData);
    });
});

// Gestion du drag and drop
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.target.classList.add('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    e.target.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length) {
        document.getElementById('dataUpload').files = files;
        handleFileUpload({ target: document.getElementById('dataUpload') });
    }
}

// Gestion de l'upload
function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const fileType = file.name.split('.').pop().toLowerCase();
    if (!['csv', 'json', 'xlsx', 'xls'].includes(fileType)) {
        showAlert('Format de fichier non supporté', 'danger');
        return;
    }

    // Afficher l'info fichier
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('filePreview').classList.remove('d-none');
    document.getElementById('uploadProgress').style.width = '0%';

    // Simuler la progression
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 10;
        if (progress >= 100) {
            progress = 100;
            clearInterval(progressInterval);
        }
        document.getElementById('uploadProgress').style.width = `${progress}%`;
    }, 200);

    const reader = new FileReader();
    reader.onload = function (e) {
        clearInterval(progressInterval);
        document.getElementById('uploadProgress').style.width = '100%';

        try {
            // Conversion selon le type de fichier
            switch (fileType) {
                case 'csv':
                    rawData = parseCSV(e.target.result);
                    break;
                case 'json':
                    rawData = parseJSON(e.target.result);
                    break;
                case 'xlsx':
                case 'xls':
                    rawData = parseExcel(e.target.result);
                    break;
            }

            if (rawData.length === 0) throw new Error("Aucune donnée valide trouvée");

            // Passer à l'étape suivante
            showStep(2);
            displayDataTable();
            setupChartOptions();

        } catch (error) {
            showAlert("Erreur: " + error.message, 'danger');
            resetUpload();
        }
    };

    if (fileType === 'xlsx' || fileType === 'xls') {
        reader.readAsArrayBuffer(file);
    } else {
        reader.readAsText(file);
    }
}

// Fonctions de parsing améliorées
function parseCSV(csvText) {
    return Papa.parse(csvText, {
        header: true,
        skipEmptyLines: true,
        dynamicTyping: true,
        transform: value => value === '' ? null : value
    }).data;
}

function parseJSON(jsonText) {
    const data = JSON.parse(jsonText);

    // Normalisation des données
    if (Array.isArray(data)) {
        return data;
    } else if (typeof data === 'object' && data !== null) {
        if (Object.keys(data).some(key => typeof data[key] === 'object')) {
            // Objet complexe avec sous-objets
            return [flattenObject(data)];
        } else {
            // Objet simple
            return [data];
        }
    } else {
        // Valeur simple
        return [{ "Valeur": data }];
    }
}

function parseExcel(excelData) {
    const workbook = XLSX.read(new Uint8Array(excelData), { type: 'array' });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
    return XLSX.utils.sheet_to_json(firstSheet, { defval: null });
}

// Helper functions
function flattenObject(obj, prefix = '') {
    return Object.keys(obj).reduce((acc, k) => {
        const pre = prefix.length ? prefix + '.' : '';
        if (typeof obj[k] === 'object' && obj[k] !== null) {
            Object.assign(acc, flattenObject(obj[k], pre + k));
        } else {
            acc[pre + k] = obj[k];
        }
        return acc;
    }, {});
}

function formatBytes(bytes) {
    const k = 1024;
    const sizes = ['Bytes', 'Ko', 'Mo', 'Go', 'To'];
    if (bytes === 0) return '0 Bytes';

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const size = (bytes / Math.pow(k, i)).toFixed(2);
    return `${size} ${sizes[i]}`;
}
/*
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    //affiché "2.33 Mo" par exemple
    //return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i]; 
    //juste le nombre en float
   //return parseFloat((bytes / Math.pow(k, i)).toFixed(2));

}*/

// Affichage des données avec pagination
function displayDataTable(filteredData = null) {
    const data = filteredData || rawData;
    if (!data || data.length === 0) return;

    const headers = Object.keys(data[0]);
    const startIdx = (currentPage - 1) * rowsPerPage;
    const paginatedData = data.slice(startIdx, startIdx + rowsPerPage);

    let html = `<table class="table table-hover"><thead><tr>`;

    headers.forEach(header => {
        html += `<th>${escapeHtml(header)}</th>`;
    });

    html += `</tr></thead><tbody>`;

    paginatedData.forEach((row, rowIdx) => {
        html += `<tr>`;
        headers.forEach(header => {
            let value = row[header];
            if (value === null || value === undefined) value = '<span class="text-muted">NULL</span>';
            if (typeof value === 'object') value = JSON.stringify(value);
            html += `<td>${value}</td>`;
        });
        html += `</tr>`;
    });

    html += `</tbody></table>`;
    document.getElementById('tableContainer').innerHTML = html;

    // Pagination
    const totalPages = Math.ceil(data.length / rowsPerPage);
    updatePagination(totalPages);

    // Info lignes
    document.getElementById('rowsInfo').textContent =
        `Affichage ${startIdx + 1}-${Math.min(startIdx + rowsPerPage, data.length)} sur ${data.length}`;
}

function updatePagination(totalPages) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    // Bouton Précédent
    addPaginationItem(pagination, 'Précédent', currentPage > 1, () => {
        if (currentPage > 1) {
            currentPage--;
            displayDataTable();
        }
    });

    // Pages
    for (let i = 1; i <= totalPages; i++) {
        addPaginationItem(pagination, i, i !== currentPage, () => {
            currentPage = i;
            displayDataTable();
        }, i === currentPage);
    }

    // Bouton Suivant
    addPaginationItem(pagination, 'Suivant', currentPage < totalPages, () => {
        if (currentPage < totalPages) {
            currentPage++;
            displayDataTable();
        }
    });
}

function addPaginationItem(container, text, enabled, onClick, isActive = false) {
    const li = document.createElement('li');
    li.className = `page-item ${!enabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;

    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = text;
    a.addEventListener('click', onClick);

    li.appendChild(a);
    container.appendChild(li);
}

// Filtrage du tableau
function filterTable() {
    const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
    if (!searchTerm) {
        displayDataTable();
        return;
    }

    const filteredData = rawData.filter(row => {
        return Object.values(row).some(value => {
            if (value === null || value === undefined) return false;
            return value.toString().toLowerCase().includes(searchTerm);
        });
    });

    currentPage = 1;
    displayDataTable(filteredData);
}

// Configuration du graphique
function setupChartOptions() {
    const headers = Object.keys(rawData[0]);
    const xAxisSelect = document.getElementById('xAxis');
    const yAxisSelect = document.getElementById('yAxis');

    xAxisSelect.innerHTML = '';
    yAxisSelect.innerHTML = '';

    headers.forEach(header => {
        xAxisSelect.add(new Option(header, header));
        yAxisSelect.add(new Option(header, header));
    });

    // Sélection automatique intelligente
    const dateCol = headers.find(h => h.match(/date|time|année|mois|jour/i));
    const valueCol = headers.find(h => h.match(/montant|valeur|quantité|total|score/i));

    xAxisSelect.value = dateCol || headers[0];
    yAxisSelect.value = valueCol || (headers.length > 1 ? headers[1] : headers[0]);

    updateChart();
}

// Mise à jour du graphique
function updateChart() {
    const chartType = document.getElementById('chartType').value;
    const xAxis = document.getElementById('xAxis').value;
    const yAxis = document.getElementById('yAxis').value;

    const ctx = document.getElementById('dataChart').getContext('2d');
    const labels = rawData.map(item => item[xAxis]);
    const values = rawData.map(item => item[yAxis]);

    // Détruire le précédent graphique
    if (myChart) myChart.destroy();

    // Créer le nouveau graphique
    myChart = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: `${yAxis} par ${xAxis}`,
                data: values,
                backgroundColor: getThemeColors(values.length),
                borderColor: colorTheme === 'monochrome' ? '#495057' : '#ffffff',
                borderWidth: 1,
                hoverBorderWidth: 2
            }]
        },
        options: getChartOptions(chartType, xAxis, yAxis)
    });
}

function getChartOptions(type, xAxis, yAxis) {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: `${yAxis} par ${xAxis}`,
                font: { size: 16 }
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        return `${context.dataset.label}: ${context.raw}`;
                    }
                }
            },
            legend: {
                position: type === 'pie' || type === 'doughnut' ? 'right' : 'top'
            }
        },
        scales: type !== 'pie' && type !== 'doughnut' ? {
            x: {
                title: { display: true, text: xAxis }
            },
            y: {
                title: { display: true, text: yAxis },
                beginAtZero: true
            }
        } : {}
    };

    // Options spécifiques
    if (type === 'bar' || type === 'horizontalBar') {
        baseOptions.scales.x.barPercentage = 0.6;
        baseOptions.scales.y.barPercentage = 0.6;
    }

    return baseOptions;
}

function getThemeColors(count) {
    const colors = colorThemes[colorTheme];
    const result = [];

    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }

    return result;
}

// Changement de thème
function changeColorTheme(e) {
    document.querySelectorAll('.color-option').forEach(opt => {
        opt.classList.remove('active');
    });
    e.target.classList.add('active');
    colorTheme = e.target.dataset.theme;

    if (myChart) {
        myChart.data.datasets[0].backgroundColor = getThemeColors(rawData.length);
        myChart.update();
    }
}

// Export de la visualisation
function exportVisualization() {
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    const title = document.getElementById('exportTitle').value || 'Visualisation';
    const notes = document.getElementById('exportNotes').value;

    switch (format) {
        case 'pdf':
            exportToPDF(title, notes);
            break;
        case 'png':
            exportToPNG(title);
            break;
        case 'json':
            exportToJSON(title);
            break;
    }
}

function exportToPDF(title, notes) {
    const doc = new jsPDF('landscape');

    // Titre
    doc.setFontSize(20);
    doc.setTextColor(40);
    doc.text(title, 15, 20);

    // Date
    doc.setFontSize(10);
    doc.text(`Généré le ${new Date().toLocaleDateString()}`, 15, 27);

    // Graphique
    const canvas = document.getElementById('dataChart');
    const chartImg = canvas.toDataURL('image/png', 1.0);
    doc.addImage(chartImg, 'PNG', 15, 35, 260, 120);

    // Notes
    if (notes) {
        doc.setFontSize(12);
        doc.text('Notes:', 15, 170);
        doc.setFontSize(10);
        const splitNotes = doc.splitTextToSize(notes, 270);
        doc.text(splitNotes, 15, 177);
    }

    // Métadonnées
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Type: ${document.getElementById('chartType').value}`, 15, 200);
    doc.text(`Axe X: ${document.getElementById('xAxis').value}`, 15, 205);
    doc.text(`Axe Y: ${document.getElementById('yAxis').value}`, 15, 210);

    doc.save(`${title.replace(/[^a-z0-9]/gi, '_')}.pdf`);
}

function exportToPNG(title) {
    const canvas = document.getElementById('dataChart');
    const link = document.createElement('a');
    link.download = `${title.replace(/[^a-z0-9]/gi, '_')}.png`;
    link.href = canvas.toDataURL('image/png', 1.0);
    link.click();
}

function exportToJSON(title) {
    const dataToExport = {
        title: title,
        chartType: document.getElementById('chartType').value,
        xAxis: document.getElementById('xAxis').value,
        yAxis: document.getElementById('yAxis').value,
        data: rawData,
        generatedAt: new Date().toISOString()
    };

    const jsonStr = JSON.stringify(dataToExport, null, 2);
    const blob = new Blob([jsonStr], { type: 'application/json' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.download = `${title.replace(/[^a-z0-9]/gi, '_')}.json`;
    link.href = url;
    link.click();
}

// Partage
function copyShareLink() {
    // Dans une vraie implémentation, vous enverriez les données au serveur
    // et obtiendriez un lien court. Voici une simulation:
    const dummyLink = `${window.location.origin}${BASE_URL}/share/${generateId()}`;

    navigator.clipboard.writeText(dummyLink).then(() => {
        showAlert('Lien copié dans le presse-papier!', 'success');
    });
}

function generateEmbedCode() {
    // Similaire à copyShareLink mais génère un iframe
    const dummyCode = `<iframe src="${window.location.origin}${BASE_URL}/embed/${generateId()}" width="800" height="500" frameborder="0"></iframe>`;

    navigator.clipboard.writeText(dummyCode).then(() => {
        showAlert('Code embed copié!', 'success');
    });
}

function generateId() {
    return Math.random().toString(36).substring(2, 9);
}

// Gestion des étapes
function showStep(stepNumber) {
    // Mettre à jour les indicateurs d'étape
    document.querySelectorAll('.step').forEach((step, idx) => {
        if (idx + 1 <= stepNumber) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    // Afficher la carte appropriée
    document.getElementById('uploadCard').classList.toggle('active', stepNumber === 1);
    document.getElementById('configCard').classList.toggle('active', stepNumber === 2);
    document.getElementById('exportCard').classList.toggle('active', stepNumber === 3);
}

// Chargement d'exemples
function loadSampleData(e) {
    e.preventDefault();
    const sampleType = e.currentTarget.dataset.sample;
    let sampleData = [];

    switch (sampleType) {
        case 'sales':
            sampleData = [
                { mois: 'Janvier', ventes: 150, clients: 45 },
                { mois: 'Février', ventes: 210, clients: 62 },
                { mois: 'Mars', ventes: 180, clients: 54 },
                { mois: 'Avril', ventes: 320, clients: 88 },
                { mois: 'Mai', ventes: 280, clients: 76 }
            ];
            break;
        case 'weather':
            sampleData = [
                { jour: 'Lun', temp: 22, pluie: 5 },
                { jour: 'Mar', temp: 24, pluie: 0 },
                { jour: 'Mer', temp: 18, pluie: 12 },
                { jour: 'Jeu', temp: 16, pluie: 8 },
                { jour: 'Ven', temp: 20, pluie: 2 }
            ];
            break;
        case 'survey':
            sampleData = [
                { option: 'Excellent', votes: 45 },
                { option: 'Bon', votes: 72 },
                { option: 'Moyen', votes: 38 },
                { option: 'Médiocre', votes: 15 },
                { option: 'Mauvais', votes: 8 }
            ];
            break;
    }

    rawData = sampleData;
    showStep(2);
    displayDataTable();
    setupChartOptions();
    $('#sampleDataModal').modal('hide');
}

// Utilitaires
function resetUpload() {
    document.getElementById('dataUpload').value = '';
    document.getElementById('filePreview').classList.add('d-none');
    document.getElementById('uploadHelp').classList.add('d-none');
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;

    const container = document.querySelector('.data-viz-container');
    container.prepend(alert);

    setTimeout(() => alert.remove(), 5000);
}

function escapeHtml(unsafe) {
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}