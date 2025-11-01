<?php
require_once '../php/config.php';

// Exiger droits admin
Security::requireAdmin();

// Actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'unblock_ip' && isset($_POST['ip'])) {
        $ip = $_POST['ip'];
        $stmt = $pdo->prepare("DELETE FROM blocked_ips WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $message = "IP $ip débloquée avec succès.";
    }
    
    if ($_POST['action'] === 'block_ip' && isset($_POST['ip']) && isset($_POST['reason'])) {
        $ip = $_POST['ip'];
        $reason = $_POST['reason'];
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 3600;
        Security::blockIP($ip, $reason, $duration);
        $message = "IP $ip bloquée pour $duration secondes.";
    }
}

// Récupérer les statistiques
$stats_24h = $pdo->query("
    SELECT event_type, COUNT(*) as count, COUNT(DISTINCT ip_address) as unique_ips
    FROM security_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY event_type
    ORDER BY count DESC
")->fetchAll();

$recent_events = $pdo->query("
    SELECT * FROM security_logs
    ORDER BY created_at DESC
    LIMIT 50
")->fetchAll();

$blocked_ips = $pdo->query("
    SELECT * FROM blocked_ips
    WHERE blocked_until > NOW()
    ORDER BY created_at DESC
")->fetchAll();

$suspicious_ips = $pdo->query("
    SELECT ip_address, COUNT(*) as failed_attempts, MAX(created_at) as last_attempt
    FROM security_logs
    WHERE event_type IN ('LOGIN_FAILED', 'CSRF_ATTACK_DETECTED', 'SQL_INJECTION_ATTEMPT', 'XSS_ATTEMPT')
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    GROUP BY ip_address
    HAVING failed_attempts >= 3
    ORDER BY failed_attempts DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sécurité - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .security-dashboard {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #0066cc;
        }
        
        .stat-card.danger {
            border-left-color: #dc3545;
        }
        
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        
        .stat-card.success {
            border-left-color: #28a745;
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            margin: 0 0 1rem 0;
            color: #0066cc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            background: #0066cc;
            color: white;
        }
        
        .btn-small:hover {
            background: #0052a3;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .block-ip-form {
            display: grid;
            grid-template-columns: 2fr 3fr 1fr 1fr;
            gap: 0.5rem;
            align-items: end;
        }
        
        .block-ip-form input, .block-ip-form select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="security-dashboard">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-shield-alt"></i> Dashboard de Sécurité</h1>
                <p>Monitoring et gestion des événements de sécurité</p>
            </div>
            <a href="dashboard.php" class="btn-primary">
                <i class="fas fa-arrow-left"></i> Retour au Dashboard
            </a>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-grid">
            <?php
            $total_events = array_sum(array_column($stats_24h, 'count'));
            $failed_logins = 0;
            $attacks_detected = 0;
            
            foreach ($stats_24h as $stat) {
                if ($stat['event_type'] === 'LOGIN_FAILED') {
                    $failed_logins = $stat['count'];
                }
                if (in_array($stat['event_type'], ['CSRF_ATTACK_DETECTED', 'SQL_INJECTION_ATTEMPT', 'XSS_ATTEMPT'])) {
                    $attacks_detected += $stat['count'];
                }
            }
            ?>
            
            <div class="stat-card">
                <h3>Événements (24h)</h3>
                <div class="value"><?php echo $total_events; ?></div>
            </div>
            
            <div class="stat-card <?php echo $failed_logins > 10 ? 'danger' : 'warning'; ?>">
                <h3>Échecs de connexion</h3>
                <div class="value"><?php echo $failed_logins; ?></div>
            </div>
            
            <div class="stat-card <?php echo $attacks_detected > 0 ? 'danger' : 'success'; ?>">
                <h3>Attaques détectées</h3>
                <div class="value"><?php echo $attacks_detected; ?></div>
            </div>
            
            <div class="stat-card <?php echo count($blocked_ips) > 0 ? 'warning' : 'success'; ?>">
                <h3>IPs bloquées</h3>
                <div class="value"><?php echo count($blocked_ips); ?></div>
            </div>
        </div>

        <!-- IPs bloquées -->
        <div class="section">
            <h2><i class="fas fa-ban"></i> Adresses IP bloquées (<?php echo count($blocked_ips); ?>)</h2>
            
            <?php if (count($blocked_ips) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Adresse IP</th>
                            <th>Raison</th>
                            <th>Bloqué jusqu'à</th>
                            <th>Temps restant</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $blocked): 
                            $remaining = strtotime($blocked['blocked_until']) - time();
                            $minutes = floor($remaining / 60);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($blocked['ip_address']); ?></strong></td>
                            <td><?php echo htmlspecialchars($blocked['reason']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($blocked['blocked_until'])); ?></td>
                            <td><?php echo $minutes; ?> min</td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unblock_ip">
                                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($blocked['ip_address']); ?>">
                                    <button type="submit" class="btn-small btn-danger" onclick="return confirm('Débloquer cette IP ?')">
                                        <i class="fas fa-unlock"></i> Débloquer
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucune IP bloquée actuellement.</p>
            <?php endif; ?>
        </div>

        <!-- IPs suspectes -->
        <?php if (count($suspicious_ips) > 0): ?>
        <div class="section">
            <h2><i class="fas fa-exclamation-triangle"></i> IPs suspectes (≥3 échecs en 1h)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Adresse IP</th>
                        <th>Tentatives échouées</th>
                        <th>Dernière tentative</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suspicious_ips as $suspicious): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($suspicious['ip_address']); ?></strong></td>
                        <td><span class="badge badge-danger"><?php echo $suspicious['failed_attempts']; ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($suspicious['last_attempt'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="block_ip">
                                <input type="hidden" name="ip" value="<?php echo htmlspecialchars($suspicious['ip_address']); ?>">
                                <input type="hidden" name="reason" value="Activité suspecte détectée automatiquement">
                                <input type="hidden" name="duration" value="3600">
                                <button type="submit" class="btn-small btn-danger">
                                    <i class="fas fa-ban"></i> Bloquer 1h
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Bloquer manuellement une IP -->
        <div class="section">
            <h2><i class="fas fa-plus-circle"></i> Bloquer une adresse IP</h2>
            <form method="POST" class="block-ip-form">
                <input type="hidden" name="action" value="block_ip">
                <div>
                    <label>Adresse IP</label>
                    <input type="text" name="ip" placeholder="ex: 192.168.1.100" required>
                </div>
                <div>
                    <label>Raison</label>
                    <input type="text" name="reason" placeholder="Raison du blocage" required>
                </div>
                <div>
                    <label>Durée</label>
                    <select name="duration">
                        <option value="3600">1 heure</option>
                        <option value="21600">6 heures</option>
                        <option value="86400">24 heures</option>
                        <option value="604800">7 jours</option>
                        <option value="2592000">30 jours</option>
                    </select>
                </div>
                <button type="submit" class="btn-small">
                    <i class="fas fa-ban"></i> Bloquer
                </button>
            </form>
        </div>

        <!-- Événements récents -->
        <div class="section">
            <h2><i class="fas fa-list"></i> Événements de sécurité récents (50 derniers)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Type</th>
                        <th>Adresse IP</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_events as $event): 
                        $badge_class = 'badge-info';
                        if (strpos($event['event_type'], 'FAILED') !== false || 
                            strpos($event['event_type'], 'ATTACK') !== false ||
                            strpos($event['event_type'], 'INJECTION') !== false) {
                            $badge_class = 'badge-danger';
                        } elseif (strpos($event['event_type'], 'SUCCESS') !== false) {
                            $badge_class = 'badge-success';
                        } elseif (strpos($event['event_type'], 'LIMIT') !== false) {
                            $badge_class = 'badge-warning';
                        }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($event['created_at'])); ?></td>
                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($event['event_type']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($event['ip_address']); ?></strong></td>
                        <td>
                            <small>
                                <?php 
                                $details = json_decode($event['details'], true);
                                if ($details) {
                                    foreach ($details as $key => $value) {
                                        echo htmlspecialchars($key) . ': ' . htmlspecialchars($value) . ' | ';
                                    }
                                }
                                ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Statistiques par type d'événement -->
        <div class="section">
            <h2><i class="fas fa-chart-bar"></i> Statistiques par type (24h)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Type d'événement</th>
                        <th>Nombre total</th>
                        <th>IPs uniques</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_24h as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['event_type']); ?></td>
                        <td><strong><?php echo $stat['count']; ?></strong></td>
                        <td><?php echo $stat['unique_ips']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-refresh toutes les 30 secondes
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
