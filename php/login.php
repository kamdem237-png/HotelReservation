<?php
require_once 'config.php';
require_once 'header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Tous les champs sont requis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                header('Location: /HotelReservation/admin/dashboard.php');
            } else {
                header('Location: /HotelReservation/index.php');
            }
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - HotelRes</title>
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
                <a href="login.php" class="btn-login active">Connexion</a>
                <a href="register.php" class="btn-register">Inscription</a>
            </li>
        </ul>
    </nav>
    <div class="auth-container">
        <form class="auth-form" method="POST" action="">
            <h2>Connexion</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
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
    <script src="../js/nav.js"></script>
</body>
</html>