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

<div class="container auth-container">
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