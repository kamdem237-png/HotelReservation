<?php
require_once 'config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tous les champs sont requis.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
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
                header("refresh:2;url=login.php");
            } catch(PDOException $e) {
                $error = "Une erreur est survenue lors de la création du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <h1><a href="../index.html" style="text-decoration: none; color: inherit;">HotelRes</a></h1>
        </div>
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links">
            <li><a href="../index.html">Accueil</a></li>
            <li><a href="rooms.php">Chambres</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li class="auth-links">
                <a href="login.php" class="btn-login">Connexion</a>
                <a href="register.php" class="btn-register active">Inscription</a>
            </li>
        </ul>
    </nav>
    <div class="auth-container">
        <form class="auth-form" method="POST" action="" id="registerForm">
            <h2>Inscription</h2>
            <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

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
    <script src="../js/nav.js"></script>
</body>

</html>