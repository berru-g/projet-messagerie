<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $userId = intval($_GET['user_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM user_crypto_holdings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cryptos);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';
    $userId = intval($data['user_id'] ?? 0);

    if ($action === 'add') {
        $cryptoId = $data['crypto_id'] ?? '';
        $cryptoName = $data['crypto_name'] ?? '';
        $price = floatval($data['purchase_price'] ?? 0);
        $quantity = floatval($data['quantity'] ?? 0);

        $stmt = $pdo->prepare("SELECT id FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
        $stmt->execute([$userId, $cryptoId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE user_crypto_holdings SET quantity = ?, purchase_price = ?, crypto_name = ? WHERE id = ?");
            $stmt->execute([$quantity, $price, $cryptoName, $exists['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_crypto_holdings (user_id, crypto_id, crypto_name, purchase_price, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $cryptoId, $cryptoName, $price, $quantity]);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $cryptoId = $data['crypto_id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM user_crypto_holdings WHERE user_id = ? AND crypto_id = ?");
        $stmt->execute([$userId, $cryptoId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Requête invalide']);
exit;
