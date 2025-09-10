# Configuration du Cron Job Windows pour les Rappels d'Invitations

## 🎯 Vue d'ensemble

Ce guide explique comment configurer l'exécution automatique des rappels d'invitations sur Windows en utilisant le Planificateur de tâches.

## 📋 Prérequis

- Windows 10/11 ou Windows Server
- XAMPP installé et configuré
- PHP accessible via la ligne de commande
- Projet EventHub installé

## 🚀 Configuration étape par étape

### 1. Ouvrir le Planificateur de tâches

1. Appuyez sur `Windows + R`
2. Tapez `taskschd.msc` et appuyez sur Entrée
3. Le Planificateur de tâches s'ouvre

### 2. Créer une tâche de base

1. Dans le panneau de droite, cliquez sur **"Créer une tâche..."**
2. Donnez un nom à votre tâche : `EventHub - Rappels d'Invitations`
3. Cochez **"Exécuter que l'utilisateur soit connecté ou non"**
4. Cochez **"Exécuter avec les privilèges les plus élevés"**

### 3. Configurer le déclencheur

1. Allez dans l'onglet **"Déclencheurs"**
2. Cliquez sur **"Nouveau..."**
3. Configurez comme suit :
   - **Commencer la tâche** : À une heure programmée
   - **Répéter la tâche** : Toutes les 30 minutes
   - **Durée** : Indéfiniment
   - **Activé** : ✓

### 4. Configurer l'action

1. Allez dans l'onglet **"Actions"**
2. Cliquez sur **"Nouveau..."**
3. Configurez comme suit :
   - **Action** : Démarrer un programme
   - **Programme/script** : `C:\xampp\htdocs\new\maplateforme\cron_invitation_reminders.bat`
   - **Démarrer dans** : `C:\xampp\htdocs\new\maplateforme`

### 5. Configurer les conditions (optionnel)

1. Allez dans l'onglet **"Conditions"**
2. Décochez **"Démarrer la tâche seulement si l'ordinateur est sur secteur"**
3. Cochez **"Réveiller l'ordinateur pour exécuter cette tâche"**

### 6. Configurer les paramètres

1. Allez dans l'onglet **"Paramètres"**
2. Cochez **"Autoriser l'exécution de la tâche à la demande"**
3. Cochez **"Exécuter la tâche dès que possible après un démarrage programmé manqué"**
4. Cochez **"Si la tâche échoue, redémarrer toutes les X minutes"**
5. Définissez **"Arrêter la tâche si elle s'exécute plus de"** : 1 heure

### 7. Tester la tâche

1. Cliquez sur **"OK"** pour sauvegarder
2. Dans la liste des tâches, trouvez votre tâche
3. Clic droit → **"Exécuter"** pour tester
4. Vérifiez les logs dans `var\log\invitation_reminders.log`

## 🔧 Configuration avancée

### Exécution à des heures spécifiques

Si vous préférez exécuter à des heures fixes plutôt que toutes les 30 minutes :

1. Créez **deux tâches séparées** :
   - **Tâche 1** : Rappels 24h - Exécution à 9h00
   - **Tâche 2** : Rappels 1h - Exécution à 18h00

2. Pour chaque tâche, configurez le déclencheur :
   - **Commencer la tâche** : À une heure programmée
   - **Répéter la tâche** : Quotidiennement
   - **Heure** : 09:00:00 (ou 18:00:00)

### Scripts séparés pour chaque type de rappel

Créez des scripts séparés :

**cron_reminders_24h.bat** :
```batch
@echo off
cd /d "C:\xampp\htdocs\new\maplateforme"
C:\xampp\php\php.exe bin/console app:send-invitation-reminders --reminder-type=24h >> var\log\reminders_24h.log 2>&1
```

**cron_reminders_1h.bat** :
```batch
@echo off
cd /d "C:\xampp\htdocs\new\maplateforme"
C:\xampp\php\php.exe bin/console app:send-invitation-reminders --reminder-type=1h >> var\log\reminders_1h.log 2>&1
```

## 📊 Monitoring et logs

### Vérification des logs

1. Ouvrez le fichier : `C:\xampp\htdocs\new\maplateforme\var\log\invitation_reminders.log`
2. Vérifiez que les tâches s'exécutent correctement
3. Surveillez les erreurs éventuelles

### Exemple de log

```
[15/01/2024 09:00:01] === Début du traitement des rappels d'invitations ===
[15/01/2024 09:00:02] Envoi des rappels 24h avant...
[15/01/2024 09:00:05] Rappels 24h envoyés avec succès
[15/01/2024 09:00:06] Envoi des rappels 1h avant...
[15/01/2024 09:00:08] Rappels 1h envoyés avec succès
[15/01/2024 09:00:09] === Fin du traitement des rappels d'invitations ===
```

## 🚨 Dépannage

### Problèmes courants

1. **La tâche ne s'exécute pas**
   - Vérifiez que l'utilisateur a les permissions nécessaires
   - Vérifiez que le chemin vers PHP est correct
   - Vérifiez que le projet EventHub est accessible

2. **Erreurs dans les logs**
   - Vérifiez la configuration SMTP dans `.env`
   - Vérifiez que la base de données est accessible
   - Vérifiez que les templates d'email existent

3. **Emails non envoyés**
   - Testez avec `--test-mode` d'abord
   - Vérifiez la configuration SMTP
   - Vérifiez que les invitations existent dans la base de données

### Commandes de test

```batch
REM Test en mode dry-run
cd C:\xampp\htdocs\new\maplateforme
C:\xampp\php\php.exe bin/console app:send-invitation-reminders --dry-run

REM Test en mode test
C:\xampp\php\php.exe bin/console app:send-invitation-reminders --test-mode

REM Vérifier les statistiques
C:\xampp\php\php.exe bin/console app:send-invitation-reminders --stats
```

## 🔒 Sécurité

### Bonnes pratiques

1. **Utilisateur de service** : Créez un utilisateur Windows dédié pour les tâches automatiques
2. **Permissions minimales** : Donnez seulement les permissions nécessaires
3. **Logs sécurisés** : Protégez les fichiers de logs contre l'accès non autorisé
4. **Rotation des logs** : Configurez la suppression automatique des anciens logs

### Configuration d'un utilisateur de service

1. Créez un utilisateur Windows : `EventHubService`
2. Donnez-lui les permissions de lecture/écriture sur le projet
3. Configurez la tâche pour s'exécuter avec cet utilisateur
4. Testez que l'utilisateur peut exécuter les commandes manuellement

## 📈 Optimisation

### Performance

1. **Exécution en parallèle** : Si vous avez beaucoup d'événements, considérez l'exécution en parallèle
2. **Base de données** : Optimisez les requêtes de base de données
3. **Cache** : Utilisez le cache Symfony pour les données fréquemment accédées

### Monitoring avancé

1. **Alertes email** : Configurez des alertes en cas d'échec
2. **Métriques** : Surveillez le nombre d'emails envoyés
3. **Temps d'exécution** : Surveillez la durée d'exécution des tâches

---

## ✅ Checklist de configuration

- [ ] Planificateur de tâches ouvert
- [ ] Tâche créée avec le bon nom
- [ ] Déclencheur configuré (toutes les 30 minutes)
- [ ] Action configurée (script .bat)
- [ ] Répertoire de travail défini
- [ ] Tâche testée manuellement
- [ ] Logs vérifiés
- [ ] Configuration SMTP testée
- [ ] Tâche activée et en cours d'exécution

Une fois tous ces éléments cochés, votre système de rappels d'invitations sera entièrement automatisé ! 🎉
