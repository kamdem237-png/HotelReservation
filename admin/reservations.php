<?php
require_once '../php/config.php';
Security::requireAdmin();

$message = '';
$error = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Jeton de sécurité invalide.";
    } else {
        $action = $_POST['action'];
        $reservation_id = (int)$_POST['reservation_id'];
        
        if ($action === 'confirm') {
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $message = "Réservation confirmée!";
        } elseif ($action === 'cancel') {
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $message = "Réservation annulée!";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            $stmt->execute([$reservation_id]);
            $message = "Réservation supprimée!";
        }
    }
}

// Filtres
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? 'all';

// Construction de la requête
$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[] = "r.status = ?";
    $params[] = $filter_status;
}

if ($filter_date === 'today') {
    $where[] = "CURRENT_DATE() BETWEEN r.check_in_date AND r.check_out_date";
} elseif ($filter_date === 'upcoming') {
    $where[] = "r.check_in_date > CURRENT_DATE()";
} elseif ($filter_date === 'past') {
    $where[] = "r.check_out_date < CURRENT_DATE()";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Récupérer les réservations
$reservations = $pdo->prepare("
    SELECT 
        r.*,
        u.username,
        u.email,
        rm.room_number,
        rt.name as room_type
    FROM reservations r
    LEFT JOIN users u ON u.id = r.user_id
    LEFT JOIN rooms rm ON rm.id = r.room_id
    LEFT JOIN room_types rt ON rt.id = rm.room_type_id
    $where_clause
    ORDER BY r.check_in_date DESC
");
$reservations->execute($params);
$reservations = $reservations->fetchAll();

// Stats
$stats_today = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE CURRENT_DATE() BETWEEN check_in_date AND check_out_date AND status = 'confirmed'")->fetch()['count'];
$stats_pending = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")->fetch()['count'];
$stats_upcoming = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE check_in_date > CURRENT_DATE() AND status = 'confirmed'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-hotel"></i> HotelRes Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="rooms.php" class="nav-item">
                <i class="fas fa-bed"></i> Chambres
            </a>
            <a href="reservations.php" class="nav-item active">
                <i class="fas fa-calendar-check"></i> Réservations
            </a>
            <a href="clients.php" class="nav-item">
                <i class="fas fa-users"></i> Clients
            </a>
            <a href="security_dashboard.php" class="nav-item">
                <i class="fas fa-shield-alt"></i> Sécurité
            </a>
            <a href="../php/logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-calendar-check"></i> Gestion des Réservations</h1>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3>Aujourd'hui</h3>
                    <p class="stat-number"><?php echo $stats_today; ?></p>
                    <span class="stat-label">En cours</span>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>En attente</h3>
                    <p class="stat-number"><?php echo $stats_pending; ?></p>
                    <span class="stat-label">À confirmer</span>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3>À venir</h3>
                    <p class="stat-number"><?php echo $stats_upcoming; ?></p>
                    <span class="stat-label">Confirmées</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="content-card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filtres</h2>
            </div>
            <div style="padding: 1.5rem;">
                <form method="GET" class="form-grid" style="grid-template-columns: 1fr 1fr auto;">
                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Tous</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmées</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Annulées</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date">Période</label>
                        <select id="date" name="date" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_date === 'all' ? 'selected' : ''; ?>>Toutes</option>
                            <option value="today" <?php echo $filter_date === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                            <option value="upcoming" <?php echo $filter_date === 'upcoming' ? 'selected' : ''; ?>>À venir</option>
                            <option value="past" <?php echo $filter_date === 'past' ? 'selected' : ''; ?>>Passées</option>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <a href="reservations.php" class="btn-secondary">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des réservations -->
        <div class="content-card">
            <div class="card-header">
                <h2>Réservations (<?php echo count($reservations); ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Chambre</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th>Nuits</th>
                            <th>Prix Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): 
                            $check_in = new DateTime($reservation['check_in_date']);
                            $check_out = new DateTime($reservation['check_out_date']);
                            $nights = $check_in->diff($check_out)->days;
                        ?>
                        <tr>
                            <td><strong>#<?php echo $reservation['id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($reservation['username']); ?></strong>
                                <br><small><?php echo htmlspecialchars($reservation['email']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($reservation['room_number']); ?>
                                <br><small><?php echo htmlspecialchars($reservation['room_type']); ?></small>
                            </td>
                            <td><?php echo $check_in->format('d/m/Y'); ?></td>
                            <td><?php echo $check_out->format('d/m/Y'); ?></td>
                            <td><?php echo $nights; ?></td>
                            <td><strong><?php echo formatPriceFCFA($reservation['total_price'], true); ?></strong></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch($reservation['status']) {
                                    case 'confirmed':
                                        $badge_class = 'badge-success';
                                        $status_text = 'Confirmée';
                                        break;
                                    case 'pending':
                                        $badge_class = 'badge-warning';
                                        $status_text = 'En attente';
                                        break;
                                    case 'cancelled':
                                        $badge_class = 'badge-danger';
                                        $status_text = 'Annulée';
                                        break;
                                    default:
                                        $badge_class = 'badge-secondary';
                                        $status_text = $reservation['status'];
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <?php if ($reservation['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="action" value="confirm">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="btn-icon" style="padding: 0.4rem 0.8rem; background: var(--admin-success); color: white; border: none;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if ($reservation['status'] !== 'cancelled'): ?>
                                <form method="POST" style="display: inline;" class="cancel-form">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="button" class="btn-icon cancel-btn" data-id="<?php echo $reservation['id']; ?>" style="padding: 0.4rem 0.8rem; background: var(--admin-warning); color: white; border: none; cursor: pointer;">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" style="display: inline;" class="delete-form">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <button type="button" class="btn-icon delete-btn" data-id="<?php echo $reservation['id']; ?>" style="padding: 0.4rem 0.8rem; background: var(--admin-danger); color: white; border: none; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <style>
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background: rgba(40,167,69,0.1);
            color: var(--admin-success);
            border: 1px solid rgba(40,167,69,0.2);
        }
        .alert-danger {
            background: rgba(220,53,69,0.1);
            color: var(--admin-danger);
            border: 1px solid rgba(220,53,69,0.2);
        }
    </style>

    <script src="../js/modal.js"></script>
    <script>
        // Afficher les messages PHP en toast
        <?php if ($message): ?>
        showSuccess('<?php echo addslashes($message); ?>');
        <?php endif; ?>
        
        <?php if ($error): ?>
        showError('<?php echo addslashes($error); ?>');
        <?php endif; ?>

        // Gérer les annulations
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                Modal.confirm(
                    'Annuler la réservation',
                    'Êtes-vous sûr de vouloir annuler cette réservation ?',
                    () => {
                        Modal.loading('Annulation en cours...');
                        form.submit();
                    }
                );
            });
        });

        // Gérer les suppressions
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                Modal.confirm(
                    'Supprimer la réservation',
                    'Êtes-vous sûr de vouloir supprimer définitivement cette réservation ?<br><br>Cette action est irréversible.',
                    () => {
                        Modal.loading('Suppression en cours...');
                        form.submit();
                    }
                );
            });
        });
    </script>
</body>
</html>
