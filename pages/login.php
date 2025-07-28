<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email)) {
        $errors[] = "L'email est requis";
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis";
    }

    // Si pas d'erreurs, vérifier les identifiants
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: " . BASE_URL);
            exit;
        } else {
            $errors[] = "Email ou mot de passe incorrect";
        }
    }
}

require_once '../includes/header.php';
?>
<!-- presentation du tool
<section class="data-tools-showcase">
    <div class="dt-container">
        <h2 class="dt-title">
            <span class="dt-icon"><i class="fas fa-chart-network"></i></span>
            Transformez vos données en insights
        </h2>

        <div class="dt-grid">
           
            <div class="dt-card">
                <div class="dt-card-icon csv">
                    <i class="fa fa-file-csv"></i>
                </div>
                <h3>CSV Transformer</h3>
                <p>Conversion vers multiples formats</p>
                <ul class="dt-features">
                    <li><i class="fas fa-chart-bar"></i> Graphiques dynamiques</li>
                    <li><i class="fas fa-table"></i> Tableaux interactifs</li>
                    <li><i class="fas fa-file-export"></i> Exports PNG/PDF</li>
                </ul>
            </div>

            
            <div class="dt-card">
                <div class="dt-card-icon excel">
                    <i class="fa fa-file-excel"></i>
                </div>
                <h3>Excel Magic</h3>
                <p>Analyse avancée</p>
                <ul class="dt-features">
                    <li><i class="fas fa-project-diagram"></i> Visualisations 3D</li>
                    <li><i class="fas fa-bolt"></i> Traitement rapide</li>
                    <li><i class="fas fa-cloud-upload"></i> Intégration cloud</li>
                </ul>
            </div>

            
            <div class="dt-card">
                <div class="dt-card-icon json">
                    <i class="fa fa-file-code"></i>
                </div>
                <h3>JSON Explorer</h3>
                <p>Analyse de structures</p>
                <ul class="dt-features">
                    <li><i class="fas fa-sitemap"></i> Arborescence</li>
                    <li><i class="fas fa-filter"></i> Filtres intelligents</li>
                    <li><i class="fas fa-share-alt"></i> Partage configurable</li>
                </ul>
            </div>
        </div>

        <div class="dt-cta">
            <p>Explorez notre galerie publique ou uploader vos propres fichiers</p>
            <div class="dt-buttons">
                
                <a href="#seconnecter" class="dt-btn primary">
                    <i class="fas fa-rocket"></i> Commencer
                </a>
                <a href="#" class="dt-btn secondary">
                    <i class="fas fa-book-open"></i> Tutoriels
                </a>
            </div>
        </div>
    </div>
</section>-->

<section class="w-full px-4 py-8 bg-gray-50 border-b border-gray-200">
  <div class="max-w-xl mx-auto text-center">
    <img src="<?= BASE_URL ?>/assets/img/agora-logo.png" alt="Logo Agora" class="mx-auto w-20 h-20 mb-4 rounded-xl shadow-md object-contain">
    
    <h1 class="text-2xl sm:text-3xl font-semibold text-gray-800 mb-2">Bienvenue sur Agora</h1>

    <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
      Ceci est un <strong>MVP</strong> (version minimale viable ou prototype) en phase de test et en recherche de niche. Vos retours sont <strong>les bienvenus</strong> pour améliorer la plateforme.<br class="hidden sm:block" />
      Nous croyons en une communauté bienveillante : <span class="text-red-600 font-semibold">tout comportement violent ou malveillant entraînera un bannissement immédiat</span>.
    </p>
  </div>
</section>


<div class="container auth-container" id="seconnecter">
    <h2>Connexion</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= $error ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            Inscription réussie ! Vous pouvez maintenant vous connecter.
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous</a></p>
    <p><a href="forgot-password.php">Mot de passe oublié ?</a></p>
</div>

<?php require_once '../includes/footer.php'; ?>