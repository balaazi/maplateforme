# Système de Rappels Automatiques en Temps Réel - EventHub

## 🎯 Vue d'ensemble

EventHub dispose d'un système complet de rappels automatiques qui fonctionne en temps réel et s'adapte automatiquement selon le rôle de chaque utilisateur. Lorsque la date et l'heure d'un rappel sont atteintes, le système déclenche automatiquement :

### ✨ Fonctionnalités principales

1. **🔔 Notifications en temps réel** dans l'interface utilisateur
   - Bulles de notification avec animations modernes
   - Alertes visuelles personnalisables selon la priorité
   - Notifications sonores avec différents sons selon le contexte
   - Actions interactives (ignorer, reporter, voir l'événement)

2. **📧 Emails automatiques** de rappel
   - Templates personnalisés selon le rôle
   - Contenu adapté au contexte (événement, utilisateur)
   - Gestion des préférences utilisateur

3. **👥 Gestion intelligente par rôle**
   - **Administrateur** : Notifications de supervision et gestion
   - **Organisateur** : Rappels pour ses événements + collaboration
   - **Participant** : Rappels pour ses participations

---

## 🏗️ Architecture du système

### Composants principaux

| Composant | Rôle | Localisation |
|-----------|------|--------------|
| **ReminderService** | Logique métier des rappels | `src/Service/ReminderService.php` |
| **RealtimeNotificationService** | Notifications temps réel | `src/Service/RealtimeNotificationService.php` |
| **RoleBasedNotificationService** | Gestion par rôles | `src/Service/RoleBasedNotificationService.php` |
| **ProcessRemindersCommand** | Traitement automatique | `src/Command/ProcessRemindersCommand.php` |
| **ReminderApiController** | API REST | `src/Controller/Api/ReminderApiController.php` |
| **NotificationApiController** | API notifications | `src/Controller/Api/NotificationApiController.php` |

### Base de données

**Table `reminder`** :
- Configuration : `send_email`, `show_notification`, `play_sound`
- États : `is_done`, `is_triggered`, `due_date`
- Classification : `type`, `priority`
- Relations : `user_id`, `event_id`

**Table `notification`** :
- Stockage persistant des notifications
- Suivi de lecture : `is_read`
- Typologie : `type` (event_reminder, invitation, etc.)

---

## 🎭 Notifications selon les rôles

### 👨‍💼 Administrateur (ROLE_ADMIN)

**Reçoit des notifications pour :**
- ✅ Tous les nouveaux événements créés
- ✅ Modifications d'événements
- ✅ Annulations d'événements
- ✅ Nouveaux utilisateurs inscrits
- ✅ Modifications de salles
- ✅ Activités de supervision générale

**Caractéristiques :**
- Priorité : `HIGH`
- Son : ✅ Activé par défaut
- Badge : `[ADMIN]` dans les titres
- Couleur : Rouge/Orange (urgent)

**Exemple de notification :**
```
🔔 [ADMIN] Nouvel événement créé
Un nouvel événement 'Formation Symfony' a été créé par Jean Dupont 
pour le 15/12/2024 à 14:00.
Actions : Ignorer | Voir l'événement | Reporter
```

### 🎯 Organisateur (ROLE_ORGANISATEUR)

**Reçoit des notifications pour :**
- ✅ Ses propres événements (créations, modifications)
- ✅ Événements auxquels il participe
- ✅ Nouvelles salles disponibles
- ✅ Collaborations possibles
- ✅ Conflits de planification

**Caractéristiques :**
- Priorité : `NORMAL` à `HIGH`
- Son : ✅ Configuré selon préférences
- Badge : `Vous organisez` / `Vous participez`
- Couleur : Bleu/Vert

**Exemple de notification :**
```
🎯 Rappel : Votre événement dans 1 heure
N'oubliez pas votre événement 'Réunion équipe' 
prévu aujourd'hui à 15:00 en Salle A.
Actions : Ignorer | Voir l'événement | Reporter (15min)
```

### 👥 Participant (ROLE_PARTICIPANT)

**Reçoit des notifications pour :**
- ✅ Événements auxquels il participe
- ✅ Nouveaux événements ouverts
- ✅ Modifications d'événements auxquels il est inscrit
- ✅ Invitations reçues

**Caractéristiques :**
- Priorité : `NORMAL`
- Son : ✅ Optionnel selon préférences
- Badge : `Vous participez`
- Couleur : Vert/Bleu clair

**Exemple de notification :**
```
👥 Événement dans 30 minutes
L'événement 'Conférence Tech' commence bientôt 
en Salle B. N'oubliez pas d'apporter vos documents.
Actions : Ignorer | Voir l'événement | Marquer présence
```

---

## ⚡ Fonctionnement en temps réel

### JavaScript Frontend (automatique)

Le système vérifie automatiquement les rappels **toutes les 10 secondes** :

```javascript
// Auto-démarrage dans base.html.twig
checkReminders(); // Vérifie immédiatement
setInterval(checkReminders, 10000); // Puis toutes les 10 secondes

// Fonction de vérification
async function checkReminders() {
    const response = await fetch('/api/reminders/check');
    const data = await response.json();
    
    // Affiche automatiquement les nouvelles notifications
    if (data.success && data.data.pending_notifications.length > 0) {
        data.data.pending_notifications.forEach(notification => {
            createAdvancedNotification(notification);
        });
    }
}
```

### Traitement backend (automatique)

**Commande de traitement :**
```bash
# Traitement standard (toutes les 5 minutes via CRON)
php bin/console app:process-reminders

# Traitement en temps réel (via API)
curl -X POST /api/reminders/process
```

---

## 🛠️ Configuration et utilisation

### 1. Création de rappels

#### Depuis le code PHP

```php
use App\Service\ReminderService;

// Injection du service
public function __construct(private ReminderService $reminderService) {}

// Créer un rappel simple
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
$reminders = $this->reminderService->createRemindersForEvent($event, 60);

// Créer un planning de rappels multiples
$reminders = $this->reminderService->createReminderSchedule($event, [60, 15, 5]);
```

#### Via l'API REST

```javascript
// Vérifier les rappels en attente
const response = await fetch('/api/reminders/check');
const data = await response.json();

// Traiter manuellement les rappels
const processResponse = await fetch('/api/reminders/process', { 
    method: 'POST' 
});

// Marquer un rappel comme terminé
const doneResponse = await fetch(`/api/reminders/${id}/mark-done`, { 
    method: 'POST' 
});

// Créer une notification urgente
const urgentResponse = await fetch('/api/reminders/urgent-notification', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        title: 'Rappel urgent',
        message: 'Action immédiate requise',
        options: { priority: 'high', playSound: true }
    })
});
```

### 2. Préférences utilisateur

Les utilisateurs peuvent configurer leurs préférences dans leur profil :

**Entité User** (champs de configuration) :
```php
private bool $notifyByEmail = false;           // Notifications par email
private bool $notifyBySms = false;             // Notifications SMS (future)
private bool $enableSoundNotifications = true; // Sons activés
private bool $enableVisualNotifications = true; // Alertes visuelles
private int $reminderFrequency = 1;            // Fréquence des rappels
private string $notificationPriority = 'normal'; // Priorité par défaut
```

### 3. Traitement automatique

#### Configuration CRON recommandée

```bash
# Traitement des rappels toutes les 5 minutes
*/5 * * * * cd /path/to/eventhub && php bin/console app:process-reminders

# Nettoyage quotidien à 2h du matin
0 2 * * * cd /path/to/eventhub && php bin/console app:process-reminders --cleanup

# Traitement élargi toutes les heures (10 minutes à l'avance)
0 * * * * cd /path/to/eventhub && php bin/console app:process-reminders --minutes-ahead=10
```

#### Commandes disponibles

```bash
# Traitement standard
php bin/console app:process-reminders

# Mode test (affichage sans traitement)
php bin/console app:process-reminders --dry-run

# Avec nettoyage des anciens rappels
php bin/console app:process-reminders --cleanup

# Traitement élargi (10 minutes à l'avance)
php bin/console app:process-reminders --minutes-ahead=10

# Statistiques détaillées
php bin/console app:process-reminders --verbose
```

---

## 🎨 Interface utilisateur

### Notifications avancées

Les notifications s'affichent avec :

- **Animations modernes** : Entrée par la droite, sortie fluide
- **Design glassmorphism** : Fond translucide avec effet de flou
- **Actions interactives** :
  - `Ignorer` : Ferme la notification
  - `Reporter (5min)` : Reporte le rappel de 5 minutes
  - `Voir l'événement` : Redirige vers la page de l'événement
  - `Marquer comme lu` : Pour les notifications persistantes

- **Sons contextuels** :
  - `urgent-reminder.mp3` : Rappels priorité haute
  - `reminder.mp3` : Rappels normaux
  - `soft-reminder.mp3` : Rappels priorité basse
  - `invitation.mp3` : Nouvelles invitations

### Badge de notifications

Le badge de notifications dans la navigation :
- Affiche le nombre de notifications non lues
- Pulse en rouge pour les notifications urgentes
- Se met à jour automatiquement toutes les 30 secondes
- Affiche les 10 notifications les plus récentes au clic

---

## 📊 Monitoring et statistiques

### API de statistiques

```javascript
// Statistiques utilisateur
const userStats = await fetch('/api/reminders/stats');
// Retourne : total, triggered, pending, success_rate

// Statistiques système (admin uniquement)
const systemStats = await fetch('/api/reminders/stats');
// Retourne : queued_notifications, active_users, memory_usage
```

### Nettoyage automatique

```bash
# Nettoyage des anciens rappels (admin uniquement)
curl -X POST /api/reminders/cleanup
```

---

## 🚀 Déploiement et activation

### 1. Vérification des dépendances

```bash
# Vérifier que toutes les entités sont créées
php bin/console doctrine:schema:update --dump-sql

# Appliquer les migrations si nécessaire
php bin/console doctrine:migrations:migrate
```

### 2. Test du système

```bash
# Test complet du système
php bin/console app:process-reminders --dry-run --verbose

# Test des notifications temps réel
curl http://localhost/api/reminders/check

# Test de création de notification urgente
curl -X POST http://localhost/api/reminders/urgent-notification \
  -H "Content-Type: application/json" \
  -d '{"title":"Test","message":"Test de notification urgente"}'
```

### 3. Activation en production

1. **Configurer le CRON** : Ajouter les tâches automatiques
2. **Optimiser les sons** : Placer les fichiers MP3 dans `/public/sounds/`
3. **Configurer HTTPS** : Pour les API en temps réel
4. **Surveiller les logs** : Vérifier `/var/log/` pour les erreurs

---

## 🔧 Dépannage

### Problèmes courants

**Les notifications ne s'affichent pas :**
- ✅ Vérifier la connexion utilisateur
- ✅ Contrôler les préférences utilisateur
- ✅ Examiner la console JavaScript (F12)

**Pas d'emails de rappel :**
- ✅ Vérifier la configuration mailer dans `.env`
- ✅ Contrôler `$user->isNotifyByEmail()`
- ✅ Examiner les logs Symfony

**Rappels non déclenchés :**
- ✅ Vérifier que le CRON fonctionne
- ✅ Exécuter manuellement `php bin/console app:process-reminders`
- ✅ Vérifier les dates des rappels en base

### Logs utiles

```bash
# Logs des rappels
tail -f var/log/dev.log | grep "Rappel"

# Logs des notifications
tail -f var/log/dev.log | grep "Notification"

# Erreurs PHP
tail -f var/log/php_errors.log
```

---

## 📋 Checklist de vérification

### ✅ Fonctionnalités de base
- [ ] Notifications temps réel s'affichent
- [ ] Sons fonctionnent selon les préférences
- [ ] Emails de rappel envoyés
- [ ] Badge de notifications mis à jour
- [ ] Actions (ignorer, reporter) fonctionnelles

### ✅ Gestion des rôles
- [ ] Admin reçoit toutes les notifications
- [ ] Organisateur reçoit ses événements + participations
- [ ] Participant reçoit uniquement ses participations
- [ ] Contenus adaptés selon le rôle

### ✅ Automatisation
- [ ] CRON configuré et fonctionnel
- [ ] Commande console fonctionne
- [ ] API REST accessible
- [ ] Nettoyage automatique opérationnel

---

Le système de rappels automatiques EventHub est maintenant **entièrement opérationnel** et prêt à offrir une expérience utilisateur moderne et personnalisée selon les rôles ! 🎉 
