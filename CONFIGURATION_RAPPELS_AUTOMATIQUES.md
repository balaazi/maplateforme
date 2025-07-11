# Configuration des Rappels Automatiques - EventHub

## Vue d'ensemble

Le système de rappels automatiques permet d'envoyer des notifications la veille des événements à tous les utilisateurs concernés (organisateurs, participants et invités).

## Fonctionnalités

### 🔔 Rappels automatiques pour :
- **Organisateurs** : Reçoivent un rappel pour leurs événements
- **Participants** : Reçoivent un rappel pour les événements auxquels ils participent
- **Invités** : Reçoivent un rappel pour les événements auxquels ils sont invités

### 📧 Types de notifications :
- **Email** : Envoi d'un email de rappel avec template personnalisé
- **Notification interne** : Création d'une notification dans l'application

## Configuration

### 1. Commande disponible

La commande `app:send-event-reminders` est disponible pour envoyer les rappels :

```bash
php bin/console app:send-event-reminders
```

### 2. Fonctionnement

- **Recherche** : Trouve tous les événements prévus pour le lendemain
- **Filtrage** : Ignore les événements annulés
- **Envoi** : Envoie les rappels selon les préférences utilisateur
- **Déduplication** : Évite les doublons (un utilisateur ne reçoit qu'un rappel par événement)

### 3. Préférences utilisateur

Les utilisateurs peuvent activer/désactiver les rappels dans leur profil :

```php
// Dans l'entité User
$user->isNotifyByEmail()  // true/false pour les notifications email
$user->isNotifyBySms()    // true/false pour les notifications SMS (future)
```

## Configuration d'une tâche CRON

### 1. Sur un serveur Linux/Unix

Pour automatiser l'envoi des rappels tous les jours à 18h :

```bash
# Ouvrir le crontab
crontab -e

# Ajouter la ligne suivante (remplacer /path/to/your/project par le chemin réel)
0 18 * * * cd /path/to/your/project && php bin/console app:send-event-reminders >> /var/log/eventhub_reminders.log 2>&1
```

### 2. Sur Windows avec Task Scheduler

1. Ouvrir le **Planificateur de tâches** Windows
2. Créer une **Tâche de base**
3. Configurer :
   - **Nom** : EventHub Reminders
   - **Déclencheur** : Tous les jours à 18h
   - **Action** : Démarrer un programme
   - **Programme** : `php.exe`
   - **Arguments** : `bin/console app:send-event-reminders`
   - **Dossier de démarrage** : `C:\xampp\htdocs\new\maplateforme`

### 3. Alternatives

#### Via un service de monitoring (recommandé)

```bash
# Service de monitoring externe (ex: cron-job.org)
# URL à appeler : https://votre-domaine.com/api/send-reminders
# Fréquence : Tous les jours à 18h
```

#### Via un script batch (Windows)

Créer un fichier `send_reminders.bat` :

```batch
@echo off
cd C:\xampp\htdocs\new\maplateforme
php bin/console app:send-event-reminders
echo Rappels envoyés le %date% à %time% >> logs\reminders.log
```

## Tests

### 1. Test manuel

```bash
# Tester la commande
php bin/console app:send-event-reminders

# Tester avec un événement spécifique (créer un événement pour demain)
php bin/console app:test-email
```

### 2. Test avec données simulées

```bash
# Créer un événement pour demain depuis l'interface
# Activer les notifications email dans les préférences utilisateur
# Exécuter la commande
php bin/console app:send-event-reminders
```

## Logs et monitoring

### 1. Logs de la commande

La commande affiche des informations détaillées :

```
🔔 Envoi des rappels d'événements
================================

 ! [NOTE] Recherche d'événements entre 08/01/2025 00:00 et 09/01/2025 00:00

Traitement de 2 événement(s)
============================

📅 Traitement de l'événement: Formation PHP
   ✅ Rappel envoyé à l'organisateur: Jean Dupont
   ✅ Rappel envoyé au participant: Marie Martin
   📊 2 rappel(s) envoyé(s) pour cet événement

 [OK] ✅ Processus terminé: 2 rappel(s) envoyé(s) au total pour 1 événement(s)
```

### 2. Logs d'erreur

En cas d'erreur, consultez les logs :

```bash
# Logs Symfony
tail -f var/log/prod.log

# Logs spécifiques (si configuré)
tail -f /var/log/eventhub_reminders.log
```

## Dépannage

### 1. Aucun rappel envoyé

- Vérifier qu'il y a des événements prévus pour demain
- Vérifier que les utilisateurs ont activé les notifications email
- Vérifier que les adresses email sont valides

### 2. Erreurs d'envoi email

- Vérifier la configuration SMTP dans `.env`
- Vérifier les credentials email
- Tester avec `php bin/console app:test-email`

### 3. Commande ne s'exécute pas

- Vérifier les permissions du fichier
- Vérifier le chemin PHP dans le cron
- Vérifier les logs d'erreur

## Personnalisation

### 1. Template email

Le template de rappel se trouve dans `templates/emails/reminder.html.twig`

### 2. Fréquence des rappels

Pour modifier la fréquence (ex: 2 jours avant) :

```php
// Dans SendEventRemindersCommand.php
$tomorrow = (new \DateTime())->modify('+2 day')->setTime(0, 0, 0);
$afterTomorrow = (new \DateTime())->modify('+3 day')->setTime(0, 0, 0);
```

### 3. Horaires personnalisés

Pour envoyer des rappels à différents moments :

```bash
# Rappel 2 jours avant (18h)
0 18 * * * cd /path/to/project && php bin/console app:send-event-reminders

# Rappel le jour même (9h)
0 9 * * * cd /path/to/project && php bin/console app:send-event-reminders-today
```

## Sécurité

- Les rappels respectent les préférences utilisateur
- Aucune donnée sensible n'est loggée
- Les erreurs sont gérées sans exposer d'informations
- Les adresses email sont validées avant envoi

## Monitoring avancé

### 1. Statistiques

Pour suivre les performances :

```bash
# Nombre de rappels envoyés
grep "rappel(s) envoyé(s)" /var/log/eventhub_reminders.log | wc -l

# Événements traités
grep "événement(s)" /var/log/eventhub_reminders.log
```

### 2. Alertes

Configurer des alertes si la commande échoue :

```bash
# Script de monitoring
#!/bin/bash
if ! php bin/console app:send-event-reminders > /dev/null 2>&1; then
    echo "Erreur lors de l'envoi des rappels" | mail -s "Alerte EventHub" admin@example.com
fi
```

---

**Note** : Ce système est conçu pour être fiable et respecter les préférences utilisateur. Il évite les doublons et gère les erreurs de manière robuste. 