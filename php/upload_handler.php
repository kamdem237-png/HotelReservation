<?php
/**
 * HANDLER D'UPLOAD D'IMAGES
 * Gestion sécurisée des uploads d'images
 */

require_once 'config.php';
Security::requireAdmin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Vérifier CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        throw new Exception("Token CSRF invalide");
    }

    $room_id = (int)($_POST['room_id'] ?? 0);
    if ($room_id <= 0) {
        throw new Exception("ID de chambre invalide");
    }

    // Vérifier que la chambre existe
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Chambre non trouvée");
    }

    // Créer le dossier si nécessaire
    $upload_dir = __DIR__ . '/../uploads/rooms/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Vérifier le fichier
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors de l'upload du fichier");
    }

    $file = $_FILES['image'];
    
    // Vérifications de sécurité
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception("Type de fichier non autorisé. Seules les images sont acceptées.");
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        throw new Exception("Extension de fichier non autorisée");
    }
    
    // Vérifier la taille (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Le fichier est trop volumineux (max 5MB)");
    }
    
    // Vérifier le nombre d'images (max 10 par chambre)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM room_images WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $count = $stmt->fetch()['count'];
    
    if ($count >= 10) {
        throw new Exception("Limite de 10 images par chambre atteinte");
    }
    
    // Générer un nom unique
    $new_filename = 'room_' . $room_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $destination = $upload_dir . $new_filename;
    
    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Erreur lors de l'enregistrement du fichier");
    }
    
    // Redimensionner l'image si nécessaire (max 1920x1080)
    resizeImage($destination, 1920, 1080);
    
    // Enregistrer en base de données
    $is_primary = ($count === 0) ? 1 : 0; // Première image = principale
    $display_order = $count;
    $caption = sanitize($_POST['caption'] ?? '');
    
    $stmt = $pdo->prepare("
        INSERT INTO room_images (room_id, image_path, is_primary, display_order, caption, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $room_id,
        'uploads/rooms/' . $new_filename,
        $is_primary,
        $display_order,
        $caption,
        $_SESSION['user_id']
    ]);
    
    $image_id = $pdo->lastInsertId();
    
    // Logger l'événement
    Security::logSecurityEvent('IMAGE_UPLOADED', $_SERVER['REMOTE_ADDR'], [
        'room_id' => $room_id,
        'image_id' => $image_id,
        'filename' => $new_filename
    ]);
    
    $response = [
        'success' => true,
        'message' => 'Image uploadée avec succès',
        'data' => [
            'id' => $image_id,
            'path' => 'uploads/rooms/' . $new_filename,
            'is_primary' => $is_primary
        ]
    ];
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Redimensionner une image
 */
function resizeImage($file, $max_width, $max_height) {
    $info = getimagesize($file);
    if (!$info) return false;
    
    list($width, $height, $type) = $info;
    
    // Si l'image est déjà assez petite, ne rien faire
    if ($width <= $max_width && $height <= $max_height) {
        return true;
    }
    
    // Calculer les nouvelles dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = (int)($width * $ratio);
    $new_height = (int)($height * $ratio);
    
    // Créer l'image source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($file);
            break;
        default:
            return false;
    }
    
    if (!$source) return false;
    
    // Créer la nouvelle image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Préserver la transparence pour PNG et GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Redimensionner
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Sauvegarder
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $file, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $file, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $file);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($destination, $file, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}
?>
