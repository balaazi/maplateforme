# Guide Complet - Système de Rappels d'Invitations EventHub

## 🎯 Vue d'ensemble

Le système de rappels d'invitations permet d'envoyer automatiquement des rappels par email aux personnes à qui vous avez envoyé une invitation pour participer à un événement. Les rappels sont envoyés à la fois 24 heures et 1 heure avant l'événement.

## ✨ Fonctionnalités principales

### 🔔 Types de rappels
- **Rappel 24h avant** : Envoyé 24 heures avant l'événement
- **Rappel 1h avant** : Envoyé 1 heure avant l'événement (urgent)

### 📧 Destinataires
- **Uniquement les invités** : Seules les personnes à qui une invitation a été envoyée reçoivent les rappels
- **Tous les statuts** : Les rappels sont envoyés peu importe le statut de l'invitation (en attente, acceptée, déclinée) - sauf expirée

### 🎨 Templates personnalisés
- **Design moderne** : Interface email responsive et attrayante
- **Informations complètes** : Date, heure, lieu, durée, description de l'événement
- **Statut de l'invitation** : Affichage du statut actuel de l'invitation
- **Actions possibles** : Boutons pour voir l'événement et répondre à l'invitation

## 🚀 Utilisation

### 1. Commandes en ligne de commande

#### Envoyer tous les rappels
```bash
# Envoyer les rappels 24h et 1h avant
php bin/console app:send-invitation-reminders

# Envoyer seulement les rappels 24h avant
php bin/console app:send-invitation-reminders --reminder-type=24h

# Envoyer seulement les rappels 1h avant
php bin/console app:send-invitation-reminders --reminder-type=1h
```

#### Options avancées
```bash
# Mode test (n'envoie pas réellement les emails)
php bin/console app:send-invitation-reminders --test-mode

# Mode dry-run (affiche seulement ce qui serait envoyé)
php bin/console app:send-invitation-reminders --dry-run

# Forcer l'envoi pour une date spécifique
php bin/console app:send-invitation-reminders --force-date=2024-01-15

# Afficher les statistiques des invitations
php bin/console app:send-invitation-reminders --stats
```

### 2. API REST

#### Envoyer des rappels pour un événement
```http
POST /api/invitation-reminders/event/{id}/send
Content-Type: application/json

{
    "reminder_type": "both"
}
```

#### Envoyer un rappel personnalisé à une invitation
```http
POST /api/invitation-reminders/invitation/{id}/send
Content-Type: application/json

{
    "reminder_type": "24h",
    "custom_message": "Message personnalisé"
}
```

#### Récupérer les statistiques
```http
GET /api/invitation-reminders/stats
```

#### Traiter tous les rappels programmés
```http
POST /api/invitation-reminders/process-scheduled
```

#### Tester les rappels
```http
POST /api/invitation-reminders/test
Content-Type: application/json

{
    "reminder_type": "both",
    "test_date": "2024-01-15"
}
```

### 3. Configuration automatique (Cron)

#### Configuration du cron job
Ajoutez cette ligne à votre crontab pour exécuter les rappels automatiquement :

```bash
# Exécuter toutes les 30 minutes
*/30 * * * * /c/xampp/htdocs/new/maplateforme/cron_invitation_reminders.sh

# Ou exécuter à des heures spécifiques
0 9 * * * /c/xampp/htdocs/new/maplateforme/cron_invitation_reminders.sh  # 9h00
0 18 * * * /c/xampp/htdocs/new/maplateforme/cron_invitation_reminders.sh # 18h00
```

#### Rendre le script exécutable
```bash
chmod +x /c/xampp/htdocs/new/maplateforme/cron_invitation_reminders.sh
```

## 📊 Monitoring et logs

### Fichiers de logs
- **Logs des rappels** : `var/log/invitation_reminders.log`
- **Logs Symfony** : `var/log/dev.log` ou `var/log/prod.log`

### Exemple de log
```
[2024-01-15 09:00:01] === Début du traitement des rappels d'invitations ===
[2024-01-15 09:00:02] Envoi des rappels 24h avant...
[2024-01-15 09:00:05] Rappels 24h envoyés avec succès
[2024-01-15 09:00:06] Envoi des rappels 1h avant...
[2024-01-15 09:00:08] Rappels 1h envoyés avec succès
[2024-01-15 09:00:09] === Fin du traitement des rappels d'invitations ===
```

## 🔧 Configuration

### Variables d'environnement
```env
# Email d'expéditeur
MAILER_DSN=smtp://username:password@smtp.example.com:587
```

### Configuration des services
Le service `InvitationReminderService` est automatiquement configuré avec :
- `MailerService` : Pour l'envoi des emails
- `NotificationService` : Pour les notifications en base
- `LoggerInterface` : Pour les logs

## 🎨 Personnalisation des templates

### Template principal
Le template `templates/emails/invitation_reminder.html.twig` peut être personnalisé pour :
- Modifier le design des emails
- Ajouter des informations supplémentaires
- Changer les couleurs et styles
- Ajouter des éléments interactifs

### Variables disponibles
- `event` : Objet Event complet
- `invitation` : Objet Invitation complet
- `user` : Objet utilisateur temporaire
- `reminder_type` : Type de rappel ('24h' ou '1h')
- `hours_before` : Nombre d'heures avant l'événement
- `event_date` : Date de l'événement
- `event_location` : Lieu de l'événement
- `event_duration` : Durée en minutes
- `event_description` : Description de l'événement

## 🚨 Gestion des erreurs

### Types d'erreurs courantes
1. **Email invalide** : L'adresse email de l'invitation est invalide
2. **Événement supprimé** : L'événement associé à l'invitation n'existe plus
3. **Problème SMTP** : Erreur de connexion au serveur email
4. **Template manquant** : Le template d'email n'existe pas

### Résolution des problèmes
1. Vérifiez les logs pour identifier l'erreur
2. Testez avec le mode `--test-mode` ou `--dry-run`
3. Vérifiez la configuration SMTP
4. Assurez-vous que les templates existent

## 📈 Statistiques et monitoring

### Métriques disponibles
- Nombre total d'invitations
- Invitations par statut (en attente, acceptées, déclinées, expirées)
- Nombre de rappels envoyés
- Taux d'erreur

### Exemple de réponse API
```json
{
    "success": true,
    "data": {
        "total_invitations": 150,
        "pending": 45,
        "accepted": 80,
        "declined": 20,
        "expired": 5
    }
}
```

## 🔒 Sécurité

### Bonnes pratiques
1. **Validation des données** : Tous les inputs sont validés
2. **Gestion des erreurs** : Les erreurs sont loggées sans exposer d'informations sensibles
3. **Rate limiting** : Limitation du nombre d'emails envoyés par minute
4. **Authentification** : Les endpoints API nécessitent une authentification

### Permissions requises
- Lecture des événements et invitations
- Envoi d'emails
- Écriture des logs
- Accès aux templates

## 🆘 Support et dépannage

### Commandes de diagnostic
```bash
# Vérifier la configuration
php bin/console debug:container InvitationReminderService

# Tester la connexion SMTP
php bin/console app:send-invitation-reminders --test-mode

# Vérifier les logs
tail -f var/log/invitation_reminders.log
```

### Contact
Pour toute question ou problème :
- Consultez les logs d'erreur
- Vérifiez la configuration SMTP
- Testez avec le mode test avant la production

---

## 📝 Changelog

### Version 1.0.0
- ✅ Système de rappels 24h et 1h avant
- ✅ Templates d'email personnalisés
- ✅ API REST complète
- ✅ Commandes en ligne de commande
- ✅ Configuration cron automatique
- ✅ Logs et monitoring
- ✅ Gestion des erreurs avancée
