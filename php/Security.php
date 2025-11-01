<?php
/**
 * Classe de sécurité centralisée
 * Gère CSRF, rate limiting, validation, logs de sécurité
 */
class Security {
    
    private static $pdo;
    
    /**
     * Initialisation de la sécurité
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
        self::configureSecureSessions();
        self::setSecurityHeaders();
    }
    
    /**
     * Configuration des sessions sécurisées
     */
    private static function configureSecureSessions() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée des sessions
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Mettre à 1 si HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 3600); // 1 heure
            
            session_start();
            
            // Régénération de l'ID de session pour éviter le hijacking
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
            
            // Vérification de l'expiration de session
            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
                session_unset();
                session_destroy();
                session_start();
            }
            $_SESSION['LAST_ACTIVITY'] = time();
        }
    }
    
    /**
     * Définir les headers de sécurité
     */
    private static function setSecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Génération de token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validation du token CSRF
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            self::logSecurityEvent('CSRF_ATTACK_DETECTED', $_SERVER['REMOTE_ADDR']);
            return false;
        }
        return true;
    }
    
    /**
     * Générer un champ de formulaire CSRF caché
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Rate Limiting pour les tentatives de connexion
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        // Créer la table si elle n'existe pas
        self::createRateLimitTable();
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = md5($identifier . $ip);
        $now = time();
        $windowStart = $now - $timeWindow;
        
        // Nettoyer les anciennes tentatives
        $stmt = self::$pdo->prepare("DELETE FROM rate_limit WHERE timestamp < ?");
        $stmt->execute([$windowStart]);
        
        // Compter les tentatives récentes
        $stmt = self::$pdo->prepare("SELECT COUNT(*) as attempts FROM rate_limit WHERE identifier = ? AND timestamp > ?");
        $stmt->execute([$key, $windowStart]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['attempts'] >= $maxAttempts) {
            self::logSecurityEvent('RATE_LIMIT_EXCEEDED', $ip, ['identifier' => $identifier]);
            return false;
        }
        
        // Enregistrer la tentative
        $stmt = self::$pdo->prepare("INSERT INTO rate_limit (identifier, ip_address, timestamp) VALUES (?, ?, ?)");
        $stmt->execute([$key, $ip, $now]);
        
        return true;
    }
    
    /**
     * Créer la table de rate limiting
     */
    private static function createRateLimitTable() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            timestamp INT NOT NULL,
            INDEX idx_identifier (identifier),
            INDEX idx_timestamp (timestamp)
        )";
        self::$pdo->exec($sql);
    }
    
    /**
     * Validation d'email avancée
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Vérifier le domaine
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, 'MX')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validation de mot de passe (6 caractères minimum)
     */
    public static function validatePassword($password) {
        // Au moins 6 caractères, n'importe lesquels
        return strlen($password) >= 6;
    }
    
    /**
     * Sanitisation avancée des entrées
     */
    public static function sanitizeInput($data, $type = 'string') {
        $data = trim($data);
        
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Protection contre les injections SQL (validation supplémentaire)
     */
    public static function detectSQLInjection($input) {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(--|\#|\/\*|\*\/)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('SQL_INJECTION_ATTEMPT', $_SERVER['REMOTE_ADDR'], ['input' => $input]);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Protection contre les attaques XSS
     */
    public static function detectXSS($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<iframe/i',
            '/<embed/i',
            '/<object/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('XSS_ATTEMPT', $_SERVER['REMOTE_ADDR'], ['input' => $input]);
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérification de l'IP pour détecter les proxies/VPN suspects
     */
    public static function validateIP($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        
        // Vérifier si c'est une IP valide
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Bloquer les IPs privées/locales en production
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Journalisation des événements de sécurité
     */
    public static function logSecurityEvent($event_type, $ip_address, $details = []) {
        self::createSecurityLogTable();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO security_logs (event_type, ip_address, user_agent, request_uri, details, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event_type,
            $ip_address,
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            $_SERVER['REQUEST_URI'] ?? 'Unknown',
            json_encode($details)
        ]);
    }
    
    /**
     * Créer la table des logs de sécurité
     */
    private static function createSecurityLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            request_uri TEXT,
            details TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_event_type (event_type),
            INDEX idx_ip (ip_address),
            INDEX idx_created (created_at)
        )";
        self::$pdo->exec($sql);
    }
    
    /**
     * Bloquer une IP
     */
    public static function blockIP($ip, $reason, $duration = 3600) {
        self::createBlockedIPsTable();
        
        $stmt = self::$pdo->prepare("
            INSERT INTO blocked_ips (ip_address, reason, blocked_until) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL ? SECOND), reason = ?
        ");
        
        $stmt->execute([$ip, $reason, $duration, $duration, $reason]);
        self::logSecurityEvent('IP_BLOCKED', $ip, ['reason' => $reason, 'duration' => $duration]);
    }
    
    /**
     * Vérifier si une IP est bloquée
     */
    public static function isIPBlocked($ip = null) {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        self::createBlockedIPsTable();
        
        $stmt = self::$pdo->prepare("
            SELECT COUNT(*) as blocked 
            FROM blocked_ips 
            WHERE ip_address = ? AND blocked_until > NOW()
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['blocked'] > 0;
    }
    
    /**
     * Créer la table des IPs bloquées
     */
    private static function createBlockedIPsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason TEXT,
            blocked_until DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_until (ip_address, blocked_until)
        )";
        self::$pdo->exec($sql);
    }
    
    /**
     * Vérifier l'authentification avec protection renforcée
     */
    public static function requireAuth($redirectTo = '/HotelReservation/php/login.php') {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $redirectTo);
            exit();
        }
        
        // Vérifier la validité de la session
        if (!isset($_SESSION['user_ip']) || $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            session_unset();
            session_destroy();
            self::logSecurityEvent('SESSION_HIJACKING_ATTEMPT', $_SERVER['REMOTE_ADDR']);
            header('Location: ' . $redirectTo);
            exit();
        }
    }
    
    /**
     * Vérifier les droits admin
     */
    public static function requireAdmin($redirectTo = '/HotelReservation/index.html') {
        self::requireAuth();
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            self::logSecurityEvent('UNAUTHORIZED_ADMIN_ACCESS', $_SERVER['REMOTE_ADDR'], [
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            header('Location: ' . $redirectTo);
            exit();
        }
    }
}
?>
