<?php
require_once '../php/config.php';
Security::requireAdmin();

$room_id = (int)($_GET['room_id'] ?? 0);

if ($room_id <= 0) {
    header('Location: rooms.php');
    exit;
}

// Récupérer les infos de la chambre
$stmt = $pdo->prepare("
    SELECT r.*, rt.name as type_name 
    FROM rooms r 
    LEFT JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE r.id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: rooms.php');
    exit;
}

// Actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF invalide";
    } else {
        $action = $_POST['action'];
        
        if ($action === 'delete') {
            $image_id = (int)$_POST['image_id'];
            
            // Récupérer le chemin de l'image
            $stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE id = ? AND room_id = ?");
            $stmt->execute([$image_id, $room_id]);
            $image = $stmt->fetch();
            
            if ($image) {
                // Supprimer le fichier
                $file_path = '../' . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Supprimer de la DB
                $stmt = $pdo->prepare("DELETE FROM room_images WHERE id = ?");
                $stmt->execute([$image_id]);
                
                $message = "Image supprimée avec succès";
            }
        }
        elseif ($action === 'set_primary') {
            $image_id = (int)$_POST['image_id'];
            $pdo->prepare("CALL set_primary_image(?)")->execute([$image_id]);
            $message = "Image principale définie";
        }
        elseif ($action === 'update_caption') {
            $image_id = (int)$_POST['image_id'];
            $caption = sanitize($_POST['caption']);
            
            $stmt = $pdo->prepare("UPDATE room_images SET caption = ? WHERE id = ? AND room_id = ?");
            $stmt->execute([$caption, $image_id, $room_id]);
            $message = "Légende mise à jour";
        }
        elseif ($action === 'reorder') {
            $orders = json_decode($_POST['orders'], true);
            foreach ($orders as $image_id => $order) {
                $stmt = $pdo->prepare("UPDATE room_images SET display_order = ? WHERE id = ? AND room_id = ?");
                $stmt->execute([$order, $image_id, $room_id]);
            }
            $message = "Ordre mis à jour";
        }
    }
}

// Récupérer les images
$images = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY display_order, id")->execute([$room_id]);
$images = $pdo->query("SELECT * FROM room_images WHERE room_id = $room_id ORDER BY display_order, id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie - <?php echo htmlspecialchars($room['room_number']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .image-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: relative; }
        .image-card img { width: 100%; height: 200px; object-fit: cover; }
        .image-card-body { padding: 1rem; }
        .image-primary-badge { position: absolute; top: 10px; left: 10px; background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .image-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
        .image-actions button { flex: 1; padding: 0.5rem; border: none; border-radius: 5px; cursor: pointer; font-size: 0.85rem; }
        .btn-set-primary { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .upload-zone { border: 2px dashed #ddd; border-radius: 10px; padding: 3rem; text-align: center; background: #f8f9fa; cursor: pointer; margin-bottom: 2rem; }
        .upload-zone:hover { border-color: #0066cc; background: #e3f2fd; }
        .upload-zone.dragover { border-color: #0066cc; background: #bbdefb; }
    </style>
</head>
<body class="admin-body">
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

    <main class="admin-main">
        <div class="admin-header">
            <h1><i class="fas fa-images"></i> Galerie - Chambre <?php echo htmlspecialchars($room['room_number']); ?></h1>
            <div class="header-actions">
                <a href="rooms.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour aux chambres
                </a>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2>Informations de la chambre</h2>
            </div>
            <div style="padding: 1.5rem;">
                <p><strong>Type:</strong> <?php echo htmlspecialchars($room['type_name']); ?></p>
                <p><strong>Étage:</strong> <?php echo $room['floor']; ?></p>
                <p><strong>Statut:</strong> <?php echo $room['status']; ?></p>
                <p><strong>Images:</strong> <?php echo count($images); ?> / 10</p>
            </div>
        </div>

        <?php if (count($images) < 10): ?>
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-upload"></i> Ajouter des images</h2>
            </div>
            <div style="padding: 1.5rem;">
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #0066cc; margin-bottom: 1rem;"></i>
                    <p><strong>Glissez-déposez des images ici</strong></p>
                    <p>ou cliquez pour sélectionner</p>
                    <small>JPG, PNG, GIF, WEBP - Max 5MB par image</small>
                    <input type="file" id="fileInput" accept="image/*" multiple style="display: none;">
                </div>
                <div id="uploadProgress" style="display: none;">
                    <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                        <div id="progressBar" style="background: #28a745; height: 100%; width: 0; transition: width 0.3s;"></div>
                    </div>
                    <p id="uploadStatus" style="margin-top: 0.5rem; text-align: center;"></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-image"></i> Images de la chambre (<?php echo count($images); ?>)</h2>
            </div>
            <div style="padding: 1.5rem;">
                <?php if (empty($images)): ?>
                    <p style="text-align: center; color: #999; padding: 2rem;">
                        <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        Aucune image pour le moment
                    </p>
                <?php else: ?>
                    <div class="image-grid" id="imageGrid">
                        <?php foreach ($images as $image): ?>
                        <div class="image-card" data-id="<?php echo $image['id']; ?>">
                            <?php if ($image['is_primary']): ?>
                            <span class="image-primary-badge"><i class="fas fa-star"></i> Principale</span>
                            <?php endif; ?>
                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="Image">
                            <div class="image-card-body">
                                <input type="text" class="caption-input" value="<?php echo htmlspecialchars($image['caption'] ?? ''); ?>" 
                                       placeholder="Légende..." style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                                <div class="image-actions">
                                    <?php if (!$image['is_primary']): ?>
                                    <button class="btn-set-primary" onclick="setPrimary(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-star"></i> Principale
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteImage(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../js/modal.js"></script>
    <script>
        const roomId = <?php echo $room_id; ?>;
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        
        // Messages
        <?php if ($message): ?>
        showSuccess('<?php echo addslashes($message); ?>');
        <?php endif; ?>
        
        <?php if ($error): ?>
        showError('<?php echo addslashes($error); ?>');
        <?php endif; ?>
        
        // Upload zone
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        async function handleFiles(files) {
            const progress = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const status = document.getElementById('uploadStatus');
            
            progress.style.display = 'block';
            
            let uploaded = 0;
            const total = files.length;
            
            for (const file of files) {
                const formData = new FormData();
                formData.append('image', file);
                formData.append('room_id', roomId);
                formData.append('csrf_token', csrfToken);
                
                try {
                    const response = await fetch('../php/upload_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        uploaded++;
                        progressBar.style.width = (uploaded / total * 100) + '%';
                        status.textContent = `${uploaded} / ${total} images uploadées`;
                    } else {
                        showError(`Erreur: ${result.message}`);
                    }
                } catch (error) {
                    showError('Erreur lors de l\'upload');
                }
            }
            
            if (uploaded > 0) {
                showSuccess(`${uploaded} image(s) uploadée(s) avec succès!`);
                setTimeout(() => location.reload(), 1500);
            }
        }
        
        function setPrimary(imageId) {
            Modal.loading('Mise à jour...');
            
            const formData = new FormData();
            formData.append('action', 'set_primary');
            formData.append('image_id', imageId);
            formData.append('csrf_token', csrfToken);
            
            fetch('', {
                method: 'POST',
                body: formData
            }).then(() => location.reload());
        }
        
        function deleteImage(imageId) {
            Modal.confirm(
                'Supprimer l\'image',
                'Êtes-vous sûr de vouloir supprimer cette image?',
                () => {
                    Modal.loading('Suppression...');
                    
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('image_id', imageId);
                    formData.append('csrf_token', csrfToken);
                    
                    fetch('', {
                        method: 'POST',
                        body: formData
                    }).then(() => location.reload());
                }
            );
        }
        
        // Sauvegarder les légendes
        document.querySelectorAll('.caption-input').forEach(input => {
            input.addEventListener('blur', function() {
                const imageId = this.closest('.image-card').dataset.id;
                const caption = this.value;
                
                const formData = new FormData();
                formData.append('action', 'update_caption');
                formData.append('image_id', imageId);
                formData.append('caption', caption);
                formData.append('csrf_token', csrfToken);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                }).then(() => showSuccess('Légende mise à jour'));
            });
        });
    </script>
</body>
</html>
