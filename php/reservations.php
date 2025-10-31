<?php
require_once 'config.php';
require_once 'header.php';

// Initialisation de la connexion PDO
$pdo = getDBConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';

// Annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['reservation_id'])) {
    $reservationId = intval($_POST['reservation_id']);

    // Vérifier que la réservation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$reservationId, $userId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $message = 'Réservation introuvable.';
    } else {
        // Autoriser l'annulation uniquement si la date d'arrivée n'est pas encore passée
        if (strtotime($reservation['check_in_date']) <= strtotime(date('Y-m-d'))) {
            $message = 'Impossible d\'annuler une réservation commencée ou passée.';
        } else {
            $upd = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $upd->execute([$reservationId]);
            $message = 'Réservation annulée.';
        }
    }
}

// Récupérer les réservations de l'utilisateur
$stmt = $pdo->prepare("SELECT r.*, rm.room_number, rt.name as room_type_name
    FROM reservations r
    LEFT JOIN rooms rm ON rm.id = r.room_id
    LEFT JOIN room_types rt ON rt.id = rm.room_type_id
    WHERE r.user_id = ?
    ORDER BY r.check_in_date DESC");
$stmt->execute([$userId]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - HotelRes</title>
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
        <div style="max-width:1000px;margin:0 auto;">
            <h2>Mes réservations</h2>
            <?php if ($message): ?>
                <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (empty($reservations)): ?>
                <p>Aucune réservation trouvée.</p>
            <?php else: ?>
                <?php foreach ($reservations as $res): ?>
                    <div class="room-card" style="margin-bottom:1rem;padding:1rem;">
                        <div class="room-info">
                            <h3><?php echo htmlspecialchars($res['room_type_name'] ?: 'Chambre'); ?> — <?php echo htmlspecialchars($res['room_number'] ?: 'N/A'); ?></h3>
                            <p>Du <strong><?php echo htmlspecialchars($res['check_in_date']); ?></strong> au <strong><?php echo htmlspecialchars($res['check_out_date']); ?></strong></p>
                            <p>Prix total : <strong><?php echo number_format($res['total_price'], 2); ?> €</strong></p>
                            <p>Statut : <span class="availability <?php echo $res['status'] === 'cancelled' ? 'unavailable' : 'available'; ?>"><?php echo htmlspecialchars($res['status']); ?></span></p>

                            <?php if ($res['status'] !== 'cancelled' && strtotime($res['check_in_date']) > strtotime(date('Y-m-d'))): ?>
                                <form method="POST" action="" style="margin-top:0.5rem;">
                                    <input type="hidden" name="reservation_id" value="<?php echo intval($res['id']); ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn-primary">Annuler</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
