# Filtrage Automatique de l'Agenda par Rôle

## Vue d'ensemble

L'agenda de la plateforme EventHub implémente un système de filtrage automatique intelligent qui adapte l'affichage des événements en fonction du rôle de l'utilisateur connecté. Cette fonctionnalité garantit que chaque utilisateur ne voit que les événements qui le concernent directement.

## Règles de Filtrage par Rôle

### 🔴 Administrateur (ROLE_ADMIN)
- **Visibilité :** Tous les événements de la plateforme
- **Justification :** Supervision complète du système
- **Badge :** 👨‍💼 Administrateur

### 🔵 Organisateur (ROLE_ORGANISATEUR)
- **Visibilité :**
  - Tous les événements qu'il organise
  - Tous les événements auxquels il participe
- **Justification :** Gestion de ses propres événements + participation
- **Badge :** 🎯 Vous organisez (pour ses événements) / 👥 Vous participez (pour les autres)

### 🟢 Participant (ROLE_PARTICIPANT)
- **Visibilité :** Uniquement les événements auxquels il participe
- **Justification :** Vision limitée à ses propres activités
- **Badge :** 👥 Vous participez

## Implémentation Technique

### Contrôleur API (`EventApiController`)
```php
public function list(EventRepository $eventRepository): JsonResponse
{
    $user = $this->getUser();
    $events = $eventRepository->findByRole($user);
    // ...
}
```

### Repository (`EventRepository::findByRole`)
```php
public function findByRole(User $user): array
{
    $roles = $user->getRoles();

    if (in_array('ROLE_ADMIN', $roles)) {
        // Tous les événements
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()->getResult();
    }

    if (in_array('ROLE_ORGANISATEUR', $roles)) {
        // Événements organisés + participation
        return $this->createQueryBuilder('e')
            ->leftJoin('e.participations', 'p')
            ->where('e.organizer = :user OR p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()->getResult();
    }

    // Participant : uniquement les participations
    return $this->createQueryBuilder('e')
        ->join('e.participations', 'p')
        ->where('p.user = :user')
        ->setParameter('user', $user)
        ->orderBy('e.dateHeure', 'ASC')
        ->getQuery()->getResult();
}
```

### Interface Utilisateur

#### Indication de Rôle
- Message informatif en haut de l'agenda expliquant la vue courante
- Badge coloré indiquant le rôle de l'utilisateur
- Description des événements visibles

#### Badges sur les Événements
- 👨‍💼 : Vue administrateur
- 🎯 : Événement que vous organisez
- 👥 : Événement auquel vous participez
- 👁️ : Vue observateur (cas exceptionnels)

#### Tooltips Informatifs
Chaque événement affiche au survol :
- Votre rôle pour cet événement
- Lieu de l'événement
- Nom de l'organisateur

## Sécurité

### Contrôle d'Accès
- Route `/calendar` protégée par `IS_AUTHENTICATED_FULLY`
- API `/api/events` protégée par `ROLE_USER`
- Filtrage automatique côté serveur (non contournable côté client)

### Validation des Données
- Vérification des rôles via Symfony Security
- Requêtes SQL sécurisées avec paramètres liés
- Validation des relations utilisateur-événement

## Avantages

1. **Sécurité** : Aucun événement non autorisé n'est transmis au client
2. **Performance** : Requêtes optimisées selon le rôle
3. **UX** : Interface claire avec indicateurs visuels
4. **Maintenabilité** : Logique centralisée dans le repository

## Cas d'Usage

### Scénario 1 : Administrateur RH
```
✅ Voit tous les événements de formation
✅ Peut superviser l'ensemble des activités
✅ Accès complet pour la gestion
```

### Scénario 2 : Manager/Organisateur
```
✅ Voit ses formations organisées
✅ Voit ses participations personnelles
❌ Ne voit pas les événements d'autres organisateurs
```

### Scénario 3 : Employé/Participant
```
✅ Voit uniquement ses formations
❌ Ne voit pas les événements des autres
❌ Vision limitée à ses activités
```

## Extensions Futures

- Filtrage par département
- Événements publics vs privés
- Niveaux de visibilité configurables
- Système de délégation de droits 