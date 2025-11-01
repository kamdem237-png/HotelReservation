<?php
require_once 'config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Vérification CSRF
    if (ENABLE_CSRF_PROTECTION && !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Jeton de sécurité invalide. Veuillez réessayer.";
    }
    // Rate Limiting
    elseif (ENABLE_RATE_LIMITING && !Security::checkRateLimit('register_' . $_SERVER['REMOTE_ADDR'], 3, 600)) {
        $error = "Trop de tentatives d'inscription. Veuillez réessayer plus tard.";
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email'], 'email');
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Tous les champs sont requis.";
        } 
        // Validation email
        elseif (!Security::validateEmail($email)) {
            $error = "Adresse email invalide.";
        }
        // Validation mot de passe
        elseif (!Security::validatePassword($password)) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } 
        elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Créer le compte
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            try {
                $stmt->execute([$username, $email, $hashed_password]);
                $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                
                // Log de création de compte
                Security::logSecurityEvent('ACCOUNT_CREATED', $_SERVER['REMOTE_ADDR'], [
                    'username' => $username,
                    'email' => $email
                ]);
                
                header("refresh:2;url=login.php");
            } catch(PDOException $e) {
                $error = "Une erreur est survenue lors de la création du compte.";
                Security::logSecurityEvent('ACCOUNT_CREATION_FAILED', $_SERVER['REMOTE_ADDR'], ['email' => $email]);
            }
        }
        }
    }
}
?>

    <link rel="stylesheet" href="../css/modal.css">
    
    <div class="auth-container">
        <form class="auth-form" method="POST" action="" id="registerForm">
            <h2>Inscription</h2>
            
            <?php echo Security::csrfField(); ?>

            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">S'inscrire</button>

            <p class="auth-links">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </p>
        </form>
    </div>

    <script src="../js/validation.js"></script>
    <script src="../js/modal.js"></script>
    <script>
        <?php if ($error): ?>
        Modal.error('Erreur d\'inscription', '<?php echo addslashes($error); ?>');
        <?php endif; ?>
        
        <?php if ($success): ?>
        Modal.success('Inscription réussie', '<?php echo addslashes($success); ?><br><br>Redirection vers la page de connexion...');
        <?php endif; ?>
    </script>
</body>
</html>