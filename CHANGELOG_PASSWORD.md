# 🔐 MODIFICATION : VALIDATION DES MOTS DE PASSE

**Date** : 2025-01-01  
**Type** : Assouplissement de sécurité

---

## 📝 CHANGEMENT EFFECTUÉ

### Avant
- **Minimum** : 8 caractères
- **Complexité requise** :
  - Au moins 1 majuscule
  - Au moins 1 minuscule
  - Au moins 1 chiffre
  - Au moins 1 caractère spécial (@$!%*?&)

**Exemple accepté** : `Password123!`  
**Exemple refusé** : `password` (pas de majuscule, pas de chiffre, pas de spécial)

### Maintenant
- **Minimum** : 6 caractères
- **Complexité** : Aucune contrainte
- **Accepte** : N'importe quels caractères

**Exemples acceptés** :
- `123456` ✅
- `azerty` ✅
- `motdepasse` ✅
- `abc123` ✅
- `@#$%^&` ✅

**Exemple refusé** : `12345` (seulement 5 caractères) ❌

---

## 🔧 FICHIERS MODIFIÉS

### 1. `php/Security.php`
```php
// AVANT
public static function validatePassword($password) {
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}

// MAINTENANT
public static function validatePassword($password) {
    return strlen($password) >= 6;
}
```

### 2. `php/config.php`
```php
// AVANT
define('PASSWORD_MIN_LENGTH', 8);

// MAINTENANT
define('PASSWORD_MIN_LENGTH', 6);
```

### 3. `php/register.php`
```php
// AVANT
$error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";

// MAINTENANT
$error = "Le mot de passe doit contenir au moins 6 caractères.";
```

### 4. `php/profile.php`
```php
// AVANT
elseif (strlen($new_password) < 8) {
    $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
}

// MAINTENANT
elseif (strlen($new_password) < 6) {
    $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
}
```

---

## ⚠️ IMPACT SUR LA SÉCURITÉ

### Risques
- ❌ **Mots de passe faibles** possibles (ex: "123456")
- ❌ **Attaques par force brute** plus faciles
- ❌ **Dictionnaire** d'attaque plus efficace

### Atténuation
Les autres mesures de sécurité restent actives :
- ✅ **Rate Limiting** : Max 5 tentatives / 5 minutes
- ✅ **Hachage fort** : password_hash() avec BCRYPT
- ✅ **Sessions sécurisées** : HTTPOnly, SameSite
- ✅ **CSRF Protection** : Tokens sur tous les formulaires
- ✅ **Logs de sécurité** : Surveillance des tentatives

---

## 📊 RECOMMANDATIONS

### Pour les utilisateurs existants
Les mots de passe actuels restent valides (même s'ils font 8+ caractères).

### Pour les nouveaux utilisateurs
Ils peuvent maintenant créer des comptes avec des mots de passe de 6 caractères minimum.

### Bonnes pratiques (recommandées mais non forcées)
1. Utiliser au moins 8 caractères
2. Mélanger majuscules et minuscules
3. Ajouter des chiffres
4. Inclure des caractères spéciaux
5. Ne pas réutiliser le même mot de passe

---

## 🧪 TESTS

### Test 1 : Inscription avec mot de passe faible
```
Email: test@example.com
Password: 123456
Confirm: 123456

✅ Devrait être accepté
```

### Test 2 : Mot de passe trop court
```
Email: test2@example.com
Password: 12345
Confirm: 12345

❌ Devrait être refusé : "Le mot de passe doit contenir au moins 6 caractères."
```

### Test 3 : Changement de mot de passe
```
Profil → Changer mot de passe
Nouveau: azerty
Confirmer: azerty

✅ Devrait être accepté
```

---

## 🔄 POUR REVENIR EN ARRIÈRE

Si vous souhaitez remettre la validation stricte (8 caractères + complexité) :

### 1. Dans `php/Security.php`
```php
public static function validatePassword($password) {
    // Au moins 8 caractères, 1 maj, 1 min, 1 chiffre, 1 spécial
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}
```

### 2. Dans `php/config.php`
```php
define('PASSWORD_MIN_LENGTH', 8);
```

### 3. Dans `php/register.php`
```php
$error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
```

### 4. Dans `php/profile.php`
```php
elseif (strlen($new_password) < 8) {
    $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
}
```

---

## 📝 NOTES

- Cette modification facilite l'inscription pour les utilisateurs
- La sécurité reste assurée par le rate limiting et le hachage
- Pour un environnement de production, considérez maintenir des exigences plus strictes
- Les mots de passe existants (créés avec l'ancienne règle) continuent de fonctionner

---

*Créé le : 2025-01-01*  
*Version : 1.0.0*  
*Statut : ✅ Appliqué*
