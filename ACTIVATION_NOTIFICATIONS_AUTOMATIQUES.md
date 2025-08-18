# 🚀 Guide d'Activation des Notifications Automatiques - EventHub

## 🎯 Problème Résolu
**"Je veux recevoir les notifications (rappels) automatiquement"**

## ✅ Diagnostic Effectué

### 🔍 État Actuel du Système
- ✅ **Rappels créés** : Les rappels sont automatiquement créés en base de données
- ✅ **Tâche planifiée** : Windows Task Scheduler configuré pour 10:35 quotidiennement
- ✅ **Préférences utilisateur** : Toutes activées (email, visuel, sonore)
- ✅ **Système JavaScript** : Vérification automatique toutes les 10 secondes

### ❌ Problèmes Identifiés
1. **Configuration email** : MAILER_DSN configuré mais emails non reçus
2. **Notifications frontend** : JavaScript fonctionne mais peut être bloqué par le navigateur
3. **Permissions navigateur** : Notifications du navigateur non autorisées

---

## 🔧 Solutions Complètes

### 1. **Activation des Notifications Navigateur**

#### Étape 1 : Autoriser les Notifications
1. **Ouvrez EventHub** dans votre navigateur
2. **Connectez-vous** avec votre compte
3. **Cliquez sur l'icône de notification** (🔔) dans la barre de navigation
4. **Autorisez les notifications** quand le navigateur le demande

#### Étape 2 : Vérifier les Permissions
```javascript
// Dans la console du navigateur (F12)
if ('Notification' in window) {
    console.log('Notifications supportées');
    console.log('Permission:', Notification.permission);
    
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
}
```

### 2. **Configuration Email Automatique**

#### Vérification de la Configuration
```bash
# Vérifier la configuration email
php bin/console debug:config framework mailer

# Tester l'envoi d'email
php bin/console mailer:test votre-email@gmail.com
```

#### Configuration Gmail (Recommandée)
```env
# Dans .env
MAILER_DSN=smtp://votre-email@gmail.com:votre-mot-de-passe-app@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

### 3. **Activation des Notifications Temps Réel**

#### Vérification JavaScript
Le système vérifie automatiquement les rappels toutes les 10 secondes :

```javascript
// Fonction automatique dans base.html.twig
checkReminders(); // Vérifie immédiatement
setInterval(checkReminders, 10000); // Puis toutes les 10 secondes
```

#### Test Manuel
```javascript
// Dans la console du navigateur
checkReminders(); // Force une vérification immédiate
```

### 4. **Vérification des Préférences Utilisateur**

#### Accès aux Préférences
1. **Connectez-vous** à EventHub
2. **Allez dans Profil** → **Préférences de notification**
3. **Vérifiez que tout est activé** :
   - ✅ Notifications par email
   - ✅ Notifications visuelles
   - ✅ Sons de notification
   - ✅ Fréquence des rappels : 1 heure

#### Configuration Recommandée
```php
// Dans votre profil utilisateur
notify_by_email = true
enable_visual_notifications = true
enable_sound_notifications = true
reminder_frequency = 1
notification_priority = 'normal'
```

---

## 🎮 Test et Vérification

### 1. **Test Immédiat des Notifications**

#### Créer un Événement de Test
1. **Créez un événement** pour dans 5 minutes
2. **Attendez** que le système crée automatiquement les rappels
3. **Vérifiez** que vous recevez les notifications

#### Test Manuel des Rappels
```bash
# Forcer l'envoi des rappels
php bin/console app:send-event-reminders

# Traiter les rappels en attente
php bin/console app:process-reminders

# Créer des rappels manquants
php bin/console app:create-missing-reminders --future-only
```

### 2. **Vérification des Logs**

#### Consulter les Logs
```bash
# Logs des rappels
type logs\reminders_output.log

# Logs généraux
type var\log\dev.log
```

#### Exemple de Log Réussi
```
🔔 Envoi des rappels d'événements
=================================

📅 Traitement de l'événement: Séminaire
   ✅ Rappel envoyé à l'organisateur: balaazi neda
   📊 1 rappel(s) envoyé(s) pour cet événement

[OK] ✅ Processus terminé: 1 rappel(s) envoyé(s) au total
```

### 3. **Test Frontend**

#### Vérification JavaScript
```javascript
// Dans la console du navigateur
// 1. Vérifier que les fonctions existent
typeof checkReminders; // doit retourner 'function'

// 2. Forcer une vérification
checkReminders();

// 3. Vérifier les notifications en attente
fetch('/api/reminders/check')
    .then(response => response.json())
    .then(data => console.log('Notifications:', data));
```

---

## 🔧 Dépannage Avancé

### Problème : "Aucune notification reçue"

#### Solutions :
1. **Vérifiez les permissions navigateur**
   - Chrome : Settings → Privacy → Notifications
   - Firefox : Settings → Privacy → Permissions

2. **Testez l'API directement**
   ```bash
   curl -X GET http://localhost/api/reminders/check
   ```

3. **Vérifiez les rappels en base**
   ```sql
   SELECT * FROM reminder WHERE user_id = 1 AND is_triggered = 0;
   ```

### Problème : "Emails non reçus"

#### Solutions :
1. **Vérifiez la configuration email**
   ```bash
   php bin/console debug:config framework mailer
   ```

2. **Testez l'envoi d'email**
   ```bash
   php bin/console mailer:test votre-email@gmail.com
   ```

3. **Vérifiez les logs d'erreur**
   ```bash
   type var\log\dev.log | findstr "mailer\|email"
   ```

### Problème : "Notifications sans son"

#### Solutions :
1. **Vérifiez les préférences utilisateur**
   - `enable_sound_notifications = true`

2. **Testez les sons**
   ```javascript
   // Dans la console navigateur
   playNotificationSound('/sounds/reminder.mp3');
   ```

3. **Vérifiez les fichiers audio**
   - `/public/sounds/reminder.mp3` doit exister

---

## 📱 Types de Notifications Disponibles

### 1. **Notifications Visuelles**
- ✅ **Bulles de notification** avec animations
- ✅ **Badge de notification** dans la barre de navigation
- ✅ **Notifications toast** temporaires
- ✅ **Couleurs selon la priorité** (rouge, orange, vert)

### 2. **Notifications Sonores**
- ✅ **Sons différents** selon le type de notification
- ✅ **Volume configurable** selon les préférences
- ✅ **Sons optionnels** (peuvent être désactivés)

### 3. **Notifications Email**
- ✅ **Templates personnalisés** selon le type d'événement
- ✅ **Informations complètes** (titre, date, lieu, participants)
- ✅ **Liens directs** vers l'événement

### 4. **Notifications Temps Réel**
- ✅ **Vérification automatique** toutes les 10 secondes
- ✅ **Actions interactives** (ignorer, reporter, voir événement)
- ✅ **Gestion des priorités** (urgent, normal, faible)

---

## 🎯 Résumé des Actions à Effectuer

### ✅ **Actions Immédiates**
1. **Autorisez les notifications** dans votre navigateur
2. **Vérifiez vos préférences** dans votre profil EventHub
3. **Testez avec un événement** dans 5 minutes
4. **Consultez les logs** pour vérifier le fonctionnement

### ✅ **Configuration Permanente**
1. **Configurez votre email** dans les préférences
2. **Activez tous les types** de notifications
3. **Testez régulièrement** le système
4. **Surveillez les logs** en cas de problème

### ✅ **Maintenance**
1. **Vérifiez quotidiennement** les logs
2. **Testez mensuellement** l'envoi d'emails
3. **Mettez à jour** les préférences si nécessaire
4. **Signalez les problèmes** pour correction

---

## 🎉 Résultat Attendu

Après avoir suivi ce guide, vous devriez recevoir :

- ✅ **Notifications visuelles** automatiques toutes les 10 secondes
- ✅ **Emails de rappel** selon vos préférences
- ✅ **Sons de notification** (si activés)
- ✅ **Badges de notification** dans la barre de navigation
- ✅ **Notifications toast** avec actions interactives

**Les notifications apparaîtront automatiquement sans action de votre part !** 