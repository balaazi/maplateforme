# Solution - Problème de Permissions pour l'Upload de Documents

## 🔍 Problème Identifié

Lors de l'upload de documents dans l'application, l'erreur suivante apparaissait :

```
Unable to write in the "C:\xampp\htdocs\new\maplateforme/public/uploads/documents\" directory.
```

Cette erreur indique un problème de permissions sur le répertoire d'upload des documents. Le serveur web (Apache/PHP) n'a pas les droits d'écriture nécessaires pour enregistrer les fichiers uploadés.

## ✅ Solution Implémentée

Pour résoudre ce problème, deux scripts ont été créés pour configurer correctement les permissions :

### 1. Script Batch (Windows) - `fix_upload_permissions.bat`

Ce script configure les permissions appropriées sur le répertoire d'upload pour permettre au serveur web d'y écrire.

**Fonctionnalités :**
- Vérification des droits administrateur
- Création du répertoire d'upload s'il n'existe pas
- Attribution des permissions complètes aux utilisateurs système nécessaires :
  - IUSR (utilisateur IIS)
  - IIS_IUSRS (groupe IIS)
  - SYSTEM
  - Utilisateurs authentifiés
  - Utilisateur actuel

### 2. Script PowerShell (Windows) - `fix_permissions_advanced.ps1`

Version plus avancée avec gestion des erreurs et vérification du service Apache.

**Fonctionnalités supplémentaires :**
- Interface utilisateur améliorée avec codes couleur
- Gestion détaillée des erreurs
- Vérification et redémarrage optionnel du service Apache
- Affichage des permissions finales

## 🚀 Comment Utiliser les Scripts

### Méthode 1 : Script Batch (Simple)

1. Cliquez avec le bouton droit sur `fix_upload_permissions.bat`
2. Sélectionnez "Exécuter en tant qu'administrateur"
3. Suivez les instructions à l'écran
4. Redémarrez Apache/XAMPP

### Méthode 2 : Script PowerShell (Avancé)

1. Ouvrez PowerShell en tant qu'administrateur
2. Naviguez vers le répertoire du projet
3. Exécutez la commande : `.\fix_permissions_advanced.ps1`
4. Suivez les instructions interactives

## 🔒 Permissions Configurées

Les scripts configurent les permissions suivantes sur le répertoire d'upload :

```
C:\xampp\htdocs\new\maplateforme\public\uploads\documents IUSR:(OI)(CI)(F)
C:\xampp\htdocs\new\maplateforme\public\uploads\documents IIS_IUSRS:(OI)(CI)(F)
C:\xampp\htdocs\new\maplateforme\public\uploads\documents SYSTEM:(OI)(CI)(F)
C:\xampp\htdocs\new\maplateforme\public\uploads\documents Utilisateurs authentifiés:(OI)(CI)(F)
C:\xampp\htdocs\new\maplateforme\public\uploads\documents [UTILISATEUR_ACTUEL]:(OI)(CI)(F)
```

Où :
- `(OI)` = Object Inherit - Les permissions s'appliquent aux fichiers
- `(CI)` = Container Inherit - Les permissions s'appliquent aux sous-dossiers
- `(F)` = Full Control - Contrôle total (lecture, écriture, exécution, etc.)

## 🔄 Vérification du Fonctionnement

Après avoir exécuté l'un des scripts et redémarré Apache/XAMPP :

1. Accédez à la page de création d'événement
2. Sélectionnez un ou plusieurs documents à uploader
3. Soumettez le formulaire
4. Vérifiez que les documents sont correctement uploadés et apparaissent dans la section Documents

## 📝 Notes Importantes

- Ces scripts doivent être exécutés en tant qu'administrateur pour pouvoir modifier les permissions
- Si vous utilisez un environnement de développement différent de XAMPP, vous devrez peut-être adapter les scripts
- Pour les environnements de production, il est recommandé de configurer les permissions de manière plus restrictive

## 🔍 Dépannage Supplémentaire

Si les problèmes persistent après l'exécution des scripts :

1. Vérifiez que le répertoire `public/uploads/documents` existe bien
2. Assurez-vous que le module PHP `fileinfo` est activé dans `php.ini`
3. Vérifiez les logs d'erreur d'Apache pour plus de détails
4. Assurez-vous que les paramètres de `upload_max_filesize` et `post_max_size` dans `php.ini` sont suffisamment élevés