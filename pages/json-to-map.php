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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON Mind Mapper</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/vis-network@9.1.2/standalone/umd/vis-network.min.js"></script>
    <style>
        /* Variables CSS */
        :root {
            --primary: #ab9ff2;
            --primary-dark: #8a7de0;
            --accent: #2575fc;
            --success: #60d394;
            --error: #ee6055;
            --text: #333333;
            --text-light: #777777;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e0e0e0;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

    
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Typographie */
        h2, h3 {
            color: var(--primary);
            margin-top: 0;
        }

        h2 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            margin-top: 2rem;
        }

        /* Layout */
        .flex-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .flex-col {
            flex: 1;
            min-width: 250px;
        }

        /* Cartes */
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Upload Zone */
        .upload-zone {
            border: 2px dashed var(--primary);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: var(--card-bg);
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px auto;
            max-width: 500px;
        }

        .upload-zone:hover {
            background: rgba(171, 159, 242, 0.05);
            border-color: var(--primary-dark);
        }

        .upload-zone i {
            color: var(--primary);
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Mind Map Container */
        #mindMap {
            width: 100%;
            height: 600px;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        /* Outils */
        .tool-section {
            margin-bottom: 15px;
        }

        .tool-section h3 {
            font-size: 1rem;
            margin-bottom: 10px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-palette {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }

        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .color-option:hover, .color-option.active {
            border-color: var(--text);
            transform: scale(1.1);
        }

        select, button {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--text);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        button {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            background: var(--primary-dark);
        }

        button.secondary {
            background: var(--accent);
        }

        /* Utilitaires */
        .hidden {
            display: none !important;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 { margin-top: 1rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-project-diagram"></i> JSON Mind Mapper</h2>
        
        <div class="upload-zone" id="dropZone">
            <i class="fas fa-file-upload"></i>
            <p><strong>Déposez un fichier JSON ici</strong></p>
            <p class="text-light">Ou cliquez pour sélectionner</p>
            <input type="file" id="jsonUpload" accept=".json" class="hidden">
        </div>

        <div id="mindMapContainer" class="hidden">
            <div class="card">
                <div class="flex-row">
                    <div class="flex-col">
                        <div class="tool-section">
                            <h3><i class="fas fa-palette"></i> Couleur</h3>
                            <div class="color-palette">
                                <div class="color-option active" style="background: var(--primary);" data-color="var(--primary)"></div>
                                <div class="color-option" style="background: var(--accent);" data-color="var(--accent)"></div>
                                <div class="color-option" style="background: var(--success);" data-color="var(--success)"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-col">
                        <div class="tool-section">
                            <h3><i class="fas fa-shapes"></i> Forme</h3>
                            <select id="nodeShape">
                                <option value="box">Boîte</option>
                                <option value="ellipse">Ellipse</option>
                                <option value="diamond">Losange</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex-col">
                        <div class="tool-section">
                            <h3><i class="fas fa-project-diagram"></i> Layout</h3>
                            <select id="layoutType">
                                <option value="hierarchical">Hiérarchique</option>
                                <option value="standard">Standard</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex-col">
                        <div class="tool-section">
                            <h3><i class="fas fa-arrows-alt"></i> Direction</h3>
                            <select id="layoutDirection">
                                <option value="UD">Haut-Bas</option>
                                <option value="LR">Gauche-Droite</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="mindMap"></div>
            
            <div class="text-center mt-3">
                <button id="resetBtn">
                    <i class="fas fa-redo"></i> Réinitialiser
                </button>
                <button id="exportBtn" class="secondary">
                    <i class="fas fa-download"></i> Exporter
                </button>
            </div>
        </div>
    </div>

    <script>
        // [Le même JavaScript que dans la version précédente]
        // (Conserve toutes les fonctionnalités existantes)
        let network;
        let allNodes = [];
        let allEdges = [];
        let currentColor = '#ab9ff2';

        const options = {
            nodes: {
                shape: 'box',
                color: {
                    background: currentColor,
                    border: '#6a0dad',
                    highlight: { background: currentColor }
                },
                font: { size: 14 },
                margin: 10
            },
            edges: {
                color: currentColor,
                smooth: true,
                arrows: { to: { enabled: true, scaleFactor: 0.5 } }
            },
            physics: {
                hierarchicalRepulsion: { nodeDistance: 120 }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            initEventListeners();
        });

        function initEventListeners() {
            // Gestion upload
            document.getElementById('jsonUpload').addEventListener('change', handleFileSelect);
            document.getElementById('dropZone').addEventListener('click', () => document.getElementById('jsonUpload').click());
            
            // Drag & drop
            ['dragover', 'drop'].forEach(event => {
                document.getElementById('dropZone').addEventListener(event, e => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (event === 'dragover') {
                        e.currentTarget.style.background = 'rgba(171, 159, 242, 0.1)';
                    } else {
                        e.currentTarget.style.background = '';
                        handleFileSelect({ target: { files: e.dataTransfer.files } });
                    }
                });
            });

            // Outils
            document.querySelectorAll('.color-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    currentColor = this.dataset.color.replace('var(--', '').replace(')', '');
                    updateNetworkStyle();
                });
            });

            document.getElementById('nodeShape').addEventListener('change', function() {
                options.nodes.shape = this.value;
                updateNetworkStyle();
            });

            document.getElementById('layoutType').addEventListener('change', function() {
                updateLayout();
            });

            document.getElementById('layoutDirection').addEventListener('change', function() {
                updateLayout();
            });

            // Boutons
            document.getElementById('resetBtn').addEventListener('click', resetMindMap);
            document.getElementById('exportBtn').addEventListener('click', exportAsPNG);
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file || !file.name.endsWith('.json')) {
                alert('Seuls les fichiers JSON sont acceptés !');
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                try {
                    const json = JSON.parse(e.target.result);
                    generateMindMap(json);
                    document.getElementById('mindMapContainer').classList.remove('hidden');
                    document.getElementById('dropZone').classList.add('hidden');
                } catch (err) {
                    alert(`Erreur JSON : ${err.message}`);
                }
            };
            reader.readAsText(file);
        }

        function generateMindMap(data) {
            allNodes = [];
            allEdges = [];
            
            // Node racine
            allNodes.push({
                id: 1,
                label: 'RACINE',
                level: 0,
                color: { background: currentColor, border: '#6a0dad' },
                font: { color: 'white' },
                shape: options.nodes.shape
            });

            // Conversion récursive
            processNode(data, 1, 1);

            // Création du réseau
            if (network) network.destroy();
            network = new vis.Network(
                document.getElementById('mindMap'),
                { nodes: new vis.DataSet(allNodes), edges: new vis.DataSet(allEdges) },
                options
            );

            function processNode(obj, parentId, level) {
                Object.entries(obj).forEach(([key, value]) => {
                    const nodeId = allNodes.length + 1;
                    const isObject = typeof value === 'object' && value !== null;
                    
                    allNodes.push({
                        id: nodeId,
                        label: isObject ? key : `${key}: ${formatValue(value)}`,
                        level: level,
                        color: { background: getNodeColor(level) },
                        shape: isObject ? options.nodes.shape : 'ellipse',
                        font: { size: 12 + (3 / level) }
                    });

                    allEdges.push({
                        from: parentId,
                        to: nodeId,
                        color: currentColor
                    });

                    if (isObject) processNode(value, nodeId, level + 1);
                });
            }
        }

        function updateNetworkStyle() {
            if (!network) return;
            
            // Mise à jour des couleurs
            options.nodes.color.background = currentColor;
            options.nodes.color.highlight.background = currentColor;
            options.edges.color = currentColor;
            
            // Mise à jour des nodes
            const updateNodes = allNodes.map(node => ({
                ...node,
                color: { 
                    background: node.id === 1 ? currentColor : getNodeColor(node.level || 1),
                    border: node.id === 1 ? '#6a0dad' : currentColor
                },
                shape: node.id === 1 || node.shape === 'ellipse' ? node.shape : options.nodes.shape
            }));
            
            // Mise à jour des edges
            const updateEdges = allEdges.map(edge => ({
                ...edge,
                color: currentColor
            }));
            
            network.setOptions(options);
            network.body.data.nodes.update(updateNodes);
            network.body.data.edges.update(updateEdges);
        }

        function updateLayout() {
            if (!network) return;
            
            const layoutType = document.getElementById('layoutType').value;
            const direction = document.getElementById('layoutDirection').value;
            
            if (layoutType === 'hierarchical') {
                options.layout = { 
                    hierarchical: { 
                        direction: direction,
                        nodeSpacing: 120,
                        levelSeparation: 100
                    }
                };
                options.physics = { hierarchicalRepulsion: { nodeDistance: 140 } };
            } else {
                options.layout = { randomSeed: 42 };
                options.physics = {
                    barnesHut: {
                        gravitationalConstant: -2000,
                        centralGravity: 0.3
                    }
                };
            }
            
            network.setOptions(options);
            network.fit();
        }

        function getNodeColor(level) {
            const colors = [
                currentColor,
                '#2575fc',
                '#60d394',
                '#ffd97d',
                '#faaf72'
            ];
            return colors[level % colors.length];
        }

        function formatValue(value) {
            if (value === null) return 'null';
            if (Array.isArray(value)) return `[${value.length} éléments]`;
            if (typeof value === 'string') return value.length > 15 ? value.substring(0, 15) + '...' : value;
            return value;
        }

        function resetMindMap() {
            document.getElementById('mindMapContainer').classList.add('hidden');
            document.getElementById('dropZone').classList.remove('hidden');
            document.getElementById('jsonUpload').value = '';
            if (network) network.destroy();
        }

        function exportAsPNG() {
            if (!network) return;
            
            const canvas = document.querySelector('#mindMap canvas');
            const dataURL = canvas.toDataURL('image/png');
            
            const link = document.createElement('a');
            link.download = 'mindmap-' + new Date().toISOString().slice(0, 10) + '.png';
            link.href = dataURL;
            link.click();
        }
    </script>

<?php require_once '../includes/footer.php'; ?>