<?php
require_once '../php/config.php';
Security::requireAdmin();

// Récupérer tous les clients avec leurs statistiques
$clients = $pdo->query("
    SELECT 
        u.*,
        COUNT(DISTINCT r.id) as total_reservations,
        SUM(CASE WHEN r.status = 'confirmed' THEN r.total_price ELSE 0 END) as total_spent,
        MAX(r.check_in_date) as last_reservation
    FROM users u
    LEFT JOIN reservations r ON r.user_id = u.id
    WHERE u.role = 'client'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

$total_clients = count($clients);
$active_clients = $pdo->query("
    SELECT COUNT(DISTINCT user_id) as count 
    FROM reservations 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 MONTH)
")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients - Admin</title>
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
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="rooms.php" class="nav-item">
                <i class="fas fa-bed"></i> Chambres
            </a>
            <a href="reservations.php" class="nav-item">
                <i class="fas fa-calendar-check"></i> Réservations
            </a>
            <a href="clients.php" class="nav-item active">
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
            <h1><i class="fas fa-users"></i> Gestion des Clients</h1>
        </div>

        <!-- Stats -->
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Total clients</h3>
                    <p class="stat-number"><?php echo $total_clients; ?></p>
                    <span class="stat-label">Inscrits</span>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>Clients actifs</h3>
                    <p class="stat-number"><?php echo $active_clients; ?></p>
                    <span class="stat-label">6 derniers mois</span>
                </div>
            </div>
        </div>

        <!-- Liste des clients -->
        <div class="content-card">
            <div class="card-header">
                <h2>Tous les clients (<?php echo $total_clients; ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Réservations</th>
                            <th>Dépenses totales</th>
                            <th>Dernière réservation</th>
                            <th>Inscrit le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>#<?php echo $client['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($client['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td>
                                <?php if ($client['total_reservations'] > 0): ?>
                                <span class="badge badge-info"><?php echo $client['total_reservations']; ?></span>
                                <?php else: ?>
                                <span class="badge badge-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo formatPriceFCFA($client['total_spent'], true); ?></strong></td>
                            <td>
                                <?php if ($client['last_reservation']): ?>
                                <?php echo date('d/m/Y', strtotime($client['last_reservation'])); ?>
                                <?php else: ?>
                                <span class="badge badge-secondary">Aucune</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></td>
                            <td>
                                <a href="client_details.php?id=<?php echo $client['id']; ?>" class="btn-icon" style="padding: 0.4rem 0.8rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
