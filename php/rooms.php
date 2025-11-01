<?php
require_once 'config.php';
require_once 'header.php';

// Initialisation de la connexion PDO
$pdo = getDBConnection();

// Récupération des types de chambres avec leurs détails
function getRoomTypes($pdo, $filters = []) {
    $where = [];
    $params = [];
    
    // Construction de la requête de base
    $sql = "
        SELECT rt.*, 
            COUNT(r.id) as total_rooms,
            SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_rooms
        FROM room_types rt
        LEFT JOIN rooms r ON rt.id = r.room_type_id
    ";
    
    // Filtrer par type de chambre
    if (!empty($filters['room_type'])) {
        $where[] = "rt.type = ?";
        $params[] = $filters['room_type'];
    }
    
    // Filtrer par capacité
    if (!empty($filters['capacity'])) {
        $where[] = "rt.capacity >= ?";
        $params[] = $filters['capacity'];
    }
    
    // Ajouter les conditions WHERE si nécessaire
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Grouper par type de chambre
    $sql .= " GROUP BY rt.id";
    
    // Préparer et exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
$guests = isset($_GET['guests']) ? intval($_GET['guests']) : 0;
$adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
$children = isset($_GET['children']) ? intval($_GET['children']) : 0;
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';

// Si on a des paramètres de recherche, rediriger vers search_rooms.php pour une meilleure interface
if ($check_in || $check_out || $guests > 0 || $room_type) {
    $params = [];
    if ($check_in) $params[] = 'check_in=' . urlencode($check_in);
    if ($check_out) $params[] = 'check_out=' . urlencode($check_out);
    if ($guests > 0) $params[] = 'guests=' . $guests;
    if ($room_type) $params[] = 'room_type=' . urlencode($room_type);
    
    $redirect_url = 'search_rooms.php';
    if (!empty($params)) {
        $redirect_url .= '?' . implode('&', $params);
    }
    
    header('Location: ' . $redirect_url);
    exit;
}

// Récupération des types de chambres
$roomTypes = getRoomTypes($pdo);

// Calcul de la disponibilité si des dates sont fournies
if ($check_in && $check_out) {
    foreach ($roomTypes as &$roomType) {
        $roomType['available'] = checkAvailability($pdo, $roomType['id'], $check_in, $check_out);
    }
}
?>
    

    <!-- Formulaire de recherche -->
    <section class="search-rooms">
        <div class="search-container">
            <h2>Rechercher une chambre</h2>
            <form class="search-form" action="search_rooms.php" method="GET">
                <div class="form-group">
                    <label for="check-in"><i class="fas fa-calendar-alt"></i> Date d'arrivée</label>
                    <input type="date" id="check-in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="check-out"><i class="fas fa-calendar-alt"></i> Date de départ</label>
                    <input type="date" id="check-out" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="form-group">
                    <label for="guests"><i class="fas fa-users"></i> Personnes</label>
                    <input type="number" id="guests" name="guests" min="1" max="10" value="2" required>
                </div>
                <div class="form-group">
                    <label for="room_type"><i class="fas fa-bed"></i> Type de chambre</label>
                    <select id="room_type" name="room_type">
                        <option value="">Tous les types</option>
                        <option value="simple">Simple</option>
                        <option value="double">Double</option>
                        <option value="suite">Suite</option>
                        <option value="deluxe">Deluxe</option>
                    </select>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Rechercher</button>
            </form>
        </div>
    </section>

    <!-- Liste des chambres -->
    <section class="rooms-list">
        <div class="rooms-grid">
            <?php foreach ($roomTypes as $room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?php echo htmlspecialchars($room['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($room['name']); ?>"
                             loading="lazy">
                        <?php if (isset($room['available']) && $room['available'] > 0): ?>
                            <div class="room-badge">Disponible</div>
                        <?php endif; ?>
                    </div>
                    <div class="room-info">
                        <div class="room-header">
                            <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                            <div class="room-price">
                                <span class="price-amount"><?php echo formatPriceFCFA($room['price_per_night'], true); ?></span>
                                <span class="price-period">/ nuit</span>
                            </div>
                        </div>

                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                        
                        <div class="room-features">
                            <div class="feature-item">
                                <i class="fas fa-bed"></i>
                                <span><?php echo $room['capacity'] > 1 ? $room['capacity'].' lits' : '1 lit'; ?></span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-users"></i>
                                <span>Capacité <?php echo $room['capacity']; ?></span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-wifi"></i>
                                <span>WiFi Gratuit</span>
                            </div>
                            <?php if (strpos(strtolower($room['name']), 'suite') !== false): ?>
                            <div class="feature-item">
                                <i class="fas fa-coffee"></i>
                                <span>Mini-bar</span>
                            </div>
                            <?php endif; ?>
                            <?php if (strpos(strtolower($room['name']), 'présidentielle') !== false): ?>
                            <div class="feature-item">
                                <i class="fas fa-hot-tub"></i>
                                <span>Jacuzzi</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($room['available'])): ?>
                            <div class="availability <?php echo $room['available'] > 0 ? 'available' : 'unavailable'; ?>">
                                <i class="fas fa-<?php echo $room['available'] > 0 ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php if ($room['available'] > 0): ?>
                                    <?php echo $room['available'] > 1 ? $room['available'].' chambres disponibles' : 'Dernière chambre !'; ?>
                                <?php else: ?>
                                    Complet
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="room-actions">
                            <?php if (isset($_SESSION['user_id']) && (!isset($room['available']) || $room['available'] > 0)): ?>
                                <a href="booking.php?room_type=<?php echo $room['id']; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>" 
                                   class="btn-book">Réserver maintenant</a>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php?redirect=rooms" class="btn-book">Connectez-vous pour réserver</a>
                            <?php else: ?>
                                <button class="btn-book disabled" disabled>Indisponible</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>

    <script src="../js/validation.js"></script>
    <script src="../js/rooms.js"></script>
    </body>
    </html>