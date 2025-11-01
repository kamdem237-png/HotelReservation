<?php
require_once 'config.php';
require_once 'header.php';

$pdo = getDBConnection();

// Récupérer les filtres de recherche
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

// Gérer les deux formats : guests OU adults+children
if (isset($_GET['guests'])) {
    $guests = (int)$_GET['guests'];
} elseif (isset($_GET['adults']) || isset($_GET['children'])) {
    $adults = (int)($_GET['adults'] ?? 1);
    $children = (int)($_GET['children'] ?? 0);
    $guests = $adults + $children;
} else {
    $guests = 1;
}

$room_type = $_GET['room_type'] ?? '';
$max_price = (int)($_GET['max_price'] ?? 0);

// Construire la requête avec filtres
$where_conditions = [];
$params = [];

$sql = "
    SELECT 
        rt.*,
        ri.image_path as main_image,
        COUNT(DISTINCT r.id) as total_rooms,
        COUNT(DISTINCT CASE WHEN r.status = 'available' THEN r.id END) as available_rooms
    FROM room_types rt
    LEFT JOIN rooms r ON rt.id = r.room_type_id
    LEFT JOIN room_images ri ON rt.id = (SELECT room_id FROM rooms WHERE room_type_id = rt.id LIMIT 1) AND ri.is_primary = 1
";

// Filtre par capacité
if ($guests > 0) {
    $where_conditions[] = "rt.capacity >= ?";
    $params[] = $guests;
}

// Filtre par type
if (!empty($room_type)) {
    $where_conditions[] = "rt.type = ?";
    $params[] = $room_type;
}

// Filtre par prix maximum
if ($max_price > 0) {
    $where_conditions[] = "rt.price_per_night <= ?";
    $params[] = $max_price;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " GROUP BY rt.id ORDER BY rt.price_per_night ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Si des dates sont fournies, vérifier la disponibilité
if (!empty($check_in) && !empty($check_out)) {
    foreach ($rooms as &$room) {
        // Vérifier si des chambres sont disponibles pour ces dates
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT r.id) as available
            FROM rooms r
            WHERE r.room_type_id = ?
            AND r.status = 'available'
            AND r.id NOT IN (
                SELECT room_id FROM reservations
                WHERE status IN ('confirmed', 'pending')
                AND (
                    (check_in_date <= ? AND check_out_date > ?)
                    OR (check_in_date < ? AND check_out_date >= ?)
                    OR (check_in_date >= ? AND check_out_date <= ?)
                )
            )
        ");
        $stmt->execute([
            $room['id'],
            $check_in, $check_in,
            $check_out, $check_out,
            $check_in, $check_out
        ]);
        $room['available_for_dates'] = $stmt->fetch()['available'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche de chambres - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f8ff !important;
        }
        .search-header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .search-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: -2rem auto 0;
            position: relative;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            align-items: end;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .room-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .room-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .room-card-body {
            padding: 1.5rem;
        }
        .room-title {
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
            color: #333;
        }
        .room-price {
            font-size: 1.8rem;
            color: #0066cc;
            font-weight: bold;
            margin: 1rem 0;
        }
        .room-features {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        .feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        .availability-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            margin: 1rem 0;
        }
        .available {
            background: #d4edda;
            color: #155724;
        }
        .unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        .filters-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="search-header">
        <div class="container">
            <h1><i class="fas fa-search"></i> Recherche de chambres</h1>
            <p>Trouvez la chambre parfaite pour votre séjour</p>
        </div>
    </div>

    <div class="container">
        <!-- Formulaire de recherche -->
        <div class="search-form">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="check_in"><i class="fas fa-calendar"></i> Arrivée</label>
                        <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="check_out"><i class="fas fa-calendar"></i> Départ</label>
                        <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="guests"><i class="fas fa-users"></i> Personnes</label>
                        <select id="guests" name="guests">
                            <option value="1" <?php echo $guests == 1 ? 'selected' : ''; ?>>1 personne</option>
                            <option value="2" <?php echo $guests == 2 ? 'selected' : ''; ?>>2 personnes</option>
                            <option value="3" <?php echo $guests == 3 ? 'selected' : ''; ?>>3 personnes</option>
                            <option value="4" <?php echo $guests == 4 ? 'selected' : ''; ?>>4 personnes</option>
                            <option value="5" <?php echo $guests == 5 ? 'selected' : ''; ?>>5+ personnes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_type"><i class="fas fa-bed"></i> Type</label>
                        <select id="room_type" name="room_type">
                            <option value="">Tous les types</option>
                            <option value="simple" <?php echo $room_type == 'simple' ? 'selected' : ''; ?>>Simple</option>
                            <option value="double" <?php echo $room_type == 'double' ? 'selected' : ''; ?>>Double</option>
                            <option value="suite" <?php echo $room_type == 'suite' ? 'selected' : ''; ?>>Suite</option>
                            <option value="deluxe" <?php echo $room_type == 'deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="opacity: 0;">Action</label>
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Résumé des filtres -->
        <?php if (!empty($check_in) || !empty($check_out) || $guests > 1): ?>
        <div class="filters-summary">
            <strong><i class="fas fa-filter"></i> Filtres actifs :</strong>
            <?php if (!empty($check_in)): ?>
                <span>Du <?php echo date('d/m/Y', strtotime($check_in)); ?></span>
            <?php endif; ?>
            <?php if (!empty($check_out)): ?>
                <span>au <?php echo date('d/m/Y', strtotime($check_out)); ?></span>
            <?php endif; ?>
            <?php if ($guests > 1): ?>
                <span>• <?php echo $guests; ?> personne(s)</span>
            <?php endif; ?>
            <?php if (!empty($room_type)): ?>
                <span>• Type: <?php echo ucfirst($room_type); ?></span>
            <?php endif; ?>
            <a href="search_rooms.php" style="margin-left: 1rem; color: #0066cc;">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
        </div>
        <?php endif; ?>

        <!-- Résultats -->
        <h2><?php echo count($rooms); ?> chambre(s) trouvée(s)</h2>
        
        <?php if (empty($rooms)): ?>
            <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 10px;">
                <i class="fas fa-bed" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                <p style="color: #666; font-size: 1.2rem;">Aucune chambre ne correspond à vos critères</p>
                <a href="search_rooms.php" class="btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-redo"></i> Nouvelle recherche
                </a>
            </div>
        <?php else: ?>
            <div class="results-grid">
                <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <img src="../<?php echo $room['main_image'] ?: 'images/default-room.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($room['name']); ?>">
                    <div class="room-card-body">
                        <h3 class="room-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                        <p style="color: #666;"><?php echo htmlspecialchars($room['description']); ?></p>
                        
                        <div class="room-features">
                            <div class="feature">
                                <i class="fas fa-users"></i>
                                <span><?php echo $room['capacity']; ?> personne(s)</span>
                            </div>
                            <div class="feature">
                                <i class="fas fa-bed"></i>
                                <span><?php echo ucfirst($room['type']); ?></span>
                            </div>
                            <?php if ($room['available_rooms'] > 0): ?>
                            <div class="feature">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                <span><?php echo $room['available_rooms']; ?> disponible(s)</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="room-price">
                            <?php echo formatPriceFCFA($room['price_per_night'], true); ?>
                            <small style="font-size: 0.5em; color: #666;">/nuit</small>
                        </div>

                        <?php 
                        $is_available = true;
                        if (isset($room['available_for_dates'])) {
                            $is_available = $room['available_for_dates'] > 0;
                        } elseif ($room['available_rooms'] == 0) {
                            $is_available = false;
                        }
                        ?>

                        <?php if ($is_available): ?>
                            <button class="btn-primary" style="width: 100%;" 
                                    onclick="reserveRoom(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['name']); ?>', <?php echo $room['price_per_night']; ?>)">
                                <i class="fas fa-calendar-check"></i> Réserver
                            </button>
                        <?php else: ?>
                            <button class="btn-secondary" style="width: 100%; opacity: 0.6;" disabled>
                                <i class="fas fa-times-circle"></i> Indisponible
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once 'footer.php'; ?>

    <script src="../js/modal.js"></script>
    <script src="../js/nav.js"></script>
    <script>
        const checkIn = '<?php echo $check_in; ?>';
        const checkOut = '<?php echo $check_out; ?>';
        const guests = <?php echo $guests; ?>;

        function reserveRoom(roomTypeId, roomName, price) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                // Utilisateur non connecté - Modal de connexion
                Modal.confirm(
                    'Connexion requise',
                    'Vous devez être connecté pour effectuer une réservation.<br><br>Souhaitez-vous vous connecter maintenant?',
                    () => {
                        // Sauvegarder les infos de réservation en session
                        fetch('save_booking_intent.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                room_type_id: roomTypeId,
                                room_name: roomName,
                                price: price,
                                check_in: checkIn,
                                check_out: checkOut,
                                guests: guests
                            })
                        }).then(() => {
                            // Rediriger vers login
                            window.location.href = 'login.php?redirect=booking';
                        });
                    }
                );
            <?php else: ?>
                // Utilisateur connecté - Aller directement à la réservation
                if (!checkIn || !checkOut) {
                    Modal.error('Dates manquantes', 'Veuillez sélectionner des dates d\'arrivée et de départ.');
                    return;
                }
                window.location.href = `create_booking.php?room_type_id=${roomTypeId}&check_in=${checkIn}&check_out=${checkOut}&guests=${guests}`;
            <?php endif; ?>
        }

        // Validation des dates
        document.getElementById('check_in')?.addEventListener('change', function() {
            const checkOutInput = document.getElementById('check_out');
            const minCheckOut = new Date(this.value);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            checkOutInput.min = minCheckOut.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
