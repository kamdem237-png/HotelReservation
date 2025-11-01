<?php
/**
 * Configuration de l'application avec sécurité renforcée
 */

// Empêcher l'accès direct au fichier
defined('APP_ACCESS') or define('APP_ACCESS', true);

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hotel_db');
define('DB_CHARSET', 'utf8mb4');

// Configuration générale
define('SITE_NAME', 'Système de Réservation d\'Hôtel');
define('EMAIL_FROM', 'noreply@hotel.com');
define('SITE_URL', 'http://localhost/HotelReservation');

// Configuration de sécurité
define('ENABLE_CSRF_PROTECTION', true);
define('ENABLE_RATE_LIMITING', true);
define('MAX_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 300); // 5 minutes
define('SESSION_LIFETIME', 3600); // 1 heure
define('PASSWORD_MIN_LENGTH', 6);

// Charger la classe de sécurité
require_once __DIR__ . '/Security.php';

// Charger le helper de conversion de devise
require_once __DIR__ . '/currency_helper.php';

// Variable globale PDO
$pdo = null;

// Connexion à la base de données sécurisée
function getDBConnection() {
    global $pdo;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            $options
        );
        
        // Initialiser la sécurité
        Security::init($pdo);
        
        // Vérifier si l'IP est bloquée
        if (Security::isIPBlocked()) {
            http_response_code(403);
            die('Accès refusé. Votre adresse IP a été temporairement bloquée.');
        }
        
        return $pdo;
    } catch(PDOException $e) {
        // Log l'erreur sans révéler les détails sensibles
        error_log("Erreur de connexion DB: " . $e->getMessage());
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
}

// Fonction de sécurité améliorée (rétrocompatibilité)
function sanitize($data, $type = 'string') {
    return Security::sanitizeInput($data, $type);
}

// Fonction de vérification d'authentification
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Fonction de vérification du rôle administrateur
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Fonction pour exiger l'authentification
function requireAuth($redirectTo = 'login.php') {
    Security::requireAuth($redirectTo);
}

// Fonction pour exiger les droits admin
function requireAdmin($redirectTo = '../index.html') {
    Security::requireAdmin($redirectTo);
}

// Protection CSRF globale pour les POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ENABLE_CSRF_PROTECTION) {
    // Exclure certaines routes si nécessaire
    $excludedPaths = []; // Ajouter les chemins à exclure si nécessaire
    
    $currentPath = $_SERVER['PHP_SELF'];
    $isExcluded = false;
    
    foreach ($excludedPaths as $path) {
        if (strpos($currentPath, $path) !== false) {
            $isExcluded = true;
            break;
        }
    }
    
    if (!$isExcluded && !isset($_SESSION['csrf_token'])) {
        // Première requête POST, générer le token
        Security::generateCSRFToken();
    }
}

// Initialiser la connexion et la sécurité
$pdo = getDBConnection();
?>