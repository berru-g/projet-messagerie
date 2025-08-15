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

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/jsonto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/vis-network@9.1.2/standalone/umd/vis-network.min.js"></script>
    <!-- Monaco Editor Loader -->
    <script src="https://unpkg.com/monaco-editor@0.36.1/min/vs/loader.js"></script>
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
                <button id="sampleDataBtn" class="secondary">
                    <i class="fas fa-vial"></i> Charger un exemple
                </button>
                <button id="pasteSqlBtn">
                    <i class="fas fa-paste"></i> Coller du SQL
                </button>
            </div>

            <div class="tool-section">
                <h3><i class="fas fa-palette"></i> Personnalisation</h3>
                <div class="color-palette">
                    <div class="color-option active" style="background: #a395f2;" data-color="#a395f2"></div>
                    <div class="color-option" style="background: #3b82f6;" data-color="#3b82f6"></div>
                    <div class="color-option" style="background: #60d394;" data-color="#60d394"></div>
                    <div class="color-option" style="background: #ffd97d;" data-color="#ffd97d"></div>
                    <div class="color-option" style="background: #ef4444;" data-color="#ef4444"></div>
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
                <button id="resetBtn" class="outline">
                    <i class="fas fa-trash-alt"></i> Réinitialiser
                </button>
                <div class="editor-toolbar-actions mt-2">
                    <button id="exportPngBtn" class="secondary">
                        <i class="fas fa-image"></i> Exporter PNG
                    </button>
                    <button id="exportSqlBtn">
                        <i class="fas fa-file-export"></i> Exporter SQL
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content" id="mainContent">
            <div class="upload-container" id="uploadContainer">
                <div class="upload-dropzone" id="dropZone">
                    <i class="fas fa-file-upload fa-3x" style="color: #a395f2; margin-bottom: 1rem;"></i>
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
                </div>

                <div class="visualization-container">
                    <!-- Mind Map View -->
                    <div id="mindmap-view" class="view-container">
                        <div id="mindMap"></div>
                    </div>

                    <!-- SQL Editor View -->
                    <div id="editor-view" class="view-container hidden">
                        <div id="sqlEditor"></div>
                    </div>

                    <!-- Tables View -->
                    <div id="tables-view" class="view-container hidden">
                        <div id="tablesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration globale
        let network, monacoEditor;
        let allNodes = [], allEdges = [];
        let currentSql = '';
        let parsedSchema = { tables: [] };
        let currentColor = '#a395f2';
        let currentView = 'mindmap';

        // Initialisation de Monaco Editor
        function initSQLEditor() {
            require.config({ paths: { 'vs': 'https://unpkg.com/monaco-editor@0.36.1/min/vs' } });

            require(['vs/editor/editor.main'], function () {
                monacoEditor = monaco.editor.create(document.getElementById('sqlEditor'), {
                    value: currentSql,
                    language: 'sql',
                    theme: 'vs',
                    automaticLayout: true,
                    minimap: { enabled: false },
                    fontSize: 14,
                    lineNumbers: 'on'
                });
            });
        }

        // Parser SQL simplifié
        function parseSQL(sql) {
            const schema = {
                tables: [],
                relations: []
            };

            // Extraire les CREATE TABLE
            const createTableRegex = /CREATE TABLE (\w+)\s*\(([\s\S]+?)\)\s*;/g;
            let tableMatch;

            while ((tableMatch = createTableRegex.exec(sql)) !== null) {
                const tableName = tableMatch[1];
                const tableContent = tableMatch[2];
                const columns = [];
                const primaryKeys = [];
                const foreignKeys = [];

                // Extraire les colonnes
                const columnLines = tableContent.split('\n')
                    .map(line => line.trim())
                    .filter(line => line && !line.startsWith('--') && !line.startsWith('/*'));

                for (const line of columnLines) {
                    if (line.startsWith('PRIMARY KEY')) {
                        const pkMatch = line.match(/PRIMARY KEY\s*\(([^)]+)\)/);
                        if (pkMatch) {
                            primaryKeys.push(...pkMatch[1].split(',').map(s => s.trim().replace(/`|"/g, '')));
                        }
                    }
                    else if (line.startsWith('FOREIGN KEY')) {
                        const fkMatch = line.match(/FOREIGN KEY\s*\(([^)]+)\) REFERENCES (\w+)\s*\(([^)]+)\)/);
                        if (fkMatch) {
                            foreignKeys.push({
                                column: fkMatch[1].replace(/`|"/g, ''),
                                refTable: fkMatch[2],
                                refColumn: fkMatch[3].replace(/`|"/g, '')
                            });
                        }
                    }
                    else if (line) {
                        const columnMatch = line.match(/^`?"?(\w+)`?"?\s+(\w+)/);
                        if (columnMatch) {
                            columns.push({
                                name: columnMatch[1],
                                type: columnMatch[2],
                                isPrimary: false
                            });
                        }
                    }
                }

                // Marquer les colonnes primaires
                columns.forEach(col => {
                    if (primaryKeys.includes(col.name)) {
                        col.isPrimary = true;
                    }
                });

                schema.tables.push({
                    name: tableName,
                    columns: columns,
                    foreignKeys: foreignKeys
                });
            }

            return schema;
        }

        // Générer la visualisation
        function generateVisualization(sql) {
            currentSql = sql;
            parsedSchema = parseSQL(sql);

            // Mettre à jour l'éditeur
            if (monacoEditor) {
                monacoEditor.setValue(sql);
            } else {
                initSQLEditor();
            }

            // Générer les vues
            createMindMap();
            createTablesView();

            // Afficher la zone de visualisation
            document.getElementById('uploadContainer').classList.add('hidden');
            document.getElementById('visualizationArea').classList.remove('hidden');

            // Afficher la vue par défaut
            switchToView(currentView);
        }

        function createMindMap() {
            allNodes = [];
            allEdges = [];

            if (network) {
                network.destroy();
            }

            // Nœud racine
            allNodes.push({
                id: 'root',
                label: 'SCHEMA SQL',
                level: 0,
                color: {
                    background: currentColor,
                    border: '#4f46e5',
                    highlight: { background: currentColor, border: '#4338ca' }
                },
                font: { color: 'white', size: 16, bold: true },
                shape: document.getElementById('nodeShape').value,
                size: 25
            });

            // Ajouter les tables
            parsedSchema.tables.forEach((table, i) => {
                const tableId = `table_${table.name}`;

                allNodes.push({
                    id: tableId,
                    label: table.name,
                    level: 1,
                    color: {
                        background: '#a5b4fc',
                        border: '#4f46e5'
                    },
                    shape: document.getElementById('nodeShape').value,
                    font: { size: 14, bold: true }
                });

                allEdges.push({
                    from: 'root',
                    to: tableId,
                    color: currentColor,
                    width: 2
                });

                // Ajouter les colonnes
                table.columns.forEach((col, j) => {
                    const colId = `col_${table.name}_${col.name}`;
                    const isPrimary = col.isPrimary;

                    allNodes.push({
                        id: colId,
                        label: `${col.name}\n${col.type}`,
                        level: 2,
                        color: {
                            background: isPrimary ? '#fcd34d' : '#e2e8f0',
                            border: isPrimary ? '#f59e0b' : '#cbd5e1'
                        },
                        shape: 'box',
                        font: { size: 12 }
                    });

                    allEdges.push({
                        from: tableId,
                        to: colId,
                        color: '#94a3b8',
                        width: 1
                    });
                });

                // Ajouter les relations
                table.foreignKeys.forEach(fk => {
                    const relationId = `rel_${table.name}_${fk.column}`;
                    const targetTableExists = parsedSchema.tables.some(t => t.name === fk.refTable);

                    if (targetTableExists) {
                        allNodes.push({
                            id: relationId,
                            label: `→ ${fk.refTable}.${fk.refColumn}`,
                            level: 2,
                            color: { background: '#93c5fd', border: '#3b82f6' },
                            shape: 'diamond',
                            font: { size: 11 }
                        });

                        allEdges.push({
                            from: `table_${table.name}`,
                            to: relationId,
                            color: '#3b82f6',
                            dashes: [5, 5],
                            width: 1
                        });

                        // Lien vers la table référencée
                        allEdges.push({
                            from: relationId,
                            to: `table_${fk.refTable}`,
                            color: '#3b82f6',
                            arrows: 'to',
                            width: 1
                        });
                    }
                });
            });

            // Créer le réseau
            const container = document.getElementById('mindMap');
            network = new vis.Network(
                container,
                { nodes: new vis.DataSet(allNodes), edges: new vis.DataSet(allEdges) },
                getNetworkOptions()
            );

            network.on('click', (params) => {
                if (params.nodes.length) {
                    const nodeId = params.nodes[0];
                    network.selectNodes([nodeId]);
                }
            });
        }

        function createTablesView() {
            const container = document.getElementById('tablesList');
            let html = '';

            parsedSchema.tables.forEach(table => {
                html += `
                    <div class="table-card" style="
                        background: white;
                        border-radius: 8px;
                        padding: 1rem;
                        margin-bottom: 1rem;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    ">
                        <h4 style="margin-top: 0; color: #4f46e5;">${table.name}</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Colonne</th>
                                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Type</th>
                                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #e2e8f0;">Clé</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${table.columns.map(col => `
                                    <tr>
                                        <td style="padding: 0.5rem; border-bottom: 1px solid #f1f5f9;">${col.name}</td>
                                        <td style="padding: 0.5rem; border-bottom: 1px solid #f1f5f9;">${col.type}</td>
                                        <td style="padding: 0.5rem; border-bottom: 1px solid #f1f5f9;">
                                            ${col.isPrimary ? 'PK' :
                        table.foreignKeys.some(fk => fk.column === col.name) ? 'FK' : ''}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        
                        ${table.foreignKeys.length ? `
                            <div style="margin-top: 1rem;">
                                <h5 style="margin-bottom: 0.5rem; font-size: 0.9rem;">Relations :</h5>
                                <ul style="margin: 0; padding-left: 1.2rem;">
                                    ${table.foreignKeys.map(fk => `
                                        <li style="margin-bottom: 0.2rem;">
                                            ${fk.column} → ${fk.refTable}.${fk.refColumn}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            container.innerHTML = html || '<p>Aucune table trouvée</p>';
        }

        function getNetworkOptions() {
            const layoutType = document.getElementById('layoutType').value;

            return {
                nodes: {
                    shape: document.getElementById('nodeShape').value,
                    color: { background: currentColor },
                    font: { size: 14 },
                    margin: 10,
                    borderWidth: 2,
                    shadow: true
                },
                edges: {
                    color: currentColor,
                    smooth: true,
                    width: 2,
                    shadow: true
                },
                physics: {
                    enabled: true,
                    hierarchicalRepulsion: {
                        nodeDistance: 140,
                        springLength: 100
                    }
                },
                layout: {
                    hierarchical: {
                        enabled: layoutType === 'hierarchical',
                        direction: 'UD',
                        nodeSpacing: 120,
                        levelSeparation: 100
                    }
                }
            };
        }

        function loadSampleData() {
            const sampleSQL = `
                CREATE TABLE users (
                    id INT PRIMARY KEY,
                    username VARCHAR(50) NOT NULL,
                    email VARCHAR(100) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                
                CREATE TABLE posts (
                    id INT PRIMARY KEY,
                    user_id INT,
                    title VARCHAR(255),
                    content TEXT,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                );
                
                CREATE TABLE comments (
                    id INT PRIMARY KEY,
                    post_id INT,
                    user_id INT,
                    comment TEXT,
                    FOREIGN KEY (post_id) REFERENCES posts(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                );
            `;

            generateVisualization(sampleSQL);
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                generateVisualization(e.target.result);
            };
            reader.readAsText(file);
        }

        function showPasteDialog() {
            const sqlText = prompt("Collez votre SQL ici:");
            if (sqlText) {
                generateVisualization(sqlText);
            }
        }

        function switchToView(viewName) {
            currentView = viewName;

            // Mettre à jour les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.view === viewName);
            });

            // Mettre à jour les vues
            document.querySelectorAll('.view-container').forEach(view => {
                view.classList.toggle('hidden', view.id !== `${viewName}-view`);
            });

            // Redessiner le réseau si nécessaire
            if (viewName === 'mindmap' && network) {
                network.redraw();
                network.fit();
            }
        }

        function resetVisualization() {
            document.getElementById('uploadContainer').classList.remove('hidden');
            document.getElementById('visualizationArea').classList.add('hidden');
            document.getElementById('sqlFileInput').value = '';
            currentSql = '';
            parsedSchema = { tables: [] };

            if (network) {
                network.destroy();
                network = null;
            }
        }

        function exportAsPNG() {
            if (!network) return;

            const canvas = document.querySelector('#mindMap canvas');
            if (!canvas) return;

            const link = document.createElement('a');
            link.download = `sql-schema-${new Date().toISOString().slice(0, 10)}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();

            alert('Export PNG terminé');
        }

        function exportAsSQL() {
            if (!currentSql) return;

            const blob = new Blob([currentSql], { type: 'text/sql' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.download = `schema-${new Date().toISOString().slice(0, 10)}.sql`;
            link.href = url;
            link.click();

            alert('Export SQL terminé');
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            // Événements d'importation
            document.getElementById('sqlFileInput').addEventListener('change', handleFileSelect);
            document.getElementById('dropZone').addEventListener('click', () =>
                document.getElementById('sqlFileInput').click());
            document.getElementById('sampleDataBtn').addEventListener('click', loadSampleData);
            document.getElementById('pasteSqlBtn').addEventListener('click', showPasteDialog);

            // Boutons d'actions
            document.getElementById('resetBtn').addEventListener('click', resetVisualization);
            document.getElementById('exportPngBtn').addEventListener('click', exportAsPNG);
            document.getElementById('exportSqlBtn').addEventListener('click', exportAsSQL);

            // Options de visualisation
            document.getElementById('nodeShape').addEventListener('change', () => {
                if (network) {
                    createMindMap();
                }
            });

            document.getElementById('layoutType').addEventListener('change', () => {
                if (network) {
                    network.setOptions(getNetworkOptions());
                    network.fit();
                }
            });

            // Gestion des couleurs
            document.querySelectorAll('.color-option').forEach(opt => {
                opt.addEventListener('click', () => {
                    document.querySelector('.color-option.active').classList.remove('active');
                    opt.classList.add('active');
                    currentColor = opt.dataset.color;
                    if (network) {
                        createMindMap();
                    }
                });
            });

            // Onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    switchToView(tab.dataset.view);
                });
            });

            // Toggle sidebar
            document.getElementById('sidebarToggle').addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('active');
            });

            // Toggle editor
            document.getElementById('toggleEditorBtn').addEventListener('click', () => {
                if (currentView === 'editor') {
                    currentView = 'mindmap';
                } else {
                    currentView = 'editor';
                }
                generateVisualization(currentData, currentView);
            });

            // Drag & drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                document.getElementById('dropZone').addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                document.getElementById('dropZone').addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                document.getElementById('dropZone').addEventListener(eventName, unhighlight, false);
            });

            document.getElementById('dropZone').addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight() {
                document.getElementById('dropZone').classList.add('dragover');
            }

            function unhighlight() {
                document.getElementById('dropZone').classList.remove('dragover');
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length) {
                    document.getElementById('sqlFileInput').files = files;
                    handleFileSelect({ target: { files } });
                }
            }
        });
    </script>
</body>

</html>