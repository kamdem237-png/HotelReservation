<?php
require_once '../php/config.php';
Security::requireAdmin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$room_id = $_GET['id'] ?? null;

// Gestion des actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protection CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Jeton de sécurité invalide.";
    } else {
        $post_action = $_POST['action'];
        
        // CRÉER
        if ($post_action === 'create') {
            $room_type_id = (int)$_POST['room_type_id'];
            $room_number = sanitize($_POST['room_number']);
            $floor = (int)$_POST['floor'];
            $status = sanitize($_POST['status']);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO rooms (room_type_id, room_number, floor, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$room_type_id, $room_number, $floor, $status]);
                $message = "Chambre créée avec succès!";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
        }
        
        // MODIFIER
        elseif ($post_action === 'update') {
            $id = (int)$_POST['id'];
            $room_type_id = (int)$_POST['room_type_id'];
            $room_number = sanitize($_POST['room_number']);
            $floor = (int)$_POST['floor'];
            $status = sanitize($_POST['status']);
            
            try {
                $stmt = $pdo->prepare("UPDATE rooms SET room_type_id = ?, room_number = ?, floor = ?, status = ? WHERE id = ?");
                $stmt->execute([$room_type_id, $room_number, $floor, $status, $id]);
                $message = "Chambre modifiée avec succès!";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
        }
        
        // SUPPRIMER
        elseif ($post_action === 'delete') {
            $id = (int)$_POST['id'];
            
            try {
                // Vérifier s'il y a des réservations
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE room_id = ? AND status != 'cancelled'");
                $stmt->execute([$id]);
                if ($stmt->fetch()['count'] > 0) {
                    $error = "Impossible de supprimer: cette chambre a des réservations actives.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Chambre supprimée avec succès!";
                }
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
        }
    }
}

// Récupérer les types de chambres
$room_types = $pdo->query("SELECT * FROM room_types ORDER BY name")->fetchAll();

// Récupérer toutes les chambres avec leurs types
$rooms = $pdo->query("
    SELECT r.*, rt.name as type_name, rt.price_per_night
    FROM rooms r
    LEFT JOIN room_types rt ON rt.id = r.room_type_id
    ORDER BY r.floor, r.room_number
")->fetchAll();

// Si mode édition, récupérer la chambre
$edit_room = null;
if ($action === 'edit' && $room_id) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $edit_room = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Chambres - Admin</title>
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
            <a href="rooms.php" class="nav-item active">
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
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-bed"></i> Gestion des Chambres</h1>
            <div class="header-actions">
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn-icon" style="background: var(--admin-primary); color: white;">
                    <i class="fas fa-plus"></i> Nouvelle chambre
                </a>
                <?php else: ?>
                <a href="rooms.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <?php endif; ?>
            </div>
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

        <?php if ($action === 'list'): ?>
        <!-- Liste des chambres -->
        <div class="content-card">
            <div class="card-header">
                <h2>Toutes les chambres (<?php echo count($rooms); ?>)</h2>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Numéro</th>
                            <th>Type</th>
                            <th>Étage</th>
                            <th>Prix/nuit</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td>#<?php echo $room['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                            <td>Étage <?php echo $room['floor']; ?></td>
                            <td><strong><?php echo formatPriceFCFA($room['price_per_night'], true); ?></strong></td>
                            <td>
                                <?php
                                $badge_class = '';
                                switch($room['status']) {
                                    case 'available':
                                        $badge_class = 'badge-success';
                                        $status_text = 'Disponible';
                                        break;
                                    case 'occupied':
                                        $badge_class = 'badge-warning';
                                        $status_text = 'Occupée';
                                        break;
                                    case 'maintenance':
                                        $badge_class = 'badge-danger';
                                        $status_text = 'Maintenance';
                                        break;
                                    default:
                                        $badge_class = 'badge-secondary';
                                        $status_text = $room['status'];
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <a href="room_images.php?room_id=<?php echo $room['id']; ?>" class="btn-icon" style="padding: 0.5rem 1rem; background: var(--admin-info); color: white;" title="Galerie d'images">
                                    <i class="fas fa-images"></i>
                                </a>
                                <a href="?action=edit&id=<?php echo $room['id']; ?>" class="btn-icon" style="padding: 0.5rem 1rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" class="delete-form">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
                                    <button type="button" class="btn-icon delete-btn" data-room="<?php echo htmlspecialchars($room['room_number']); ?>" style="padding: 0.5rem 1rem; background: var(--admin-danger); color: white; border: none; cursor: pointer;">
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

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Formulaire Ajout/Édition -->
        <div class="content-card">
            <div class="card-header">
                <h2><?php echo $action === 'add' ? 'Nouvelle chambre' : 'Modifier la chambre'; ?></h2>
            </div>
            <div style="padding: 2rem;">
                <form method="POST" class="form-grid">
                    <?php echo Security::csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_room['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="room_number">Numéro de chambre *</label>
                        <input type="text" id="room_number" name="room_number" 
                               value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_number']) : ''; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="room_type_id">Type de chambre *</label>
                        <select id="room_type_id" name="room_type_id" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    <?php echo ($edit_room && $edit_room['room_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?> - <?php echo formatPriceFCFA($type['price_per_night'], true); ?>/nuit
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="floor">Étage *</label>
                        <input type="number" id="floor" name="floor" min="0" max="20"
                               value="<?php echo $edit_room ? $edit_room['floor'] : '1'; ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="status">Statut *</label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo ($edit_room && $edit_room['status'] === 'available') ? 'selected' : ''; ?>>
                                Disponible
                            </option>
                            <option value="occupied" <?php echo ($edit_room && $edit_room['status'] === 'occupied') ? 'selected' : ''; ?>>
                                Occupée
                            </option>
                            <option value="maintenance" <?php echo ($edit_room && $edit_room['status'] === 'maintenance') ? 'selected' : ''; ?>>
                                Maintenance
                            </option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="action-btn primary" style="width: auto; min-width: 200px;">
                            <i class="fas fa-save"></i> <?php echo $action === 'add' ? 'Créer la chambre' : 'Enregistrer les modifications'; ?>
                        </button>
                        <a href="rooms.php" class="btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script src="../js/modal.js"></script>
    <script>
        // Afficher les messages PHP en toast
        <?php if ($message): ?>
        showSuccess('<?php echo addslashes($message); ?>');
        <?php endif; ?>
        
        <?php if ($error): ?>
        showError('<?php echo addslashes($error); ?>');
        <?php endif; ?>

        // Gérer les suppressions avec modal de confirmation
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const roomName = this.dataset.room;
                const form = this.closest('form');
                
                Modal.confirm(
                    'Supprimer la chambre',
                    `Êtes-vous sûr de vouloir supprimer la chambre <strong>${roomName}</strong> ?<br><br>Cette action est irréversible.`,
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
