# Guide d'Automatisation Complète - Rappels EventHub

## 🎯 Objectif
Configurer un système de rappels **100% automatique** sans intervention manuelle.

## ✅ Solutions Disponibles

### 1. **Solution Recommandée : Worker Continu**

#### Configuration Automatique
```bash
# Exécuter le script de configuration
.\setup_messenger_worker.bat
```

#### Scripts Créés
- `worker_monitor.bat` - Monitoring continu (toutes les 5 minutes)
- `start_automatic_reminders.bat` - Démarrage automatique
- `test_automatic_system.bat` - Test du système

#### Utilisation
```bash
# Démarrer le système automatique
.\start_automatic_reminders.bat

# Ou directement
.\worker_monitor.bat

# Tester le système
.\test_automatic_system.bat
```

---

### 2. **Solution Alternative : Tâche Planifiée Windows**

#### Configuration PowerShell (Administrateur)
```powershell
# Exécuter en tant qu'administrateur
.\setup_automatic_reminders.ps1
```

#### Configuration Manuelle
1. Ouvrir le **Planificateur de tâches** Windows
2. Créer une nouvelle tâche :
   - **Nom** : "EventHub Reminders"
   - **Déclencheur** : Quotidien à 10:35
   - **Action** : `C:\xampp\htdocs\new\maplateforme\send_reminders.bat`
   - **Dossier de démarrage** : `C:\xampp\htdocs\new\maplateforme`

---

### 3. **Solution Avancée : Service Windows**

#### Installation du Service
```bash
# Créer un service Windows (nécessite des droits administrateur)
sc create "EventHubReminders" binPath= "C:\xampp\php\php.exe C:\xampp\htdocs\new\maplateforme\bin\console app:process-reminders" start= auto

# Démarrer le service
sc start "EventHubReminders"

# Vérifier le statut
sc query "EventHubReminders"
```

---

## 🚀 Démarrage Rapide

### Option 1 : Worker Continu (Recommandé)

1. **Double-cliquez** sur `start_automatic_reminders.bat`
2. Le système démarre automatiquement
3. Les rappels sont vérifiés toutes les 5 minutes
4. **Fermez la fenêtre** pour arrêter

### Option 2 : Tâche Planifiée

1. **Exécutez** `setup_automatic_reminders.ps1` en tant qu'administrateur
2. La tâche est créée automatiquement
3. Les rappels sont envoyés quotidiennement à 10:35

### Option 3 : Test Manuel

1. **Double-cliquez** sur `test_automatic_system.bat`
2. Vérifiez les résultats dans la console
3. Consultez les logs dans le dossier `logs\`

---

## 📊 Monitoring et Surveillance

### Logs Automatiques
```
logs/
├── reminders_output.log    # Sortie des rappels
├── worker_output.log       # Log du worker continu
└── reminders.log          # Historique des envois
```

### Vérification du Statut
```bash
# Vérifier les rappels en attente
php bin/console app:process-reminders --dry-run

# Vérifier les logs
type logs\reminders_output.log

# Tester l'envoi manuel
php bin/console app:send-event-reminders
```

### Surveillance en Temps Réel
```bash
# Démarrer le monitoring continu
.\worker_monitor.bat

# Ou avec plus de détails
php bin/console app:process-reminders --verbose
```

---

## 🔧 Configuration Avancée

### 1. **Fréquence Personnalisée**

#### Modifier l'intervalle du worker
```batch
# Dans worker_monitor.bat, changer :
timeout /t 300 /nobreak >nul  # 5 minutes (300 secondes)
timeout /t 60 /nobreak >nul   # 1 minute
timeout /t 600 /nobreak >nul  # 10 minutes
```

#### Modifier l'heure de la tâche planifiée
```powershell
# Dans setup_automatic_reminders.ps1, changer :
$trigger = New-ScheduledTaskTrigger -Daily -At "10:35"
$trigger = New-ScheduledTaskTrigger -Daily -At "18:00"  # 18h00
```

### 2. **Notifications Avancées**

#### Ajouter des notifications par email
```php
// Dans src/Service/ReminderService.php
// Les emails sont envoyés automatiquement selon les préférences utilisateur
```

#### Configurer des sons de notification
```javascript
// Dans templates/base.html.twig
// Les sons sont joués automatiquement selon les préférences
```

### 3. **Gestion des Erreurs**

#### Logs d'erreur automatiques
```bash
# Les erreurs sont automatiquement loggées dans :
logs/worker_output.log
logs/reminders_output.log
```

#### Notification d'erreur par email
```php
// Les erreurs critiques peuvent être envoyées par email
// Configuration dans src/Service/EmailService.php
```

---

## 📈 Statistiques et Rapports

### Commandes de Diagnostic
```bash
# Statistiques des rappels
php bin/console app:process-reminders --verbose

# Vérifier les rappels créés
php bin/console app:create-missing-reminders --dry-run

# Test complet du système
.\test_automatic_system.bat
```

### Métriques Importantes
- **Rappels créés** : Nombre de rappels en attente
- **Rappels envoyés** : Nombre de notifications envoyées
- **Taux de succès** : Pourcentage de rappels délivrés
- **Erreurs** : Nombre d'erreurs détectées

---

## 🛠️ Dépannage

### Problème : Les rappels ne sont pas envoyés

#### Vérifications
```bash
# 1. Vérifier que le worker fonctionne
.\worker_monitor.bat

# 2. Vérifier les logs
type logs\worker_output.log

# 3. Tester manuellement
php bin/console app:send-event-reminders

# 4. Vérifier les préférences utilisateur
php bin/console doctrine:query:sql "SELECT email, notify_by_email FROM users"
```

#### Solutions
1. **Redémarrer le worker** : Fermez et relancez `worker_monitor.bat`
2. **Vérifier PHP** : `php --version`
3. **Vérifier les permissions** : Exécuter en tant qu'administrateur
4. **Vérifier la base de données** : Connexion active

### Problème : Tâche planifiée ne fonctionne pas

#### Vérifications
```bash
# 1. Vérifier la tâche
schtasks /query /tn "EventHub Reminders"

# 2. Tester manuellement
.\send_reminders.bat

# 3. Vérifier les permissions
# Exécuter en tant qu'administrateur
```

#### Solutions
1. **Recréer la tâche** : `.\setup_automatic_reminders.ps1`
2. **Vérifier le chemin** : S'assurer que le script existe
3. **Vérifier les droits** : Exécuter en tant qu'administrateur

---

## ✅ Checklist de Configuration

### Avant de Démarrer
- [ ] PHP installé et accessible
- [ ] Base de données connectée
- [ ] Préférences utilisateur configurées
- [ ] Scripts de configuration créés

### Configuration Automatique
- [ ] Worker continu démarré
- [ ] Tâche planifiée créée
- [ ] Logs configurés
- [ ] Tests effectués

### Vérification
- [ ] Rappels créés pour les événements
- [ ] Emails envoyés automatiquement
- [ ] Notifications affichées dans l'interface
- [ ] Logs sans erreur

---

## 🎉 Résultat Final

Avec cette configuration, votre système EventHub :

✅ **Envoie automatiquement** les rappels sans intervention manuelle
✅ **Vérifie continuellement** les nouveaux événements
✅ **Gère les erreurs** automatiquement
✅ **Fournit des logs** détaillés
✅ **S'adapte aux préférences** utilisateur
✅ **Fonctionne 24h/24** et 7j/7

**Plus besoin d'exécuter manuellement des commandes !** 🚀 