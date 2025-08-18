# Guide : Système de Détection de Conflits d'Horaires

## Vue d'ensemble

Le système EventHub intègre une fonctionnalité avancée de détection de conflits d'horaires qui empêche les utilisateurs d'accepter des invitations à des événements qui se chevauchent dans le temps.

## Fonctionnement

### 1. Déclenchement de la Vérification

La vérification se déclenche automatiquement lorsqu'un utilisateur :
- ✅ **Clique sur "Accepter l'invitation"** dans un email d'invitation
- ✅ **Tente de s'inscrire** à un événement via l'interface web
- ✅ **Répond à une invitation** depuis un lien public

### 2. Processus de Détection

#### Étape 1 : Collecte des Événements Existants
Le système recherche tous les événements où l'utilisateur est déjà inscrit :
- **Via les invitations acceptées** : `invitation.status = 'accepted'`
- **Via les participations directes** : `participation.invitationStatus = 'accepté'`

#### Étape 2 : Vérification des Chevauchements
Pour chaque événement existant, le système vérifie :
```php
// Nouvel événement
$newEventStart = $newEvent->getDateHeure();
$newEventEnd = $newEventStart + $newEvent->getDuree();

// Événement existant
$existingStart = $existingEvent->getDateHeure();
$existingEnd = $existingStart + $existingEvent->getDuree();

// Conflit si chevauchement
if ($newEventStart < $existingEnd && $newEventEnd > $existingStart) {
    return $existingEvent; // Conflit détecté
}
```

#### Étape 3 : Décision
- **Aucun conflit** → L'invitation est acceptée normalement
- **Conflit détecté** → Affichage de la page de conflit

## Architecture Technique

### Services Impliqués

#### 1. `ScheduleConflictService`
Service principal pour la gestion des conflits :
```php
class ScheduleConflictService
{
    public function checkScheduleConflict(User $user, Event $newEvent): ?array
    public function canUserParticipate(User $user, Event $event): bool
    public function generateConflictWarningMessage(array $conflict): string
}
```

#### 2. `EventRepository::findConflictingEventForUser()`
Méthode de repository optimisée pour la détection :
```php
public function findConflictingEventForUser(User $user, Event $newEvent): ?Event
{
    // Requête optimisée avec JOIN sur invitations et participations
    // Vérification des chevauchements temporels
    // Retour de l'événement en conflit ou null
}
```

### Contrôleur Principal

#### `InvitationResponseController::respond()`
```php
if ($response === 'accepted') {
    // Vérifier s'il y a un conflit d'horaires avant d'accepter
    $conflict = $this->scheduleConflictService->checkScheduleConflict($user, $invitation->getEvent());
    
    if ($conflict) {
        // Afficher la page de conflit
        return $this->render('invitation/conflict.html.twig', [
            'invitation' => $invitation,
            'newEvent' => $invitation->getEvent(),
            'conflictingEvent' => $conflict['conflictingEvent'],
            'user' => $user
        ]);
    }
    
    // Aucun conflit → accepter l'invitation
    $participation->setInvitationStatus('accepté');
}
```

## Interface Utilisateur

### Page de Conflit (`conflict.html.twig`)

#### 1. Message d'Avertissement Clair
```
⚠️ Vous participez déjà à un autre événement à cette date et heure.
Il n'est pas possible d'accepter cette nouvelle invitation car elle créerait un conflit dans votre agenda.
```

#### 2. Comparaison Visuelle
- **Nouvel événement** (côté gauche) avec badge vert
- **Événement en conflit** (côté droit) avec badge rouge
- **Séparateur "VS"** entre les deux événements

#### 3. Informations Détaillées
Pour chaque événement :
- 📅 **Date** : Format français (dd/mm/yyyy)
- 🕐 **Heures** : Début et fin calculées automatiquement
- ⏱️ **Durée** : En minutes
- 📍 **Lieu** : Si renseigné

#### 4. Actions Possibles
- **Retour** : Retour à la page précédente
- **Accueil** : Retour à la page d'accueil
- **Gestion des conflits** : Pour une implémentation future

## Cas d'Usage

### Scénario 1 : Conflit Simple
```
Utilisateur inscrit à "Réunion équipe" (14h-15h)
Tente d'accepter "Formation technique" (14h30-16h)
→ Conflit détecté et affiché
```

### Scénario 2 : Conflit Partiel
```
Utilisateur inscrit à "Conférence" (9h-12h)
Tente d'accepter "Atelier" (11h-13h)
→ Conflit détecté (chevauchement de 11h à 12h)
```

### Scénario 3 : Aucun Conflit
```
Utilisateur inscrit à "Réunion matin" (9h-10h)
Tente d'accepter "Réunion après-midi" (14h-15h)
→ Aucun conflit → Invitation acceptée
```

## Logs et Monitoring

### Logs de Conflits
```php
$this->logger->warning('Conflit d\'horaires détecté', [
    'user_id' => $user->getId(),
    'new_event_id' => $invitation->getEvent()->getId(),
    'new_event_title' => $invitation->getEvent()->getTitle(),
    'conflicting_event_id' => $conflictingEvent->getId(),
    'conflicting_event_title' => $conflictingEvent->getTitle(),
    'user_email' => $user->getEmail()
]);
```

### Métriques à Surveiller
- **Nombre de conflits détectés** par jour/semaine
- **Types d'événements** les plus conflictuels
- **Utilisateurs** les plus touchés par les conflits
- **Périodes** où les conflits sont fréquents

## Améliorations Futures

### 1. Résolution Automatique
- **Bouton "Remplacer l'événement"** : Annule automatiquement l'ancien
- **Suggestion de créneaux alternatifs** : Propose d'autres horaires
- **Négociation automatique** : Demande à l'organisateur de déplacer l'événement

### 2. Prévention Proactive
- **Alerte lors de la création** d'événements
- **Vérification en temps réel** dans l'agenda
- **Notifications push** pour les conflits potentiels

### 3. Gestion Avancée
- **Conflits partiels** : Permettre l'acceptation avec conditions
- **Priorisation** : Définir des règles de priorité entre événements
- **Délégation** : Permettre à un collègue de participer à sa place

## Tests et Validation

### Test Automatisé
```bash
php test_schedule_conflict.php
```

### Cas de Test Couverts
- ✅ **Événements qui se chevauchent** → Conflit détecté
- ✅ **Événements consécutifs** → Aucun conflit
- ✅ **Événements sur des jours différents** → Aucun conflit
- ✅ **Événements avec durées variables** → Conflit détecté correctement

## Configuration

### Paramètres de Détection
- **Tolérance temporelle** : Actuellement 0 minute (conflit dès qu'il y a chevauchement)
- **Types d'événements exclus** : Événements annulés, archivés
- **Statuts d'invitation valides** : 'accepted', 'accepté'

### Personnalisation
Le système peut être adapté pour :
- **Différentes fuseaux horaires**
- **Règles métier spécifiques**
- **Tolérances personnalisées** par utilisateur ou type d'événement

## Support et Maintenance

### Débogage
En cas de problème :
1. **Vérifier les logs** : `var/log/dev.log` ou `var/log/prod.log`
2. **Tester la détection** : Utiliser le fichier de test
3. **Vérifier la base** : Contrôler les données d'événements et participations

### Performance
- **Indexation** : Les champs `dateHeure` et `email` sont indexés
- **Requêtes optimisées** : Utilisation de JOIN pour éviter les requêtes multiples
- **Cache** : Possibilité d'ajouter du cache pour les vérifications fréquentes
