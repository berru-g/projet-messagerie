<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$userId = intval($_SESSION['user_id']);
$user = getUserById($userId);
if (!$user)
    die("Utilisateur non trouvé");

require_once '../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600&display=swap');

    .wallet {
        max-width: 300px;
        margin: 0 auto;
        padding: 20px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 16px;
        box-shadow: 0 0 30px rgba(0, 255, 231, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 255, 229, 0.605);
    }


    .form-wallet {
        display: grid;
        margin: 0 auto;
        gap: 12px;
        background: #F4F4F4;
        padding: 20px;
        border-radius: 12px;
        box-shadow: inset 0 0 10px rgba(0, 255, 231, 0.05);
        margin-bottom: 30px;
        max-width: 300px;
    }

    .form-wallet input[type="text"],
    input[type="number"] {
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: #F1F1F1;
        color: #444;
        font-size: 1em;
        outline: none;
        transition: 0.2s ease;
    }

    input:focus {
        box-shadow: 0 0 0 2px #00ffe7;
    }

    .btn-wallet {
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(145deg, #00ffe7, #9f7bff);
        color: #F1F1F1;
        font-weight: bold;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-wallet:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 255, 231, 0.3);
    }

    #autocomplete-list {
        background: grey;
        border-radius: 8px;
        margin-top: -8px;
        overflow: hidden;
    }

    #autocomplete-list li {
        padding: 10px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    #autocomplete-list li:hover {
        background: #2a2f376d;
    }

    #wallet-list {
        margin-top: 30px;
    }

    .crypto-item {
        display: flex;
        justify-content: space-between;
        background: #5995e9ff;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 12px;
        align-items: center;
        transition: 0.2s ease;
    }

    .crypto-item:hover {
        box-shadow: 0 0 10px rgba(0, 255, 231, 0.05);
    }

    .crypto-name {
        font-weight: 600;
        color: #555;
    }

    .crypto-meta {
        font-size: 0.9em;
        color: #aaa;
        margin-top: 5px;
    }

    .crypto-actions btn-wallet {
        margin-left: 10px;
        background: none;
        color: #ff7070;
        border: 1px solid #ff7070;
    }

    .crypto-actions btn-wallet:hover {
        background: #ff7070;
        color: #0d1117;
    }

    /* Style de base */
    #personal-wallet {
        margin-top: 40px;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 10px;
    }

    .wallet-controls {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    #crypto-results {
        list-style: none;
        padding: 0;
        margin: 5px 0 0 0;
        border: 1px solid #ddd;
        border-radius: 5px;
        max-height: 200px;
        overflow-y: auto;
    }

    #crypto-results li {
        padding: 8px 12px;
        cursor: pointer;
    }

    #crypto-results li:hover {
        background-color: #f0f0f0;
    }

    .wallet-summary {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .holdings-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .holding {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        background: white;
        border-radius: 8px;
    }

    .holding-info {
        flex-grow: 1;
    }

    .delete-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #ff4444;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .wallet-controls {
            grid-template-columns: 1fr;
        }
    }
</style>


<body>
    <section id="crypto-comparison">
        <h2>Comparaison des Narratifs Crypto</h2>
        <p>Visualiser l'évolution des narratifs majeurs</p>
        <select id="timeRange">
            <option value="7">7 Jours</option>
            <option value="30">1 Mois</option>
            <option value="365">1 An</option>
        </select>
        <div class="chart-container">
            <canvas id="cryptoChart"></canvas>
        </div>
    </section>

    <section id="personal-wallet">
        <h2>Mon Portefeuille Personnel</h2>
        <div class="wallet-controls">
            <div class="form-group">
                <label>Crypto :</label>
                <input type="text" id="crypto-search" placeholder="Rechercher...">
                <ul id="crypto-results"></ul>
                <input type="hidden" id="selected-crypto-id">
            </div>
            <div class="form-group">
                <label>Prix d'achat ($) :</label>
                <input type="number" id="purchase-price" step="0.000001">
            </div>
            <div class="form-group">
                <label>Quantité :</label>
                <input type="number" id="crypto-quantity" step="0.000001">
            </div>
            <button id="add-to-wallet" class="btn-primary">Ajouter au portefeuille</button>
        </div>

        <div class="wallet-summary">
            <h3>Résumé</h3>
            <p>Total investi : <span id="total-invested">$0.00</span></p>
            <p>Valeur actuelle : <span id="current-value">$0.00</span></p>
            <p>Performance : <span id="performance">0.00%</span></p>
        </div>

        <div id="wallet-holdings" class="holdings-container"></div>
    </section>

    <script>
        // Déclaration debug de userId
        const userId = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;

        // Vérification de la connexion
        if (!userId || userId <= 0) {
            window.location.href = '/login.php';
        }
    </script>

    <!-- Chart -->
    <script src="/assets/js/wallet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.3.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom"></script>
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>

</body>

</html>