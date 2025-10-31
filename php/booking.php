<?php
require_once 'config.php';

// Initialisation de la connexion PDO
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si non authentifié
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Récupérer les paramètres (GET ou POST)
$room_type_id = isset($_REQUEST['room_type']) ? intval($_REQUEST['room_type']) : 0;
$check_in = isset($_REQUEST['check_in']) ? sanitize($_REQUEST['check_in']) : '';
$check_out = isset($_REQUEST['check_out']) ? sanitize($_REQUEST['check_out']) : '';
$adults = isset($_REQUEST['adults']) ? intval($_REQUEST['adults']) : 1;
$children = isset($_REQUEST['children']) ? intval($_REQUEST['children']) : 0;

// Récupérer les informations du type de chambre
$roomType = null;
if ($room_type_id) {
    $stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ? LIMIT 1");
    $stmt->execute([$room_type_id]);
    $roomType = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$roomType) {
        $error = "Type de chambre introuvable.";
    }
}

// Fonction utilitaire : trouver une chambre disponible pour l'intervalle donné
function findAvailableRoom($pdo, $room_type_id, $check_in, $check_out) {
    $stmt = $pdo->prepare("SELECT r.id FROM rooms r
        WHERE r.room_type_id = ?
        AND r.status = 'available'
        AND r.id NOT IN (
            SELECT room_id FROM reservations
            WHERE status != 'cancelled'
            AND (check_in_date <= ? AND check_out_date >= ?)
        )
        LIMIT 1");
    $stmt->execute([$room_type_id, $check_out, $check_in]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id'] : false;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_type_id = intval($_POST['room_type']);
    $check_in = sanitize($_POST['check_in']);
    $check_out = sanitize($_POST['check_out']);
    $adults = intval($_POST['adults']);
    $children = intval($_POST['children']);
    $special_requests = isset($_POST['special_requests']) ? sanitize($_POST['special_requests']) : '';

    // Validation minimale
    if (empty($check_in) || empty($check_out) || !$room_type_id) {
        $error = "Veuillez fournir les dates et le type de chambre.";
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = "La date de départ doit être après la date d'arrivée.";
    } else {
        // Vérifier qu'il existe un type de chambre
        $stmt = $pdo->prepare("SELECT price_per_night FROM room_types WHERE id = ? LIMIT 1");
        $stmt->execute([$room_type_id]);
        $type = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$type) {
            $error = "Type de chambre invalide.";
        } else {
            // Chercher une chambre disponible
            $availableRoomId = findAvailableRoom($pdo, $room_type_id, $check_in, $check_out);
            if (!$availableRoomId) {
                $error = "Désolé, aucune chambre disponible pour ces dates.";
            } else {
                // Calculer le prix total
                $nights = (strtotime($check_out) - strtotime($check_in)) / 86400;
                if ($nights < 1) $nights = 1;
                $total_price = $type['price_per_night'] * $nights;

                // Insérer la réservation
                $stmt = $pdo->prepare("INSERT INTO reservations (user_id, room_id, check_in_date, check_out_date, total_price, num_adults, num_children, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
                try {
                    $stmt->execute([$_SESSION['user_id'], $availableRoomId, $check_in, $check_out, $total_price, $adults, $children, $special_requests]);
                    $success = "Réservation effectuée avec succès !";
                    // Rediriger vers la page des réservations après 2s
                    header("refresh:2;url=reservations.php");
                } catch (PDOException $e) {
                    $error = "Erreur lors de la création de la réservation.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header style="padding:0 1rem;">
        <nav class="navbar">
            <div class="logo"><h1>HotelRes</h1></div>
            <ul class="nav-links">
                <li><a href="../index.html">Accueil</a></li>
                <li><a href="rooms.php">Chambres</a></li>
                <li><a href="reservations.php" class="active">Mes réservations</a></li>
            </ul>
        </nav>
    </header>

    <main style="padding:2rem;">
        <div style="max-width:700px;margin:2rem auto;">
            <h2>Réserver : <?php echo $roomType ? htmlspecialchars($roomType['name']) : 'Chambre'; ?></h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="reservation-form">
                <input type="hidden" name="room_type" value="<?php echo intval($room_type_id); ?>">

                <div class="form-group">
                    <label for="check_in">Date d'arrivée</label>
                    <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" required>
                </div>

                <div class="form-group">
                    <label for="check_out">Date de départ</label>
                    <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>" required>
                </div>

                <div class="form-group">
                    <label for="adults">Adultes</label>
                    <input type="number" id="adults" name="adults" min="1" value="<?php echo $adults; ?>">
                </div>

                <div class="form-group">
                    <label for="children">Enfants</label>
                    <input type="number" id="children" name="children" min="0" value="<?php echo $children; ?>">
                </div>

                <div class="form-group">
                    <label for="special_requests">Demandes spéciales (optionnel)</label>
                    <textarea id="special_requests" name="special_requests" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-reserve">Confirmer la réservation</button>
            </form>
        </div>
    </main>

</body>
</html>
