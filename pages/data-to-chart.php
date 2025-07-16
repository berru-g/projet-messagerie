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

<div class="container data-viz-container">
    <!-- Header Section -->
    <div class="viz-header">
        <h2><i class="fas fa-chart-line"></i> Data Visualizer Pro</h2>
        <p class="text-muted">Transformez vos fichiers en insights visuels en 3 étapes</p>
    </div>

    <!-- Process Steps -->
    <div class="viz-steps">
        <div class="step active" id="step1">
            <span class="step-number">1</span>
            <span class="step-text">Importer</span>
        </div>
        <div class="step" id="step2">
            <span class="step-number">2</span>
            <span class="step-text">Configurer</span>
        </div>
        <div class="step" id="step3">
            <span class="step-number">3</span>
            <span class="step-text">Visualiser</span>
        </div>
    </div>

    <!-- Upload Card -->
    <div class="card upload-card active" id="uploadCard">
        <div class="card-body">
            <div class="dropzone" id="dropZone">
                <i class="fas fa-cloud-upload-alt"></i>
                <h5>Déposez votre fichier ici</h5>
                <p class="text-muted">ou cliquez pour parcourir</p>
                <input type="file"  id="dataUpload" accept=".csv,.json,.xlsx,.xls">
                <div class="supported-formats">
                    <span class="badge badge-pill badge-secondary">CSV</span>
                    <span class="badge badge-pill badge-secondary">JSON</span>
                    <span class="badge badge-pill badge-secondary">Excel</span>
                </div>
            </div>
            <div class="file-preview mt-3 d-none" id="filePreview">
                <div class="file-info">
                    <span id="fileName"></span>
                    <span class="file-size" id="fileSize"></span>
                    <button class="btn btn-sm btn-outline-danger" id="cancelUpload">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="progress mt-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         id="uploadProgress" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Card -->
    <div class="card config-card" id="configCard">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-preview-container">
                        <canvas id="dataChart"></canvas>
                        <div class="chart-watermark">Preview</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="chartType"><i class="fas fa-chart-pie"></i> Type de graphique</label>
                        <select class="form-control custom-select" id="chartType">
                            <option value="line">Ligne</option>
                            <option value="bar">Barres verticales</option>
                            <option value="horizontalBar">Barres horizontales</option>
                            <option value="pie">Camembert</option>
                            <option value="doughnut">Anneau</option>
                            <option value="radar">Radar</option>
                        </select>
                    </div>
                    
                    <div class="axis-config">
                        <div class="form-group">
                            <label for="xAxis"><i class="fas fa-arrows-alt-h"></i> Axe X</label>
                            <select class="form-control custom-select" id="xAxis"></select>
                        </div>
                        
                        <div class="form-group">
                            <label for="yAxis"><i class="fas fa-arrows-alt-v"></i> Axe Y</label>
                            <select class="form-control custom-select" id="yAxis"></select>
                        </div>
                    </div>

                    <div class="color-picker mt-3">
                        <label><i class="fas fa-palette"></i> Palette de couleurs</label>
                        <div class="color-options">
                            <div class="color-option theme1 active" data-theme="default"></div>
                            <div class="color-option theme2" data-theme="pastel"></div>
                            <div class="color-option theme3" data-theme="vibrant"></div>
                            <div class="color-option theme4" data-theme="monochrome"></div>
                        </div>
                    </div>

                    <button id="updateChart" class="btn btn-primary btn-block mt-3">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data & Export Card -->
    <div class="card export-card" id="exportCard">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="data-table-container">
                        <div class="table-actions">
                            <div class="search-box">
                                <input type="text" id="tableSearch" placeholder="Rechercher...">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="rows-info" id="rowsInfo"></div>
                        </div>
                        <div id="tableContainer" class="table-responsive"></div>
                        <nav aria-label="Data pagination">
                            <ul class="pagination" id="pagination"></ul>
                        </nav>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="export-options">
                        <h5><i class="fas fa-download"></i> Options d'export</h5>
                        
                        <div class="export-format">
                            <label>Format :</label>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-secondary active">
                                    <input type="radio" name="exportFormat" value="pdf" checked> PDF
                                </label>
                                <label class="btn btn-outline-secondary">
                                    <input type="radio" name="exportFormat" value="png"> PNG
                                </label>
                                <label class="btn btn-outline-secondary">
                                    <input type="radio" name="exportFormat" value="json"> JSON
                                </label>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label for="exportTitle"><i class="fas fa-heading"></i> Titre du rapport</label>
                            <input type="text" class="form-control" id="exportTitle" placeholder="Ma visualisation">
                        </div>

                        <div class="form-group">
                            <label for="exportNotes"><i class="fas fa-sticky-note"></i> Notes</label>
                            <textarea class="form-control" id="exportNotes" rows="2"></textarea>
                        </div>

                        <button id="exportBtn" class="btn btn-success btn-block mt-3">
                            <i class="fas fa-file-export"></i> Exporter
                        </button>

                        <div class="share-options mt-3">
                            <label><i class="fas fa-share-alt"></i> Partager :</label>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" id="copyLinkBtn">
                                    <i class="fas fa-link"></i> Lien
                                </button>
                                <button class="btn btn-sm btn-outline-info" id="embedBtn">
                                    <i class="fas fa-code"></i> Embed
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Data Modal -->
    <div class="modal fade" id="sampleDataModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Charger des exemples</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action" data-sample="sales">
                            <i class="fas fa-shopping-cart"></i> Données de ventes
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-sample="weather">
                            <i class="fas fa-cloud-sun"></i> Données météo
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" data-sample="survey">
                            <i class="fas fa-poll"></i> Résultats de sondage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bibliothèques -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="../assets/js/data-to-chart.js"></script>


<style>
.data-viz-container {
    margin-top: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.viz-header {
    text-align: center;
    margin-bottom: 30px;
}

.viz-header h2 {
    color: #2c3e50;
    font-weight: 700;
}

/* Steps Indicator */
.viz-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 20px;
    position: relative;
    color: #95a5a6;
}

.step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 20px;
    right: -20px;
    width: 40px;
    height: 2px;
    background: #bdc3c7;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ecf0f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    border: 2px solid #bdc3c7;
    color: #7f8c8d;
}

.step.active .step-number {
    background: #3498db;
    border-color: #2980b9;
    color: white;
}

.step.active .step-text {
    color: #2c3e50;
    font-weight: 600;
}

/* Cards */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    display: none;
}

.card.active {
    display: block;
    animation: fadeIn 0.5s ease-in-out;
}

.card-body {
    padding: 25px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Upload Section */
.dropzone {
    border: 2px dashed #3498db;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: rgba(52, 152, 219, 0.05);
}

.dropzone:hover {
    background: rgba(52, 152, 219, 0.1);
}

.dropzone.dragover {
    background: rgba(52, 152, 219, 0.2);
    border-color: #2980b9;
}

.dropzone i {
    font-size: 48px;
    color: #3498db;
    margin-bottom: 15px;
}

.dropzone h5 {
    color: #2c3e50;
    margin-bottom: 5px;
}

#dataUpload {
    opacity: 0;
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    cursor: pointer;
}

.file-preview {
    border: 1px solid #eee;
    padding: 15px;
    border-radius: 8px;
}

.file-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.file-size {
    color: #7f8c8d;
    font-size: 0.9em;
}

.supported-formats {
    margin-top: 15px;
}

.supported-formats .badge {
    margin: 0 3px;
    font-weight: normal;
}

/* Chart Section */
.chart-preview-container {
    position: relative;
    height: 400px;
    width: 100%;
    background: white;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #eee;
}

.chart-watermark {
    position: absolute;
    bottom: 10px;
    right: 10px;
    color: rgba(0, 0, 0, 0.1);
    font-size: 24px;
    pointer-events: none;
}

.axis-config {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.color-picker {
    margin-bottom: 20px;
}

.color-options {
    display: flex;
    margin-top: 10px;
}

.color-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    margin-right: 10px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: transform 0.2s;
}

.color-option:hover {
    transform: scale(1.1);
}

.color-option.active {
    border-color: #2c3e50;
    transform: scale(1.1);
}

.theme1 { background: linear-gradient(135deg, #4e73df, #1cc88a, #36b9cc); }
.theme2 { background: linear-gradient(135deg, #a8d8ea, #aa96da, #fcbad3); }
.theme3 { background: linear-gradient(135deg, #ff595e, #ffca3a, #1982c4); }
.theme4 { background: linear-gradient(135deg, #495057, #6c757d, #adb5bd); }

/* Table Section */
.data-table-container {
    background: white;
    border-radius: 8px;
    padding: 15px;
    border: 1px solid #eee;
}

.table-actions {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.search-box {
    position: relative;
    width: 250px;
}

.search-box input {
    padding-left: 30px;
    border-radius: 20px;
}

.search-box i {
    position: absolute;
    left: 10px;
    top: 10px;
    color: #7f8c8d;
}

.rows-info {
    color: #7f8c8d;
    font-size: 0.9em;
    align-self: center;
}

.table {
    font-size: 0.9em;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #2c3e50;
}


.export-options {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    height: 100%;
}

.export-format {
    margin-bottom: 15px;
}

.export-format .btn-group {
    width: 100%;
}

.export-format .btn {
    flex: 1;
}

.share-options {
    margin-top: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .viz-steps {
        flex-wrap: wrap;
    }
    
    .step {
        margin-bottom: 15px;
    }
    
    .step:after {
        display: none;
    }
    
    .chart-container {
        height: 300px;
    }
    
    .col-md-8, .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .export-options {
        margin-top: 20px;
        height: auto;
    }
}
</style>     

<?php require_once '../includes/footer.php'; ?>