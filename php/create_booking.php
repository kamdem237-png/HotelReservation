<?php
require_once 'config.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$error = '';

// Récupérer les paramètres
$room_type_id = (int)($_GET['room_type_id'] ?? 0);
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests = (int)($_GET['guests'] ?? 1);

// Validation
if ($room_type_id <= 0 || empty($check_in) || empty($check_out)) {
    header('Location: search_rooms.php');
    exit;
}

// Vérifier que check_out est après check_in
if (strtotime($check_out) <= strtotime($check_in)) {
    $_SESSION['error'] = "La date de départ doit être après la date d'arrivée.";
    header('Location: search_rooms.php');
    exit;
}

// Récupérer les infos du type de chambre
$stmt = $pdo->prepare("SELECT * FROM room_types WHERE id = ?");
$stmt->execute([$room_type_id]);
$room_type = $stmt->fetch();

if (!$room_type) {
    $_SESSION['error'] = "Type de chambre introuvable.";
    header('Location: search_rooms.php');
    exit;
}

// Chercher une chambre disponible pour ces dates
$stmt = $pdo->prepare("
    SELECT r.id
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
    LIMIT 1
");
$stmt->execute([
    $room_type_id,
    $check_in, $check_in,
    $check_out, $check_out,
    $check_in, $check_out
]);
$available_room = $stmt->fetch();

if (!$available_room) {
    $_SESSION['error'] = "Aucune chambre disponible pour ces dates.";
    header('Location: search_rooms.php');
    exit;
}

// Calculer le prix total
$check_in_date = new DateTime($check_in);
$check_out_date = new DateTime($check_out);
$nights = $check_in_date->diff($check_out_date)->days;
$total_price = $room_type['price_per_night'] * $nights;

// Créer la réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token de sécurité invalide.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, room_id, check_in_date, check_out_date, total_price, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $available_room['id'],
                $check_in,
                $check_out,
                $total_price
            ]);
            
            $reservation_id = $pdo->lastInsertId();
            
            // Calculer les points de fidélité (1 point par 1000 FCFA)
            $points = floor($total_price / 1000);
            if ($points > 0) {
                $pdo->prepare("CALL calculate_loyalty_points(?, ?, ?)")
                    ->execute([$_SESSION['user_id'], $total_price, $reservation_id]);
            }
            
            // Log de sécurité
            Security::logSecurityEvent('RESERVATION_CREATED', $_SERVER['REMOTE_ADDR'], [
                'reservation_id' => $reservation_id,
                'user_id' => $_SESSION['user_id'],
                'room_id' => $available_room['id'],
                'total_price' => $total_price
            ]);
            
            // Nettoyer l'intention de réservation
            unset($_SESSION['booking_intent']);
            
            // Rediriger vers la page de confirmation
            $_SESSION['success_message'] = "Réservation créée avec succès! Numéro de réservation: #$reservation_id";
            header("Location: reservations.php");
            exit;
            
        } catch (PDOException $e) {
            $error = "Erreur lors de la création de la réservation: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la réservation</title>
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .booking-header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 2rem;
        }
        .booking-body {
            padding: 2rem;
        }
        .booking-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: #0066cc;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-item strong {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .info-item span {
            font-size: 1.1rem;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-container">
            <div class="booking-header">
                <h1><i class="fas fa-calendar-check"></i> Confirmer votre réservation</h1>
                <p>Vérifiez les détails avant de confirmer</p>
            </div>
            
            <div class="booking-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <h2><?php echo htmlspecialchars($room_type['name']); ?></h2>
                <p style="color: #666;"><?php echo htmlspecialchars($room_type['description']); ?></p>

                <div class="info-grid">
                    <div class="info-item">
                        <strong><i class="fas fa-calendar"></i> Arrivée</strong>
                        <span><?php echo date('d/m/Y', strtotime($check_in)); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-calendar"></i> Départ</strong>
                        <span><?php echo date('d/m/Y', strtotime($check_out)); ?></span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-moon"></i> Nombre de nuits</strong>
                        <span><?php echo $nights; ?> nuit(s)</span>
                    </div>
                    <div class="info-item">
                        <strong><i class="fas fa-users"></i> Personnes</strong>
                        <span><?php echo $guests; ?> personne(s)</span>
                    </div>
                </div>

                <div class="booking-summary">
                    <h3 style="margin-top: 0;">Résumé de la réservation</h3>
                    <div class="summary-row">
                        <span>Prix par nuit</span>
                        <span><?php echo formatPriceFCFA($room_type['price_per_night'], true); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Nombre de nuits</span>
                        <span>×<?php echo $nights; ?></span>
                    </div>
                    <div class="summary-row">
                        <span><i class="fas fa-trophy"></i> Points de fidélité à gagner</span>
                        <span>+<?php echo floor($total_price / 1000); ?> points</span>
                    </div>
                    <div class="summary-row">
                        <span>Total à payer</span>
                        <span><?php echo formatPriceFCFA($total_price, true); ?></span>
                    </div>
                </div>

                <form method="POST">
                    <?php echo Security::csrfField(); ?>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            <i class="fas fa-check"></i> Confirmer la réservation
                        </button>
                        <a href="search_rooms.php" class="btn-secondary" style="flex: 1; text-align: center; padding: 0.75rem;">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>

                <p style="margin-top: 2rem; padding: 1rem; background: #fff3cd; border-radius: 8px; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> Votre réservation sera en statut "En attente" jusqu'à confirmation par l'hôtel.
                </p>
            </div>
        </div>
    </div>

    <script src="../js/modal.js"></script>
    <script src="../js/nav.js"></script>
</body>
</html>
