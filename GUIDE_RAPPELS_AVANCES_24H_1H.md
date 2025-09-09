# Guide Complet - Système de Rappels Avancés 24h et 1h

## 🎯 Vue d'ensemble

Le système de rappels avancés EventHub envoie automatiquement des notifications par e-mail et sur la plateforme **24 heures** et **1 heure** avant chaque événement à tous les participants, organisateurs et invités.

## ✨ Fonctionnalités

### 🔔 Types de rappels
- **Rappel 24h avant** : Notification préventive la veille de l'événement
- **Rappel 1h avant** : Notification urgente 1 heure avant le début
- **Double notification** : E-mail + notification sur la plateforme
- **Ciblage intelligent** : Organisateurs, participants et invités acceptés

### 📧 E-mails personnalisés
- **Design moderne** avec template responsive
- **Contenu adapté** selon le type de rappel (24h ou 1h)
- **Informations complètes** : date, heure, lieu, durée, description
- **Actions directes** : liens vers l'événement

### 🔔 Notifications plateforme
- **Notifications visuelles** dans l'interface
- **Types différenciés** : `event_reminder_24h` et `event_reminder_1h`
- **Messages personnalisés** selon l'urgence

## 🚀 Utilisation

### 1. Exécution manuelle

#### Rappels 24h uniquement
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=24h
```

#### Rappels 1h uniquement
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=1h
```

#### Rappels combinés (recommandé)
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=both
```

### 2. Options avancées

#### Mode test (simulation)
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=both --test-mode
```

#### Mode dry-run (affichage seulement)
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=both --dry-run
```

#### Date forcée
```bash
php bin/console app:send-event-reminders-advanced --reminder-type=both --force-date=2024-12-25
```

### 3. Scripts Windows

#### Exécution simple
```batch
.\send_advanced_reminders.bat
```

#### Tests complets
```batch
.\test_advanced_reminders.bat
```

#### Configuration automatique
```powershell
# Exécuter en tant qu'administrateur
powershell -ExecutionPolicy Bypass -File setup_advanced_reminders.ps1
```

## ⚙️ Configuration automatique

### 1. Configuration via PowerShell (Recommandée)

1. **Ouvrir PowerShell en tant qu'administrateur**
2. **Naviguer vers le projet** : `cd C:\xampp\htdocs\new\maplateforme`
3. **Exécuter le script** : `.\setup_advanced_reminders.ps1`

Le script configure automatiquement :
- ✅ Tâche planifiée quotidienne à 08:00
- ✅ Exécution des rappels 24h et 1h
- ✅ Gestion des erreurs et logs
- ✅ Test automatique de la configuration

### 2. Configuration manuelle

1. **Ouvrir le Planificateur de tâches Windows** (`taskschd.msc`)
2. **Créer une tâche** : "EventHub Advanced Reminders"
3. **Définir le déclencheur** : Quotidien à 08:00
4. **Définir l'action** : `C:\xampp\htdocs\new\maplateforme\run_advanced_reminders.bat`
5. **Configurer les paramètres** :
   - ✅ Exécuter que l'utilisateur soit connecté ou non
   - ✅ Exécuter avec les privilèges les plus élevés
   - ✅ Démarrer la tâche même si l'ordinateur est sur batterie

## 📊 Monitoring et logs

### 1. Logs de la commande
Les logs sont affichés dans la console lors de l'exécution :
```
[INFO] Traitement de 3 événement(s) pour rappels 24h
   📅 Traitement: Formation Python
      ✅ Rappel 24h envoyé à l'organisateur: Jean Dupont
      ✅ Rappel 24h envoyé au participant: Marie Martin
      📊 2 rappel(s) envoyé(s) pour cet événement
```

### 2. Logs Symfony
Consultez les logs dans `var/log/prod.log` :
```bash
tail -f var/log/prod.log | grep "Rappels avancés"
```

### 3. Vérification des e-mails
- Vérifiez la configuration SMTP dans `.env`
- Testez avec `php bin/console app:send-event-reminders-advanced --test-mode`

## 🎨 Personnalisation

### 1. Template d'e-mail
Modifiez `templates/emails/reminder_advanced.html.twig` pour personnaliser :
- Design et couleurs
- Contenu des messages
- Informations affichées
- Actions disponibles

### 2. Messages de notification
Modifiez `src/Service/NotificationService.php` pour personnaliser :
- Titres des notifications
- Contenu des messages
- Types de notifications

### 3. Configuration des rappels
Modifiez `src/Service/AdvancedReminderService.php` pour :
- Changer les délais (ex: 48h et 2h au lieu de 24h et 1h)
- Ajouter d'autres types de rappels
- Modifier la logique de ciblage

## 🔧 Dépannage

### Problèmes courants

#### 1. Les rappels ne sont pas envoyés
**Vérifications :**
- ✅ Configuration SMTP correcte dans `.env`
- ✅ Utilisateurs ont `notify_by_email = true`
- ✅ Événements existent pour la période ciblée
- ✅ Tâche planifiée est active et configurée

**Solutions :**
```bash
# Test de la configuration SMTP
php bin/console app:send-event-reminders-advanced --test-mode

# Vérification des événements
php bin/console app:send-event-reminders-advanced --dry-run
```

#### 2. E-mails non reçus
**Vérifications :**
- ✅ Adresse e-mail valide dans le profil utilisateur
- ✅ Configuration SMTP fonctionnelle
- ✅ Pas de blocage par le fournisseur d'e-mail
- ✅ Vérifier le dossier spam

**Test :**
```bash
# Test d'envoi simple
php bin/console app:send-event-reminders-advanced --reminder-type=24h --test-mode
```

#### 3. Notifications plateforme absentes
**Vérifications :**
- ✅ Utilisateur a `enable_visual_notifications = true`
- ✅ JavaScript activé dans le navigateur
- ✅ Pas d'erreurs dans la console du navigateur

#### 4. Tâche planifiée ne s'exécute pas
**Vérifications :**
- ✅ Tâche est activée dans le Planificateur
- ✅ Utilisateur SYSTEM a les permissions
- ✅ Chemin vers le script est correct
- ✅ PHP est dans le PATH système

**Redémarrage :**
```powershell
# Redémarrer la tâche
Restart-ScheduledTask -TaskName "EventHub Advanced Reminders"
```

### Commandes de diagnostic

#### Vérification complète
```bash
# Test complet avec logs détaillés
php bin/console app:send-event-reminders-advanced --reminder-type=both -v
```

#### Test avec date spécifique
```bash
# Tester avec une date connue
php bin/console app:send-event-reminders-advanced --reminder-type=both --force-date=2024-12-25 --dry-run
```

#### Vérification des événements
```bash
# Lister les événements à venir
php bin/console doctrine:query:sql "SELECT title, date_heure FROM event WHERE date_heure > NOW() ORDER BY date_heure LIMIT 10"
```

## 📈 Statistiques et rapports

### 1. Statistiques d'exécution
La commande affiche un tableau récapitulatif :
```
┌─────────────┬─────────────┬─────────────────┬─────────┐
│ Type        │ Événements  │ Rappels envoyés │ Erreurs │
├─────────────┼─────────────┼─────────────────┼─────────┤
│ 24h avant   │ 3           │ 8               │ 0       │
│ 1h avant    │ 2           │ 5               │ 0       │
│ TOTAL       │ 5           │ 13              │ 0       │
└─────────────┴─────────────┴─────────────────┴─────────┘
```

### 2. Logs détaillés
Chaque exécution génère des logs avec :
- Nombre d'événements traités
- Nombre de rappels envoyés par type
- Erreurs rencontrées
- Détails par événement

## 🔐 Sécurité et permissions

### 1. Permissions requises
- **Lecture** : Base de données, fichiers de configuration
- **Écriture** : Logs, cache Symfony
- **Réseau** : Envoi d'e-mails SMTP

### 2. Données sensibles
- **E-mails** : Stockés de manière sécurisée
- **Tokens** : Générés de manière cryptographiquement sûre
- **Logs** : Ne contiennent pas d'informations sensibles

### 3. Conformité RGPD
- **Consentement** : Utilisateurs peuvent désactiver les notifications
- **Suppression** : Données supprimées lors de la suppression du compte
- **Transparence** : Logs d'audit disponibles

## 🚀 Améliorations futures

### Fonctionnalités prévues
- [ ] **Rappels personnalisables** : L'utilisateur choisit les délais
- [ ] **Rappels récurrents** : Pour les événements répétitifs
- [ ] **Notifications push** : Pour les applications mobiles
- [ ] **Templates personnalisés** : Par organisation
- [ ] **Statistiques avancées** : Taux d'ouverture, clics, etc.

### Intégrations
- [ ] **Calendriers externes** : Google Calendar, Outlook
- [ ] **SMS** : Notifications par SMS
- [ ] **Webhooks** : Intégration avec d'autres systèmes
- [ ] **API REST** : Gestion programmatique des rappels

---

## 📞 Support

Pour toute question ou problème :
1. **Consulter ce guide** en premier
2. **Vérifier les logs** dans `var/log/prod.log`
3. **Tester avec les commandes** de diagnostic
4. **Contacter l'administrateur** système si nécessaire

Le système est conçu pour être robuste et récupérer automatiquement des erreurs temporaires.
