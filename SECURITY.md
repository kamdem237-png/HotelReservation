# 🔒 DOCUMENTATION DE SÉCURITÉ - Système de Réservation d'Hôtel

## 📋 Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Mesures de sécurité implémentées](#mesures-de-sécurité-implémentées)
3. [Configuration](#configuration)
4. [Utilisation de la classe Security](#utilisation-de-la-classe-security)
5. [Logs de sécurité](#logs-de-sécurité)
6. [WAF et .htaccess](#waf-et-htaccess)
7. [Checklist de sécurité](#checklist-de-sécurité)
8. [Maintenance et monitoring](#maintenance-et-monitoring)

---

## Vue d'ensemble

Le système de réservation d'hôtel implémente plusieurs couches de sécurité pour protéger:
- Les données des utilisateurs
- Les transactions et réservations
- L'accès administrateur
- L'infrastructure de l'application

---

## Mesures de sécurité implémentées

### ✅ 1. Protection CSRF (Cross-Site Request Forgery)
**Fichiers**: `php/Security.php`, `php/config.php`

- Génération de tokens CSRF uniques par session
- Validation automatique sur tous les formulaires POST
- Régénération après actions sensibles

**Activation**: 
```php
define('ENABLE_CSRF_PROTECTION', true);
```

**Utilisation dans les formulaires**:
```php
<form method="POST">
    <?php echo Security::csrfField(); ?>
    <!-- autres champs -->
</form>
```

**Validation côté serveur**:
```php
if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide');
}
```

---

### ✅ 2. Rate Limiting
**Fichiers**: `php/Security.php`

Protection contre:
- Attaques brute force sur login
- Spam d'inscription
- DoS/DDoS

**Configuration**:
```php
define('MAX_LOGIN_ATTEMPTS', 5);       // 5 tentatives max
define('RATE_LIMIT_WINDOW', 300);      // sur 5 minutes (300 secondes)
```

**Utilisation**:
```php
if (!Security::checkRateLimit('login_' . $email, MAX_LOGIN_ATTEMPTS, RATE_LIMIT_WINDOW)) {
    die('Trop de tentatives');
}
```

**Table créée automatiquement**: `rate_limit`

---

### ✅ 3. Sessions sécurisées
**Fichiers**: `php/Security.php`

- Configuration sécurisée des cookies de session
- HTTPOnly activé (protection XSS)
- SameSite=Strict (protection CSRF)
- Expiration automatique après 1 heure d'inactivité
- Régénération d'ID après login
- Vérification de l'IP pour éviter le hijacking

**Configuration automatique dans** `Security::init()`

---

### ✅ 4. Headers de sécurité HTTP
**Fichiers**: `php/Security.php`, `.htaccess`

Headers envoyés automatiquement:
```
X-Frame-Options: DENY                           → Anti-clickjacking
X-Content-Type-Options: nosniff                 → Anti MIME-sniffing
X-XSS-Protection: 1; mode=block                 → Protection XSS
Content-Security-Policy: ...                     → Contrôle des ressources
Referrer-Policy: strict-origin-when-cross-origin → Protection referer
Permissions-Policy: ...                          → Permissions navigateur
```

---

### ✅ 5. Validation et sanitisation des entrées
**Fichiers**: `php/Security.php`

**Sanitisation**:
```php
$clean = Security::sanitizeInput($input, 'string');  // par défaut
$email = Security::sanitizeInput($input, 'email');
$number = Security::sanitizeInput($input, 'int');
$url = Security::sanitizeInput($input, 'url');
```

**Validation email**:
```php
if (!Security::validateEmail($email)) {
    die('Email invalide');
}
```

**Validation mot de passe** (min 8 car., 1 maj, 1 min, 1 chiffre, 1 spécial):
```php
if (!Security::validatePassword($password)) {
    die('Mot de passe trop faible');
}
```

**Détection SQL Injection**:
```php
if (Security::detectSQLInjection($input)) {
    die('Tentative d\'injection SQL détectée');
}
```

**Détection XSS**:
```php
if (Security::detectXSS($input)) {
    die('Tentative XSS détectée');
}
```

---

### ✅ 6. Gestion des IP bloquées
**Fichiers**: `php/Security.php`

**Bloquer une IP**:
```php
Security::blockIP($ip, 'Trop de tentatives de connexion', 3600); // 1 heure
```

**Vérifier si une IP est bloquée**:
```php
if (Security::isIPBlocked()) {
    die('Votre IP est bloquée');
}
```

**Table créée automatiquement**: `blocked_ips`

---

### ✅ 7. Logs de sécurité
**Fichiers**: `php/Security.php`

Tous les événements de sécurité sont enregistrés:
- Tentatives de connexion (réussies/échouées)
- Dépassement de rate limit
- Détection d'attaques (SQL injection, XSS, CSRF)
- Création de comptes
- Accès non autorisés

**Journaliser un événement**:
```php
Security::logSecurityEvent('LOGIN_FAILED', $ip, ['email' => $email]);
```

**Table créée automatiquement**: `security_logs`

**Colonnes**:
- event_type
- ip_address
- user_agent
- request_uri
- details (JSON)
- created_at

---

### ✅ 8. Authentification et autorisation
**Fichiers**: `php/Security.php`, `php/config.php`

**Exiger l'authentification**:
```php
Security::requireAuth(); // Redirige vers login si non connecté
```

**Exiger les droits admin**:
```php
Security::requireAdmin(); // Redirige si non admin
```

**Vérifications incluses**:
- Session valide
- IP correspondante (anti-hijacking)
- Rôle approprié

---

### ✅ 9. WAF (Web Application Firewall) via .htaccess
**Fichier**: `.htaccess`

**Protections actives**:
- Blocage des User-Agents suspects (bots, scrapers)
- Blocage des injections SQL dans l'URL
- Blocage des tentatives XSS
- Protection contre directory listing
- Limitation de taille des requêtes
- Protection des fichiers sensibles
- Cache et performances

**Fichiers protégés**:
- `.htaccess`, `.htpasswd`
- `config.php`, `Security.php`
- `.ini`, `.log`, `.sh`, `.sql`, `.bak`

---

### ✅ 10. Protection de la base de données
**Fichiers**: `php/config.php`

**Options PDO sécurisées**:
```php
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION     // Gestion erreurs
PDO::ATTR_EMULATE_PREPARES => false             // Vraies prepared statements
PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"  // Encodage sécurisé
```

**Utilisation obligatoire de prepared statements**:
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

---

## Configuration

### Fichier `php/config.php`

```php
// Sécurité
define('ENABLE_CSRF_PROTECTION', true);     // Protection CSRF
define('ENABLE_RATE_LIMITING', true);       // Rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);            // Max tentatives de connexion
define('RATE_LIMIT_WINDOW', 300);           // Fenêtre de temps (secondes)
define('SESSION_LIFETIME', 3600);           // Durée de session (1h)
define('PASSWORD_MIN_LENGTH', 8);           // Longueur min mot de passe

// Base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');                      // ⚠️ À changer en production
define('DB_NAME', 'hotel_db');
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_URL', 'http://localhost/HotelReservation');  // ⚠️ Adapter en prod
define('EMAIL_FROM', 'noreply@hotel.com');
```

---

## Utilisation de la classe Security

### Initialisation automatique
La classe est initialisée automatiquement dans `config.php`:
```php
Security::init($pdo);
```

### Méthodes principales

| Méthode | Description | Retour |
|---------|-------------|---------|
| `generateCSRFToken()` | Génère un token CSRF | string |
| `validateCSRFToken($token)` | Valide un token CSRF | bool |
| `csrfField()` | Champ input CSRF pour formulaires | HTML |
| `checkRateLimit($id, $max, $window)` | Vérifie le rate limit | bool |
| `sanitizeInput($data, $type)` | Nettoie une entrée | string |
| `validateEmail($email)` | Valide un email | bool |
| `validatePassword($pwd)` | Valide un mot de passe fort | bool |
| `detectSQLInjection($input)` | Détecte injection SQL | bool |
| `detectXSS($input)` | Détecte tentative XSS | bool |
| `blockIP($ip, $reason, $duration)` | Bloque une IP | void |
| `isIPBlocked($ip)` | Vérifie si IP bloquée | bool |
| `logSecurityEvent($type, $ip, $details)` | Enregistre un événement | void |
| `requireAuth($redirect)` | Exige authentification | void/redirect |
| `requireAdmin($redirect)` | Exige droits admin | void/redirect |

---

## Logs de sécurité

### Consulter les logs

```sql
-- Derniers événements de sécurité
SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 100;

-- Tentatives de connexion échouées
SELECT * FROM security_logs WHERE event_type = 'LOGIN_FAILED' 
ORDER BY created_at DESC;

-- Attaques détectées
SELECT * FROM security_logs WHERE event_type LIKE '%ATTACK%' 
OR event_type LIKE '%INJECTION%';

-- Statistiques par IP
SELECT ip_address, COUNT(*) as events 
FROM security_logs 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address ORDER BY events DESC;
```

### Événements enregistrés

- `LOGIN_SUCCESS` - Connexion réussie
- `LOGIN_FAILED` - Échec de connexion
- `LOGIN_RATE_LIMIT` - Trop de tentatives
- `ACCOUNT_CREATED` - Nouveau compte
- `ACCOUNT_CREATION_FAILED` - Échec création compte
- `CSRF_ATTACK_DETECTED` - Token CSRF invalide
- `SQL_INJECTION_ATTEMPT` - Tentative d'injection SQL
- `XSS_ATTEMPT` - Tentative XSS
- `SESSION_HIJACKING_ATTEMPT` - Changement d'IP suspect
- `UNAUTHORIZED_ADMIN_ACCESS` - Accès admin non autorisé
- `IP_BLOCKED` - IP bloquée
- `RATE_LIMIT_EXCEEDED` - Rate limit dépassé

---

## WAF et .htaccess

### Règles actives

1. **Blocage User-Agents suspects**
2. **Protection SQL Injection dans URL**
3. **Protection XSS dans URL**
4. **Headers de sécurité**
5. **Protection fichiers sensibles**
6. **Désactivation directory listing**
7. **Limitation taille requêtes**
8. **Cache et performances**

### Activer HTTPS en production

Décommenter dans `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

Et dans `config.php`:
```php
ini_set('session.cookie_secure', 1);  // Cookies uniquement HTTPS
```

---

## Checklist de sécurité

### ✅ Développement
- [x] CSRF protection activée
- [x] Rate limiting activé
- [x] Sessions sécurisées
- [x] Validation des entrées
- [x] Prepared statements
- [x] Logs de sécurité
- [x] WAF .htaccess

### ⚠️ Avant production

- [ ] **Changer les mots de passe DB**
- [ ] **Activer HTTPS**
- [ ] **Mettre à jour SITE_URL**
- [ ] **Configurer emails (noreply@)**
- [ ] **Tester tous les formulaires**
- [ ] **Vérifier les permissions fichiers** (644 pour PHP, 755 pour dossiers)
- [ ] **Désactiver l'affichage des erreurs PHP**:
  ```php
  ini_set('display_errors', 0);
  error_reporting(E_ALL);
  ini_set('log_errors', 1);
  ini_set('error_log', '/path/to/php-errors.log');
  ```
- [ ] **Sauvegardes automatiques de la DB**
- [ ] **Monitoring des logs de sécurité**
- [ ] **SSL/TLS configuré**
- [ ] **Certificat SSL valide**

### 🔍 Tests de sécurité recommandés

1. **Test injection SQL**: Essayer `' OR 1=1--` dans les formulaires
2. **Test XSS**: Essayer `<script>alert('XSS')</script>`
3. **Test CSRF**: Soumettre un formulaire sans token
4. **Test rate limiting**: Faire 10 tentatives de connexion rapides
5. **Test session hijacking**: Changer d'IP en cours de session
6. **Test permissions**: Accéder aux pages admin sans droits

---

## Maintenance et monitoring

### Nettoyage régulier

```sql
-- Nettoyer les anciens logs (>30 jours)
DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Nettoyer le rate limiting (>24h)
DELETE FROM rate_limit WHERE timestamp < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR));

-- Débloquer les IPs expirées
DELETE FROM blocked_ips WHERE blocked_until < NOW();
```

### Script de monitoring (à créer)

```php
// admin/security_monitor.php
requireAdmin();

// IPs bloquées
$blocked = $pdo->query("SELECT * FROM blocked_ips WHERE blocked_until > NOW()")->fetchAll();

// Événements suspects
$suspicious = $pdo->query("
    SELECT event_type, COUNT(*) as count 
    FROM security_logs 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY event_type
")->fetchAll();

// Afficher dashboard
```

### Alertes recommandées

- Email admin si > 10 échecs de connexion en 5min
- Email admin si IP bloquée
- Email admin si tentative d'injection détectée
- Notification si accès admin non autorisé

---

## 🚨 En cas d'incident

1. **Bloquer immédiatement l'IP**:
   ```php
   Security::blockIP('xxx.xxx.xxx.xxx', 'Activité suspecte', 86400); // 24h
   ```

2. **Consulter les logs**:
   ```sql
   SELECT * FROM security_logs WHERE ip_address = 'xxx.xxx.xxx.xxx';
   ```

3. **Analyser l'incident**
4. **Changer les mots de passe si nécessaire**
5. **Mettre à jour les règles de sécurité**
6. **Documenter l'incident**

---

## 📞 Support

Pour toute question de sécurité:
- Consulter la documentation: `SECURITY.md`
- Vérifier les logs: table `security_logs`
- Code source: `php/Security.php`

---

**Dernière mise à jour**: 2025-01-01  
**Version**: 1.0.0  
**Auteur**: Système de Réservation d'Hôtel
