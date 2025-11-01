-- ========================================
-- TABLES DE SÉCURITÉ
-- Système de Réservation d'Hôtel
-- ========================================

-- Table des logs de sécurité
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'Type d''événement (LOGIN_FAILED, XSS_ATTEMPT, etc.)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Adresse IP du client',
    user_agent TEXT COMMENT 'User-Agent du navigateur',
    request_uri TEXT COMMENT 'URI de la requête',
    details TEXT COMMENT 'Détails en JSON',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de l''événement',
    
    INDEX idx_event_type (event_type),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Journalisation des événements de sécurité';

-- Table de rate limiting
CREATE TABLE IF NOT EXISTS rate_limit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'Hash unique de l''action + IP',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Adresse IP',
    timestamp INT NOT NULL COMMENT 'Timestamp Unix de la tentative',
    
    INDEX idx_identifier (identifier),
    INDEX idx_timestamp (timestamp),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suivi des tentatives pour rate limiting';

-- Table des IPs bloquées
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE COMMENT 'Adresse IP bloquée',
    reason TEXT COMMENT 'Raison du blocage',
    blocked_until DATETIME NOT NULL COMMENT 'Date de fin du blocage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date du blocage',
    
    INDEX idx_ip_until (ip_address, blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Liste des adresses IP bloquées';

-- ========================================
-- ÉVÉNEMENTS DE SÉCURITÉ TYPES
-- ========================================
-- Les types d'événements enregistrés:
-- - LOGIN_SUCCESS: Connexion réussie
-- - LOGIN_FAILED: Échec de connexion
-- - LOGIN_RATE_LIMIT: Trop de tentatives de connexion
-- - ACCOUNT_CREATED: Nouveau compte créé
-- - ACCOUNT_CREATION_FAILED: Échec création de compte
-- - CSRF_ATTACK_DETECTED: Token CSRF invalide
-- - SQL_INJECTION_ATTEMPT: Tentative d'injection SQL
-- - XSS_ATTEMPT: Tentative d'attaque XSS
-- - SESSION_HIJACKING_ATTEMPT: Changement d'IP suspect
-- - UNAUTHORIZED_ADMIN_ACCESS: Tentative d'accès admin non autorisé
-- - IP_BLOCKED: IP bloquée automatiquement
-- - RATE_LIMIT_EXCEEDED: Rate limit dépassé

-- ========================================
-- VUES UTILES
-- ========================================

-- Vue des événements récents (24h)
CREATE OR REPLACE VIEW recent_security_events AS
SELECT 
    event_type,
    ip_address,
    user_agent,
    request_uri,
    details,
    created_at
FROM security_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;

-- Vue des IPs suspectes (plus de 5 échecs en 1h)
CREATE OR REPLACE VIEW suspicious_ips AS
SELECT 
    ip_address,
    COUNT(*) as failed_attempts,
    MAX(created_at) as last_attempt
FROM security_logs
WHERE event_type IN ('LOGIN_FAILED', 'CSRF_ATTACK_DETECTED', 'SQL_INJECTION_ATTEMPT', 'XSS_ATTEMPT')
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address
HAVING failed_attempts >= 5
ORDER BY failed_attempts DESC;

-- Vue des IPs actuellement bloquées
CREATE OR REPLACE VIEW currently_blocked_ips AS
SELECT 
    ip_address,
    reason,
    blocked_until,
    created_at,
    TIMESTAMPDIFF(MINUTE, NOW(), blocked_until) as minutes_remaining
FROM blocked_ips
WHERE blocked_until > NOW()
ORDER BY blocked_until DESC;

-- ========================================
-- PROCÉDURES STOCKÉES UTILES
-- ========================================

DELIMITER //

-- Procédure pour nettoyer les anciens logs (>30 jours)
CREATE PROCEDURE IF NOT EXISTS clean_old_security_logs()
BEGIN
    DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    SELECT ROW_COUNT() as deleted_rows;
END //

-- Procédure pour nettoyer le rate limiting (>24h)
CREATE PROCEDURE IF NOT EXISTS clean_old_rate_limits()
BEGIN
    DELETE FROM rate_limit WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR));
    SELECT ROW_COUNT() as deleted_rows;
END //

-- Procédure pour débloquer les IPs expirées
CREATE PROCEDURE IF NOT EXISTS unblock_expired_ips()
BEGIN
    DELETE FROM blocked_ips WHERE blocked_until < NOW();
    SELECT ROW_COUNT() as unblocked_ips;
END //

-- Procédure pour obtenir les statistiques de sécurité
CREATE PROCEDURE IF NOT EXISTS get_security_stats(IN hours INT)
BEGIN
    SELECT 
        event_type,
        COUNT(*) as count,
        COUNT(DISTINCT ip_address) as unique_ips
    FROM security_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL hours HOUR)
    GROUP BY event_type
    ORDER BY count DESC;
END //

DELIMITER ;

-- ========================================
-- ÉVÉNEMENT AUTOMATIQUE DE NETTOYAGE
-- ========================================

-- Activer l'event scheduler
SET GLOBAL event_scheduler = ON;

-- Créer un événement qui nettoie automatiquement tous les jours à 3h du matin
CREATE EVENT IF NOT EXISTS daily_security_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 3 HOUR)
DO
BEGIN
    -- Nettoyer les vieux logs
    CALL clean_old_security_logs();
    
    -- Nettoyer le rate limiting
    CALL clean_old_rate_limits();
    
    -- Débloquer les IPs expirées
    CALL unblock_expired_ips();
END;

-- ========================================
-- INDEXES SUPPLÉMENTAIRES POUR PERFORMANCE
-- ========================================

-- Index composite pour recherche par événement et date
CREATE INDEX IF NOT EXISTS idx_event_date ON security_logs(event_type, created_at);

-- Index pour recherche par IP et date
CREATE INDEX IF NOT EXISTS idx_ip_date ON security_logs(ip_address, created_at);

-- ========================================
-- INSERTION DE DONNÉES DE TEST (OPTIONNEL)
-- ========================================

-- Décommenter pour ajouter des données de test
/*
INSERT INTO security_logs (event_type, ip_address, user_agent, request_uri, details) VALUES
('LOGIN_SUCCESS', '192.168.1.100', 'Mozilla/5.0', '/php/login.php', '{"user_id": 1}'),
('LOGIN_FAILED', '192.168.1.101', 'Mozilla/5.0', '/php/login.php', '{"email": "test@test.com"}'),
('CSRF_ATTACK_DETECTED', '192.168.1.102', 'curl/7.68', '/php/login.php', '{"token": "invalid"}');
*/

-- ========================================
-- VÉRIFICATION
-- ========================================

-- Afficher les tables créées
SHOW TABLES LIKE '%security%' OR SHOW TABLES LIKE '%rate%' OR SHOW TABLES LIKE '%blocked%';

-- Afficher la structure des tables
-- DESC security_logs;
-- DESC rate_limit;
-- DESC blocked_ips;

SELECT 'Tables de sécurité créées avec succès!' as status;
