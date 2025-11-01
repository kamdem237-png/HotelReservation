-- ========================================
-- TABLES POUR GALERIE D'IMAGES DES CHAMBRES
-- ========================================

-- Table des images de chambres
CREATE TABLE IF NOT EXISTS room_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL COMMENT 'ID de la chambre',
    image_path VARCHAR(255) NOT NULL COMMENT 'Chemin de l''image',
    is_primary BOOLEAN DEFAULT FALSE COMMENT 'Image principale',
    display_order INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    caption VARCHAR(255) DEFAULT NULL COMMENT 'Légende de l''image',
    uploaded_by INT DEFAULT NULL COMMENT 'ID de l''administrateur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_room_id (room_id),
    INDEX idx_primary (room_id, is_primary),
    INDEX idx_order (room_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Images des chambres avec galerie';

-- Trigger pour s'assurer qu'une seule image principale par chambre
DELIMITER //

CREATE TRIGGER IF NOT EXISTS before_room_image_primary_insert
BEFORE INSERT ON room_images
FOR EACH ROW
BEGIN
    IF NEW.is_primary = TRUE THEN
        UPDATE room_images SET is_primary = FALSE WHERE room_id = NEW.room_id;
    END IF;
    
    -- Si c'est la première image, la définir comme principale
    IF (SELECT COUNT(*) FROM room_images WHERE room_id = NEW.room_id) = 0 THEN
        SET NEW.is_primary = TRUE;
    END IF;
END //

CREATE TRIGGER IF NOT EXISTS before_room_image_primary_update
BEFORE UPDATE ON room_images
FOR EACH ROW
BEGIN
    IF NEW.is_primary = TRUE AND OLD.is_primary = FALSE THEN
        UPDATE room_images SET is_primary = FALSE WHERE room_id = NEW.room_id AND id != NEW.id;
    END IF;
END //

DELIMITER ;

-- Vue pour obtenir l'image principale de chaque chambre
CREATE OR REPLACE VIEW room_primary_images AS
SELECT 
    r.id as room_id,
    ri.id as image_id,
    ri.image_path,
    ri.caption
FROM rooms r
LEFT JOIN room_images ri ON r.id = ri.room_id AND ri.is_primary = TRUE;

-- Vue pour le compte d'images par chambre
CREATE OR REPLACE VIEW room_image_counts AS
SELECT 
    room_id,
    COUNT(*) as total_images,
    COUNT(CASE WHEN is_primary = TRUE THEN 1 END) as has_primary
FROM room_images
GROUP BY room_id;

-- Procédure pour réorganiser l'ordre des images
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS reorder_room_images(IN p_room_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE img_id INT;
    DECLARE new_order INT DEFAULT 0;
    DECLARE cur CURSOR FOR SELECT id FROM room_images WHERE room_id = p_room_id ORDER BY display_order, id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO img_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE room_images SET display_order = new_order WHERE id = img_id;
        SET new_order = new_order + 1;
    END LOOP;
    
    CLOSE cur;
END //

-- Procédure pour définir une image comme principale
CREATE PROCEDURE IF NOT EXISTS set_primary_image(IN p_image_id INT)
BEGIN
    DECLARE img_room_id INT;
    
    -- Récupérer le room_id de l'image
    SELECT room_id INTO img_room_id FROM room_images WHERE id = p_image_id;
    
    -- Désactiver toutes les images principales pour cette chambre
    UPDATE room_images SET is_primary = FALSE WHERE room_id = img_room_id;
    
    -- Activer l'image sélectionnée comme principale
    UPDATE room_images SET is_primary = TRUE WHERE id = p_image_id;
END //

DELIMITER ;

-- Index supplémentaires pour optimisation
CREATE INDEX IF NOT EXISTS idx_uploaded_by ON room_images(uploaded_by);
CREATE INDEX IF NOT EXISTS idx_created_at ON room_images(created_at);

SELECT 'Tables de galerie d''images créées avec succès!' as status;
