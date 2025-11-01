<?php
session_start();

// Log l'événement de déconnexion si la sécurité est initialisée
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    
    Security::logSecurityEvent('LOGOUT', $_SERVER['REMOTE_ADDR'], [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
}

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../index.html');
exit();
?>
