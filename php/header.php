<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Système de Réservation d'Hôtel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script defer src="../js/nav.js"></script>
</head>

<body>
    <!-- En-tête -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <h1>HotelRes</h1>
            </div>
            <button class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links">
                <li><a href="../index.html"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.html' ? 'active' : ''; ?>">Accueil</a>
                </li>
                <li><a href="rooms.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">Chambres</a>
                </li>
                <li><a href="reservations.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">Réservations</a>
                </li>
                <li><a href="contact.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a>
                </li>
                <li class="auth-links">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn-login">Mon Profil</a>
                    <a href="logout.php" class="btn-register">Déconnexion</a>
                    <?php else: ?>
                    <a href="login.php" class="btn-login <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">Connexion</a>
                    <a href="register.php" class="btn-register <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">Inscription</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>
    <!-- Bouton retour en haut -->
    <div class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </div>