# 🔔 Système de Rappels Automatiques Optimisé

## Vue d'ensemble

Ce système permet d'envoyer automatiquement des rappels d'événements à intervalles précis (24 heures et 1 heure avant l'événement) en s'exécutant chaque minute pour une précision maximale.

## ✨ Fonctionnalités

### 🎯 Rappels Intelligents
- **Rappels 24h** : Envoyés exactement 24 heures avant l'événement
- **Rappels 1h** : Envoyés exactement 1 heure avant l'événement
- **Tolérance configurable** : Évite les doublons avec une fenêtre de tolérance
- **Détection des doublons** : Vérifie les rappels récents pour éviter les envois multiples

### 🛡️ Gestion des Cas Limites
- **Gestion des fuseaux horaires** : Utilise les dates UTC pour la cohérence
- **Prévention des doublons** : Système de tolérance en minutes
- **Nettoyage automatique** : Suppression des anciens rappels déclenchés
- **Gestion d'erreurs robuste** : Logging détaillé et récupération d'erreurs

### 📊 Monitoring et Logging
- **Statistiques détaillées** : Nombre d'événements traités, rappels envoyés, erreurs
- **Logs structurés** : Enregistrement de toutes les actions importantes
- **Mode simulation** : Test sans envoi réel d'emails
- **Rapports d'exécution** : Résumé complet après chaque exécution

## 🚀 Installation et Configuration

### 1. Configuration Automatique (Recommandé)

#### Option A : Script PowerShell (Windows)
```powershell
# Exécuter en tant qu'administrateur
PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Install

# Avec paramètres personnalisés
PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Install -ToleranceMinutes 5 -ReminderType "both"

# Vérifier le statut
PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Status

# Désinstaller
PowerShell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1 -Uninstall
```

#### Option B : Script Batch (Windows)
```batch
# Exécuter en tant qu'administrateur
setup_minute_reminders.bat
```

### 2. Configuration Manuelle

#### Créer une tâche planifiée Windows
```batch
schtasks /create /tn "EventRemindersAdvanced" /tr "php \"C:\path\to\project\bin\console\" app:send-event-reminders-advanced --reminder-type=both --tolerance-minutes=2" /sc minute /mo 1 /ru "SYSTEM" /f
```

#### Configuration Cron (Linux/Mac)
```bash
# Ajouter au crontab
* * * * * cd /path/to/project && php bin/console app:send-event-reminders-advanced --reminder-type=both --tolerance-minutes=2 >> /var/log/reminders.log 2>&1
```

## 📋 Utilisation de la Commande

### Syntaxe de Base
```bash
php bin/console app:send-event-reminders-advanced [options]
```

### Options Disponibles

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| `--reminder-type` | Type de rappel (24h, 1h, both) | both |
| `--tolerance-minutes` | Tolérance en minutes pour éviter les doublons | 2 |
| `--dry-run` | Mode simulation sans envoi réel | false |
| `--cleanup` | Nettoie les anciens rappels déclenchés | false |

### Exemples d'Utilisation

#### Exécution Standard
```bash
# Envoie tous les types de rappels avec tolérance de 2 minutes
php bin/console app:send-event-reminders-advanced

# Seulement les rappels 24h
php bin/console app:send-event-reminders-advanced --reminder-type=24h

# Seulement les rappels 1h
php bin/console app:send-event-reminders-advanced --reminder-type=1h

# Avec tolérance personnalisée
php bin/console app:send-event-reminders-advanced --tolerance-minutes=5
```

#### Mode Test et Maintenance
```bash
# Mode simulation (aucun email envoyé)
php bin/console app:send-event-reminders-advanced --dry-run

# Nettoyage des anciens rappels
php bin/console app:send-event-reminders-advanced --cleanup

# Test avec simulation et nettoyage
php bin/console app:send-event-reminders-advanced --dry-run --cleanup
```

## 🔧 Configuration Avancée

### Variables d'Environnement
```env
# Dans .env ou .env.local
REMINDER_TOLERANCE_MINUTES=2
REMINDER_CLEANUP_DAYS=7
REMINDER_LOG_LEVEL=info
```

### Personnalisation des Templates
Les templates d'emails sont configurables dans :
- `templates/emails/reminder_24h.html.twig`
- `templates/emails/reminder_1h.html.twig`

### Configuration des Logs
```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        reminders:
            type: rotating_file
            path: '%kernel.logs_dir%/reminders.log'
            level: info
            max_files: 10
            channels: ['reminders']
```

## 📊 Monitoring et Surveillance

### Vérification du Statut
```bash
# Vérifier les tâches planifiées
schtasks /query /tn "EventRemindersAdvanced"

# Voir les logs récents
tail -f var/log/reminders.log

# Statistiques d'exécution
php bin/console app:send-event-reminders-advanced --dry-run
```

### Métriques Importantes
- **Événements traités** : Nombre d'événements vérifiés
- **Rappels envoyés** : Nombre de rappels effectivement envoyés
- **Rappels ignorés** : Nombre de rappels évités (doublons)
- **Erreurs** : Nombre d'erreurs rencontrées
- **Temps d'exécution** : Durée de traitement

### Alertes et Notifications
Le système peut être configuré pour envoyer des alertes en cas de :
- Erreurs répétées
- Nombre anormalement élevé de rappels
- Échecs de connexion à la base de données

## 🛠️ Dépannage

### Problèmes Courants

#### La tâche ne s'exécute pas
1. Vérifier les privilèges administrateur
2. Contrôler le chemin vers PHP
3. Vérifier les permissions sur le projet

#### Doublons de rappels
1. Augmenter la tolérance : `--tolerance-minutes=5`
2. Vérifier la synchronisation de l'horloge système
3. Contrôler les logs pour identifier les causes

#### Performances lentes
1. Optimiser les requêtes de base de données
2. Ajouter des index sur les colonnes de date
3. Limiter la période de recherche

### Commandes de Diagnostic
```bash
# Test de connectivité base de données
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM event"

# Vérification de la configuration
php bin/console debug:config

# Test des services
php bin/console debug:container reminder
```

## 📈 Optimisations et Bonnes Pratiques

### Performance
- **Index de base de données** : Créer des index sur `dateHeure` et `status`
- **Limitation des requêtes** : Utiliser des critères de filtrage efficaces
- **Cache** : Mettre en cache les configurations fréquemment utilisées

### Sécurité
- **Validation des données** : Vérifier toutes les entrées utilisateur
- **Logs sécurisés** : Ne pas logger d'informations sensibles
- **Permissions** : Exécuter avec les privilèges minimaux nécessaires

### Maintenance
- **Nettoyage régulier** : Programmer le nettoyage hebdomadaire
- **Surveillance des logs** : Mettre en place une rotation des logs
- **Tests réguliers** : Exécuter des tests en mode simulation

## 🔄 Mise à Jour et Migration

### Mise à Jour du Système
1. Sauvegarder la configuration actuelle
2. Arrêter les tâches planifiées
3. Mettre à jour le code
4. Relancer la configuration
5. Tester en mode simulation

### Migration depuis l'Ancien Système
1. Exporter les données de rappels existants
2. Adapter le format si nécessaire
3. Importer dans le nouveau système
4. Valider la migration avec des tests

## 📞 Support et Contact

Pour toute question ou problème :
1. Consulter les logs : `var/log/reminders.log`
2. Exécuter en mode simulation pour diagnostiquer
3. Vérifier la documentation technique
4. Contacter l'équipe de développement

---

*Dernière mise à jour : $(date)*
*Version du système : 2.0*