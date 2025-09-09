# Test - Création d'Événement avec Documents

## 🎯 Objectif
Tester le système de placement automatique des documents en créant un événement avec des fichiers uploadés.

## 📋 Étapes de Test

### 1. **Créer un Nouvel Événement**
   - Aller sur la page de création d'événement
   - Remplir les informations de base :
     - Titre : "Test Documents - [Date]"
     - Description : "Événement de test pour vérifier l'upload de documents"
     - Date et heure : Choisir une date future
     - Durée : 60 minutes
     - Catégorie : "Formation"

### 2. **Uploader des Documents** 
   - Dans la section "Documents (PDF, Word, Images, etc.)"
   - Sélectionner 1-2 fichiers de test :
     - Un PDF (ex: guide.pdf)
     - Une image (ex: photo.jpg)
   - Vérifier que les fichiers sont bien sélectionnés

### 3. **Créer l'Événement**
   - Cliquer sur "Créer l'événement"
   - Vérifier le message de succès : "X document(s) uploadé(s)"

### 4. **Vérifier dans "Mes Documents"**
   - Aller dans la section "Mes Documents"
   - Les documents devraient maintenant apparaître
   - Chaque document doit afficher :
     - Nom du fichier
     - Type de document (PDF, Image, etc.)
     - Événement associé
     - Organisateur
     - Date de création
     - Boutons Télécharger/Supprimer

## 🔍 Points de Vérification

### ✅ **Si ça fonctionne :**
- Les documents apparaissent dans "Mes Documents"
- Le compteur affiche le bon nombre
- Les métadonnées sont correctes
- Le téléchargement fonctionne

### ❌ **Si ça ne fonctionne pas :**
1. **Vérifier les logs** : `var/log/dev.log`
2. **Vérifier le répertoire** : `public/uploads/documents/`
3. **Vérifier la base de données** : table `document`
4. **Vérifier VichUploader** : configuration dans `config/`

## 💡 Diagnostic Rapide

Si le problème persiste :

```php
// Ajouter dans EventController.php après la ligne 131
error_log("DEBUG: Documents créés pour événement {$event->getId()}: $documentsCreated");
error_log("DEBUG: Répertoire uploads existe: " . (is_dir('public/uploads/documents/') ? 'OUI' : 'NON'));
```

## 🎯 Résultat Attendu

Après ce test, la page "Mes Documents" devrait afficher :
- **Header** : "1 Documents" (ou plus selon le nombre uploadé)
- **Section** : "Documents Uploadés" avec les cartes des documents
- **Fonctionnalités** : Téléchargement et suppression opérationnels

---

**Note** : Ce test confirme que le système de placement automatique fonctionne. Le problème initial était simplement l'absence de documents uploadés.
