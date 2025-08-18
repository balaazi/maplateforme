# Guide d'utilisation - Liste de présence pour les formations

## 📋 Vue d'ensemble

Le système de liste de présence automatique est activé pour tous les événements de type **Formation**. Il permet à l'organisateur de suivre facilement la participation effective des inscrits lors du jour J.

## 🚀 Comment utiliser le système

### 1. Création d'un événement de formation

1. **Créer un nouvel événement** via le bouton "Créer un Événement"
2. **Sélectionner la catégorie "Formation"** dans le formulaire
3. Remplir les autres informations (titre, description, date, lieu, etc.)
4. **Enregistrer l'événement**

### 2. Invitation des participants

1. Aller sur la page de l'événement créé
2. Utiliser le bouton **"Gérer Invitations"** 
3. Ajouter les participants par email
4. Envoyer les invitations

> 💡 **Important** : Une `Participation` est automatiquement créée pour chaque personne qui accepte l'invitation, avec le statut de présence "En attente" par défaut. La présence ne pourra être validée que le jour de l'événement.

### 3. Accès à la liste de présence

**Uniquement le jour de la formation**, l'organisateur peut accéder à la liste de présence :

1. Aller sur la page de l'événement
2. Dans la section "Outils", cliquer sur le bouton **"Liste de présence"** (avec l'icône 📋 verte)

> ⚠️ **Restrictions importantes** : 
> - Seul l'organisateur/créateur de l'événement ou un administrateur peut accéder à cette fonctionnalité
> - **La gestion de présence n'est accessible que le jour même de l'événement**
> - Avant cette date, un message d'avertissement sera affiché

### 4. Gestion de la présence

L'interface de liste de présence permet :

#### ✅ Fonctionnalités disponibles :
- **Visualisation de tous les participants** ayant accepté l'invitation
- **Boutons Présent/Absent** pour chaque participant (uniquement le jour J)
- **Commutateurs présent/absent** pour chaque participant (formations)
- **Actions en lot** : marquer tous comme présents ou absents
- **Sauvegarde en temps réel** via AJAX
- **Interface responsive** adaptée aux tablettes/mobiles
- **Gestion depuis la liste des invitations** : boutons directs dans l'interface principale

## 🆕 Nouvelle fonctionnalité : Boutons de présence dans la liste des invitations

### Utilisation

1. **Accéder à la gestion des invitations** :
   - Aller sur la page de votre événement
   - Cliquer sur "Gérer Invitations"

2. **Marquer la présence (le jour J uniquement)** :
   - Dans la colonne "PRÉSENCE", vous verrez deux boutons pour chaque participant ayant accepté :
     - 🟢 **Présent** : Cliquer pour marquer comme présent
     - 🔴 **Absent** : Cliquer pour marquer comme absent
   - Les boutons se mettent à jour instantanément
   - Une notification confirme la modification

3. **Avant le jour de l'événement** :
   - La colonne affiche "⏰ En attente"
   - La date de l'événement est indiquée
   - Les boutons ne sont pas disponibles

### Avantages

✅ **Gestion centralisée** : Tout depuis l'interface des invitations  
✅ **Mise à jour instantanée** : AJAX en temps réel  
✅ **Restriction temporelle** : Seulement le jour J  
✅ **Confirmation visuelle** : Boutons colorés et notifications  
✅ **Sécurité** : Seul l'organisateur peut modifier

#### 📊 Informations affichées :
- Nom, prénom et email de chaque participant
- Société (si renseignée)
- Statut actuel (Présent/Absent) avec badge coloré
- Avatar avec initiales

#### 🔧 Actions disponibles :
- **Cocher individuellement** chaque participant
- **"Tous présents"** : marque tous les participants comme présents
- **"Tous absents"** : marque tous les participants comme absents  
- **"Enregistrer"** : sauvegarde les modifications en base de données

## 🏗️ Architecture technique

### Entités impliquées :
- **Event** : L'événement de formation (avec `category = 'formation'`)
- **Participation** : Lien User ↔ Event avec statut de présence (`isPresent`)
- **Invitation** : Système d'invitation par email

### Endpoints :
- **`/event/{id}/training-attendance`** : Affichage de la liste de présence
- **`/event/{id}/update-training-attendance`** : Mise à jour AJAX des présences

### Sécurité :
- Accès restreint à l'organisateur/créateur ou admin
- Vérification que l'événement est de type "formation"
- Protection CSRF via Symfony

## 🎯 Workflow complet

```
1. Organisateur crée événement "Formation"
   ↓
2. Organisateur envoie invitations
   ↓  
3. Participants acceptent → Participation créée (absent par défaut)
   ↓
4. Jour J : Organisateur ouvre "Liste de présence"
   ↓
5. Organisateur coche présents/absents selon arrivées
   ↓
6. Sauvegarde → Données mises à jour en BDD
```

## 🔧 Personnalisations possibles

### Ajout de nouvelles fonctionnalités :
- Export PDF de la liste de présence
- Signature électronique des participants
- Intégration avec badge/QR codes
- Statistiques de présence par département
- Rappels automatiques pour les absents

### Modifications du design :
- Le template `templates/event/training_attendance.html.twig` peut être personnalisé
- CSS intégré dans le template pour un style moderne et responsive

## 📱 Compatibilité

- ✅ **Desktop** : Interface complète
- ✅ **Tablette** : Optimisée pour la saisie tactile
- ✅ **Mobile** : Design responsive adaptatif
- ✅ **Navigateurs** : Chrome, Firefox, Safari, Edge

## 🔍 Débogage

### Vérifications en cas de problème :

1. **L'événement est-il de type "Formation" ?**
   ```sql
   SELECT category FROM event WHERE id = [EVENT_ID];
   ```

2. **L'utilisateur a-t-il les droits ?**
   - Doit être l'organisateur, créateur ou admin

3. **Y a-t-il des participations créées ?**
   ```sql
   SELECT * FROM participation WHERE event_id = [EVENT_ID] AND invitation_status = 'accepté';
   ```

4. **Les routes sont-elles configurées ?**
   - `event_training_attendance`
   - `event_update_training_attendance`

## 🎉 Avantages du système

- **🤖 Automatique** : Pas de configuration manuelle nécessaire
- **⚡ Rapide** : Interface optimisée pour la saisie rapide
- **💾 Fiable** : Sauvegarde instantanée via AJAX
- **📱 Mobile** : Utilisable sur tablette pendant la formation
- **🔒 Sécurisé** : Accès contrôlé et données protégées
- **🎨 Moderne** : Interface élégante et intuitive

---

> **Note** : Cette fonctionnalité est automatiquement disponible pour tous les événements de type "Formation". Aucune configuration supplémentaire n'est nécessaire.