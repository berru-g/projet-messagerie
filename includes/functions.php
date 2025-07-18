<?php
require_once  'db.php';

// Fonction pour vérifier si un utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour obtenir les informations de l'utilisateur
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour ajouter un commentaire ( cohérence avec la table gael putain!!!!)
function addComment($user_id, $content, $parent_id = null, $file_path = null, $file_type = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, content, parent_id, file_path, file_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $content, $parent_id, $file_path, $file_type]);
}


// Fonction pour liker un commentaire
function likeComment($user_id, $comment_id) {
    global $pdo;
    
    // Vérifier si l'utilisateur a déjà liké ce commentaire
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);
    
    if ($stmt->rowCount() > 0) {
        // Retirer le like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND comment_id = ?");
        return $stmt->execute([$user_id, $comment_id]);
    } else {
        // Ajouter le like
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, comment_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $comment_id]);
    }
}

// Fonction pour obtenir tous les commentaires avec leurs likes
function getAllComments() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, u.username, COUNT(l.id) as like_count 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN likes l ON c.id = l.comment_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour vérifier si l'utilisateur courant a liké un commentaire
function hasLiked($user_id, $comment_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);
    return $stmt->rowCount() > 0;
}

// fonction de visualisation des uploads
function displayCsvFile($filePath) {
    $html = '<table class="table table-bordered table-striped">';
    
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $firstRow = true;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $html .= '<tr>';
            foreach ($data as $cell) {
                if ($firstRow) {
                    $html .= '<th>' . htmlspecialchars($cell) . '</th>';
                } else {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
            }
            $html .= '</tr>';
            $firstRow = false;
        }
        fclose($handle);
    }
    
    $html .= '</table>';
    return $html;
}

// Fonction pour obtenir les commentaires parents (principaux)
function getParentComments() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT c.*, u.username, COUNT(l.id) as like_count 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN likes l ON c.id = l.comment_id
        WHERE c.parent_id IS NULL
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir les réponses à un commentaire
function getReplies($comment_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, COUNT(l.id) as like_count 
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN likes l ON c.id = l.comment_id
        WHERE c.parent_id = ?
        GROUP BY c.id
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$comment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour uploader un fichier
function uploadFile($file) {
    $uploadDir = '../uploads/';
    $allowedTypes = ['image/jpeg', 'image/png', 'video/mp4'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Type de fichier non autorisé'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'path' => 'uploads/' . $fileName, // ← chemin relatif
            'type' => strpos($file['type'], 'image') !== false ? 'image' : 'video'
        ];
    }
    
    return ['error' => 'Erreur lors du téléchargement'];
}
// ???
function displayExcelFile($filePath) {
    require_once '../vendor/autoload.php';
    $html = '<table class="table table-bordered table-striped">';
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        foreach ($worksheet->getRowIterator() as $row) {
            $html .= '<tr>';
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $value = $cell->getFormattedValue();
                if ($row->getRowIndex() == 1) {
                    $html .= '<th>' . htmlspecialchars($value) . '</th>';
                } else {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            }
            $html .= '</tr>';
        }
    } catch (Exception $e) {
        return '<div class="alert alert-danger">Erreur Excel: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    $html .= '</table>';
    return $html;
}
/*
function displayJsonFile($filePath) {
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<div class="alert alert-danger">Erreur JSON: ' . json_last_error_msg() . '</div>';
    }
    
    return '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
}
*/
function displayJsonFile($filePath) {
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<div class="alert alert-danger">Erreur JSON: ' . json_last_error_msg() . '</div>';
    }
    
    // Vérifie si c'est un fichier de tarifs suivant le template
    if (isset($data['meta'])) {
        return renderPriceTemplate($data);
    }
    
    // Fallback pour les autres JSON
    return '<pre class="json-display">' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
}

function renderPriceTemplate($data) {
    ob_start(); ?>
    <div class="price-template">
        <!-- En-tête avec les métadonnées -->
        <div class="price-header mb-4 p-3 bg-light rounded">
            <h4>Fiche tarifaire</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Fournisseur:</strong> <?= htmlspecialchars($data['meta']['fournisseur'] ?? 'Non spécifié') ?></p>
                    <p><strong>Date de mise à jour:</strong> <?= htmlspecialchars($data['meta']['date_maj'] ?? 'Inconnue') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Devise:</strong> <?= htmlspecialchars($data['meta']['devise'] ?? 'EUR') ?></p>
                </div>
            </div>
        </div>
        
        <!-- Tableau des produits -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix HT</th>
                        <th>Unité</th>
                        <th>TVA</th>
                        <th>EAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['produits'] as $produit): ?>
                    <tr>
                        <td><?= htmlspecialchars($produit['id_unique'] ?? '') ?></td>
                        <td><?= htmlspecialchars($produit['nom'] ?? '') ?></td>
                        <td><?= htmlspecialchars($produit['categorie'] ?? '') ?></td>
                        <td class="text-right"><?= number_format($produit['prix_ht'] ?? 0, 2, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($produit['unite'] ?? '') ?></td>
                        <td class="text-right"><?= number_format($produit['tva'] ?? 0, 2, ',', ' ') ?>%</td>
                        <td><?= htmlspecialchars($produit['code_ean'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php return ob_get_clean();
}

// test formatage template json
function generatePriceTemplate() {
    return [
        "meta" => [
            "fournisseur" => "",
            "date_maj" => date('Y-m-d'),
            "devise" => "EUR",
            "commentaire" => ""
        ],
        "produits" => [
            [
                "id_unique" => "001",
                "nom" => "Exemple produit",
                "categorie" => "viandes|poissons|légumes|boissons|épicerie",
                "prix_ht" => 0.00,
                "unite" => "kg|L|pièce",
                "tva" => 5.5,
                "code_ean" => ""
            ]
        ]
    ];
}
?>

