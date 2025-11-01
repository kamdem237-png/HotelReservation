<?php
require_once 'config.php';
require_once 'header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Vérification CSRF
    if (ENABLE_CSRF_PROTECTION && !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Jeton de sécurité invalide. Veuillez réessayer.";
    } 
    // Rate Limiting
    elseif (ENABLE_RATE_LIMITING && !Security::checkRateLimit('login_' . ($_POST['email'] ?? 'unknown'), MAX_LOGIN_ATTEMPTS, RATE_LIMIT_WINDOW)) {
        $error = "Trop de tentatives de connexion. Veuillez réessayer dans quelques minutes.";
        Security::logSecurityEvent('LOGIN_RATE_LIMIT', $_SERVER['REMOTE_ADDR'], ['email' => $_POST['email'] ?? '']);
    } else {
        $email = sanitize($_POST['email'], 'email');
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Tous les champs sont requis.";
        } 
        // Validation email
        elseif (!Security::validateEmail($email)) {
            $error = "Adresse email invalide.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Régénérer l'ID de session pour éviter le hijacking
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['initiated'] = true;
                
                // Log de connexion réussie
                Security::logSecurityEvent('LOGIN_SUCCESS', $_SERVER['REMOTE_ADDR'], [
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ]);
                
                // Vérifier s'il y a une intention de réservation
                if (isset($_SESSION['booking_intent'])) {
                    $intent = $_SESSION['booking_intent'];
                    
                    // Rediriger vers la création de réservation
                    $url = sprintf(
                        'create_booking.php?room_type_id=%d&check_in=%s&check_out=%s&guests=%d',
                        $intent['room_type_id'],
                        urlencode($intent['check_in']),
                        urlencode($intent['check_out']),
                        $intent['guests']
                    );
                    header('Location: ' . $url);
                    exit();
                }
                
                // Redirection normale selon le rôle
                if ($user['role'] === 'admin') {
                    header('Location: /HotelReservation/admin/dashboard.php');
                } else {
                    header('Location: /HotelReservation/index.html');
                }
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
                Security::logSecurityEvent('LOGIN_FAILED', $_SERVER['REMOTE_ADDR'], ['email' => $email]);
            }
        }
    }
}
?>

    <link rel="stylesheet" href="../css/modal.css">
    
    <div class="auth-container">
        <form class="auth-form" method="POST" action="">
            <h2>Connexion</h2>
            
            <?php echo Security::csrfField(); ?>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-primary">Se connecter</button>
            
            <p class="auth-links">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </p>
        </form>
    </div>

    <script src="../js/validation.js"></script>
    <script src="../js/modal.js"></script>
    <script>
        <?php if ($error): ?>
        Modal.error('Erreur de connexion', '<?php echo addslashes($error); ?>');
        <?php endif; ?>
    </script>
</body>
</html>