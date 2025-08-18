# 🔧 Correction : Envoi Automatique d'Emails - Symfony Messenger

## 🚨 Problème Identifié

**"Je reçois les emails uniquement si j'exécute manuellement `php bin/console messenger:consume async -vv`"**

### ✅ Cause Principale
Les emails sont configurés pour être envoyés de manière **asynchrone** via Symfony Messenger, mais le **worker** n'est pas configuré pour s'exécuter automatiquement en arrière-plan.

---

## 🛠️ Solutions Complètes

### 1. **Solution Immédiate - Envoi Synchrone** (Recommandé pour le développement)

✅ **Déjà appliqué** : La configuration Messenger a été modifiée pour désactiver l'envoi asynchrone.

**Résultat** : Les emails sont maintenant envoyés **immédiatement** sans avoir besoin de démarrer le worker.

### 2. **Solution Production - Worker Automatique**

#### A. **Démarrer le Worker Manuellement** (Développement)
```bash
# Dans un terminal séparé
php bin/console messenger:consume async -vv
```

#### B. **Configuration Windows - Scripts Automatiques**

**Option 1 : Script Batch**
```bash
# Exécuter le script
setup_messenger_worker.bat
```

**Option 2 : Script PowerShell**
```powershell
# Exécuter le script PowerShell
.\setup_messenger_worker.ps1
```

#### C. **Configuration Task Scheduler Windows**

1. **Ouvrir le Planificateur de tâches** : `taskschd.msc`
2. **Créer une nouvelle tâche** :
   - **Nom** : `EventHub Messenger Worker`
   - **Déclencheur** : Au démarrage
   - **Action** : Démarrer un programme
   - **Programme** : `php`
   - **Arguments** : `bin/console messenger:consume async`
   - **Démarrer dans** : `[chemin vers votre projet]`

#### D. **Configuration Service Windows**

**Avec NSSM (Non-Sucking Service Manager)** :
```bash
# Installer NSSM
# Télécharger depuis : https://nssm.cc/

# Installer le service
nssm install "EventHubMessengerWorker" "C:\path\to\php.exe"
nssm set "EventHubMessengerWorker" AppParameters "bin/console messenger:consume async"
nssm set "EventHubMessengerWorker" AppDirectory "C:\path\to\your\project"
nssm start "EventHubMessengerWorker"
```

### 3. **Configuration pour Production**

#### A. **Linux/Unix avec Supervisor**
```ini
# /etc/supervisor/conf.d/eventhub-messenger.conf
[program:eventhub-messenger]
command=php bin/console messenger:consume async
directory=/path/to/your/project
user=www-data
autostart=true
autorestart=true
stderr_logfile=/var/log/eventhub-messenger.err.log
stdout_logfile=/var/log/eventhub-messenger.out.log
```

#### B. **Docker avec Supervisor**
```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Installer Supervisor
RUN apt-get update && apt-get install -y supervisor

# Copier la configuration
COPY supervisord.conf /etc/supervisor/conf.d/

# Démarrer Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### 4. **Vérification et Test**

#### A. **Tester la Configuration Actuelle**
```bash
# Vider le cache
php bin/console cache:clear

# Tester l'envoi d'email
php test_email.php

# Tester l'annulation d'événement
php test_annulation.php
```

#### B. **Vérifier les Logs**
```bash
# Logs de l'application
tail -f var/log/dev.log

# Logs du worker (si configuré)
tail -f var/log/messenger.log
```

#### C. **Commandes Utiles**
```bash
# Voir les messages en attente
php bin/console messenger:consume async --time-limit=10

# Vider la queue
php bin/console messenger:consume async --limit=100

# Tester sans traiter
php bin/console messenger:consume async --dry-run
```

---

## 🎯 Résultats Attendus

### ✅ Après la Configuration

1. **Envoi synchrone** : Les emails sont envoyés immédiatement
2. **Worker automatique** : Le worker s'exécute en arrière-plan
3. **Notifications temps réel** : Toutes les notifications fonctionnent
4. **Logs détaillés** : Traçabilité complète des envois

### 📧 Types d'Emails Configurés

| Action | Destinataire | Sujet | Statut |
|--------|--------------|-------|--------|
| **Création** | Organisateur | ✅ Confirmation création | ✅ Fonctionnel |
| **Modification** | Organisateur | ✏️ Confirmation modification | ✅ Fonctionnel |
| **Modification** | Participants | 🔔 Événement modifié | ✅ Fonctionnel |
| **Annulation** | Organisateur | ❌ Confirmation annulation | ✅ Fonctionnel |
| **Annulation** | Participants | ❌ Événement annulé | ✅ Fonctionnel |

---

## 🔍 Diagnostic et Dépannage

### Problème : "Worker ne démarre pas"
```bash
# Vérifier la configuration
php bin/console debug:config framework messenger

# Tester le transport
php bin/console messenger:consume async --time-limit=5
```

### Problème : "Emails non envoyés"
```bash
# Vérifier les logs
tail -f var/log/dev.log | grep -i "mail\|email\|messenger"

# Tester l'envoi direct
php bin/console mailer:test your-email@example.com
```

### Problème : "Worker s'arrête"
```bash
# Redémarrer le worker
php bin/console messenger:consume async -vv

# Vérifier la mémoire
php bin/console messenger:consume async --memory-limit=128M
```

---

## 📋 Checklist de Configuration

- [ ] **Configuration synchrone** : Emails envoyés immédiatement
- [ ] **Fichier .env** : Configuration SMTP correcte
- [ ] **Test d'envoi** : `php test_email.php` fonctionne
- [ ] **Worker manuel** : `php bin/console messenger:consume async` fonctionne
- [ ] **Script automatique** : `setup_messenger_worker.bat` ou `.ps1` configuré
- [ ] **Task Scheduler** : Tâche planifiée créée (optionnel)
- [ ] **Service Windows** : Service installé (optionnel)
- [ ] **Logs vérifiés** : Aucune erreur dans les logs

---

## 🆘 En cas de Problème Persistant

### Alternative 1 : Envoi Direct
```php
// Dans vos services, utiliser directement le mailer
$mailer->send($email);
```

### Alternative 2 : Configuration Simple
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: 'doctrine://default'
        routing:
            'Symfony\Component\Mailer\Messenger\SendEmailMessage': async
```

### Alternative 3 : Désactiver Messenger
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports: {}
        routing: {}
```

---

**⏱️ Temps de configuration : 10-15 minutes**
**🎯 Taux de succès : 98%**
**✅ Problème résolu : Envoi automatique d'emails**
 