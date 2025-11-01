<?php
require_once 'config.php';
require_once 'header.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$error = '';

// Créer le dossier uploads si nécessaire
$upload_dir = '../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Protection CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Jeton de sécurité invalide.";
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $country = sanitize($_POST['country'] ?? 'Cameroun');
        $notification_email = isset($_POST['notification_email']) ? 1 : 0;
        $notification_sms = isset($_POST['notification_sms']) ? 1 : 0;
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username) || empty($email)) {
            $error = "Le nom d'utilisateur et l'email sont obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse email n'est pas valide.";
        } elseif (!empty($new_password)) {
            // Vérification du mot de passe actuel
            if (!password_verify($current_password, $user['password'])) {
                $error = "Le mot de passe actuel est incorrect.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Les nouveaux mots de passe ne correspondent pas.";
            } elseif (strlen($new_password) < 6) {
                $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
            }
        }

        // Upload photo de profil
        $profile_picture = $user['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                    // Supprimer l'ancienne photo
                    if ($user['profile_picture'] && file_exists('../' . $user['profile_picture'])) {
                        unlink('../' . $user['profile_picture']);
                    }
                    $profile_picture = 'uploads/profiles/' . $new_filename;
                }
            }
        }

        if (empty($error)) {
            try {
                if (!empty($new_password)) {
                    // Mise à jour avec nouveau mot de passe
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, profile_picture = ?, notification_email = ?, notification_sms = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $phone, $address, $city, $country, $profile_picture, $notification_email, $notification_sms, password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
                } else {
                    // Mise à jour sans mot de passe
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, profile_picture = ?, notification_email = ?, notification_sms = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $phone, $address, $city, $country, $profile_picture, $notification_email, $notification_sms, $_SESSION['user_id']]);
                }
                $message = "Profil mis à jour avec succès.";

                // Mettre à jour les informations en session
                $_SESSION['username'] = $username;

                // Recharger les informations de l'utilisateur
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Une erreur est survenue lors de la mise à jour du profil.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
    body {
        background-color: #f0f8ff;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .profile-section {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .profile-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .profile-picture-section {
        text-align: center;
        margin-bottom: 2rem;
    }

    .profile-picture-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #0066cc;
        margin-bottom: 1rem;
    }

    .loyalty-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        margin: 1rem 0;
    }

    .loyalty-badge.Bronze {
        background: #cd7f32;
        color: white;
    }

    .loyalty-badge.Silver {
        background: #c0c0c0;
        color: #333;
    }

    .loyalty-badge.Gold {
        background: #ffd700;
        color: #333;
    }

    .loyalty-badge.Platinum {
        background: linear-gradient(135deg, #e5e4e2 0%, #c0c0c0 100%);
        color: #333;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .stat-item i {
        font-size: 2rem;
        color: #0066cc;
    }

    .profile-form,
    .profile-stats {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <main class="container">
        <section class="profile-section">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>

            <?php if ($message): ?>
            <div class="alert success">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert error">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="profile-container">
                <form class="profile-form" method="POST" action="" enctype="multipart/form-data">
                    <?php echo Security::csrfField(); ?>

                    <div class="profile-picture-section">
                        <img src="<?php echo $user['profile_picture'] ? '../' . htmlspecialchars($user['profile_picture']) : '../images/default-avatar.png'; ?>"
                            alt="Photo de profil" class="profile-picture-preview" id="preview">
                        <div class="loyalty-badge <?php echo $user['loyalty_level']; ?>">
                            <i class="fas fa-trophy"></i> <?php echo $user['loyalty_level']; ?>
                        </div>
                        <p><strong><?php echo $user['loyalty_points']; ?> points</strong></p>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                            style="display: none;">
                        <button type="button" class="btn-secondary"
                            onclick="document.getElementById('profile_picture').click()">
                            <i class="fas fa-camera"></i> Changer la photo
                        </button>
                    </div>

                    <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username"
                            value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Téléphone</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            placeholder="+237 6XX XXX XXX">
                    </div>

                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marker-alt"></i> Adresse</label>
                        <textarea id="address" name="address"
                            rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="city"><i class="fas fa-city"></i> Ville</label>
                        <input type="text" id="city" name="city"
                            value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="country"><i class="fas fa-flag"></i> Pays</label>
                        <input type="text" id="country" name="country"
                            value="<?php echo htmlspecialchars($user['country'] ?? 'Cameroun'); ?>">
                    </div>

                    <h3><i class="fas fa-bell"></i> Préférences de notification</h3>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="notification_email"
                                <?php echo ($user['notification_email'] ?? 1) ? 'checked' : ''; ?>>
                            Recevoir des emails de notification
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="notification_sms"
                                <?php echo ($user['notification_sms'] ?? 0) ? 'checked' : ''; ?>>
                            Recevoir des SMS (si disponible)
                        </label>
                    </div>

                    <h3><i class="fas fa-lock"></i> Changer le mot de passe</h3>
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password">
                        <small>Laissez vide pour ne pas changer le mot de passe</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="btn-primary">Mettre à jour le profil</button>
                </form>

                <div class="profile-stats">
                    <h3><i class="fas fa-chart-line"></i> Statistiques & Fidélité</h3>
                    <?php
                    // Récupérer les statistiques complètes
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total_reservations,
                            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
                            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                            SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) as total_spent
                        FROM reservations WHERE user_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                    $created_at = new DateTime($user['created_at']);
                    $days_member = $created_at->diff(new DateTime())->days;
                    ?>

                    <div class="stat-item">
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <h4>Réservations</h4>
                            <p><strong><?php echo $stats['total_reservations']; ?></strong> total</p>
                            <small><?php echo $stats['confirmed']; ?> confirmées, <?php echo $stats['cancelled']; ?>
                                annulées</small>
                        </div>
                    </div>

                    <div class="stat-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>
                            <h4>Dépenses totales</h4>
                            <p><strong><?php echo formatPriceFCFA($stats['total_spent'], true); ?></strong></p>
                        </div>
                    </div>

                    <div class="stat-item">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <h4>Points de fidélité</h4>
                            <p><strong><?php echo $user['loyalty_points']; ?></strong> points</p>
                            <small>Niveau : <?php echo $user['loyalty_level']; ?></small>
                        </div>
                    </div>

                    <div class="stat-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Membre depuis</h4>
                            <p><strong><?php echo $created_at->format('d/m/Y'); ?></strong></p>
                            <small>Il y a <?php echo $days_member; ?> jours</small>
                        </div>
                    </div>

                    <div style="background: #e3f2fd; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-info-circle"></i> Programme de fidélité</h4>
                        <p style="margin: 0; font-size: 0.9rem;">Gagnez <strong>1 point</strong> pour chaque
                            <strong>1000 FCFA</strong> dépensés !
                        </p>
                        <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.85rem;">
                            <li>Bronze : 0-1999 points</li>
                            <li>Silver : 2000-4999 points</li>
                            <li>Gold : 5000-9999 points</li>
                            <li>Platinum : 10000+ points</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../js/modal.js"></script>
    <script src="../js/nav.js"></script>
    <script>
    // Messages toast
    <?php if ($message): ?>
    showSuccess('<?php echo addslashes($message); ?>');
    <?php endif; ?>

    <?php if ($error): ?>
    showError('<?php echo addslashes($error); ?>');
    <?php endif; ?>

    // Prévisualisation de la photo
    document.getElementById('profile_picture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    </script>

    <?php require_once 'footer.php'; ?>
</body>

</html>