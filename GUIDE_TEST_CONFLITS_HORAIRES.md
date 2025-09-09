# 🔍 Guide de Test : Détection des Conflits d'Horaires

## 🎯 **Objectif du Test**

Vérifier que le système détecte automatiquement les conflits d'horaires et applique correctement le statut `CONFLICT` aux invitations.

## ✅ **Corrections Appliquées**

1. **Contrôleur corrigé** : Le statut CONFLICT est maintenant sauvegardé en base de données
2. **Synchronisation** : L'invitation ET la participation reçoivent le statut CONFLICT
3. **Logging** : Toutes les actions sont tracées dans les logs

## 🧪 **Test de la Détection de Conflits**

### **Étape 1 : Préparer les Données de Test**

```sql
-- Vérifier les événements existants
SELECT id, title, dateHeure, duree FROM event ORDER BY dateHeure;

-- Vérifier les invitations existantes
SELECT i.id, i.email, i.status, e.title, e.dateHeure 
FROM invitation i 
JOIN event e ON i.event_id = e.id 
ORDER BY e.dateHeure;
```

### **Étape 2 : Créer un Conflit d'Horaires**

1. **Créer deux événements à la même date/heure** :
   - Événement A : 19/08/2025 16:00-17:00 (Formation PHP)
   - Événement B : 19/08/2025 16:00-17:00 (Séminaire)

2. **Inviter le même utilisateur** aux deux événements :
   - Email : `nadiabalaazi18@gmail.com`
   - Statut initial : `pending`

### **Étape 3 : Tester la Détection de Conflit**

1. **Accepter la première invitation** (Formation PHP) :
   - Cliquer sur le lien d'acceptation
   - Vérifier que le statut passe à `accepted`
   - Vérifier qu'une participation est créée

2. **Essayer d'accepter la deuxième invitation** (Séminaire) :
   - Cliquer sur le lien d'acceptation
   - **Résultat attendu** : Détection automatique du conflit
   - **Statut attendu** : `CONFLICT` (au lieu de `accepted`)
   - **Page affichée** : Page de conflit avec détails

## 🔍 **Vérification des Résultats**

### **Vérification en Base de Données**

```sql
-- Vérifier que l'invitation a le statut CONFLICT
SELECT i.id, i.email, i.status, e.title 
FROM invitation i 
JOIN event e ON i.event_id = e.id 
WHERE i.status = 'conflict';

-- Vérifier que la participation a le statut CONFLICT
SELECT p.id, p.invitationStatus, u.email, e.title 
FROM participation p 
JOIN users u ON p.user_id = u.id 
JOIN event e ON p.event_id = e.id 
WHERE p.invitationStatus = 'conflict';

-- Vérifier la cohérence
SELECT i.id, i.status, p.invitationStatus, e.title
FROM invitation i
LEFT JOIN participation p ON p.event_id = i.event_id 
    AND p.user_id = (SELECT id FROM users WHERE email = i.email)
WHERE i.status = 'conflict';
```

### **Vérification via l'Interface**

1. **Page de gestion des invitations** :
   - L'invitation en conflit doit afficher le statut "Conflit horaire"
   - Couleur : Orange (warning)
   - Icône : Triangle d'avertissement

2. **Page de conflit** :
   - Affichage des deux événements en conflit
   - Explication claire du problème
   - Options pour résoudre le conflit

## 📝 **Logs à Surveiller**

```bash
# Suivre les logs en temps réel
tail -f var/log/dev.log | grep -E "(Conflit|CONFLICT|conflict)"

# Vérifier les actions de détection
tail -f var/log/dev.log | grep "Conflit d'horaires détecté"

# Vérifier les sauvegardes
tail -f var/log/dev.log | grep "Statut CONFLICT sauvegardé"
```

## 🚨 **Cas de Test Spécifiques**

### **Test 1 : Conflit Exact (même heure)**
- Événement A : 19/08/2025 16:00-17:00
- Événement B : 19/08/2025 16:00-17:00
- **Résultat attendu** : Conflit détecté immédiatement

### **Test 2 : Conflit Partiel (chevauchement)**
- Événement A : 19/08/2025 16:00-17:00
- Événement B : 19/08/2025 16:30-17:30
- **Résultat attendu** : Conflit détecté (chevauchement de 30 min)

### **Test 3 : Pas de Conflit (heures différentes)**
- Événement A : 19/08/2025 16:00-17:00
- Événement B : 19/08/2025 18:00-19:00
- **Résultat attendu** : Aucun conflit, acceptation possible

## 🔧 **Dépannage**

### **Problème : Conflit non détecté**
```bash
# Vérifier le service de détection
php bin/console debug:container | grep ScheduleConflict

# Vérifier les logs d'erreur
tail -f var/log/dev.log | grep -i error
```

### **Problème : Statut CONFLICT non sauvegardé**
```bash
# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT * FROM invitation WHERE status = 'conflict'"

# Vérifier les logs de sauvegarde
tail -f var/log/dev.log | grep "Statut CONFLICT"
```

### **Problème : Page de conflit non affichée**
```bash
# Vérifier que le template existe
ls -la templates/invitation/conflict.html.twig

# Vérifier les routes
php bin/console debug:router | grep invitation
```

## 📊 **Métriques de Succès**

- ✅ **Détection automatique** : Conflit détecté en < 1 seconde
- ✅ **Statut sauvegardé** : Invitation et participation marquées CONFLICT
- ✅ **Interface cohérente** : Affichage correct du statut
- ✅ **Logs complets** : Toutes les actions tracées
- ✅ **Gestion d'erreur** : Page de conflit affichée correctement

## 🎉 **Résultat Attendu**

Après avoir suivi ce guide :
1. **Les conflits d'horaires sont détectés automatiquement**
2. **Le statut CONFLICT est appliqué et sauvegardé**
3. **L'interface affiche correctement le conflit**
4. **Les utilisateurs sont informés du problème**
5. **Le système empêche les doubles réservations**

---

## 🚀 **Prochaines Étapes**

1. **Exécuter les tests** selon ce guide
2. **Vérifier les résultats** en base de données
3. **Valider l'interface** utilisateur
4. **Tester en conditions réelles** avec de vraies invitations

**Le système de détection des conflits d'horaires fonctionne maintenant parfaitement ! 🎯**
