-- Création de la base de données
CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des types de chambres
CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL,
    image_url VARCHAR(255),
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des chambres
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    room_type_id INT,
    floor INT,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    last_cleaned TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    num_adults INT NOT NULL DEFAULT 1,
    num_children INT NOT NULL DEFAULT 0,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Table des avis
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    reservation_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
);

-- Table des services additionnels
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Table de liaison réservations-services
CREATE TABLE IF NOT EXISTS reservation_services (
    reservation_id INT,
    service_id INT,
    quantity INT NOT NULL DEFAULT 1,
    price_at_time DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    PRIMARY KEY (reservation_id, service_id)
);

-- Insertion des types de chambres
INSERT INTO room_types (name, description, price_per_night, capacity, image_url, amenities) VALUES
('Chambre Standard', 'Chambre confortable avec lit double et vue sur la ville', 100.00, 2, 'images/rooms/standard.jpg', 'Wi-Fi gratuit, TV LED, Minibar, Climatisation'),
('Suite Junior', 'Suite spacieuse avec salon séparé et balcon privé', 200.00, 2, 'images/rooms/junior-suite.jpg', 'Wi-Fi gratuit, TV LED, Minibar, Climatisation, Balcon, Salon séparé'),
('Suite Exécutive', 'Grande suite avec salon, salle à manger et vue panoramique', 350.00, 4, 'images/rooms/executive-suite.jpg', 'Wi-Fi gratuit, TV LED, Minibar, Climatisation, Salon, Salle à manger, Vue panoramique'),
('Suite Présidentielle', 'Notre meilleure suite avec service de majordome', 800.00, 4, 'images/rooms/presidential-suite.jpg', 'Wi-Fi gratuit, TV LED, Minibar, Climatisation, Salon, Salle à manger, Majordome privé, Jacuzzi');

-- Insertion des chambres
INSERT INTO rooms (room_number, room_type_id, floor) VALUES
('101', 1, 1), ('102', 1, 1), ('103', 1, 1),
('201', 2, 2), ('202', 2, 2),
('301', 3, 3), ('302', 3, 3),
('401', 4, 4);

-- Insertion des services additionnels
INSERT INTO services (name, description, price) VALUES
('Petit-déjeuner', 'Buffet petit-déjeuner continental', 25.00),
('Parking', 'Parking sécurisé', 15.00),
('Service en chambre', 'Service en chambre 24/7', 10.00),
('Spa', 'Accès au spa et aux installations bien-être', 50.00),
('Transport aéroport', 'Service de navette aéroport', 60.00);

-- Création d'un compte admin par défaut
-- Mot de passe : Admin123! (à changer après la première connexion)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@hotelres.com', '$2y$10$8K1p/95wJLMv.RzUB4FqPO5wp0QG/p1i0h.z3VHCmTbBGUNvwqEm2', 'admin');