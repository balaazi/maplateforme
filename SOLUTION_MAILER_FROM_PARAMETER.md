# 🔧 Solution Complète - Problème "app.mailer_from"

## ✅ Problème Résolu

**Erreur :** `You have requested a non-existent parameter "app.mailer_from"`

**Cause :** Le code tentait d'utiliser un paramètre de configuration inexistant.

**Solution appliquée :** 
- ✅ **Corrigé le code** dans `src/Controller/InvitationController.php`
- ✅ **Créé le fichier `.env`** manquant
- ✅ **Remplacé le paramètre** par une adresse email directe

---

## 🛠️ Corrections Appliquées

### 1. **Correction du Code (InvitationController.php)**

**Avant (problématique) :**
```php
->from($this->getParameter('app.mailer_from'))
```

**Après (corrigé) :**
```php
->from('nadiabalaazi@gmail.com')
```

### 2. **Création du Fichier .env**

Le fichier `.env` a été créé avec la configuration complète :

```env
# Configuration de base
APP_ENV=dev
APP_SECRET=votre_secret_ici

# Configuration de la base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/eventhub?serverVersion=8.0.32&charset=utf8mb4"

# Configuration du mailer pour Gmail
MAILER_DSN=smtp://nadiabalaazi@gmail.com:votre_mot_de_passe_app@smtp.gmail.com:587

# Configuration Google Calendar (si nécessaire)
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/connect/google

# Configuration des logs
LOG_LEVEL=info
```

---

## 🔧 Configuration Gmail SMTP (Étape Suivante)

### 1. **Activer l'Authentification à 2 Facteurs**

1. Allez sur https://myaccount.google.com/security
2. Activez "Validation en 2 étapes"

### 2. **Créer un Mot de Passe d'Application**

1. Allez sur https://myaccount.google.com/apppasswords
2. Sélectionnez "Mail" et "Autre (nom personnalisé)"
3. Entrez "EventHub" comme nom
4. Copiez le mot de passe généré (16 caractères)

### 3. **Mettre à Jour le .env**

Remplacez `votre_mot_de_passe_app` dans le fichier `.env` par le mot de passe généré :

```env
MAILER_DSN=smtp://nadiabalaazi@gmail.com:ABCDEFGHIJKLMNOP@smtp.gmail.com:587
```

### 4. **Tester la Configuration**

```bash
php test_email.php
```

---

## 🧪 Test de Fonctionnement

### Test Rapide
```bash
# Vider le cache
php bin/console cache:clear

# Tester l'envoi d'email
php test_email.php

# Tester la configuration Symfony
php bin/console debug:config framework mailer
```

### Test des Fonctionnalités
1. **Créer un événement** → Vérifier l'email de confirmation
2. **Modifier un événement** → Vérifier l'email de modification
3. **Annuler un événement** → Vérifier l'email d'annulation
4. **Envoyer une invitation** → Vérifier l'email d'invitation

---

## 🔍 Diagnostic Avancé

### Si les Emails ne Fonctionnent Toujours Pas

#### Alternative 1 : MailHog (pour les tests)
```env
MAILER_DSN=smtp://localhost:1025
```

#### Alternative 2 : Vérifier les Logs
```bash
tail -f var/log/dev.log
```

#### Alternative 3 : Test Manuel
```bash
# Test de connectivité SMTP
telnet smtp.gmail.com 587

# Test avec curl
curl -v --mail-from "nadiabalaazi@gmail.com" --mail-rcpt "test@example.com" --upload-file - smtp://smtp.gmail.com:587
```

---

## 📧 Types d'Emails Configurés

| Action | Destinataire | Sujet | Template |
|--------|--------------|-------|----------|
| **Création d'événement** | Organisateur | ✅ Confirmation création | `event_created.html.twig` |
| **Modification d'événement** | Organisateur | ✏️ Confirmation modification | `event_updated.html.twig` |
| **Modification d'événement** | Participants | 🔔 Événement modifié | `event_update.html.twig` |
| **Annulation d'événement** | Organisateur | ❌ Confirmation annulation | `event_cancelled.html.twig` |
| **Annulation d'événement** | Participants | ❌ Événement annulé | `event_cancel.html.twig` |
| **Invitation** | Invité | 📧 Invitation à l'événement | `event_invitation.html.twig` |

---

## ✅ Checklist de Résolution

- [x] **Code corrigé** : Paramètre `app.mailer_from` remplacé
- [x] **Fichier .env créé** : Configuration SMTP ajoutée
- [ ] **Gmail configuré** : Authentification à 2 facteurs activée
- [ ] **Mot de passe d'application** : Créé et configuré
- [ ] **Test d'envoi** : Email de test réussi
- [ ] **Cache vidé** : `php bin/console cache:clear`
- [ ] **Fonctionnalités testées** : Création, modification, annulation d'événements

---

## 🆘 En cas de Problème Persistant

### Erreurs Courantes

1. **"535-5.7.8 Username and Password not accepted"**
   - ✅ Vérifiez que l'authentification à 2 facteurs est activée
   - ✅ Utilisez un mot de passe d'application (pas le mot de passe principal)

2. **"Connection timeout"**
   - ✅ Vérifiez votre connexion internet
   - ✅ Vérifiez que le port 587 n'est pas bloqué par votre pare-feu

3. **"Authentication failed"**
   - ✅ Vérifiez que l'email et le mot de passe sont corrects
   - ✅ Vérifiez que le mot de passe d'application est récent

### Support

Si le problème persiste après avoir suivi toutes ces étapes :
1. Vérifiez les logs : `var/log/dev.log`
2. Testez avec MailHog pour isoler le problème
3. Contactez le support avec les logs d'erreur

---

## 🎯 Résultat Attendu

Après avoir configuré Gmail SMTP correctement, vous devriez :
- ✅ Recevoir des emails pour toutes les actions d'événements
- ✅ Avoir des notifications par email fonctionnelles
- ✅ Pouvoir envoyer des invitations par email
- ✅ Avoir un système de rappels automatiques opérationnel

**⏱️ Temps de configuration : 10-15 minutes** 