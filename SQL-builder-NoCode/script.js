// Templates prédéfinis
const TEMPLATES = {
    users: {
        name: "Utilisateurs",
        description: "Table pour gérer les comptes utilisateurs",
        fields: [
            { name: "id", type: "INT", required: true, primary: true, autoIncrement: true },
            { name: "username", type: "VARCHAR(50)", required: true },
            { name: "email", type: "VARCHAR(100)", required: true, unique: true },
            { name: "password_hash", type: "CHAR(60)", required: true },
            { name: "created_at", type: "TIMESTAMP", defaultValue: "CURRENT_TIMESTAMP" }
        ]
    },
    posts: {
        name: "Articles",
        description: "Table pour les articles/blog posts",
        fields: [
            { name: "id", type: "INT", required: true, primary: true, autoIncrement: true },
            { name: "user_id", type: "INT", required: true, foreignKey: { table: "users", field: "id" } },
            { name: "title", type: "VARCHAR(255)", required: true },
            { name: "content", type: "TEXT", required: true },
            { name: "created_at", type: "TIMESTAMP", defaultValue: "CURRENT_TIMESTAMP" }
        ]
    },
    comments: {
        name: "Commentaires",
        description: "Table pour les commentaires",
        fields: [
            { name: "id", type: "INT", required: true, primary: true },
            { name: "content", type: "TEXT", required: true },
            { name: "user_id", type: "INT", required: true, foreignKey: { table: "users", field: "id" } },
            { name: "post_id", type: "INT", required: true, foreignKey: { table: "posts", field: "id" } },
            { name: "created_at", type: "TIMESTAMP", defaultValue: "CURRENT_TIMESTAMP" }
        ]
    },
    products: {
        name: "Produits",
        description: "Table pour les produits e-commerce",
        fields: [
            { name: "id", type: "INT", required: true, primary: true, autoIncrement: true },
            { name: "name", type: "VARCHAR(100)", required: true },
            { name: "price", type: "DECIMAL(10,2)", required: true },
            { name: "stock", type: "INT", defaultValue: 0 },
            { name: "created_at", type: "TIMESTAMP", defaultValue: "CURRENT_TIMESTAMP" }
        ]
    }
};

// État de l'application
const AppState = {
    dbName: "",
    selectedTemplates: [],
    network: null,
    monacoEditor: null,
    
    init() {
        this.setupEventListeners();
        this.initMindMap();
        this.initMonacoEditor();
    },
    
    setupEventListeners() {
        // Gestion des onglets
        document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const tabId = e.target.getAttribute('href');
                
                // Masquer tous les contenus d'onglets
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                
                // Désactiver tous les onglets
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                
                // Activer l'onglet cliqué
                e.target.classList.add('active');
                document.querySelector(tabId).classList.add('active');
                
                // Si c'est l'onglet "SQL Brut", mettre à jour son contenu
                if (tabId === '#raw-sql') {
                    document.getElementById('sql-output').textContent = 
                        document.getElementById('sql-preview').textContent;
                }
            });
        });

        // Étape 1: Choix initial
        document.getElementById('new-db-btn').addEventListener('click', () => {
            document.getElementById('step-1').style.display = 'none';
            document.getElementById('step-2').style.display = 'block';
            this.renderTemplates();
        });

        document.getElementById('upload-sql-btn').addEventListener('click', () => this.handleFileUpload());

        // Étape 2: Configuration
        document.getElementById('back-to-start').addEventListener('click', () => {
            document.getElementById('step-2').style.display = 'none';
            document.getElementById('step-1').style.display = 'block';
        });

        document.getElementById('generate-sql-btn').addEventListener('click', () => this.generateSQL());

        // Étape 3: Résultat
        document.getElementById('edit-schema-btn').addEventListener('click', () => {
            document.getElementById('step-3').style.display = 'none';
            document.getElementById('step-2').style.display = 'block';
        });

        document.getElementById('download-sql-btn').addEventListener('click', () => this.downloadSQL());
        document.getElementById('view-mindmap-btn').addEventListener('click', () => this.viewMindmap());
    },
    
    renderTemplates() {
        const container = document.getElementById('templates-container');
        container.innerHTML = '';

        Object.entries(TEMPLATES).forEach(([key, template]) => {
            const isSelected = this.selectedTemplates.includes(key);
            const templateHTML = `
                <div class="template-card ${isSelected ? 'selected' : ''}" data-template="${key}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>${template.name}</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''}>
                        </div>
                    </div>
                    <p class="text-muted">${template.description}</p>
                    <ul class="list-unstyled">
                        ${template.fields.map(field => `
                            <li class="field-item">
                                <span><strong>${field.name}</strong> (${field.type})</span>
                                ${field.primary ? '<span class="badge bg-primary">PK</span>' : ''}
                                ${field.unique ? '<span class="badge bg-info">UNIQUE</span>' : ''}
                                ${field.foreignKey ? '<span class="badge bg-warning">FK</span>' : ''}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
            container.innerHTML += templateHTML;
        });

        // Gestion des sélections
        document.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT') return;

                const templateKey = card.dataset.template;
                const checkbox = card.querySelector('input[type="checkbox"]');
                
                if (this.selectedTemplates.includes(templateKey)) {
                    this.selectedTemplates = this.selectedTemplates.filter(t => t !== templateKey);
                    checkbox.checked = false;
                    card.classList.remove('selected');
                } else {
                    this.selectedTemplates.push(templateKey);
                    checkbox.checked = true;
                    card.classList.add('selected');
                }
                
                this.updateLivePreview();
            });
        });
    },
    
    generateSQL() {
        this.dbName = document.getElementById('db-name').value.trim() || 'my_database';

        let sql = `-- Généré automatiquement par agora-dataviz.com/SQL-Builder \n`;
        sql += `-- Date: ${new Date().toLocaleString()}\n\n`;
        sql += `CREATE DATABASE IF NOT EXISTS ${this.dbName};\nUSE ${this.dbName};\n\n`;

        // Génération des tables
        this.selectedTemplates.forEach(key => {
            const template = TEMPLATES[key];
            sql += `-- Table: ${template.name}\n`;
            sql += `CREATE TABLE ${key} (\n`;

            const fields = [];
            const constraints = [];

            template.fields.forEach(field => {
                let fieldDef = `  ${field.name} ${field.type}`;
                if (field.required) fieldDef += ' NOT NULL';
                if (field.autoIncrement) fieldDef += ' AUTO_INCREMENT';
                if (field.defaultValue) fieldDef += ` DEFAULT ${field.defaultValue}`;
                if (field.primary) constraints.push(`PRIMARY KEY (${field.name})`);
                if (field.unique) constraints.push(`UNIQUE (${field.name})`);
                if (field.foreignKey) {
                    constraints.push(`FOREIGN KEY (${field.name}) REFERENCES ${field.foreignKey.table}(${field.foreignKey.field})`);
                }
                fields.push(fieldDef);
            });

            sql += fields.join(',\n');
            
            if (constraints.length > 0) {
                sql += ',\n' + constraints.join(',\n');
            }

            sql += '\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n';
        });

        document.getElementById('sql-preview').textContent = sql;
        document.getElementById('step-2').style.display = 'none';
        document.getElementById('step-3').style.display = 'block';
        
        this.updateLivePreview();
    },
    
    updateLivePreview() {
        const sql = document.getElementById('sql-preview').textContent;
        
        // Mettre à jour Monaco Editor
        if (this.monacoEditor) {
            this.monacoEditor.setValue(sql);
        }
        
        // Mettre à jour l'onglet SQL Brut
        document.getElementById('sql-output').textContent = sql;
        
        // Mettre à jour le mindmap
        this.updateMindMap();
    },
    
    downloadSQL() {
        const sql = document.getElementById('sql-preview').textContent;
        const blob = new Blob([sql], { type: 'text/sql' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `${this.dbName}_schema_${new Date().toISOString().split('T')[0]}.sql`;
        a.click();
        
        URL.revokeObjectURL(url);
    },
    
    handleFileUpload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.sql';
        
        input.onchange = e => {
            const file = e.target.files[0];
            const reader = new FileReader();
            
            reader.onload = event => {
                document.getElementById('sql-preview').textContent = event.target.result;
                document.getElementById('step-1').style.display = 'none';
                document.getElementById('step-3').style.display = 'block';
                this.dbName = file.name.replace('.sql', '');
                this.updateLivePreview();
            };
            
            reader.readAsText(file);
        };
        
        input.click();
    },
    
    initMindMap() {
        const container = document.getElementById('mindmap-container');
        if (container) {
            this.network = new vis.Network(container, { nodes: [], edges: [] }, {
                nodes: {
                    shape: 'box',
                    margin: 10,
                    font: { size: 14 }
                },
                edges: {
                    arrows: { to: { enabled: true } },
                    smooth: true
                },
                physics: {
                    enabled: true,
                    solver: 'forceAtlas2Based'
                }
            });
        }
    },
    
    updateMindMap() {
        if (!this.network) return;
        
        const nodes = [];
        const edges = [];
        
        this.selectedTemplates.forEach(tableName => {
            const template = TEMPLATES[tableName];
            
            // Noeud principal pour la table
            nodes.push({
                id: tableName,
                label: template.name,
                color: '#4CAF50',
                font: { size: 16, bold: true }
            });
            
            // Sous-noeuds pour les champs
            template.fields.forEach(field => {
                const fieldId = `${tableName}_${field.name}`;
                let color = '#E3F2FD';
                if (field.primary) color = '#FFF9C4';
                if (field.foreignKey) color = '#FFCCBC';
                
                nodes.push({
                    id: fieldId,
                    label: `${field.name}\n${field.type}`,
                    color: color,
                    shape: 'box',
                    margin: 5
                });
                
                edges.push({
                    from: tableName,
                    to: fieldId
                });
                
                // Relations FK
                if (field.foreignKey) {
                    edges.push({
                        from: fieldId,
                        to: `${field.foreignKey.table}_${field.foreignKey.field}`,
                        dashes: true,
                        arrows: 'to',
                        color: '#FF5722'
                    });
                }
            });
        });
        
        this.network.setData({ nodes, edges });
    },
    
    viewMindmap() {
        const element = document.getElementById('mindmap-container');
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    },
    
    initMonacoEditor() {
        if (typeof monaco === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js';
            script.onload = () => {
                require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
                require(['vs/editor/editor.main'], () => {
                    this.createMonacoEditor();
                });
            };
            document.body.appendChild(script);
        } else {
            this.createMonacoEditor();
        }
    },
    
    createMonacoEditor() {
    const container = document.getElementById('live-sql-editor');
    if (container && typeof monaco !== 'undefined') {
        this.monacoEditor = monaco.editor.create(container, {
            value: '',
            language: 'sql',
            theme: 'vs',
            automaticLayout: true,
            readOnly: false, // Passer en mode éditable
            minimap: { enabled: false }
        });

        // Écouter les changements pour mettre à jour la mind map
        this.monacoEditor.onDidChangeModelContent(() => {
            const sql = this.monacoEditor.getValue();
            document.getElementById('sql-preview').textContent = sql;
            document.getElementById('sql-output').textContent = sql;
            this.parseSQLToUpdateMindMap(sql);
        });
    }
}
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    AppState.init();
});

// Canvas background - points network (simple, performant)
const canvas = document.getElementById("canvas-bg");
const ctx = canvas.getContext("2d");
let width, height;
let points = [];

function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width * devicePixelRatio;
    canvas.height = height * devicePixelRatio;
    canvas.style.width = width + "px";
    canvas.style.height = height + "px";
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(devicePixelRatio, devicePixelRatio);
}

class Point {
    constructor(x, y, vx, vy) {
        this.x = x;
        this.y = y;
        this.vx = vx;
        this.vy = vy;
        this.radius = 2;
    }
    
    update() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > width) this.vx = -this.vx;
        if (this.y < 0 || this.y > height) this.vy = -this.vy;
    }
    
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.fillStyle = "rgba(156, 141, 234, 0.7)";
        ctx.fill();
    }
}

function connectPoints() {
    let maxDist = 130;
    for (let i = 0; i < points.length; i++) {
        for (let j = i + 1; j < points.length; j++) {
            let dx = points[i].x - points[j].x;
            let dy = points[i].y - points[j].y;
            let dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < maxDist) {
                ctx.beginPath();
                ctx.strokeStyle = `rgba(156, 141, 234, ${1 - dist / maxDist})`;
                ctx.lineWidth = 1;
                ctx.moveTo(points[i].x, points[i].y);
                ctx.lineTo(points[j].x, points[j].y);
                ctx.stroke();
            }
        }
    }
}

function animate() {
    ctx.clearRect(0, 0, width, height);
    points.forEach((p) => {
        p.update();
        p.draw();
    });
    connectPoints();
    requestAnimationFrame(animate);
}

function initCanvas() {
    points = [];
    for (let i = 0; i < 140; i++) {
        let x = Math.random() * width;
        let y = Math.random() * height;
        let vx = (Math.random() - 0.5) * 0.3;
        let vy = (Math.random() - 0.5) * 0.3;
        points.push(new Point(x, y, vx, vy));
    }
    animate();
}

window.addEventListener("resize", () => {
    resize();
    initCanvas();
});

// Initialisation du canvas
resize();
initCanvas();