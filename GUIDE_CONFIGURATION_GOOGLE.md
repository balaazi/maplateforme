# Guide de Configuration Google OAuth

## 🔧 Configuration des identifiants Google

### Étape 1 : Créer un projet Google Cloud
1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un nouveau projet ou sélectionnez un projet existant
3. Activez les APIs nécessaires :
   - Google Calendar API
   - Google Drive API

### Étape 2 : Configurer l'écran de consentement OAuth
1. Dans le menu latéral, allez à **APIs & Services > OAuth consent screen**
2. Choisissez **External** pour les utilisateurs externes
3. Remplissez les informations obligatoires :
   - Nom de l'application : "MaPlateforme"
   - Email de support utilisateur
   - Logo de l'application (optionnel)

### Étape 3 : Créer les identifiants OAuth 2.0
1. Allez à **APIs & Services > Credentials**
2. Cliquez sur **Create Credentials > OAuth 2.0 Client IDs**
3. Choisissez **Web application**
4. Configurez les URIs :
   - **Authorized JavaScript origins** : 
     - `http://localhost`
     - `http://localhost:8000` (si vous utilisez le serveur Symfony)
   - **Authorized redirect URIs** :
     - `http://localhost/maplateforme/public/oauth/callback`
     - `http://localhost:8000/oauth/callback`

### Étape 4 : Récupérer les identifiants
1. Après création, téléchargez le fichier JSON ou copiez :
   - **Client ID** → GOOGLE_CLIENT_ID
   - **Client Secret** → GOOGLE_CLIENT_SECRET

## 🛠️ Résolution de l'erreur cURL 52

### Causes possibles :
1. **Identifiants manquants ou incorrects**
2. **Proxy ou firewall bloquant les requêtes HTTPS**
3. **Token expiré ou corrompu**
4. **Configuration SSL/TLS incorrecte**

### Solutions :

#### 1. Vérifier la configuration réseau
```bash
# Tester la connectivité vers Google
curl -I https://oauth2.googleapis.com/token
```

#### 2. Nettoyer les tokens existants
Supprimez le fichier `tokens/google_token.json` et `var/google-token.json`

#### 3. Vérifier les certificats SSL
```bash
# Mettre à jour les certificats CA
curl --cacert path/to/cacert.pem https://oauth2.googleapis.com/token
```

#### 4. Configuration proxy (si applicable)
Ajoutez dans votre `.env` :
```env
HTTP_PROXY=http://proxy:port
HTTPS_PROXY=http://proxy:port
```

## 🧪 Test de la configuration

Exécutez le script de test :
```bash
php test_google.php
```

Ou utilisez cette commande pour tester manuellement :
```bash
curl -X POST https://oauth2.googleapis.com/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=YOUR_CLIENT_ID&client_secret=YOUR_CLIENT_SECRET&grant_type=client_credentials"
```

## 📝 Fichier .env complet

```env
# Configuration de l'application
APP_ENV=dev
APP_SECRET=your_app_secret_here

# Configuration Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdef.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdef123456
GOOGLE_REDIRECT_URI=http://localhost/maplateforme/public/oauth/callback
GOOGLE_TOKEN_EXPIRATION=3600

# Base de données
DATABASE_URL="mysql://root:@localhost:3306/maplateforme"

# Configuration email
MAILER_DSN=smtp://localhost:1025
```

## 🔄 Processus de reconnexion

1. Supprimez tous les tokens existants
2. Redémarrez votre serveur web
3. Allez sur `/oauth/connect`
4. Autorisez l'application
5. Testez la synchronisation