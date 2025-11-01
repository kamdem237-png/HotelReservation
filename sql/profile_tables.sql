-- ========================================
-- TABLES PROFIL UTILISATEUR ENRICHI
-- ========================================

-- Ajouter colonnes au profil utilisateur
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL COMMENT 'Téléphone',
ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL COMMENT 'Adresse postale',
ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT 'Cameroun' COMMENT 'Pays',
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL COMMENT 'Photo de profil',
ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0 COMMENT 'Points de fidélité',
ADD COLUMN IF NOT EXISTS loyalty_level ENUM('Bronze', 'Silver', 'Gold', 'Platinum') DEFAULT 'Bronze' COMMENT 'Niveau de fidélité',
ADD COLUMN IF NOT EXISTS notification_email BOOLEAN DEFAULT TRUE COMMENT 'Recevoir emails',
ADD COLUMN IF NOT EXISTS notification_sms BOOLEAN DEFAULT FALSE COMMENT 'Recevoir SMS',
ADD COLUMN IF NOT EXISTS preferred_language ENUM('fr', 'en') DEFAULT 'fr' COMMENT 'Langue préférée';

-- Table des préférences utilisateur
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL COMMENT 'Clé de préférence',
    preference_value TEXT NOT NULL COMMENT 'Valeur de préférence',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_pref (user_id, preference_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Préférences utilisateur personnalisées';

-- Table historique des points de fidélité
CREATE TABLE IF NOT EXISTS loyalty_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL COMMENT 'Points gagnés ou dépensés (négatif)',
    reason VARCHAR(255) NOT NULL COMMENT 'Raison du changement',
    reservation_id INT DEFAULT NULL COMMENT 'ID réservation liée',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique des points de fidélité';

-- Vue des statistiques utilisateur
CREATE OR REPLACE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.loyalty_points,
    u.loyalty_level,
    COUNT(DISTINCT r.id) as total_reservations,
    COUNT(DISTINCT CASE WHEN r.status = 'confirmed' THEN r.id END) as confirmed_reservations,
    COUNT(DISTINCT CASE WHEN r.status = 'cancelled' THEN r.id END) as cancelled_reservations,
    SUM(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE 0 END) as total_spent,
    MAX(r.check_in_date) as last_reservation_date,
    DATEDIFF(CURRENT_DATE, u.created_at) as days_member
FROM users u
LEFT JOIN reservations r ON u.id = r.user_id
GROUP BY u.id;

-- Procédure pour calculer les points de fidélité
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS calculate_loyalty_points(IN p_user_id INT, IN p_amount DECIMAL(10,2), IN p_reservation_id INT)
BEGIN
    DECLARE points INT;
    DECLARE current_points INT;
    DECLARE new_level VARCHAR(20);
    
    -- Calculer les points (1 point par 1000 FCFA dépensés)
    SET points = FLOOR(p_amount / 1000);
    
    -- Ajouter les points
    UPDATE users SET loyalty_points = loyalty_points + points WHERE id = p_user_id;
    
    -- Enregistrer l'historique
    INSERT INTO loyalty_history (user_id, points, reason, reservation_id)
    VALUES (p_user_id, points, CONCAT('Réservation confirmée : +', points, ' points'), p_reservation_id);
    
    -- Récupérer le nouveau total de points
    SELECT loyalty_points INTO current_points FROM users WHERE id = p_user_id;
    
    -- Calculer le nouveau niveau
    IF current_points >= 10000 THEN
        SET new_level = 'Platinum';
    ELSEIF current_points >= 5000 THEN
        SET new_level = 'Gold';
    ELSEIF current_points >= 2000 THEN
        SET new_level = 'Silver';
    ELSE
        SET new_level = 'Bronze';
    END IF;
    
    -- Mettre à jour le niveau
    UPDATE users SET loyalty_level = new_level WHERE id = p_user_id;
END //

-- Procédure pour dépenser des points
CREATE PROCEDURE IF NOT EXISTS spend_loyalty_points(IN p_user_id INT, IN p_points INT, IN p_reason VARCHAR(255))
BEGIN
    DECLARE current_points INT;
    
    -- Vérifier si l'utilisateur a assez de points
    SELECT loyalty_points INTO current_points FROM users WHERE id = p_user_id;
    
    IF current_points >= p_points THEN
        -- Déduire les points
        UPDATE users SET loyalty_points = loyalty_points - p_points WHERE id = p_user_id;
        
        -- Enregistrer l'historique
        INSERT INTO loyalty_history (user_id, points, reason)
        VALUES (p_user_id, -p_points, p_reason);
        
        SELECT 'SUCCESS' as status, (current_points - p_points) as new_balance;
    ELSE
        SELECT 'INSUFFICIENT_POINTS' as status, current_points as current_balance;
    END IF;
END //

DELIMITER ;

-- Index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_loyalty_level ON users(loyalty_level);
CREATE INDEX IF NOT EXISTS idx_loyalty_points ON users(loyalty_points);

-- Données de test (optionnel)
-- UPDATE users SET loyalty_points = 2500, loyalty_level = 'Silver' WHERE id = 1;

SELECT 'Tables de profil enrichi créées avec succès!' as status;
