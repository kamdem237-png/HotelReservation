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

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

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
        } elseif (strlen($new_password) < 8) {
            $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
    }

    if (empty($error)) {
        try {
            if (!empty($new_password)) {
                // Mise à jour avec nouveau mot de passe
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            } else {
                // Mise à jour sans mot de passe
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
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
?>

<!DOCTYPE html>
<html lang="fr">
<body>
    <main class="container">
        <section class="profile-section">
            <h1>Mon Profil</h1>

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
                <form class="profile-form" method="POST" action="">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <h3>Changer le mot de passe</h3>
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
                    <h3>Statistiques</h3>
                    <?php
                    // Récupérer le nombre de réservations
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $reservations_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    // Récupérer la date d'inscription
                    $created_at = new DateTime($user['created_at']);
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <h4>Réservations totales</h4>
                                <p><?php echo $reservations_count; ?></p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Membre depuis</h4>
                                <p><?php echo $created_at->format('d/m/Y'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../js/nav.js"></script>
</body>
</html>