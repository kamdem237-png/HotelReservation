<?php
require_once '../php/config.php';

// Exiger droits admin
Security::requireAdmin();

// Récupérer les statistiques
$stats = [];

// Total réservations
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations");
$stats['total_reservations'] = $stmt->fetch()['total'];

// Réservations du mois
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE MONTH(check_in_date) = MONTH(CURRENT_DATE()) AND YEAR(check_in_date) = YEAR(CURRENT_DATE())");
$stats['month_reservations'] = $stmt->fetch()['total'];

// Revenus du mois
$stmt = $pdo->query("SELECT SUM(total_price) as total FROM reservations WHERE status = 'confirmed' AND MONTH(check_in_date) = MONTH(CURRENT_DATE()) AND YEAR(check_in_date) = YEAR(CURRENT_DATE())");
$stats['month_revenue'] = $stmt->fetch()['total'] ?? 0;

// Total chambres
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
$stats['total_rooms'] = $stmt->fetch()['total'];

// Chambres disponibles
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
$stats['available_rooms'] = $stmt->fetch()['total'];

// Total clients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'client'");
$stats['total_clients'] = $stmt->fetch()['total'];

// Taux d'occupation
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT r.room_id) as occupied_rooms
    FROM reservations r
    WHERE r.status = 'confirmed'
    AND CURRENT_DATE() BETWEEN r.check_in_date AND r.check_out_date
");
$occupied = $stmt->fetch()['occupied_rooms'];
$stats['occupancy_rate'] = $stats['total_rooms'] > 0 ? round(($occupied / $stats['total_rooms']) * 100, 1) : 0;

// Réservations récentes
$recent_reservations = $pdo->query("
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
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();

// Réservations par mois (6 derniers mois)
$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(check_in_date, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(total_price) as revenue
    FROM reservations
    WHERE check_in_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(check_in_date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-hotel"></i> HotelRes Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="rooms.php" class="nav-item">
                <i class="fas fa-bed"></i> Chambres
            </a>
            <a href="reservations.php" class="nav-item">
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
        <div class="sidebar-footer">
            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <small>Administrateur</small>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <div class="header-actions">
                <button class="btn-icon" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
                <a href="../index.html" class="btn-secondary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Voir le site
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <h3>Réservations totales</h3>
                    <p class="stat-number"><?php echo $stats['total_reservations']; ?></p>
                    <span class="stat-label">
                        <?php echo $stats['month_reservations']; ?> ce mois
                    </span>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3>Revenus du mois</h3>
                    <p class="stat-number"><?php echo formatPriceFCFA($stats['month_revenue'], true); ?></p>
                    <span class="stat-label">Réservations confirmées</span>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-content">
                    <h3>Chambres</h3>
                    <p class="stat-number"><?php echo $stats['available_rooms']; ?> / <?php echo $stats['total_rooms']; ?></p>
                    <span class="stat-label">Disponibles</span>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3>Taux d'occupation</h3>
                    <p class="stat-number"><?php echo $stats['occupancy_rate']; ?>%</p>
                    <span class="stat-label">Aujourd'hui</span>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Clients inscrits</h3>
                    <p class="stat-number"><?php echo $stats['total_clients']; ?></p>
                    <span class="stat-label">Total</span>
                </div>
            </div>
        </div>

        <!-- Charts & Tables -->
        <div class="content-grid">
            <!-- Recent Reservations -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Réservations récentes</h2>
                    <a href="reservations.php" class="btn-link">Voir tout</a>
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
                                <th>Prix</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reservations as $reservation): ?>
                            <tr>
                                <td>#<?php echo $reservation['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($reservation['username']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($reservation['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['room_number']); ?> - <?php echo htmlspecialchars($reservation['room_type']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reservation['check_in_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reservation['check_out_date'])); ?></td>
                                <td><strong><?php echo formatPriceFCFA($reservation['total_price'], true); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch($reservation['status']) {
                                        case 'confirmed':
                                            $status_class = 'badge-success';
                                            $status_text = 'Confirmée';
                                            break;
                                        case 'pending':
                                            $status_class = 'badge-warning';
                                            $status_text = 'En attente';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'badge-danger';
                                            $status_text = 'Annulée';
                                            break;
                                        default:
                                            $status_class = 'badge-secondary';
                                            $status_text = $reservation['status'];
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Statistiques mensuelles</h2>
                </div>
                <div class="chart-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mois</th>
                                <th>Réservations</th>
                                <th>Revenus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $stat): 
                                $month_name = date('F Y', strtotime($stat['month'] . '-01'));
                            ?>
                            <tr>
                                <td><strong><?php echo $month_name; ?></strong></td>
                                <td><?php echo $stat['count']; ?></td>
                                <td><strong><?php echo formatPriceFCFA($stat['revenue'], true); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Actions rapides</h2>
            <div class="action-buttons">
                <a href="rooms.php?action=add" class="action-btn primary">
                    <i class="fas fa-plus"></i> Ajouter une chambre
                </a>
                <a href="reservations.php" class="action-btn info">
                    <i class="fas fa-calendar"></i> Gérer les réservations
                </a>
                <a href="clients.php" class="action-btn success">
                    <i class="fas fa-user-plus"></i> Voir les clients
                </a>
                <a href="security_dashboard.php" class="action-btn warning">
                    <i class="fas fa-shield-alt"></i> Sécurité
                </a>
            </div>
        </div>
    </main>

    <script>
        // Auto-refresh stats every 30 seconds
        setTimeout(() => {
            document.querySelectorAll('.stat-number').forEach(el => {
                el.style.transition = 'all 0.3s ease';
            });
        }, 100);
    </script>
</body>
</html>
