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
</style>


<body>
    <div class="wallet">
        <h3>Mon Portefeuille Crypto</h3>
        <div class="wallet-summary">
            <p>Total investi : <span id="total-invested">$0.00</span></p>
            <p>Valeur actuelle : <span id="current-value">$0.00</span></p>
            <p>Variation 24h : <span id="performance-24h">0.00%</span></p>
        </div>

        <div id="growthchart" style="width: 100%; height: 400px;"></div>


        <div class="form-wallet">
            <label>Ajouter une crypto :</label>
            <input type="text" id="search-crypto" placeholder="ex: bitcoin">
            <ul id="autocomplete-list"></ul>
            <input type="hidden" id="crypto-id">
            <input type="text" id="crypto-name" placeholder="Nom complet">
            <input type="number" id="purchase-price" placeholder="Prix d'achat ($)">
            <input type="number" id="quantity" placeholder="Quantité">
            <button class="btn-wallet" id="add-btn">Ajouter</button>
        </div>

        <div id="wallet-list"></div>
        <div id="chartdiv" style="width: 100%; height: 400px;"></div>
    </div>

    <script>const userId = <?= $userId ?>;</script>
<script src="../assets/js/wallet.js"></script>
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

</body>

</html>