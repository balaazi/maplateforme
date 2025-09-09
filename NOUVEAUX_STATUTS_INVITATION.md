# 🎯 Nouveaux Statuts d'Invitation - EventHub

## 📋 **Vue d'ensemble des Statuts**

Le système EventHub dispose maintenant de **5 statuts d'invitation** pour une gestion complète des participations aux événements.

## 🚀 **Statuts Disponibles**

### 1. **PENDING** - En attente
- **Valeur** : `'pending'`
- **Description** : Invitation envoyée, en attente de réponse
- **Action possible** : Accepter, Refuser, Marquer comme expiré
- **Couleur** : Jaune (warning)

### 2. **ACCEPTED** - Acceptée
- **Valeur** : `'accepted'`
- **Description** : Invitation acceptée par le participant
- **Action possible** : Aucune (statut final)
- **Couleur** : Vert (success)
- **Participation** : ✅ Permise

### 3. **DECLINED** - Refusée
- **Valeur** : `'declined'`
- **Description** : Invitation refusée par le participant
- **Action possible** : Aucune (statut final)
- **Couleur** : Rouge (danger)
- **Participation** : ❌ Non permise

### 4. **EXPIRED** - Expirée
- **Valeur** : `'expired'`
- **Description** : Invitation expirée automatiquement après 30 jours
- **Action possible** : Aucune (statut final)
- **Couleur** : Gris (secondary)
- **Participation** : ❌ Non permise

### 5. **CONFLICT** - Conflit Horaire ⭐ **NOUVEAU**
- **Valeur** : `'conflict'`
- **Description** : Conflit d'horaires détecté lors de l'acceptation
- **Action possible** : Aucune (statut final)
- **Couleur** : Orange (warning)
- **Participation** : ❌ Bloquée par conflit

## 🔧 **Implémentation Technique**

### **Enum InvitationStatus**
```php
enum InvitationStatus: string {
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';
    case CONFLICT = 'conflict';
}
```

### **Service InvitationStatusService**
- Gestion centralisée des statuts
- Conversion texte lisible
- Classes CSS pour l'affichage
- Validation des statuts

### **Gestion des Conflits**
- Détection automatique via `ScheduleConflictService`
- Blocage de l'acceptation en cas de conflit
- Redirection vers page de conflit
- Logs détaillés des conflits

## 📊 **Flux de Gestion des Statuts**

```
Invitation → PENDING → ACCEPTED/DECLINED
     ↓
  30 jours → EXPIRED
     ↓
Conflit détecté → CONFLICT (bloque l'acceptation)
```

## 🎨 **Affichage dans l'Interface**

### **Badges de Statut**
- **Acceptée** : 🟢 `bg-success`
- **Refusée** : 🔴 `bg-danger`
- **En attente** : 🟡 `bg-warning`
- **Expirée** : ⚫ `bg-secondary`
- **Conflit horaire** : 🟠 `bg-warning`

### **Templates Mise à Jour**
- `invitation/response.html.twig` : Affichage des statuts
- `invitation/conflict.html.twig` : Page de conflit
- Tous les contrôleurs utilisent l'enum

## 🔄 **Migration et Compatibilité**

### **Changements Effectués**
1. ✅ Ajout du statut `CONFLICT` à l'enum
2. ✅ Mise à jour de tous les contrôleurs
3. ✅ Création du service `InvitationStatusService`
4. ✅ Mise à jour des templates
5. ✅ Gestion des conflits horaires

### **Compatibilité**
- Les anciens statuts (`'accepté'`, `'refusé'`, `'en_attente'`) sont remplacés
- Migration automatique des données existantes
- Aucune perte de données

## 🚀 **Avantages des Nouveaux Statuts**

1. **Gestion des Conflits** : Empêche les doubles réservations
2. **Statuts Standardisés** : Utilisation d'enums PHP 8.1+
3. **Interface Cohérente** : Badges colorés et lisibles
4. **Logs Détaillés** : Traçabilité complète des actions
5. **Expiration Automatique** : Gestion des invitations obsolètes

## 📝 **Utilisation dans le Code**

### **Vérification de Statut**
```php
use App\Enum\InvitationStatus;

if ($participation->getInvitationStatus() === InvitationStatus::ACCEPTED->value) {
    // Participation confirmée
}
```

### **Service de Statut**
```php
$statusText = $this->invitationStatusService->getStatusText($status);
$statusClass = $this->invitationStatusService->getStatusClass($status);
```

## 🔮 **Évolutions Futures**

- **Statuts personnalisés** par type d'événement
- **Notifications automatiques** selon le statut
- **Rapports avancés** de gestion des invitations
- **Workflow d'approbation** pour certains événements

---

**Date de mise à jour** : 18/08/2025  
**Version** : 2.0.0  
**Statut** : ✅ Implémenté et testé
