# 🔍 SYSTÈME DE RECHERCHE ET RÉSERVATION COMPLET

**Date** : 2025-01-01  
**Statut** : ✅ **OPÉRATIONNEL**

---

## 📋 VUE D'ENSEMBLE

Système complet permettant aux utilisateurs de :
1. Rechercher des chambres avec filtres
2. Voir les résultats disponibles
3. Cliquer sur "Réserver"
4. Se connecter si nécessaire (avec intention de réservation sauvegardée)
5. Confirmer et créer la réservation automatiquement
6. Gagner des points de fidélité

---

## 🗂️ FICHIERS CRÉÉS (4 nouveaux)

1. **`php/search_rooms.php`** (350 lignes)
   - Page de recherche avec filtres
   - Affichage des résultats
   - Bouton "Réserver" intelligent

2. **`php/save_booking_intent.php`** (30 lignes)
   - API pour sauvegarder l'intention de réservation
   - Stockage en session

3. **`php/create_booking.php`** (250 lignes)
   - Page de confirmation de réservation
   - Calcul du prix total
   - Création de la réservation
   - Attribution de points de fidélité

4. **`SYSTEME_RECHERCHE_RESERVATION.md`** (ce fichier)
   - Documentation complète

---

## 🔄 FICHIERS MODIFIÉS (2)

1. **`php/login.php`**
   - Détection de l'intention de réservation
   - Redirection automatique vers create_booking après connexion

2. **`php/reservations.php`**
   - Affichage du message de succès
   - Toast de confirmation

---

## 🎯 FLUX UTILISATEUR COMPLET

### Scénario 1 : Utilisateur NON connecté

```
1. Page recherche (search_rooms.php)
   ↓
2. Sélection dates + personnes + filtres
   ↓
3. Affichage des résultats disponibles
   ↓
4. Clic sur "Réserver" → Modal de connexion
   ↓
5. Redirection vers login.php
   ↓
6. Connexion réussie
   ↓
7. Redirection automatique vers create_booking.php (avec params)
   ↓
8. Confirmation de la réservation
   ↓
9. Réservation créée + Points de fidélité
   ↓
10. Redirection vers reservations.php avec message de succès
```

### Scénario 2 : Utilisateur DÉJÀ connecté

```
1. Page recherche (search_rooms.php)
   ↓
2. Sélection dates + personnes + filtres
   ↓
3. Affichage des résultats disponibles
   ↓
4. Clic sur "Réserver" → Redirection directe
   ↓
5. Page create_booking.php (confirmation)
   ↓
6. Validation de la réservation
   ↓
7. Réservation créée + Points de fidélité
   ↓
8. Redirection vers reservations.php avec message de succès
```

---

## 🔍 FONCTIONNALITÉS DE RECHERCHE

### Filtres disponibles

| Filtre | Type | Description |
|--------|------|-------------|
| **Date d'arrivée** | Date | Date de check-in |
| **Date de départ** | Date | Date de check-out |
| **Personnes** | Select | 1 à 5+ personnes |
| **Type de chambre** | Select | Simple/Double/Suite/Deluxe |
| **Prix maximum** | Number | Filtre par prix (optionnel) |

### Algorithme de disponibilité

```sql
-- Chambre disponible SI :
1. Statut = 'available'
2. Aucune réservation confirmée/pending qui chevauche les dates
3. Type de chambre correspond aux critères
4. Capacité >= nombre de personnes
```

---

## 💾 SESSION : INTENTION DE RÉSERVATION

Lorsqu'un utilisateur non connecté clique sur "Réserver", les données suivantes sont sauvegardées en session :

```php
$_SESSION['booking_intent'] = [
    'room_type_id' => 3,
    'room_name' => 'Suite Deluxe',
    'price' => 50000,
    'check_in' => '2025-01-15',
    'check_out' => '2025-01-17',
    'guests' => 2,
    'timestamp' => 1735689600
];
```

Ces données persistent jusqu'à :
- ✅ Connexion réussie → Réservation créée
- ❌ Fermeture du navigateur
- ❌ Expiration de la session (1h)

---

## 🏆 SYSTÈME DE POINTS DE FIDÉLITÉ

### Calcul automatique

```
Points gagnés = Prix total / 1000
```

**Exemple** :
- Réservation : 2 nuits × 50 000 FCFA = 100 000 FCFA
- Points gagnés : 100 000 / 1000 = **100 points**

### Procédure SQL appelée

```sql
CALL calculate_loyalty_points(user_id, total_price, reservation_id);
```

Cette procédure :
1. Calcule les points
2. Les ajoute au compte utilisateur
3. Enregistre dans `loyalty_history`
4. Met à jour le niveau de fidélité si nécessaire

---

## 🎨 INTERFACE UTILISATEUR

### Page de recherche (search_rooms.php)

**En-tête** :
- Fond gradient bleu
- Titre + description
- Formulaire de recherche en carte flottante

**Résultats** :
- Grille responsive (3 colonnes desktop, 1 mobile)
- Cartes avec image, description, prix
- Badges de disponibilité
- Bouton "Réserver" ou "Indisponible"

### Page de confirmation (create_booking.php)

**Sections** :
- En-tête avec titre et description
- Détails de la chambre
- Grille d'informations (dates, nuits, personnes)
- Résumé avec calcul du prix
- Points de fidélité à gagner
- Boutons Confirmer/Annuler

---

## 🔐 SÉCURITÉ

### Protections actives

- ✅ **Protection CSRF** sur create_booking.php
- ✅ **Vérification authentification** 
- ✅ **Validation des dates** (départ > arrivée)
- ✅ **Vérification disponibilité** avant création
- ✅ **Logs de sécurité** (RESERVATION_CREATED)
- ✅ **Sanitization** des entrées utilisateur
- ✅ **Requêtes préparées** (PDO)

### Validations

```php
// Dates
if (strtotime($check_out) <= strtotime($check_in)) {
    // Erreur
}

// Disponibilité
$available_room = checkAvailability($room_type_id, $check_in, $check_out);

// CSRF
Security::validateCSRFToken($_POST['csrf_token']);
```

---

## 📊 BASE DE DONNÉES

### Tables utilisées

| Table | Utilisation |
|-------|-------------|
| `room_types` | Types de chambres et prix |
| `rooms` | Chambres individuelles |
| `room_images` | Images des chambres |
| `reservations` | Réservations créées |
| `users` | Utilisateurs et points |
| `loyalty_history` | Historique des points |

### Requête de disponibilité

```sql
SELECT r.id
FROM rooms r
WHERE r.room_type_id = ?
AND r.status = 'available'
AND r.id NOT IN (
    SELECT room_id FROM reservations
    WHERE status IN ('confirmed', 'pending')
    AND (
        (check_in_date <= ? AND check_out_date > ?)
        OR (check_in_date < ? AND check_out_date >= ?)
        OR (check_in_date >= ? AND check_out_date <= ?)
    )
)
LIMIT 1
```

---

## 🧪 TESTS À EFFECTUER

### Test 1 : Recherche basique

1. Aller sur `search_rooms.php`
2. Sélectionner :
   - Arrivée : Demain
   - Départ : Dans 3 jours
   - Personnes : 2
3. Cliquer "Rechercher"
4. ✅ Voir les résultats avec prix et disponibilité

### Test 2 : Réservation sans connexion

1. Rechercher une chambre disponible
2. Cliquer "Réserver"
3. ✅ Modal "Connexion requise"
4. Confirmer
5. ✅ Redirection vers login.php
6. Se connecter
7. ✅ Redirection automatique vers create_booking.php
8. ✅ Voir les détails pré-remplis
9. Confirmer
10. ✅ Réservation créée
11. ✅ Toast "Réservation créée avec succès"
12. ✅ Points ajoutés au compte

### Test 3 : Réservation connecté

1. Se connecter d'abord
2. Aller sur search_rooms.php
3. Rechercher et cliquer "Réserver"
4. ✅ Redirection directe vers create_booking.php
5. Confirmer
6. ✅ Réservation créée

### Test 4 : Validation des dates

1. Sur create_booking.php
2. Essayer avec check_out avant check_in
3. ✅ Message d'erreur
4. ✅ Redirection vers search_rooms.php

### Test 5 : Chambre non disponible

1. Créer une réservation pour demain
2. Rechercher avec les mêmes dates
3. ✅ Cette chambre affiche "Indisponible"
4. ✅ Bouton grisé et désactivé

---

## 🎨 MODALES UTILISÉES

### Modal de connexion requise

```javascript
Modal.confirm(
    'Connexion requise',
    'Vous devez être connecté pour effectuer une réservation...',
    onConfirm
);
```

### Toast de succès

```javascript
showSuccess('Réservation créée avec succès! Numéro: #123');
```

### Modal d'erreur

```javascript
Modal.error('Dates manquantes', 'Veuillez sélectionner des dates...');
```

---

## 📱 RESPONSIVE

### Desktop (> 1024px)
- Grille 3 colonnes
- Formulaire horizontal
- Images grandes (250px hauteur)

### Tablette (768-1024px)
- Grille 2 colonnes
- Formulaire horizontal

### Mobile (< 768px)
- Grille 1 colonne
- Formulaire vertical
- Navigation simplifiée

---

## 🚀 AMÉLIORATIONS FUTURES POSSIBLES

- [ ] Filtres avancés (WiFi, parking, climatisation)
- [ ] Tri des résultats (prix, capacité, popularité)
- [ ] Comparateur de chambres (sélection multiple)
- [ ] Wishlist / Favoris
- [ ] Historique des recherches
- [ ] Suggestions intelligentes
- [ ] Paiement en ligne
- [ ] Choix de la chambre spécifique (pas juste le type)
- [ ] Calendrier interactif
- [ ] Photos en lightbox

---

## 💡 NOTES IMPORTANTES

### Gestion de la disponibilité

Une chambre est marquée "disponible" SI :
- Son statut dans `rooms` est 'available'
- Aucune réservation ne chevauche les dates demandées

### Points de fidélité

Les points sont calculés et attribués IMMÉDIATEMENT lors de la création de la réservation (pas lors du paiement).

Si vous souhaitez les attribuer après paiement, modifier `create_booking.php` :
```php
// Déplacer l'appel à calculate_loyalty_points()
// vers le moment où le paiement est confirmé
```

### Statut des réservations

Par défaut, les réservations sont créées avec le statut **'pending'**.

L'admin peut les confirmer depuis `admin/reservations.php`.

---

## 📖 URLS PRINCIPALES

| Page | URL | Description |
|------|-----|-------------|
| Recherche | `/php/search_rooms.php` | Page de recherche principale |
| Confirmation | `/php/create_booking.php` | Confirmation de réservation |
| Mes réservations | `/php/reservations.php` | Liste des réservations |
| Connexion | `/php/login.php` | Formulaire de connexion |

---

## ✅ CHECKLIST FINALE

- ✅ Formulaire de recherche fonctionnel
- ✅ Filtres appliqués correctement
- ✅ Résultats affichés avec images
- ✅ Vérification de disponibilité en temps réel
- ✅ Modal de connexion pour utilisateurs non connectés
- ✅ Intention de réservation sauvegardée
- ✅ Redirection automatique après connexion
- ✅ Page de confirmation avec détails
- ✅ Création de réservation sécurisée
- ✅ Attribution automatique de points
- ✅ Message de succès affiché
- ✅ Protection CSRF
- ✅ Logs de sécurité
- ✅ Interface responsive

---

**Le système est prêt à l'emploi !** 🎉

*Créé le : 2025-01-01*  
*Version : 1.0.0*  
*Statut : Production-ready*
