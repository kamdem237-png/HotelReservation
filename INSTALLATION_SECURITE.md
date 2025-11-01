# 🚀 GUIDE D'INSTALLATION DE LA SÉCURITÉ

## ✅ ÉTAPE 1 : Créer les tables de sécurité

### Via phpMyAdmin
1. Ouvrez phpMyAdmin : `http://localhost/phpmyadmin`
2. Sélectionnez la base de données `hotel_db`
3. Allez dans l'onglet "SQL"
4. Copiez et exécutez le contenu du fichier : `sql/security_tables.sql`
5. Vérifiez que les tables ont été créées :
   - `security_logs`
   - `rate_limit`
   - `blocked_ips`

### Via ligne de commande
```bash
cd c:\xampp\htdocs\HotelReservation
mysql -u root -p hotel_db < sql/security_tables.sql
```

---

## ✅ ÉTAPE 2 : Activer les modules Apache nécessaires

### Ouvrez le fichier httpd.conf
Chemin: `C:\xampp\apache\conf\httpd.conf`

### Décommentez ces lignes (retirez le #)
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
```

### Redémarrez Apache
Dans le panneau de contrôle XAMPP, cliquez sur "Stop" puis "Start" pour Apache.

---

## ✅ ÉTAPE 3 : Vérifier le fichier .htaccess

Le fichier `.htaccess` a été créé à la racine du projet avec toutes les règles de sécurité.

**Vérification :**
- Le fichier existe : `C:\xampp\htdocs\HotelReservation\.htaccess`
- Il contient les règles WAF
- Il n'y a pas de fichier .htaccess conflictuel dans les sous-dossiers

---

## ✅ ÉTAPE 4 : Tester la sécurité

### Test 1 : Protection CSRF
1. Ouvrez : `http://localhost/HotelReservation/php/login.php`
2. Inspectez le formulaire (F12 > Elements)
3. Cherchez le champ caché : `<input type="hidden" name="csrf_token" value="...">`
4. ✅ Si présent, CSRF activé

### Test 2 : Rate Limiting
1. Allez sur : `http://localhost/HotelReservation/php/login.php`
2. Essayez de vous connecter 6 fois avec un mauvais mot de passe
3. ✅ À la 6ème tentative, vous devriez voir : "Trop de tentatives de connexion"

### Test 3 : Validation mot de passe fort
1. Allez sur : `http://localhost/HotelReservation/php/register.php`
2. Essayez de créer un compte avec le mot de passe : `test123`
3. ✅ Vous devriez voir une erreur demandant un mot de passe plus fort

### Test 4 : Headers de sécurité
1. Ouvrez n'importe quelle page du site
2. F12 > Network > Rechargez la page
3. Cliquez sur la requête principale
4. Onglet "Headers" > "Response Headers"
5. ✅ Vérifiez la présence de :
   - `X-Frame-Options: DENY`
   - `X-XSS-Protection: 1; mode=block`
   - `Content-Security-Policy: ...`

### Test 5 : Dashboard de sécurité
1. Créez un utilisateur admin dans la base de données :
```sql
UPDATE users SET role = 'admin' WHERE email = 'votre-email@test.com';
```
2. Connectez-vous avec ce compte
3. Accédez à : `http://localhost/HotelReservation/admin/security_dashboard.php`
4. ✅ Vous devriez voir le dashboard avec statistiques et logs

---

## ✅ ÉTAPE 5 : Vérifier les logs de sécurité

### Via le dashboard admin
`http://localhost/HotelReservation/admin/security_dashboard.php`

### Via phpMyAdmin
```sql
SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 20;
```

Vous devriez voir des événements comme :
- `LOGIN_FAILED` si vous avez testé le rate limiting
- `ACCOUNT_CREATED` si vous avez créé un compte
- `LOGIN_SUCCESS` après connexion réussie

---

## ✅ ÉTAPE 6 : Configuration personnalisée (optionnel)

### Modifier les limites dans `php/config.php`

```php
// Modifier ces valeurs selon vos besoins
define('MAX_LOGIN_ATTEMPTS', 5);        // Nombre de tentatives autorisées
define('RATE_LIMIT_WINDOW', 300);       // Fenêtre de temps (5 min)
define('SESSION_LIFETIME', 3600);       // Durée session (1 heure)
define('PASSWORD_MIN_LENGTH', 8);       // Longueur min mot de passe
```

---

## 🎯 CHECKLIST FINALE

- [ ] Tables de sécurité créées dans la DB
- [ ] Modules Apache activés (rewrite, headers, expires)
- [ ] Apache redémarré
- [ ] Fichier .htaccess présent et actif
- [ ] Test CSRF réussi (token présent dans formulaires)
- [ ] Test Rate Limiting réussi (blocage après 5 tentatives)
- [ ] Test validation password réussi (refus password faible)
- [ ] Headers de sécurité présents (vérifiés dans F12)
- [ ] Dashboard admin accessible
- [ ] Logs enregistrés dans security_logs
- [ ] Aucune erreur PHP visible

---

## 🐛 DÉPANNAGE

### Problème : .htaccess ne fonctionne pas

**Solution 1 :** Vérifier AllowOverride
```apache
# Dans httpd.conf, cherchez :
<Directory "C:/xampp/htdocs">
    AllowOverride All  # Doit être "All" et non "None"
</Directory>
```

**Solution 2 :** Vérifier mod_rewrite
```apache
# Dans httpd.conf, décommentez :
LoadModule rewrite_module modules/mod_rewrite.so
```

### Problème : Erreur "Class 'Security' not found"

**Solution :** Vérifier que `php/Security.php` existe et est chargé dans `php/config.php`
```php
require_once __DIR__ . '/Security.php';
```

### Problème : Tables n'existent pas

**Solution :** Exécuter manuellement le SQL
```sql
-- Se connecter à MySQL
mysql -u root -p

-- Sélectionner la DB
USE hotel_db;

-- Créer les tables
source C:/xampp/htdocs/HotelReservation/sql/security_tables.sql
```

### Problème : Headers de sécurité non envoyés

**Solution :** Activer mod_headers
```apache
# Dans httpd.conf
LoadModule headers_module modules/mod_headers.so
```

### Problème : Session expirée trop vite

**Solution :** Augmenter SESSION_LIFETIME dans `php/config.php`
```php
define('SESSION_LIFETIME', 7200); // 2 heures au lieu de 1
```

---

## 📊 VÉRIFICATION FINALE - REQUÊTES SQL UTILES

### Voir tous les événements de sécurité
```sql
SELECT event_type, COUNT(*) as count 
FROM security_logs 
GROUP BY event_type 
ORDER BY count DESC;
```

### Voir les derniers logs
```sql
SELECT * FROM security_logs 
ORDER BY created_at DESC 
LIMIT 20;
```

### Voir les IPs bloquées
```sql
SELECT * FROM blocked_ips 
WHERE blocked_until > NOW();
```

### Statistiques des 24 dernières heures
```sql
SELECT 
    event_type,
    COUNT(*) as events,
    COUNT(DISTINCT ip_address) as unique_ips
FROM security_logs
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY event_type;
```

---

## 🎓 PROCHAINES ÉTAPES RECOMMANDÉES

1. **Créer un compte admin**
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'admin@hotel.com';
   ```

2. **Tester tous les formulaires**
   - Login avec bon/mauvais identifiants
   - Inscription avec password fort/faible
   - Rate limiting sur login

3. **Monitorer le dashboard**
   - Ouvrir `admin/security_dashboard.php`
   - Vérifier les logs en temps réel
   - S'assurer qu'aucune IP légitime n'est bloquée

4. **Lire la documentation**
   - `SECURITY.md` - Documentation complète
   - `SECURITY_SUMMARY.md` - Résumé des mesures

5. **Configurer pour la production** (plus tard)
   - Activer HTTPS
   - Changer password DB
   - Désactiver display_errors
   - Configurer backups

---

## 📞 SUPPORT

### Documentation disponible
- **SECURITY.md** - Documentation complète (50+ pages)
- **SECURITY_SUMMARY.md** - Résumé et checklist
- **INSTALLATION_SECURITE.md** - Ce fichier

### Fichiers de sécurité
- **php/Security.php** - Code source (500+ lignes)
- **.htaccess** - Règles WAF Apache
- **sql/security_tables.sql** - Tables et procédures
- **admin/security_dashboard.php** - Interface de monitoring

### En cas de problème
1. Vérifier les logs PHP : `C:\xampp\php\logs\php_error_log`
2. Vérifier les logs Apache : `C:\xampp\apache\logs\error.log`
3. Consulter les logs de sécurité : table `security_logs`

---

## ✅ CONFIRMATION D'INSTALLATION RÉUSSIE

Si tous ces points sont vérifiés, votre sécurité est complète :

✅ CSRF Protection active  
✅ Rate Limiting fonctionnel  
✅ Sessions sécurisées  
✅ Headers HTTP de sécurité  
✅ WAF Apache actif  
✅ Validation des entrées renforcée  
✅ Logs de sécurité enregistrés  
✅ IP Blocking opérationnel  
✅ Dashboard admin accessible  
✅ Aucune erreur détectée  

**🎉 FÉLICITATIONS ! Votre système est sécurisé.**

---

*Dernière mise à jour: 2025-01-01*  
*Version: 1.0.0*
