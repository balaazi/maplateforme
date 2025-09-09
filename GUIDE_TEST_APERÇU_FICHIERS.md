# 🧪 Guide de Test - Aperçu des Fichiers

## 🎯 **Objectif du Test**

Vérifier que la fonctionnalité d'aperçu des fichiers fonctionne correctement lors de la création d'événements.

## 📋 **Prérequis**

- ✅ Serveur Symfony en cours d'exécution
- ✅ Navigateur web moderne (Chrome, Firefox, Safari, Edge)
- ✅ Fichiers de test (PDF, Word, Images) disponibles

## 🚀 **Méthodes de Test**

### **1. Test via l'Interface Symfony (Recommandé)**

#### **Étapes :**
1. **Démarrer le serveur Symfony :**
   ```bash
   php bin/console server:start
   # ou utiliser XAMPP/WAMP si configuré
   ```

2. **Accéder à la page de création d'événement :**
   ```
   http://localhost:8000/event/create
   # ou l'URL correspondante dans votre configuration
   ```

3. **Tester la sélection de fichiers :**
   - Cliquer sur "Select fichiers"
   - Sélectionner plusieurs fichiers de types différents
   - Vérifier que l'aperçu apparaît immédiatement

4. **Tester la suppression de fichiers :**
   - Cliquer sur "Retirer" pour un fichier spécifique
   - Vérifier que le fichier disparaît de l'aperçu
   - Vérifier que le champ d'upload est mis à jour

### **2. Test via le Fichier HTML Autonome**

#### **Étapes :**
1. **Ouvrir le fichier de test :**
   ```
   test_apercu_fichiers.html
   ```

2. **Tester dans le navigateur :**
   - Ouvrir le fichier dans un navigateur
   - Tester la sélection et suppression de fichiers
   - Vérifier le responsive design

## 🔍 **Scénarios de Test**

### **Scénario 1 : Sélection de Fichiers**
- [ ] Sélectionner un seul fichier PDF
- [ ] Sélectionner plusieurs fichiers de types différents
- [ ] Sélectionner des fichiers de grande taille (>5MB)
- [ ] Sélectionner des fichiers avec des noms longs

### **Scénario 2 : Affichage de l'Aperçu**
- [ ] Vérifier que l'icône correspond au type de fichier
- [ ] Vérifier que le nom du fichier est affiché correctement
- [ ] Vérifier que la taille est formatée correctement
- [ ] Vérifier que les animations fonctionnent

### **Scénario 3 : Suppression de Fichiers**
- [ ] Supprimer le premier fichier de la liste
- [ ] Supprimer le dernier fichier de la liste
- [ ] Supprimer un fichier au milieu de la liste
- [ ] Vérifier que l'aperçu se met à jour correctement

### **Scénario 4 : Responsive Design**
- [ ] Tester sur écran large (desktop)
- [ ] Tester sur tablette (768px)
- [ ] Tester sur mobile (320px)
- [ ] Vérifier l'adaptation des cartes

## ✅ **Critères de Validation**

### **Fonctionnalité :**
- [ ] L'aperçu apparaît immédiatement après sélection
- [ ] Chaque fichier est affiché dans une carte distincte
- [ ] Les icônes correspondent aux types de fichiers
- [ ] La suppression fonctionne pour chaque fichier
- [ ] L'aperçu se masque quand aucun fichier n'est sélectionné

### **Interface :**
- [ ] Les cartes ont un design moderne et cohérent
- [ ] Les animations sont fluides et naturelles
- [ ] Le responsive design fonctionne sur tous les écrans
- [ ] Les couleurs et typographies sont cohérentes

### **Performance :**
- [ ] Pas de lag lors de la sélection de fichiers
- [ ] Les animations sont fluides (60fps)
- [ ] Pas de consommation excessive de mémoire
- [ ] Compatible avec de nombreux fichiers

## 🐛 **Dépannage**

### **Problèmes Courants :**

#### **1. L'aperçu n'apparaît pas :**
- Vérifier que JavaScript est activé
- Vérifier la console du navigateur pour les erreurs
- Vérifier que l'ID `event_documents_input` est correct

#### **2. Les icônes ne s'affichent pas :**
- Vérifier que Font Awesome est chargé
- Vérifier la connexion internet pour les CDN
- Vérifier que les classes CSS sont correctes

#### **3. La suppression ne fonctionne pas :**
- Vérifier que la fonction `removeFile` est accessible globalement
- Vérifier que l'événement `change` est déclenché
- Vérifier la compatibilité du navigateur avec `DataTransfer`

### **Solutions :**
- **Recharger la page** pour réinitialiser JavaScript
- **Vider le cache** du navigateur
- **Vérifier les erreurs** dans la console développeur
- **Tester sur un autre navigateur** pour isoler le problème

## 📊 **Résultats Attendus**

### **Succès :**
- ✅ Aperçu immédiat des fichiers sélectionnés
- ✅ Cartes visuelles avec informations complètes
- ✅ Suppression individuelle fonctionnelle
- ✅ Interface responsive et moderne
- ✅ Animations fluides et naturelles

### **Échec :**
- ❌ Aperçu ne s'affiche pas
- ❌ Erreurs JavaScript dans la console
- ❌ Interface non responsive
- ❌ Animations saccadées ou absentes
- ❌ Suppression de fichiers non fonctionnelle

## 🎉 **Validation Finale**

Une fois tous les tests passés avec succès, la fonctionnalité est considérée comme **OPÉRATIONNELLE** et peut être utilisée en production.

---

**📝 Note :** Ce guide de test doit être exécuté après chaque modification de la fonctionnalité pour s'assurer de sa stabilité.
