# 💰 CONVERSION EN FRANC CFA (XAF)

## ✅ CONVERSION EFFECTUÉE

Tous les prix de l'application ont été convertis de **EUR (€)** vers **XAF (Franc CFA)**.

---

## 📊 TAUX DE CHANGE

**Taux fixe utilisé** : `1 EUR = 656 FCFA`

Ce taux est défini dans `php/currency_helper.php` :
```php
define('EUR_TO_FCFA', 656);
```

### Pourquoi 656 ?

Le taux officiel est environ **655.957 FCFA = 1 EUR** (taux fixe XOF/XAF).  
Arrondi à **656** pour simplifier les calculs.

---

## 🔧 FICHIERS MODIFIÉS

### 1. Nouveau fichier créé
- **`php/currency_helper.php`** - Fonctions de conversion et formatage

### 2. Fichiers mis à jour (8 fichiers)

#### Admin
- ✅ `admin/dashboard.php` - Revenus, statistiques
- ✅ `admin/rooms.php` - Prix des chambres
- ✅ `admin/reservations.php` - Prix des réservations
- ✅ `admin/clients.php` - Dépenses totales

#### Client
- ✅ `php/rooms.php` - Affichage prix des chambres
- ✅ `php/reservations.php` - Prix des réservations

#### Configuration
- ✅ `php/config.php` - Inclusion du helper
- ✅ `CONVERSION_FCFA.md` - Cette documentation

---

## 📖 FONCTIONS DISPONIBLES

### 1. `convertToFCFA($amountEUR)`
Convertit un montant EUR en FCFA.
```php
$priceEUR = 100;
$priceFCFA = convertToFCFA($priceEUR);
// Résultat : 65600
```

### 2. `formatPriceFCFA($amount, $convertFromEUR = false)`
Formate un prix en FCFA avec séparateurs.
```php
// Avec conversion
echo formatPriceFCFA(100, true);
// Affiche : 65 600 FCFA

// Sans conversion (déjà en FCFA)
echo formatPriceFCFA(65600);
// Affiche : 65 600 FCFA
```

### 3. `displayPrice($amount)`
Affiche un prix déjà en FCFA.
```php
echo displayPrice(65600);
// Affiche : 65 600 FCFA
```

### 4. `priceForDB($displayPrice)`
Nettoie un prix pour l'insertion en base de données.
```php
$clean = priceForDB("65 600 FCFA");
// Résultat : 65600 (float)
```

---

## 💡 UTILISATION

### Dans vos pages PHP

```php
// Inclure le helper (déjà fait dans config.php)
require_once 'php/currency_helper.php';

// Afficher un prix (conversion automatique depuis EUR)
<?php echo formatPriceFCFA($room['price_per_night'], true); ?>

// Afficher un prix déjà en FCFA
<?php echo displayPrice($totalFCFA); ?>
```

---

## 📝 EXEMPLES DE CONVERSION

| Prix EUR | Prix FCFA | Affichage |
|----------|-----------|-----------|
| 50 € | 32 800 FCFA | 32 800 FCFA |
| 100 € | 65 600 FCFA | 65 600 FCFA |
| 150 € | 98 400 FCFA | 98 400 FCFA |
| 250 € | 164 000 FCFA | 164 000 FCFA |
| 500 € | 328 000 FCFA | 328 000 FCFA |

---

## 🗄️ BASE DE DONNÉES

### Option 1 : Conversion à l'affichage (Recommandé)

**État actuel** : Les prix restent en EUR dans la base de données, conversion au moment de l'affichage.

**Avantages** :
- Facile à changer de devise
- Pas de migration de données nécessaire
- Taux de change modifiable

**Utilisation** :
```php
// Toujours utiliser le paramètre true pour convertir
echo formatPriceFCFA($room['price_per_night'], true);
```

### Option 2 : Conversion permanente en base de données

Si vous voulez stocker directement en FCFA :

```sql
-- Convertir tous les prix (ATTENTION: Irréversible sans backup!)
UPDATE room_types SET price_per_night = price_per_night * 656;
UPDATE reservations SET total_price = total_price * 656;
```

Puis utiliser sans conversion :
```php
echo formatPriceFCFA($room['price_per_night']); // false par défaut
```

---

## 🔄 CHANGER LE TAUX DE CHANGE

Dans `php/currency_helper.php`, modifiez :

```php
// Taux actuel
define('EUR_TO_FCFA', 656);

// Pour changer
define('EUR_TO_FCFA', 660); // Nouveau taux
```

---

## 🎨 FORMAT D'AFFICHAGE

### Formatage actuel
```
65 600 FCFA
```

- Séparateur de milliers : **espace**
- Pas de décimales (FCFA ne se divise pas)
- Suffixe : **FCFA**

### Personnaliser le format

Dans `php/currency_helper.php`, modifiez la fonction `formatPriceFCFA()` :

```php
// Format actuel
return number_format($amount, 0, ',', ' ') . ' FCFA';

// Autres formats possibles
return number_format($amount, 0, ',', ' ') . ' F CFA'; // avec espace
return 'FCFA ' . number_format($amount, 0, ',', ' '); // préfixe
return number_format($amount, 0, ',', '.') . ' FCFA'; // point comme séparateur
```

---

## 📱 AFFICHAGE PAR PAGE

### Interface Admin

**Dashboard**
- ✅ Revenus du mois : **FCFA**
- ✅ Prix des réservations : **FCFA**
- ✅ Statistiques mensuelles : **FCFA**

**Chambres**
- ✅ Prix par nuit : **FCFA**
- ✅ Sélection type de chambre : **FCFA/nuit**

**Réservations**
- ✅ Prix total : **FCFA**

**Clients**
- ✅ Dépenses totales : **FCFA**

### Interface Client

**Chambres**
- ✅ Prix par nuit : **FCFA**

**Réservations**
- ✅ Prix total : **FCFA**

---

## 🧪 TESTS

### Vérifier la conversion

```php
// Test 1: Conversion simple
$test = convertToFCFA(100);
echo $test; // Devrait afficher: 65600

// Test 2: Formatage
$test = formatPriceFCFA(100, true);
echo $test; // Devrait afficher: 65 600 FCFA

// Test 3: Sans conversion
$test = formatPriceFCFA(65600);
echo $test; // Devrait afficher: 65 600 FCFA
```

### Vérifier l'affichage

1. Allez sur **Dashboard Admin**
2. Vérifiez que les revenus sont en **FCFA**
3. Allez sur **Chambres**
4. Vérifiez que les prix sont en **FCFA**
5. Allez sur la page client **Chambres**
6. Vérifiez que les prix sont en **FCFA**

---

## ⚠️ NOTES IMPORTANTES

1. **Conversion à l'affichage** : Les prix en base de données restent en EUR (par défaut)
2. **Paramètre `true`** : Toujours mettre `true` pour convertir depuis EUR
3. **Taux fixe** : Le taux 656 est fixe (pas de mise à jour automatique)
4. **Pas de décimales** : FCFA s'affiche sans centimes

---

## 🔮 AMÉLIORATIONS FUTURES

- [ ] Support multi-devises (EUR, USD, FCFA)
- [ ] Taux de change dynamique (API)
- [ ] Sélection de devise par utilisateur
- [ ] Historique des taux de change
- [ ] Conversion automatique à la réservation

---

## 📞 SUPPORT

Pour toute question sur la conversion :
- Documentation : `CONVERSION_FCFA.md`
- Code source : `php/currency_helper.php`
- Configuration : `php/config.php`

---

**Créé le** : 2025-01-01  
**Version** : 1.0.0  
**Taux appliqué** : 1 EUR = 656 FCFA
