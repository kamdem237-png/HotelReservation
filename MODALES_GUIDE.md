# 🎨 GUIDE DES MODALES - Système Moderne

## ✅ FICHIERS CRÉÉS

1. **`css/modal.css`** - Styles des modales et toasts
2. **`js/modal.js`** - Système de modales JavaScript

---

## 🚀 UTILISATION

### 1. Inclure dans vos pages HTML

```html
<link rel="stylesheet" href="css/modal.css">
<script src="js/modal.js"></script>
```

### 2. Remplacer les alert()

**❌ Avant** :
```javascript
alert("Opération réussie!");
```

**✅ Après** :
```javascript
Modal.success("Succès", "Opération réussie!");
// ou simplement
showSuccess("Opération réussie!");
```

### 3. Remplacer les confirm()

**❌ Avant** :
```javascript
if (confirm("Êtes-vous sûr?")) {
    // action
}
```

**✅ Après** :
```javascript
Modal.confirm(
    "Confirmation",
    "Êtes-vous sûr de vouloir continuer?",
    () => {
        // action si confirmé
    }
);
```

---

## 📋 MÉTHODES DISPONIBLES

### Modales complètes

```javascript
// Modal de succès
Modal.success("Titre", "Message de succès");

// Modal d'erreur
Modal.error("Erreur", "Message d'erreur");

// Modal d'avertissement
Modal.warning("Attention", "Message d'avertissement");

// Modal d'information
Modal.alert("Info", "Message informatif", "info");

// Modal de confirmation
Modal.confirm("Titre", "Message", onConfirm, onCancel);

// Modal de chargement
Modal.loading("Chargement en cours...");

// Modal prompt (saisie)
Modal.prompt("Titre", "Message", defaultValue, onConfirm);

// Fermer la modale
Modal.close();
```

### Notifications Toast (coins)

```javascript
// Toast succès (vert)
showSuccess("Opération réussie!");

// Toast erreur (rouge)
showError("Une erreur est survenue");

// Toast avertissement (jaune)
showWarning("Attention, vérifiez vos données");

// Toast info (bleu)
showInfo("Information importante");

// Toast personnalisé
showToast("Message", "success", 5000); // type, durée en ms
```

---

## 🎨 TYPES DE MODALES

### 1. Success (Succès)
- Couleur : **Vert**
- Icône : ✓ Check circle
- Usage : Confirmation d'actions réussies

### 2. Error (Erreur)
- Couleur : **Rouge**
- Icône : ⚠ Exclamation circle
- Usage : Erreurs, échecs

### 3. Warning (Avertissement)
- Couleur : **Jaune/Orange**
- Icône : ⚠ Triangle
- Usage : Confirmations, avertissements

### 4. Info (Information)
- Couleur : **Bleu**
- Icône : ℹ Info circle
- Usage : Informations générales

---

## 💡 EXEMPLES PRATIQUES

### Exemple 1 : Suppression avec confirmation

```javascript
document.querySelector('.delete-btn').addEventListener('click', function() {
    Modal.confirm(
        'Supprimer l\'élément',
        'Êtes-vous sûr de vouloir supprimer cet élément ?<br><br>Cette action est irréversible.',
        () => {
            Modal.loading('Suppression en cours...');
            // Soumettre le formulaire
            form.submit();
        }
    );
});
```

### Exemple 2 : Messages PHP en Toast

```javascript
<?php if ($message): ?>
showSuccess('<?php echo addslashes($message); ?>');
<?php endif; ?>

<?php if ($error): ?>
showError('<?php echo addslashes($error); ?>');
<?php endif; ?>
```

### Exemple 3 : Validation avant soumission

```javascript
form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        Modal.error('Erreur de validation', 'Veuillez remplir tous les champs requis.');
        return;
    }
    
    Modal.loading('Envoi en cours...');
    this.submit();
});
```

### Exemple 4 : Demander une saisie

```javascript
Modal.prompt(
    'Nouveau nom',
    'Entrez le nouveau nom :',
    'Valeur par défaut',
    (value) => {
        showSuccess(`Nom changé en : ${value}`);
    }
);
```

---

## 🎯 PAGES DÉJÀ MISES À JOUR

✅ **admin/rooms.php** - Gestion des chambres
- Suppression avec modal de confirmation
- Messages de succès/erreur en toast

✅ **admin/reservations.php** - Gestion des réservations
- Annulation avec modal
- Suppression avec modal
- Toasts pour les feedbacks

---

## 📝 À FAIRE POUR LES AUTRES PAGES

### Pages Admin à mettre à jour :
- [ ] `admin/dashboard.php`
- [ ] `admin/clients.php`
- [ ] `admin/security_dashboard.php`

### Pages Client à mettre à jour :
- [ ] `php/login.php`
- [ ] `php/register.php`
- [ ] `php/contact.php`
- [ ] `php/rooms.php`

---

## 🎨 PERSONNALISATION

### Modifier les couleurs

Dans `css/modal.css`, changez les variables :

```css
.modal-header.success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.modal-header.error {
    background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
}
```

### Modifier la durée des toasts

```javascript
showToast("Message", "success", 5000); // 5 secondes au lieu de 3
```

---

## 🔧 FONCTIONNALITÉS AVANCÉES

### Fermeture au clic sur l'overlay

Les modales se ferment automatiquement si on clique en dehors (sur le fond gris).

### Fermeture avec la touche ESC

Appuyez sur **Échap** pour fermer la modale active.

### Animations fluides

- FadeIn pour l'overlay
- SlideUp pour la modale
- SlideInRight pour les toasts

### Spinner de chargement

```javascript
Modal.loading("Traitement en cours...");
// Affiche un spinner rotatif
```

---

## 📱 RESPONSIVE

Les modales sont **100% responsive** :
- Mobile : 95% de largeur
- Tablette/Desktop : Max 500px
- Toasts adaptés aux petits écrans

---

## ✅ AVANTAGES

1. **Plus modernes** que les alert() natifs
2. **Plus esthétiques** et cohérents avec le design
3. **Plus de contrôle** (callbacks, personnalisation)
4. **Meilleure UX** (animations, fermeture intuitive)
5. **Non-bloquants** pour les toasts
6. **Accessibles** (ESC pour fermer)

---

## 🎉 RÉSULTAT

Au lieu de ça :
```
[Navigateur] ⚠ Êtes-vous sûr?
           [OK] [Annuler]
```

Vous avez ça :
```
╔══════════════════════════════════╗
║  ⚠  Supprimer la chambre      ×  ║
╠══════════════════════════════════╣
║  Êtes-vous sûr de vouloir       ║
║  supprimer la chambre 101 ?     ║
║                                  ║
║  Cette action est irréversible. ║
╠══════════════════════════════════╣
║         [Annuler] [Confirmer]   ║
╚══════════════════════════════════╝
```

---

**Créé le** : 2025-01-01  
**Version** : 1.0.0  
**Auteur** : Système HotelRes
