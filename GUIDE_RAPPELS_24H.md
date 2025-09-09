# Guide des Rappels 24h avant Événements

Ce document explique comment fonctionne le système de rappels 24 heures avant les événements, implémenté dans la plateforme EventHub.

## Fonctionnalités

Le système de rappels 24h offre les fonctionnalités suivantes :

- **Rappels automatiques** 24 heures avant chaque événement
- **Double notification** : par e-mail et sur la plateforme
- **Ciblage intelligent** : organisateurs, participants et invités
- **Configuration flexible** : possibilité d'activer/désactiver selon les préférences utilisateur

## Comment ça marche

### 1. Création des rappels

Le système crée automatiquement des rappels pour tous les événements à venir dans les 7 prochains jours. Pour chaque événement, il crée un rappel programmé exactement 24 heures avant l'heure de début de l'événement.

### 2. Envoi des notifications

Lorsqu'un rappel est déclenché (24h avant l'événement) :

- Un **e-mail de rappel** est envoyé aux utilisateurs ayant activé cette option
- Une **notification sur la plateforme** est créée pour les utilisateurs concernés

### 3. Destinataires des rappels

Les rappels sont envoyés à trois catégories d'utilisateurs :

- **Organisateurs** de l'événement
- **Participants** inscrits à l'événement
- **Invités** ayant accepté l'invitation

## Configuration et exécution

### Exécution manuelle

Pour exécuter manuellement le système de rappels 24h :

```bash
php bin/console app:send-24h-event-reminders
```

Options disponibles :

- `--create-only` : Crée uniquement les rappels sans envoyer de notifications
- `--send-only` : Envoie uniquement les notifications sans créer de nouveaux rappels
- `--days-ahead=X` : Nombre de jours à l'avance pour créer les rappels (défaut: 7)

### Configuration automatique

Un script de configuration automatique est fourni pour Windows :

1. Exécutez PowerShell en tant qu'administrateur
2. Naviguez vers le répertoire du projet
3. Exécutez : `.\setup_24h_reminders.ps1`

Ce script configure une tâche planifiée qui s'exécute tous les jours à 8h00 du matin.

## Préférences utilisateur

Chaque utilisateur peut configurer ses préférences de notification dans son profil :

- **Notifications par e-mail** : Activer/désactiver les rappels par e-mail
- **Notifications visuelles** : Activer/désactiver les notifications sur la plateforme
- **Notifications sonores** : Activer/désactiver les alertes sonores (si applicable)

## Dépannage

### Problèmes courants

1. **Les rappels ne sont pas créés** : Vérifiez que la commande `app:send-24h-event-reminders` est bien exécutée quotidiennement.

2. **Les e-mails ne sont pas reçus** : Vérifiez la configuration du serveur SMTP et les préférences de notification de l'utilisateur.

3. **Les notifications ne s'affichent pas** : Assurez-vous que l'utilisateur a activé les notifications visuelles dans son profil.

### Logs

Les logs du système de rappels sont disponibles dans les fichiers de log Symfony :

```
var/log/prod.log
```

## Développement et extension

Pour étendre le système de rappels, vous pouvez modifier les classes suivantes :

- `src/Service/EventReminderService.php` : Service principal de gestion des rappels 24h
- `src/Command/Send24hEventRemindersCommand.php` : Commande Symfony pour l'exécution des rappels

---

Pour toute question ou assistance, contactez l'administrateur système.
