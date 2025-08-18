# Résolution - Documents Vides dans la Section "Documents"

## 🎯 Problème Identifié

La section "Documents" affiche "Aucun document disponible" même après avoir uploadé des fichiers lors de la création d'événements.

## 🔍 Cause Racine

**Problème de connexion à la base de données MySQL** - L'erreur `Aucune connexion n'a pu être établie car l'ordinateur cible l'a expressément refusée` indique que :

1. Le service MySQL n'est pas démarré
2. La configuration de connexion est incorrecte
3. XAMPP n'est pas en cours d'exécution

## ✅ Solutions

### 1. **Vérifier et Démarrer XAMPP**

```bash
# Ouvrir XAMPP Control Panel et s'assurer que :
- Apache est démarré (vert)
- MySQL est démarré (vert)
```

### 2. **Vérifier la Configuration de Base de Données**

Fichier `.env` actuel :
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/my_database"
```

Vérifiez que :
- Le nom de la base de données (`my_database`) existe
- L'utilisateur (`root`) a les bonnes permissions
- Le port MySQL (3306) est correct

### 3. **Créer la Base de Données si Nécessaire**

```bash
# Une fois MySQL démarré, exécuter :
php bin/console doctrine:database:create
```

### 4. **Exécuter les Migrations**

```bash
# Appliquer toutes les migrations pending
php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. **Vérifier la Table Document**

```bash
# Vérifier que la table document existe
php bin/console doctrine:query:sql "DESCRIBE document"
```

## 🔧 Modifications Déjà Appliquées

### ✅ **Code EventController**
- ✅ Traitement des fichiers multiples lors de la création/modification d'événements
- ✅ Création automatique d'entités `Document` liées aux événements
- ✅ Gestion d'erreurs avec logs

### ✅ **Code ParticipantController**
- ✅ Récupération des documents des événements où l'utilisateur participe
- ✅ **NOUVEAU** : Récupération des documents des événements créés par l'utilisateur
- ✅ Évitement des doublons

### ✅ **Formulaire EventFormType**
- ✅ Support des fichiers multiples (`multiple => true`)
- ✅ Contraintes de validation avec `All` pour les tableaux
- ✅ Types de fichiers autorisés : PDF, Word, Images

### ✅ **Entité Document**
- ✅ Configuration VichUploader
- ✅ Relation ManyToOne avec Event
- ✅ Propriétés fileName et createdAt

## 📋 Plan de Test

### Étape 1 : Redémarrer les Services
1. Fermer complètement XAMPP
2. Redémarrer XAMPP en tant qu'administrateur
3. Démarrer Apache et MySQL
4. Vérifier que les services sont verts

### Étape 2 : Vérifier la Base de Données
```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

### Étape 3 : Test Complet
1. Créer un nouvel événement via `/event/create`
2. Ajouter 2-3 fichiers dans "Documents et fichiers"
3. Créer l'événement
4. Vérifier le message de succès (doit mentionner X document(s) uploadé(s))
5. Aller dans "Documents" depuis le tableau de bord
6. Vérifier que les documents apparaissent

### Étape 4 : Vérification Base de Données
```bash
# Vérifier que les documents sont bien créés
php bin/console doctrine:query:sql "SELECT COUNT(*) as total FROM document"

# Lister les documents avec leurs événements
php bin/console doctrine:query:sql "
SELECT d.id, d.file_name, d.created_at, e.title as event_title 
FROM document d 
JOIN event e ON d.event_id = e.id 
ORDER BY d.created_at DESC 
LIMIT 10"
```

## 🚨 Erreurs Possibles et Solutions

### Erreur : "Table 'document' doesn't exist"
**Solution :** Exécuter la migration personnalisée :
```bash
php bin/console doctrine:migrations:migrate DoctrineMigrations\\Version20250114210000
```

### Erreur : "SQLSTATE[HY000] [2002] No connection could be made"
**Solution :** Redémarrer MySQL dans XAMPP et vérifier le port 3306

### Erreur : "Access denied for user 'root'"
**Solution :** Vérifier le mot de passe MySQL dans `.env`

### Documents uploadés mais pas visibles
**Vérifications :**
1. Fichiers présents dans `public/uploads/documents/` ?
2. Entrées dans la table `document` ?
3. Relation correcte avec `event_id` ?

## 📊 Diagnostic Rapide

Exécutez ces commandes pour un diagnostic complet :

```bash
# 1. Vérifier la connexion DB
php bin/console doctrine:query:sql "SELECT 1"

# 2. Compter les événements
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM event"

# 3. Compter les documents
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM document"

# 4. Vérifier les fichiers uploadés
dir public\uploads\documents

# 5. Tester la route documents
curl http://127.0.0.1:8001/documents
```

## ✅ Résultat Attendu

Une fois MySQL redémarré et les migrations appliquées :

1. **Création d'événement** : Message "Événement créé avec succès ! 3 rappel(s) automatique(s) programmé(s). 2 document(s) uploadé(s)."

2. **Section Documents** : 
   - Affichage du nombre total de documents dans l'en-tête
   - Liste des documents avec nom, événement associé, date d'upload
   - Boutons de téléchargement fonctionnels

3. **Base de données** :
   - Table `document` créée avec les bonnes colonnes
   - Entrées créées automatiquement lors de l'upload
   - Relations correctes avec la table `event`

---

**🔑 Point Clé :** Le problème principal est la connexion à MySQL. Une fois ce problème résolu, tous les documents uploadés apparaîtront automatiquement dans la section "Documents" grâce aux modifications de code déjà appliquées.
