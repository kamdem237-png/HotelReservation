<?php
require_once 'config.php';
require_once 'header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message_content = sanitize($_POST['message']);
    
    // Validation simple
    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        // Enregistrer le message dans la base de données
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $subject, $message_content]);
            $message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - HotelRes</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f8ff; }
        .contact-header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .contact-container { 
            display: grid; 
            grid-template-columns: 1fr 2fr; 
            gap: 2rem; 
            margin-top: 2rem;
        }
        .contact-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .info-item i {
            font-size: 2rem;
            color: #0066cc;
        }
        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
        }
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="contact-header">
        <h1><i class="fas fa-envelope"></i> Contactez-nous</h1>
        <p>Nous sommes là pour vous aider</p>
    </div>

    <main class="container">
        <section class="contact-section">

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

            <div class="contact-container">
                <div class="contact-info">
                    <h2 style="margin-bottom: 1.5rem;"><i class="fas fa-info-circle"></i> Nos Coordonnées</h2>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Adresse</h3>
                            <p>Yaoundé, Cameroun</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Téléphone</h3>
                            <p>+237 6XX XXX XXX</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>contact@hotel.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Horaires</h3>
                            <p>Disponible 24/7</p>
                        </div>
                    </div>
                </div>

                <form class="contact-form" method="POST" action="">
                    <h2 style="margin-bottom: 1.5rem;"><i class="fas fa-paper-plane"></i> Envoyez-nous un message</h2>
                    
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Nom complet</label>
                        <input type="text" id="name" name="name" required placeholder="Votre nom complet"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" required placeholder="votre@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject"><i class="fas fa-tag"></i> Sujet</label>
                        <input type="text" id="subject" name="subject" required placeholder="Objet de votre message"
                            value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="message"><i class="fas fa-comment-dots"></i> Message</label>
                        <textarea id="message" name="message" rows="6" placeholder="Votre message..."
                            required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
            </div>
        </section>
    </main>

    <?php require_once 'footer.php'; ?>

    <script src="../js/modal.js"></script>
    <script src="../js/nav.js"></script>
    <script>
        <?php if ($message): ?>
        showSuccess('<?php echo addslashes($message); ?>');
        <?php endif; ?>
        
        <?php if ($error): ?>
        showError('<?php echo addslashes($error); ?>');
        <?php endif; ?>
    </script>
    </body>
    </html>