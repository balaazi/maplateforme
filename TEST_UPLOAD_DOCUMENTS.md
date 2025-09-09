# 🧪 Test de la Fonctionnalité d'Upload de Documents

## 🎯 **Objectif du Test**

Vérifier que la fonctionnalité de placement automatique des documents fonctionne correctement après les corrections apportées.

## 🔧 **Corrections Implémentées**

### **1. ParticipantController.php**
- ✅ Logique de récupération des événements étendue
- ✅ Inclut tous les événements accessibles (participant, créateur, organisateur)

### **2. EventController.php**
- ✅ Logs de debug détaillés ajoutés
- ✅ Gestion correcte des fichiers uploadés
- ✅ Utilisation correcte de `setFile()` avec VichUploader

### **3. Event.php**
- ✅ Annotation ORM corrigée pour `created_by_id`

## 🧪 **Procédure de Test**

### **Étape 1 : Préparation**
1. **Nettoyer le cache** : `php bin/console cache:clear`
2. **Vérifier la base** : 0 document actuellement

### **Étape 2 : Test d'Upload**
1. **Se connecter** avec nadiabalaazi@gmail.com
2. **Aller dans l'événement** "Formation Python" (ID: 10)
3. **Cliquer sur "Modifier"**
4. **Sélectionner un fichier** (ex: 1.docx)
5. **Sauvegarder** avec "ENREGISTRER LES MODIFICATIONS"

### **Étape 3 : Vérification des Logs**
1. **Vérifier les logs** dans `var/log/dev.log`
2. **Rechercher** les messages "DEBUG:" et "ERROR:"
3. **Confirmer** que l'upload est traité

### **Étape 4 : Vérification de la Base**
1. **Compter les documents** : `php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM document"`
2. **Vérifier les relations** : `php bin/console doctrine:query:sql "SELECT d.file_name, e.title FROM document d LEFT JOIN event e ON d.event_id = e.id"`

### **Étape 5 : Vérification de l'Interface**
1. **Aller dans la section "Documents"**
2. **Vérifier** que le document est visible
3. **Confirmer** que le compteur affiche le bon nombre

## 📋 **Messages de Debug Attendus**

### **Dans les Logs (var/log/dev.log) :**
```
DEBUG: Formulaire soumis et valide pour l'événement ID 10
DEBUG: Fichiers uploadés récupérés : 1 fichier unique
DEBUG: Traitement de 1 fichier(s)
DEBUG: Traitement du fichier 1 : 1.docx
DEBUG: Nouvelle entité Document créée
DEBUG: Fichier assigné au document
DEBUG: Événement assigné au document
DEBUG: Document persisté en EntityManager
DEBUG: Document 1 créé avec succès
DEBUG: Total documents créés : 1
DEBUG: EntityManager flush effectué
```

## 🚨 **Problèmes Possibles et Solutions**

### **1. Aucun fichier uploadé détecté**
- **Cause** : Problème dans le formulaire
- **Solution** : Vérifier la configuration du champ `imageFile`

### **2. Erreur lors de la création du document**
- **Cause** : Problème avec VichUploader ou l'entité Document
- **Solution** : Vérifier les annotations ORM et la configuration VichUploader

### **3. Document créé mais non visible**
- **Cause** : Problème dans ParticipantController
- **Solution** : Vérifier la logique de récupération des événements

## ✅ **Résultat Attendu**

Après un upload réussi :
- ✅ **1 document en base** de données
- ✅ **Document visible** dans la section Documents
- ✅ **Compteur affiche** "1 DOCUMENT"
- ✅ **Interface fonctionnelle** avec boutons de téléchargement/suppression

## 🔍 **Diagnostic en Cas d'Échec**

### **Vérifier les Logs :**
```bash
# Dernières lignes du log
tail -f var/log/dev.log | grep "DEBUG\|ERROR"
```

### **Vérifier la Base :**
```bash
# Compter les documents
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM document"

# Vérifier les relations
php bin/console doctrine:query:sql "SELECT d.file_name, e.title FROM document d LEFT JOIN event e ON d.event_id = e.id"
```

### **Vérifier le Dossier d'Upload :**
```bash
# Contenu du dossier
dir "public\uploads\documents\*1.docx*"
```

## 🎉 **Succès du Test**

Si le test réussit, cela confirme que :
1. ✅ **L'upload fonctionne** correctement
2. ✅ **Les documents sont créés** en base de données
3. ✅ **La visibilité est assurée** dans la section Documents
4. ✅ **Toutes les corrections** sont opérationnelles

---

**📅 Date du test :** Août 2025  
**👨‍💻 Testeur :** Assistant IA EventHub  
**🎯 Statut :** En cours de test
