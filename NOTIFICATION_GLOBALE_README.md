# Système de Notification Globale - EventHub

## Vue d'ensemble

Le système de notification globale permet de recevoir automatiquement des e-mails pour toutes les modifications importantes effectuées sur la plateforme EventHub. 

## Fonctionnalités

### 🔔 Notifications automatiques pour :
- **Événements** : Création, modification, annulation
- **Utilisateurs** : Modification de profil, changement de mot de passe
- **Salles** : Création, modification, suppression
- **Invitations** : Création, suppression
- **Autres entités** : Extensible pour d'autres types de modifications

### 📧 Types de destinataires :
- **Administrateurs** : Reçoivent toutes les notifications
- **Utilisateurs concernés** : Reçoivent les notifications qui les concernent (selon leurs préférences)

## Configuration

### 1. Préférences utilisateur

Chaque utilisateur peut activer/désactiver les notifications dans son profil :

```php
// Dans l'entité User
private bool $notifyByEmail = false;  // Notifications par e-mail
private bool $notifyBySms = false;    // Notifications par SMS (future)
```

### 2. Adresses e-mail

Les adresses e-mail sont configurées dans le service :

```php
// Dans GlobalNotificationService
private const ADMIN_EMAIL = 'enterpriseeventhub@gmail.com';  // Admin principal
private const FROM_EMAIL = 'nadiabalaazi@gmail.com';         // Expéditeur
```

## Utilisation

### Intégration automatique

Le système est déjà intégré dans les contrôleurs principaux :

```php
// Exemple dans EventController
try {
    $this->globalNotificationService->notifyPlatformModification('créé', 'event', $event);
} catch (\Exception $e) {
    error_log('Erreur notification globale: ' . $e->getMessage());
}
```

### Contrôleurs intégrés

✅ **EventController** : Notifications pour créations, modifications, annulations d'événements
✅ **UserController** : Notifications pour modifications de profil et changements de mot de passe  
✅ **SalleController** : Notifications pour créations, modifications, suppressions de salles
✅ **InvitationController** : Notifications pour créations et suppressions d'invitations

### Ajout manuel

Pour ajouter des notifications dans d'autres contrôleurs :

```php
// 1. Injecter le service
public function __construct(GlobalNotificationService $globalNotificationService)
{
    $this->globalNotificationService = $globalNotificationService;
}

// 2. Appeler la notification
$this->globalNotificationService->notifyPlatformModification(
    'créé',           // Action (créé, modifié, supprimé, etc.)
    'document',       // Type d'entité
    $document,        // L'entité concernée
    $this->getUser()  // Utilisateur (optionnel)
);
```

## Types d'actions supportées

| Action | Icône | Description |
|--------|-------|-------------|
| `créé` | 🎉 | Création d'une nouvelle entité |
| `modifié` | ✏️ | Modification d'une entité existante |
| `supprimé` | 🗑️ | Suppression d'une entité |
| `annulé` | ❌ | Annulation (ex: événement) |
| `accepté` | ✅ | Acceptation (ex: invitation) |
| `refusé` | ❌ | Refus (ex: invitation) |

## Types d'entités supportées

| Type | Description | Informations incluses |
|------|-------------|----------------------|
| `event` | Événements | Date, lieu, organisateur |
| `user` | Utilisateurs | Email, département, rôles |
| `salle` | Salles | Capacité, localisation |
| `invitation` | Invitations | Événement associé |
| `document` | Documents | Nom du fichier |
| `participation` | Participations | Événement associé |

## Template d'e-mail

Le système utilise un template HTML moderne et responsive :

- **Design professionnel** avec dégradés et couleurs modernes
- **Responsive** pour tous les appareils
- **Informations détaillées** sur la modification
- **Lien direct** vers la plateforme
- **Instructions** pour désactiver les notifications

Fichier : `templates/emails/global_notification.html.twig`

## Tests

### Commande de test

```bash
php bin/console app:test-global-notification event
php bin/console app:test-global-notification user
php bin/console app:test-global-notification salle
```

### Test manuel

```php
// Dans un contrôleur
$this->globalNotificationService->notifyPlatformModification(
    'créé',
    'test',
    $entity,
    $user
);
```

## Gestion des erreurs

Le système inclut une gestion d'erreurs robuste :

```php
try {
    $this->globalNotificationService->notifyPlatformModification(...);
} catch (\Exception $e) {
    // L'erreur est loggée mais n'interrompt pas le processus principal
    error_log('Erreur notification globale: ' . $e->getMessage());
}
```

## Logs

Tous les erreurs sont loggées pour faciliter le débogage :

- Erreurs d'envoi d'e-mail
- Erreurs de rendu de template
- Erreurs de récupération d'utilisateur

## Sécurité

- **Authentification requise** : Seuls les utilisateurs connectés peuvent déclencher des notifications
- **Validation des données** : Toutes les données sont validées et échappées
- **Préférences utilisateur** : Respecte les choix de notification de chaque utilisateur
- **Gestion des exceptions** : Aucune information sensible n'est exposée en cas d'erreur

## Extensions possibles

### Nouvelles entités

Pour ajouter une nouvelle entité :

1. Ajouter le type dans `getEntityName()`
2. Ajouter les détails dans `getEntityDetails()`
3. Ajouter la logique dans `getUsersToNotify()`

### Nouveaux canaux

Le système est préparé pour :
- **Notifications SMS** (champ `notifyBySms` déjà présent)
- **Notifications push**
- **Notifications Slack/Teams**

### Filtres avancés

Possibilité d'ajouter :
- Filtres par type d'action
- Filtres par entité
- Planification de notifications
- Digest quotidien/hebdomadaire

## Maintenance

### Vérification des e-mails

```bash
# Vérifier la configuration SMTP
php bin/console debug:mailer

# Tester l'envoi d'e-mail
php bin/console app:test-global-notification
```

### Monitoring

- Surveiller les logs d'erreur
- Vérifier les bounces d'e-mail
- Contrôler le volume de notifications

## Support

Pour toute question ou problème :

1. Vérifier les logs d'erreur
2. Tester avec la commande de test
3. Vérifier la configuration SMTP
4. Consulter la documentation Symfony Mailer

## Conclusion

Ce système offre une solution complète et extensible pour les notifications par e-mail sur EventHub. Il respecte les meilleures pratiques de Symfony et peut être facilement adapté aux besoins futurs de la plateforme. 