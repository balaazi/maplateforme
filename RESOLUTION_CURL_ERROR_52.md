# Résolution erreur cURL 52 : Empty reply from server

## 🚨 Diagnostic du problème

L'erreur `cURL error 52: Empty reply from server` pour `https://oauth2.googleapis.com/token` indique que :

1. **Variables d'environnement manquantes** : Les identifiants Google ne sont pas configurés
2. **Token corrompu** : Le token d'accès est invalide ou expiré
3. **Problème de connectivité** : Firewall ou proxy bloque les requêtes HTTPS

## 🔧 Solutions immédiates

### 1. Créer le fichier .env manquant

Créez un fichier `.env` à la racine avec :

```env
APP_ENV=dev
APP_SECRET=your_secret_here

# REMPLACEZ PAR VOS VRAIS IDENTIFIANTS GOOGLE
GOOGLE_CLIENT_ID=votre_client_id_google
GOOGLE_CLIENT_SECRET=votre_client_secret_google  
GOOGLE_REDIRECT_URI=http://localhost/maplateforme/public/oauth/callback
GOOGLE_TOKEN_EXPIRATION=3600

DATABASE_URL="mysql://root:@localhost:3306/maplateforme"
MAILER_DSN=smtp://localhost:1025
```

### 2. Obtenir les identifiants Google

1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un projet ou sélectionnez-en un
3. Activez l'API Google Calendar
4. Créez des identifiants OAuth 2.0 :
   - Type : Application Web
   - URI de redirection : `http://localhost/maplateforme/public/oauth/callback`

### 3. Nettoyer les tokens existants

Supprimez ces fichiers s'ils existent :
- `tokens/google_token.json`
- `var/google-token.json`

### 4. Test de connectivité

```bash
# Tester la connectivité vers Google
curl -I https://oauth2.googleapis.com/token
```

## 🔄 Processus de reconnexion

1. **Configurez le .env** avec vos vrais identifiants
2. **Supprimez les anciens tokens**
3. **Redémarrez votre serveur**
4. **Allez sur** `/oauth/connect` dans votre navigateur
5. **Autorisez l'application** dans Google
6. **Testez** la création d'événements

## ⚠️ Points importants

- Les identifiants doivent être **réels** (pas de placeholder)
- L'URI de redirection doit **correspondre exactement**
- Le projet Google doit avoir l'**API Calendar activée**
- Votre serveur doit avoir accès à **HTTPS vers Google**

## 🧪 Script de test

Utilisez le script existant :
```bash
php test_google.php
```

Cela vous dira exactement où est le problème dans votre configuration. 