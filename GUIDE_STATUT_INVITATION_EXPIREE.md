# Guide : Statut d'Invitation Expirée

## Vue d'ensemble

Le système EventHub dispose maintenant d'un nouveau statut **"EXPIRÉ"** pour les invitations qui n'ont pas reçu de réponse dans un délai défini.

## Nouveaux Statuts d'Invitation

### Statuts Disponibles

1. **EN ATTENTE** (`pending`) - Invitation envoyée, en attente de réponse
2. **ACCEPTÉE** (`accepted`) - Invitation acceptée par le participant
3. **REFUSÉE** (`declined`) - Invitation refusée par le participant
4. **EXPIRÉE** (`expired`) - **NOUVEAU** - Invitation expirée (délai dépassé)

## Fonctionnalités Implémentées

### 1. Enum InvitationStatus
- Ajout du cas `EXPIRED = 'expired'`
- Validation des statuts dans l'entité

### 2. Entité Invitation
- Constante `STATUS_EXPIRED`
- Méthode `isExpired()` pour vérifier le statut
- Validation mise à jour dans `setStatus()`

### 3. Formulaire de Réponse
- Option "Marquer comme expiré" dans le formulaire de réponse
- Gestion du nouveau statut

### 4. Templates d'Affichage
- Badge gris avec icône d'horloge pour les invitations expirées
- Affichage dans les listes et tableaux d'invitations
- Template dédié `expired.html.twig`

### 5. Service d'Expiration Automatique
- `InvitationExpirationService` pour gérer l'expiration
- Expiration automatique après un délai configurable (défaut: 30 jours)
- Logging des actions d'expiration

### 6. Repository
- Méthode `findExpiredInvitations()` pour récupérer les invitations expirées
- Requête optimisée avec Doctrine

### 7. Commande Console
- `app:expire-invitations` pour exécuter l'expiration manuellement
- Option `--days` pour configurer le délai d'expiration

## Utilisation

### Expiration Automatique

#### Via la Commande Console
```bash
# Expiration par défaut (30 jours)
php bin/console app:expire-invitations

# Expiration personnalisée (15 jours)
php bin/console app:expire-invitations --days=15

# Expiration rapide (7 jours)
php bin/console app:expire-invitations -d 7
```

#### Via le Fichier Batch (Windows)
```batch
# Double-cliquer sur expire_invitations.bat
# Ou exécuter en ligne de commande
expire_invitations.bat
```

### Expiration Manuelle

#### Via le Service
```php
use App\Service\InvitationExpirationService;

// Dans un contrôleur ou service
$expirationService->expireInvitation($invitation);
```

#### Via le Formulaire
- L'utilisateur peut marquer manuellement une invitation comme expirée
- Option disponible dans le formulaire de réponse

## Configuration

### Délai d'Expiration
- **Défaut** : 30 jours
- **Configurable** : via l'option `--days` de la commande
- **Personnalisable** : dans le service `InvitationExpirationService`

### Logging
- Toutes les actions d'expiration sont loggées
- Informations tracées :
  - ID de l'invitation
  - Email du participant
  - Titre de l'événement
  - Date d'expiration

## Interface Utilisateur

### Affichage des Statuts
- **EN ATTENTE** : Badge orange avec icône d'horloge
- **ACCEPTÉE** : Badge vert avec icône de validation
- **REFUSÉE** : Badge rouge avec icône de refus
- **EXPIRÉE** : Badge gris avec icône d'horloge

### Template d'Invitation Expirée
- Page dédiée pour les invitations expirées
- Informations détaillées sur l'invitation et l'événement
- Actions disponibles (retour accueil, voir événement)
- Message explicatif sur l'expiration

## Intégration avec le Système Existant

### Compatibilité
- ✅ Compatible avec les invitations existantes
- ✅ Pas de modification des données existantes
- ✅ Rétrocompatible avec l'ancien système

### Relations
- **Invitation** → **Event** : Maintien de la relation
- **Invitation** → **Participant** : Maintien de la relation
- **Participation** : Pas d'impact sur les participations existantes

## Maintenance et Surveillance

### Vérifications Recommandées
- Exécuter la commande d'expiration régulièrement
- Surveiller les logs d'expiration
- Vérifier les statistiques d'invitations par statut

### Planification Automatique
- **Windows** : Utiliser le Planificateur de tâches avec `expire_invitations.bat`
- **Linux/Unix** : Utiliser cron avec la commande Symfony
- **Fréquence recommandée** : Quotidienne ou hebdomadaire

## Exemples d'Utilisation

### Scénario 1 : Expiration Automatique Quotidienne
```batch
# Créer une tâche planifiée Windows
schtasks /create /tn "Expiration Invitations" /tr "C:\path\to\expire_invitations.bat" /sc daily /st 02:00
```

### Scénario 2 : Expiration avec Délai Personnalisé
```bash
# Expirer les invitations après 15 jours
php bin/console app:expire-invitations --days=15
```

### Scénario 3 : Vérification d'Expiration
```php
// Dans un contrôleur
if ($expirationService->isInvitationExpired($invitation, 15)) {
    // L'invitation est expirée après 15 jours
    $expirationService->expireInvitation($invitation);
}
```

## Dépannage

### Erreurs Courantes
1. **Namespace Error** : Vérifier qu'il n'y a pas de lignes vides avant `<?php`
2. **Permission Denied** : Vérifier les droits d'exécution du fichier batch
3. **Database Error** : Vérifier la connexion à la base de données

### Logs de Debug
- Vérifier les logs Symfony dans `var/log/`
- Utiliser le mode debug pour plus de détails
- Surveiller les erreurs de base de données

## Évolutions Futures

### Fonctionnalités Prévues
- [ ] Configuration via fichier YAML
- [ ] Notifications automatiques aux organisateurs
- [ ] Statistiques d'expiration avancées
- [ ] Interface d'administration pour la gestion des délais

### Améliorations Techniques
- [ ] Cache pour les requêtes d'expiration
- [ ] Traitement par lots pour de gros volumes
- [ ] API REST pour l'expiration
- [ ] Webhook pour les notifications

---

**Statut** : ✅ Implémenté et testé  
**Version** : 1.0  
**Date** : 2025-01-XX  
**Auteur** : Assistant IA
