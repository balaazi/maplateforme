# Guide : Expiration des Invitations pour Événements Passés

## Vue d'ensemble

Le système EventHub dispose maintenant d'une fonctionnalité qui marque automatiquement comme **"EXPIRÉE"** les invitations en attente pour des événements dont la date est déjà passée.

## Fonctionnalités Implémentées

### 1. Service d'Expiration pour Événements Passés
- Nouveau service `EventExpirationService` pour gérer l'expiration basée sur la date de l'événement
- Méthodes pour vérifier si un événement est passé et expirer les invitations correspondantes
- Logging complet des actions d'expiration

### 2. Contrôleur de Réponse aux Invitations
- Vérification automatique si l'événement est passé lors d'une tentative de réponse
- Mise à jour du statut de l'invitation vers "EXPIRÉE" si l'événement est passé
- Message spécifique pour informer l'utilisateur

### 3. Template d'Affichage
- Message personnalisé indiquant que l'événement est passé
- Distinction entre expiration par délai et expiration par date d'événement passée

### 4. Commande Console
- `app:expire-event-invitations` pour exécuter l'expiration automatiquement
- Option `--days` pour configurer la période de recherche des événements passés
- Option `--dry-run` pour simuler l'exécution sans modifier la base de données

### 5. Repository
- Méthode `findEventsEndedBetween()` pour trouver les événements terminés dans une période donnée

## Utilisation

### Expiration Automatique

#### Via la Commande Console
```bash
# Expiration par défaut (30 jours)
php bin/console app:expire-event-invitations

# Expiration personnalisée (15 jours)
php bin/console app:expire-event-invitations --days=15

# Simulation sans modifier la base de données
php bin/console app:expire-event-invitations --dry-run
```

#### Via le Fichier Batch
```bash
# Exécuter le fichier batch
expire_event_invitations.bat
```

### Expiration Manuelle

Pour marquer manuellement une invitation comme expirée si l'événement est passé :

```php
// Dans un contrôleur ou un service
$eventExpirationService->expireInvitationIfEventPassed($invitation);
```

## Comportement

1. **Lors de l'accès à une invitation** : Si l'événement est passé, l'invitation est automatiquement marquée comme expirée
2. **Lors de l'exécution de la commande** : Toutes les invitations en attente pour des événements passés sont marquées comme expirées
3. **Message à l'utilisateur** : "La date de l'événement est dépassée, cette invitation n'est plus valide."

## Test

Un script de test `test_expiration_evenement_passe.php` est disponible pour vérifier le bon fonctionnement de cette fonctionnalité.
