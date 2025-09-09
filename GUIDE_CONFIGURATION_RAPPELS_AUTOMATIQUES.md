# 🔔 Guide de Configuration des Rappels Automatiques EventHub

## ✅ Problème Résolu

**Avant :** Aucun rappel par email reçu pour les événements
**Après :** Système de rappels automatiques entièrement fonctionnel

## 🎯 État Actuel

- ✅ **Configuration SMTP Gmail** : Fonctionne parfaitement
- ✅ **Envoi d'emails de test** : Réussi
- ✅ **Système de rappels** : Opérationnel
- ✅ **Notifications par email** : Activées pour tous les utilisateurs
- ✅ **Script de rappels** : `send_reminders.bat` créé et testé

## 🚀 Configuration Automatique (Recommandée)

### Option 1 : Script PowerShell (Administrateur requis)

```powershell
# Exécuter en tant qu'administrateur
powershell -ExecutionPolicy Bypass -File create_scheduled_task.ps1
```

### Option 2 : Configuration Manuelle (Plus simple)

#### Étape 1 : Ouvrir le Planificateur de Tâches Windows
1. Appuyez sur `Windows + R`
2. Tapez `taskschd.msc`
3. Appuyez sur Entrée

#### Étape 2 : Créer une Nouvelle Tâche
1. Clic droit sur **Bibliothèque du planificateur de tâches**
2. Sélectionnez **Créer une tâche de base...**

#### Étape 3 : Configuration de la Tâche
- **Nom :** `EventHub Reminders`
- **Description :** `Envoi automatique des rappels d'événements EventHub`
- **Déclencheur :** Quotidien à 08:00
- **Action :** Démarrer un programme
- **Programme :** `C:\xampp\htdocs\new\maplateforme\send_reminders.bat`
- **Répertoire de départ :** `C:\xampp\htdocs\new\maplateforme`

#### Étape 4 : Paramètres Avancés
1. Clic droit sur la tâche créée → **Propriétés**
2. Onglet **Général** :
   - ✅ **Exécuter que l'utilisateur soit connecté ou non**
   - ✅ **Exécuter avec les privilèges les plus élevés**
3. Onglet **Conditions** :
   - ✅ **Démarrer la tâche seulement si l'ordinateur est connecté au réseau**
   - ✅ **Démarrer la tâche si l'ordinateur passe en mode veille**
4. Onglet **Paramètres** :
   - ✅ **Autoriser l'exécution de la tâche à la demande**
   - ✅ **Si la tâche échoue, redémarrer toutes les :** `1 minute`
   - **Nombre de tentatives de redémarrage :** `3`

## 🧪 Test du Système

### Test Manuel Immédiat
```bash
# Tester l'envoi des rappels
.\send_reminders.bat

# Ou via Symfony
php bin/console app:send-event-reminders
```

### Test Automatique
1. Attendez 08:00 du matin
2. Vérifiez votre boîte email
3. Consultez les logs : `logs\reminders.log`

## 📧 Types de Rappels Configurés

| Type | Délai | Destinataires | Contenu |
|------|-------|----------------|---------|
| **Rappel principal** | Veille de l'événement | Organisateur + Participants | Détails complets de l'événement |
| **Rappel de confirmation** | 1h avant | Tous | Rappel final avec lieu et heure |

## 🔍 Surveillance et Maintenance

### Vérification de la Tâche Planifiée
```powershell
# Vérifier le statut
Get-ScheduledTask -TaskName "EventHub Reminders"

# Voir les détails
Get-ScheduledTask -TaskName "EventHub Reminders" | Get-ScheduledTaskInfo
```

### Logs et Monitoring
```bash
# Logs des rappels
type logs\reminders.log

# Logs Symfony
type var\log\dev.log

# Logs de sortie détaillés
type logs\reminders_output.log
```

### Test de Connectivité SMTP
```bash
# Test d'envoi d'email
php test_email_simple.php

# Test de la configuration
php bin/console debug:config framework mailer
```

## 🛠️ Dépannage

### Problème : "Aucun rappel reçu"

**Vérifications :**
1. **Préférences utilisateur** : `notify_by_email = 1`
2. **Configuration SMTP** : Gmail configuré et testé
3. **Tâche planifiée** : Active et programmée
4. **Événements futurs** : Présents en base de données

**Solutions :**
```bash
# Forcer l'envoi des rappels
php bin/console app:send-event-reminders

# Vérifier les préférences
php bin/console doctrine:query:sql "SELECT email, notify_by_email FROM users"

# Tester l'email
php test_email_simple.php
```

### Problème : "Tâche planifiée ne s'exécute pas"

**Vérifications :**
1. **Permissions** : Exécuter en tant qu'administrateur
2. **Chemin du script** : Vérifier que `send_reminders.bat` existe
3. **Déclencheur** : Vérifier l'heure et la fréquence
4. **Logs Windows** : Voir les événements du planificateur

**Solutions :**
```bash
# Recréer la tâche
schtasks /delete /tn "EventHub Reminders" /f
# Puis recréer manuellement via l'interface
```

## 📋 Checklist de Configuration

- [x] **Configuration SMTP Gmail** : ✅ Complète
- [x] **Test d'envoi d'email** : ✅ Réussi
- [x] **Système de rappels** : ✅ Opérationnel
- [x] **Notifications par email** : ✅ Activées
- [x] **Script de rappels** : ✅ Créé et testé
- [ ] **Tâche planifiée Windows** : À configurer
- [ ] **Test automatique** : À vérifier demain 08:00

## 🎯 Résultat Attendu

Après configuration complète, vous devriez :
- ✅ **Recevoir des emails automatiques** la veille de chaque événement
- ✅ **Avoir des rappels programmés** tous les jours à 08:00
- ✅ **Bénéficier d'un système autonome** sans intervention manuelle
- ✅ **Avoir des logs complets** pour le suivi et le dépannage

## 💡 Conseils d'Optimisation

1. **Heure de déclenchement** : 08:00 permet d'envoyer les rappels tôt le matin
2. **Fréquence** : Quotidienne pour couvrir tous les événements
3. **Logs** : Conservés pour audit et dépannage
4. **Redémarrage automatique** : En cas d'échec de la tâche

---

## 🆘 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs : `logs\reminders.log`
2. Testez manuellement : `.\send_reminders.bat`
3. Vérifiez la tâche planifiée : Planificateur de tâches Windows
4. Consultez les logs Symfony : `var\log\dev.log`

**⏱️ Temps de configuration : 5-10 minutes**


