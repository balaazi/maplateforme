# 🧪 Guide de Test - Fonctionnalité Documents Complète

## 🎯 **Objectif du Test**

Vérifier que la fonctionnalité complète des documents fonctionne :
1. ✅ **Aperçu en temps réel** lors de la création d'événements
2. ✅ **Affichage dans la section Documents** après création
3. ✅ **Gestion complète** des documents (upload, affichage, téléchargement)

## 📋 **Prérequis Résolus**

- ✅ **Base de données** : Schéma synchronisé avec les entités
- ✅ **Table document** : Créée et accessible
- ✅ **Entités** : Event et Document correctement configurées
- ✅ **Relations** : OneToMany/ManyToOne fonctionnelles
- ✅ **Template** : Aperçu des fichiers implémenté

## 🚀 **Étapes de Test**

### **Étape 1 : Créer un Événement avec Documents**

1. **Accéder à la page de création d'événement :**
   ```
   http://localhost:8000/event/create
   # ou l'URL correspondante dans votre configuration
   ```

2. **Remplir le formulaire :**
   - **Titre** : "Test Documents - " + [Date actuelle]
   - **Description** : "Événement de test pour vérifier la fonctionnalité des documents"
   - **Catégorie** : "Test"
   - **Salle** : Sélectionner une salle disponible
   - **Date et heure** : Date/heure future
   - **Durée** : 60 minutes

3. **Tester l'aperçu des fichiers :**
   - **Sélectionner plusieurs fichiers** de types différents :
     - 1 fichier PDF
     - 1 fichier Word (.docx)
     - 1 image (.jpg ou .png)
   - **Vérifier que l'aperçu apparaît** immédiatement
   - **Vérifier les cartes** avec icônes, noms et tailles
   - **Tester la suppression** d'un fichier individuel

4. **Soumettre le formulaire :**
   - Cliquer sur "CRÉER L'ÉVÉNEMENT"
   - Vérifier le message de succès mentionnant les documents uploadés

### **Étape 2 : Vérifier la Création en Base**

1. **Vérifier la table document :**
   ```bash
   php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM document"
   # Doit retourner le nombre de fichiers uploadés
   ```

2. **Vérifier les détails des documents :**
   ```bash
   php bin/console doctrine:query:sql "SELECT d.id, d.file_name, d.event_id, e.title as event_title FROM document d LEFT JOIN event e ON d.event_id = e.id ORDER BY d.id DESC LIMIT 5"
   ```

### **Étape 3 : Vérifier l'Affichage dans la Section Documents**

1. **Accéder à la section Documents :**
   ```
   http://localhost:8000/participant/documents
   # ou l'URL correspondante
   ```

2. **Vérifier l'affichage :**
   - ✅ **Compteur** : Doit afficher le bon nombre de documents
   - ✅ **Section "Documents Uploadés"** : Doit être visible
   - ✅ **Cartes de documents** : Doit afficher chaque fichier
   - ✅ **Informations** : Nom, type, événement, organisateur
   - ✅ **Boutons** : Téléchargement et suppression

### **Étape 4 : Tester les Fonctionnalités**

1. **Téléchargement :**
   - Cliquer sur "Télécharger" pour chaque document
   - Vérifier que le fichier se télécharge correctement

2. **Suppression :**
   - Cliquer sur "Supprimer" pour un document
   - Confirmer la suppression
   - Vérifier que le document disparaît de la liste

3. **Responsive Design :**
   - Tester sur différentes tailles d'écran
   - Vérifier l'adaptation des cartes

## ✅ **Critères de Validation**

### **Fonctionnalité Aperçu :**
- [ ] L'aperçu apparaît immédiatement après sélection de fichiers
- [ ] Chaque fichier est affiché dans une carte distincte
- [ ] Les icônes correspondent aux types de fichiers
- [ ] La suppression individuelle fonctionne
- [ ] L'aperçu se masque quand aucun fichier n'est sélectionné

### **Fonctionnalité Upload :**
- [ ] Les fichiers sont correctement uploadés
- [ ] Les documents sont créés en base de données
- [ ] Les relations Event-Document sont correctes
- [ ] Le message de succès mentionne le nombre de documents

### **Fonctionnalité Affichage :**
- [ ] La section Documents affiche le bon nombre de documents
- [ ] Chaque document est affiché avec ses informations
- [ ] Les boutons de téléchargement et suppression fonctionnent
- [ ] L'interface est responsive et moderne

## 🐛 **Dépannage**

### **Si l'aperçu n'apparaît pas :**
- Vérifier que JavaScript est activé
- Vérifier la console du navigateur pour les erreurs
- Vérifier que l'ID `event_documents_input` est correct

### **Si les documents ne s'affichent pas :**
- Vérifier que la table `document` existe
- Vérifier que les migrations sont à jour
- Vérifier les logs Symfony pour les erreurs

### **Si l'upload échoue :**
- Vérifier les permissions du dossier uploads
- Vérifier la configuration VichUploader
- Vérifier la taille maximale des fichiers

## 📊 **Résultats Attendus**

### **Succès :**
- ✅ Aperçu en temps réel des fichiers sélectionnés
- ✅ Upload réussi des documents
- ✅ Affichage correct dans la section Documents
- ✅ Fonctionnalités de téléchargement et suppression
- ✅ Interface responsive et moderne

### **Échec :**
- ❌ Aperçu ne s'affiche pas
- ❌ Upload échoue
- ❌ Documents non visibles dans la section Documents
- ❌ Erreurs dans la console ou les logs

## 🎉 **Validation Finale**

Une fois tous les tests passés avec succès, la fonctionnalité complète des documents est **OPÉRATIONNELLE** et peut être utilisée en production.

---

**📝 Note :** Ce guide doit être exécuté après chaque modification majeure de la fonctionnalité pour s'assurer de sa stabilité.
