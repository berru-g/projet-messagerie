<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

$page_title = "Éditeur SQL - Outil de visualisation";
$meta_description = "Outil avancé pour visualiser et éditer vos schémas SQL sous forme de mind maps interactives";
$meta_keywords = "SQL, visualisation, base de données, éditeur, outil développeur";

require_once '../includes/header.php';
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/jsonto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <section class="tool-header">
        <div class="tool-header-container">
            <h2><i class="fas fa-database"></i> SQL Editor</h2>
            <div class="header-actions">
                <button class="outline" id="toggleEditorBtn">
                    <i class="fas fa-code"></i> Éditeur SQL
                </button>

            </div>
        </div>
    </section>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-sliders-h"></i>
    </button>

    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="tool-section">
                <h3><i class="fas fa-upload"></i> Importation</h3>
                <input type="file" id="sqlFileInput" accept=".sql" class="mb-4">
                <button id="sampleDataBtn" class="outline">
                    <i class="fas fa-vial"></i> Charger un exemple
                </button>
                <button id="pasteSqlBtn">
                    <i class="fas fa-paste"></i> Coller du SQL
                </button>
            </div>

            <div class="tool-section">
                <h3><i class="fas fa-palette"></i> Mind Map</h3>
                <div class="color-palette">
                    <div class="color-option active" style="background: #ab9ff2;" data-color="#ab9ff2"></div>
                    <div class="color-option" style="background: #2575fc;" data-color="#2575fc"></div>
                    <div class="color-option" style="background: #60d394;" data-color="#60d394"></div>
                    <div class="color-option" style="background: #ffd97d;" data-color="#ffd97d"></div>
                    <div class="color-option" style="background: #ee6055;" data-color="#ee6055"></div>
                </div>
            </div>

            <div class="tool-section">
                <h3><i class="fas fa-sliders-h"></i> Options de visualisation</h3>
                <label for="nodeShape">Forme des nœuds</label>
                <select id="nodeShape">
                    <option value="box">Boîte</option>
                    <option value="database">Base de données</option>
                    <option value="diamond">Losange</option>
                    <option value="circle">Cercle</option>
                </select>

                <label for="layoutType">Type de disposition</label>
                <select id="layoutType">
                    <option value="hierarchical">Hiérarchique</option>
                    <option value="standard">Standard</option>
                </select>
            </div>

            <div class="tool-section">
                <h3><i class="fas fa-tools"></i> Actions</h3>
                <!--<button id="updateMindmapBtn" class="secondary">
                    <i class="fas fa-sync-alt"></i> Mettre à jour la map
                </button>-->
                <button id="resetBtn" class="outline">
                    <i class="fas fa-trash-alt"></i> Réinitialiser
                </button>
                <button id="saveFileBtn" class="primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <div class="editor-toolbar-actions mt-2">
                    <button id="exportPngBtn" class="secondary">
                        <i class="fas fa-image"></i> Exporter PNG
                    </button>
                    <button id="exportSqlBtn">
                        <i class="fas fa-database"></i> Exporter SQL
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content" id="mainContent">
            <div class="upload-container" id="uploadContainer">
                <div class="upload-dropzone" id="dropZone">
                    <i class="fas fa-file-upload fa-3x" style="color: #ab9ff2; margin-bottom: 1rem;"></i>
                    <h3>Déposez un fichier SQL ici</h3>
                    <p>Ou cliquez pour sélectionner un fichier</p>
                    <input type="file" id="sqlUpload" accept=".sql" style="display: none;">
                </div>
            </div>

            <div id="visualizationArea" class="hidden">

                <div class="tabs">
                    <div class="tab active" data-view="mindmap">Mind Map</div>
                    <div class="tab" data-view="editor">Éditeur SQL</div>
                    <div class="tab" data-view="tables">Tables</div>
                    <button id="updateMindmapBtn" class="refreshmap">
                        <i class="fas fa-sync-alt"></i> Mettre à jour la map
                    </button>
                </div>

                <div class="visualization-container">
                    <!-- Mind Map View -->
                    <div id="mindmap-view" class="view-container">
                        <div id="mindMap"></div>
                    </div>

                    <!-- SQL Editor View -->
                    <div id="editor-view" class="view-container hidden">
                        <div id="sqlEditor" style="height:100%; width:100%;"></div>
                        <div class="editor-actions">
                        </div>
                    </div>

                    <!-- Tables View -->
                    <div id="tables-view" class="view-container hidden">
                        <div id="tablesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/sqleditor.js"></script>
    <script src="https://unpkg.com/vis-network@9.1.2/standalone/umd/vis-network.min.js"></script>
    <script src="https://unpkg.com/monaco-editor@0.36.1/min/vs/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>