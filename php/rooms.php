<?php
session_start();
require_once 'config.php';

// Récupération des types de chambres avec leurs détails
function getRoomTypes($pdo) {
    $stmt = $pdo->query("
        SELECT rt.*, 
            COUNT(r.id) as total_rooms,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
        GROUP BY rt.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Vérification de la disponibilité
function checkAvailability($pdo, $room_type_id, $check_in, $check_out) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as available_rooms
        FROM rooms r
        WHERE r.room_type_id = ?
        AND r.status = 'available'
        AND r.id NOT IN (
            SELECT room_id
            FROM reservations
            WHERE (check_in_date <= ? AND check_out_date >= ?)
            AND status != 'cancelled'
        )
    ");
    $stmt->execute([$room_type_id, $check_out, $check_in]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['available_rooms'];
}

// Récupération des dates de recherche depuis l'URL
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;

// Récupération des types de chambres
$roomTypes = getRoomTypes($pdo);

// Calcul de la disponibilité si des dates sont fournies
if ($check_in && $check_out) {
    foreach ($roomTypes as &$roomType) {
        $roomType['available'] = checkAvailability($pdo, $roomType['id'], $check_in, $check_out);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Chambres - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="rooms.php" class="active">Chambres</a></li>
                <li><a href="reservations.php">Réservations</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <li class="auth-links">
                        <a href="login.php" class="btn-login">Connexion</a>
                        <a href="register.php" class="btn-register">Inscription</a>
                    </li>
                <?php else: ?>
                    <li><a href="profile.php">Mon Profil</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Formulaire de recherche -->
    <section class="search-rooms">
        <div class="search-container">
            <h2>Rechercher une chambre</h2>
            <form class="search-form" action="" method="GET">
                <div class="form-group">
                    <label for="check-in">Date d'arrivée</label>
                    <input type="date" id="check-in" name="check_in" value="<?php echo $check_in; ?>" required>
                </div>
                <div class="form-group">
                    <label for="check-out">Date de départ</label>
                    <input type="date" id="check-out" name="check_out" value="<?php echo $check_out; ?>" required>
                </div>
                <div class="form-group">
                    <label for="adults">Adultes</label>
                    <input type="number" id="adults" name="adults" min="1" value="<?php echo $adults; ?>" required>
                </div>
                <div class="form-group">
                    <label for="children">Enfants</label>
                    <input type="number" id="children" name="children" min="0" value="<?php echo $children; ?>">
                </div>
                <button type="submit" class="btn-search">Rechercher</button>
            </form>
        </div>
    </section>

    <!-- Liste des chambres -->
    <section class="rooms-list">
        <div class="rooms-grid">
            <?php foreach ($roomTypes as $room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                    </div>
                    <div class="room-info">
                        <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                        <div class="room-features">
                            <span><i class="fas fa-user"></i> <?php echo $room['capacity']; ?> personnes max</span>
                            <span><i class="fas fa-euro-sign"></i> <?php echo number_format($room['price_per_night'], 2); ?> /nuit</span>
                            <?php if (isset($room['available'])): ?>
                                <span class="availability <?php echo $room['available'] > 0 ? 'available' : 'unavailable'; ?>">
                                    <i class="fas fa-<?php echo $room['available'] > 0 ? 'check' : 'times'; ?>"></i>
                                    <?php echo $room['available'] > 0 ? $room['available'] . ' chambres disponibles' : 'Complet'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && (!isset($room['available']) || $room['available'] > 0)): ?>
                            <a href="booking.php?room_type=<?php echo $room['id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" 
                               class="btn-book">Réserver</a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn-book">Connectez-vous pour réserver</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact</h3>
                <p>Email: contact@hotel.com</p>
                <p>Téléphone: +33 1 23 45 67 89</p>
                <p>Adresse: 123 Rue de l'Hôtel, 75000 Paris</p>
            </div>
            <div class="footer-section">
                <h3>Liens rapides</h3>
                <ul>
                    <li><a href="../index.html">Accueil</a></li>
                    <li><a href="rooms.php">Chambres</a></li>
                    <li><a href="about.php">À propos</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 HotelRes. Tous droits réservés.</p>
        </div>
    </footer>

    <script src="../js/validation.js"></script>
    <script src="../js/rooms.js"></script>
</body>
</html>