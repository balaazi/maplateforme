# 🚀 Guide Rapide - Configuration Manuelle de la Tâche Planifiée

## ❌ Problème Actuel
Votre tâche "EventHub Reminders" est configurée mais **n'est pas automatique**. Elle nécessite une intervention manuelle.

## ✅ Solution : Reconfiguration Manuelle (5 minutes)

### **Étape 1 : Supprimer la Tâche Existante**
1. Appuyez sur `Windows + R`
2. Tapez : `taskschd.msc`
3. Appuyez sur Entrée
4. Dans la liste, **clic droit** sur "EventHub Reminders"
5. Sélectionnez **"Supprimer"**
6. Confirmez la suppression

### **Étape 2 : Créer une Nouvelle Tâche Automatique**
1. **Clic droit** sur "Bibliothèque du planificateur de tâches"
2. Sélectionnez **"Créer une tâche de base..."**

### **Étape 3 : Configuration de Base**
- **Nom :** `EventHub Reminders`
- **Description :** `Envoi automatique des rappels d'événements EventHub`
- **Déclencheur :** `Quotidien`
- **Heure :** `08:00` (8h00 du matin)
- **Action :** `Démarrer un programme`
- **Programme :** `C:\xampp\htdocs\new\maplateforme\send_reminders.bat`
- **Répertoire de départ :** `C:\xampp\htdocs\new\maplateforme`

### **Étape 4 : Paramètres Avancés (CRUCIAL)**
1. **Clic droit** sur la tâche créée → **"Propriétés"**
2. Onglet **"Général"** :
   - ✅ **"Exécuter que l'utilisateur soit connecté ou non"**
   - ✅ **"Exécuter avec les privilèges les plus élevés"**
3. Onglet **"Conditions"** :
   - ✅ **"Démarrer la tâche seulement si l'ordinateur est connecté au réseau"**
   - ✅ **"Démarrer la tâche si l'ordinateur passe en mode veille"**
4. Onglet **"Paramètres"** :
   - ✅ **"Autoriser l'exécution de la tâche à la demande"**
   - ✅ **"Si la tâche échoue, redémarrer toutes les :"** `1 minute`
   - **"Nombre de tentatives de redémarrage :"** `3`

## 🎯 Résultat Attendu
Après cette configuration :
- ✅ **Tâche 100% automatique** : Aucune intervention requise
- ✅ **Exécution quotidienne** : Tous les jours à 08:00
- ✅ **Redémarrage automatique** : En cas d'échec
- ✅ **Rappels automatiques** : Emails envoyés sans intervention

## 🧪 Test Immédiat
1. **Clic droit** sur la tâche → **"Exécuter maintenant"**
2. Vérifiez que le script s'exécute
3. Consultez les logs : `logs\reminders_output.log`

## 🔍 Vérification
```powershell
# Vérifier le statut
Get-ScheduledTask -TaskName "EventHub Reminders"

# Voir les détails
Get-ScheduledTask -TaskName "EventHub Reminders" | Get-ScheduledTaskInfo
```

## 💡 Alternative : Script PowerShell
Si vous préférez l'automatisation :
```powershell
# Exécuter en tant qu'administrateur
powershell -ExecutionPolicy Bypass -File corriger_tache_automatique.ps1
```

---

**⏱️ Temps de configuration manuelle : 5 minutes**  
**✅ Résultat : Système 100% automatique !**
