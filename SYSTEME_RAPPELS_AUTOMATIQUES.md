# Système de Rappels Automatiques - EventHub

## Vue d'ensemble

Le système de rappels automatiques d'EventHub offre des notifications en temps réel avec des fonctionnalités avancées :

- ✅ **Notifications en temps réel** dans l'interface
- ✅ **Alertes visuelles** personnalisables avec animations
- ✅ **Notifications sonores** avec différents sons selon la priorité
- ✅ **Envoi d'e-mails** automatique pour les rappels
- ✅ **Préférences utilisateur** complètes
- ✅ **Gestion automatique** via commandes console

## 🔧 Architecture du système

### Composants principaux

1. **Entité Reminder** - Stockage des rappels
2. **ReminderService** - Logique métier des rappels
3. **RealtimeNotificationService** - Notifications temps réel
4. **ReminderApiController** - API REST pour les rappels
5. **ProcessRemindersCommand** - Traitement automatique en ligne de commande

### Base de données

La table `reminder` contient :
- `title`, `description` - Contenu du rappel
- `due_date` - Date d'échéance
- `is_done`, `is_triggered` - États de traitement
- `send_email`, `show_notification`, `play_sound` - Configuration
- `type`, `priority` - Classification
- Relations vers `user` et `event`

## 🚀 Utilisation

### 1. Création de rappels

#### Depuis le code PHP

```php
// Injection du service
public function __construct(private ReminderService $reminderService) {}

// Créer un rappel pour un événement
$dueDate = (clone $event->getDateHeure())->modify('-1 hour');
$reminder = $this->reminderService->createEventReminder(
    $event,
    $user,
    $dueDate,
    [
        'title' => 'Rappel personnalisé',
        'send_email' => true,
        'play_sound' => true,
        'priority' => 'high'
    ]
);

// Créer des rappels pour tous les participants
$reminders = $this->reminderService->createRemindersForEvent($event, 60); // 60 minutes avant

// Créer un planning de rappels multiples
$reminders = $this->reminderService->createReminderSchedule($event, [60, 15, 5]); // 1h, 15min, 5min avant
```

#### Via l'API REST

```javascript
// Vérifier les rappels en attente
const response = await fetch('/api/reminders/check');
const data = await response.json();

// Traiter manuellement les rappels
const processResponse = await fetch('/api/reminders/process', { method: 'POST' });

// Envoyer une notification de test
// Fonction de test supprimée

// Marquer un rappel comme terminé
const doneResponse = await fetch(`/api/reminders/${id}/mark-done`, { method: 'POST' });
```

### 2. Traitement automatique

#### Commande console

```bash
# Traitement standard
php bin/console app:process-reminders

# Mode test (affichage sans traitement)
php bin/console app:process-reminders --dry-run

# Avec nettoyage des anciens rappels
php bin/console app:process-reminders --cleanup

# Traitement élargi (10 minutes à l'avance)
php bin/console app:process-reminders --minutes-ahead=10
```

#### Configuration CRON

```bash
# Traitement toutes les 5 minutes
*/5 * * * * cd /path/to/project && php bin/console app:process-reminders

# Nettoyage quotidien à 2h du matin
0 2 * * * cd /path/to/project && php bin/console app:process-reminders --cleanup
```

### 3. Notifications temps réel

Le système JavaScript vérifie automatiquement les rappels toutes les 10 secondes :

```javascript
// Vérifie automatiquement les rappels
checkReminders(); // Fonction appelée automatiquement

// Les notifications s'affichent automatiquement avec :
// - Animations d'entrée/sortie
// - Sons selon les préférences
// - Actions (ignorer, reporter, voir événement)
// - Styles selon la priorité
```

## ⚙️ Configuration utilisateur

### Préférences disponibles

Les utilisateurs peuvent configurer leurs préférences via `/user/preferences` :

- **Notifications e-mail** - Recevoir des e-mails de rappel
- **Notifications sonores** - Jouer des sons pour les rappels
- **Notifications visuelles** - Afficher des bulles de notification
- **Fréquence des rappels** - 1h, 6h, 24h ou 48h avant l'événement
- **Priorité des notifications** - Faible, normale ou élevée

### Entité User - Nouveaux champs

```php
private bool $enableSoundNotifications = true;
private bool $enableVisualNotifications = true;
private int $reminderFrequency = 1; // en heures
private string $notificationPriority = 'normal';
```

## 🎨 Interface utilisateur

### Notifications visuelles

Les notifications apparaissent avec :
- **Design glassmorphism** avec effet de flou
- **Animations fluides** d'entrée et de sortie
- **Couleurs selon le type** (rappel, urgent, info, etc.)
- **Actions interactives** (ignorer, reporter 5min, voir événement)
- **Sons personnalisés** selon la priorité

### Template de préférences

- Interface moderne avec animations CSS
- **Aperçu en temps réel** des paramètres
- **Tests interactifs** pour son et visuel
- **Bouton de remise à zéro** aux valeurs par défaut
- **Design responsive** pour mobile

## 📧 Notifications e-mail

### Configuration

Les e-mails utilisent le service `EmailService` existant avec des templates dédiés :

```php
// Envoi automatique lors du déclenchement
if ($config['sendEmail'] && $reminder->getEvent()) {
    $this->emailService->sendReminder($reminder->getUser(), $reminder->getEvent());
}
```

### Template d'e-mail

Le template `emails/reminder.html.twig` (à créer) doit contenir :
- Informations de l'événement
- Date et heure
- Localisation
- Actions possibles

## 🔍 Surveillance et statistiques

### Logs automatiques

Tous les événements sont loggés :
```
[2024-01-15 10:30:00] app.INFO: Rappel déclenché avec succès 
{"reminder_id":123,"user_id":45,"email_sent":true}
```

### Statistiques utilisateur

```php
$stats = $this->reminderService->getUserReminderStats($user);
// Retourne : total, triggered, pending, success_rate
```

### Métriques système

```php
$realtimeStats = $this->realtimeNotificationService->getRealtimeStats();
// Retourne : queued_notifications, active_users, memory_usage
```

## 🚨 Gestion des erreurs

### Récupération automatique

- Les rappels échoués restent en attente pour un nouveau traitement
- Les notifications en queue sont nettoyées automatiquement après 30 minutes
- Les anciens rappels sont supprimés après 30 jours

### Surveillance des performances

```bash
# Vérification des performances
php bin/console app:process-reminders --dry-run
```

## 🔐 Sécurité et permissions

### Contrôle d'accès

- API protégée par `ROLE_USER`
- Utilisateurs ne voient que leurs propres rappels
- Nettoyage réservé aux `ROLE_ADMIN`

### Validation des données

- Validation Symfony sur tous les formulaires
- Échéances dans le futur uniquement
- Déduplication automatique des rappels

## 📱 Extensibilité

### Nouveaux types de rappels

```php
// Créer un type personnalisé
$reminder = new Reminder();
$reminder->setType('custom_reminder');
$reminder->setPriority('high');
// ... configuration
```

### Nouvelles sources de notifications

Le service peut être étendu pour d'autres entités :
```php
// Rappels pour documents, réunions, etc.
$this->reminderService->createCustomReminder($user, $dueDate, $options);
```

## 🛠️ Résolution de problèmes

### Problèmes courants

1. **Rappels non déclenchés**
   - Vérifier la configuration CRON
   - Exécuter manuellement la commande
   - Vérifier les logs

2. **Notifications visuelles absentes**
   - Vérifier les préférences utilisateur
   - Tester via le bouton de test
   - Contrôler la console JavaScript

3. **E-mails non reçus**
   - Vérifier la configuration du mailer
   - Contrôler les préférences utilisateur
   - Examiner les logs d'envoi

### Debug

```bash
# Mode verbose
php bin/console app:process-reminders -v

# Logs détaillés
tail -f var/log/dev.log | grep reminder
```

## 📈 Performance

### Optimisations

- Index sur `due_date`, `is_triggered`, `is_done`
- Limitation des requêtes par utilisateur
- Nettoyage automatique des anciens éléments
- Cache des notifications fréquentes

### Monitoring

```sql
-- Requêtes de monitoring
SELECT COUNT(*) FROM reminder WHERE is_triggered = 0 AND due_date <= NOW();
SELECT type, COUNT(*) FROM reminder GROUP BY type;
```

## 🎯 Prochaines améliorations

### Fonctionnalités prévues

- [ ] **Notifications push** pour mobiles
- [ ] **Rappels récurrents** pour événements répétitifs
- [ ] **Intelligence artificielle** pour optimiser les horaires
- [ ] **Intégration calendriers** externes (Google, Outlook)
- [ ] **Statistiques avancées** avec graphiques
- [ ] **Templates personnalisés** d'e-mails
- [ ] **Webhook** pour intégrations tierces

### API enrichie

- [ ] Endpoints pour création de rappels personnalisés
- [ ] Gestion des rappels en lot
- [ ] Synchronisation avec calendriers externes
- [ ] Export/import des préférences

---

## 📞 Support

Pour toute question ou problème :
1. Consulter les logs dans `var/log/`
2. Tester les commandes en mode `--dry-run`
3. Vérifier les préférences utilisateur
4. Examiner la configuration de la base de données

Le système est conçu pour être robuste et récupérer automatiquement des erreurs temporaires. 