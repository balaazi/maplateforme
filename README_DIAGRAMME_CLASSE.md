# Diagramme de Classe - MaPlateforme

## Vue d'ensemble

Ce diagramme de classe représente l'architecture complète de votre application Symfony **MaPlateforme**, une plateforme de gestion d'événements et de salles avec système de notifications avancé.

## Entités Principales

### 🧑‍💼 User (Utilisateur)
**Couleur :** Violet clair (#F3E5F5)

**Rôle central** dans l'application avec les fonctionnalités suivantes :
- **Authentification** : Implémente `UserInterface` et `PasswordAuthenticatedUserInterface`
- **Profil complet** : Nom, prénom, email, téléphone, société, spécialité
- **Préférences de notification** : Email, SMS, sons, visuelles
- **Intégration Google** : Token pour synchronisation
- **Gestion des rôles** : Système de permissions
- **Relations** : Appartient à un département, participe à des événements

**Méthodes importantes :**
- `getFullName()` : Nom complet
- `getUserIdentifier()` : Identifiant unique (email)
- `eraseCredentials()` : Sécurité

### 📅 Event (Événement)
**Couleur :** Vert clair (#E8F5E8)

**Entité centrale** pour la gestion des événements :
- **Informations de base** : Titre, description, lieu, date/heure, durée
- **Organisation** : Organisateur, département, salle
- **Intégrations** : Google Drive, Etherpad
- **Gestion des fichiers** : Documents, images, notes collaboratives
- **Statuts** : Archive, actif
- **Catégorisation** : Type d'événement

**Fonctionnalités avancées :**
- Gestion des participants et invitations
- Documents partagés
- Notes collaboratives
- Rappels automatiques

### 🏢 Salle (Salle de réunion)
**Couleur :** Orange clair (#FFF3E0)

**Gestion complète des salles :**
- **Caractéristiques** : Capacité, superficie, localisation
- **Équipements** : Liste d'équipements disponibles
- **Disponibilité** : Horaires, réservations
- **Accessibilité** : Handicap, tarifs
- **Types** : Réunion, conférence, formation, etc.

**Méthodes utilitaires :**
- `hasEquipement()` : Vérification d'équipement
- `getTypeLabel()` : Libellé du type
- `getPrioriteLabel()` : Libellé de priorité

### 🏛️ Departement (Département)
**Couleur :** Bleu clair (#E1F5FE)

**Organisation hiérarchique :**
- **Informations** : Nom, code, responsable, contact
- **Budget** : Budget annuel
- **Statut** : Actif/inactif
- **Relations** : Utilisateurs et événements

### 📧 Invitation (Invitation)
**Couleur :** Bleu clair (#E1F5FE)

**Système d'invitation :**
- **Statuts** : En attente, acceptée, refusée
- **Sécurité** : Token unique
- **Suivi** : Dates de création/modification
- **Relations** : Événement et participant

### 👥 Participation (Participation)
**Couleur :** Bleu clair (#E1F5FE)

**Suivi de participation :**
- **Statut** : Accepté, refusé, en attente
- **Présence** : Confirmation de présence
- **Feedback** : Commentaires des participants

## Système de Notifications

### 🔔 Reminder (Rappel)
**Couleur :** Rouge clair (#FFEBEE)

**Système de rappels avancé :**
- **Déclenchement** : Date/heure programmée
- **Types** : Email, notification, son
- **Priorités** : Basse, normale, haute
- **Métadonnées** : Informations supplémentaires
- **Statuts** : En attente, déclenché, terminé

**Méthodes intelligentes :**
- `shouldTrigger()` : Vérification de déclenchement
- `isOverdue()` : Vérification de retard
- `getTimeUntilDue()` : Temps restant
- `getFormattedMessage()` : Message formaté

### 📢 Notification (Notification)
**Couleur :** Rouge clair (#FFEBEE)

**Notifications en temps réel :**
- **Types** : Rappel, mise à jour, annulation, invitation
- **Lecture** : Statut lu/non lu
- **Interface** : Icônes et couleurs par type
- **Temps** : Affichage relatif ("il y a 2 heures")

## Gestion des Ressources

### 📋 Reservation (Réservation)
**Couleur :** Bleu clair (#E1F5FE)

**Système de réservation :**
- **Périodes** : Date début/fin
- **Récurrence** : Hebdomadaire, mensuelle
- **Statuts** : Confirmée, en attente, annulée
- **Contacts** : Téléphone, email
- **Validation** : Chevauchement, disponibilité

### 📄 Document (Document)
**Couleur :** Bleu clair (#E1F5FE)

**Gestion des fichiers :**
- **Upload** : Intégration VichUploader
- **Métadonnées** : Nom, taille, type
- **Relations** : Lié à un événement

### 📝 CollaborativeNote (Note collaborative)
**Couleur :** Bleu clair (#E1F5FE)

**Notes partagées :**
- **Contenu** : Texte riche
- **Auteur** : Utilisateur créateur
- **Historique** : Dates création/modification

## Entités Support

### 👤 Participant (Participant)
**Couleur :** Bleu clair (#E1F5FE)

**Participants externes :**
- **Informations** : Email, nom, prénom, téléphone
- **Invitations** : Liens vers invitations

### 🔐 ResetPasswordRequest (Demande de réinitialisation)
**Couleur :** Bleu clair (#E1F5FE)

**Sécurité :**
- **Token** : Sécurisé et unique
- **Expiration** : Date limite
- **Validation** : Vérification d'expiration

### 📅 CalendarEvent (Événement calendrier)
**Couleur :** Bleu clair (#E1F5FE)

**Intégration calendrier :**
- **Affichage** : Couleurs, bordures, texte
- **Journée complète** : Option allDay
- **Personnalisation** : Couleurs par utilisateur

### 🗂️ EventFile (Fichier d'événement)
**Couleur :** Bleu clair (#E1F5FE)

**Fichiers spécifiques :**
- **Métadonnées** : Taille, type MIME
- **Stockage** : Chemin de fichier
- **Horodatage** : Date d'upload

### 🏢 GestionSalle (Gestion de salle)
**Couleur :** Bleu clair (#E1F5FE)

**Gestion simplifiée :**
- **Informations de base** : Nom, description, capacité
- **Disponibilité** : Statut disponible/indisponible

## Relations Principales

### Relations One-to-Many (1:N)
- **User → Participation** : Un utilisateur peut participer à plusieurs événements
- **Event → Invitation** : Un événement peut avoir plusieurs invitations
- **Event → Document** : Un événement peut avoir plusieurs documents
- **Salle → Reservation** : Une salle peut avoir plusieurs réservations
- **Departement → User** : Un département peut avoir plusieurs utilisateurs

### Relations Many-to-One (N:1)
- **Participation → User** : Une participation appartient à un utilisateur
- **Participation → Event** : Une participation concerne un événement
- **Document → Event** : Un document appartient à un événement
- **Reservation → Salle** : Une réservation concerne une salle

### Relations Many-to-Many (N:N)
- **User ↔ Event** : Via Participation (table de liaison)
- **User ↔ Event** : Via Invitation (table de liaison)

## Fonctionnalités Avancées

### 🔄 Système de Rappels Automatiques
- **Déclenchement intelligent** : Vérification automatique
- **Multi-canal** : Email, notification, son
- **Personnalisation** : Fréquence et priorités par utilisateur
- **Métadonnées** : Informations contextuelles

### 📊 Gestion des Départements
- **Hiérarchie** : Organisation des utilisateurs
- **Budget** : Suivi des budgets annuels
- **Responsabilité** : Gestion des responsables
- **Activité** : Statut actif/inactif

### 🔐 Sécurité et Authentification
- **Rôles** : Système de permissions
- **Tokens** : Sécurisation des invitations
- **Réinitialisation** : Processus sécurisé
- **Google** : Intégration OAuth

### 📱 Notifications Multi-canal
- **Email** : Notifications par email
- **SMS** : Notifications par SMS
- **Interface** : Notifications visuelles
- **Son** : Notifications sonores

## Architecture Technique

### 🏗️ Pattern MVC
- **Modèle** : Entités Doctrine
- **Vue** : Templates Twig
- **Contrôleur** : Contrôleurs Symfony

### 🗄️ ORM Doctrine
- **Mapping** : Annotations/Attributs
- **Relations** : One-to-Many, Many-to-One, Many-to-Many
- **Migrations** : Gestion des versions de base de données

### 🔧 Services
- **EmailService** : Envoi d'emails
- **NotificationService** : Gestion des notifications
- **ReminderService** : Système de rappels
- **AutoArchiveService** : Archivage automatique

### 📦 Bundles Utilisés
- **VichUploader** : Upload de fichiers
- **Security** : Authentification et autorisation
- **Mailer** : Envoi d'emails
- **Messenger** : Traitement asynchrone

## Points Forts de l'Architecture

1. **Modularité** : Entités bien séparées et spécialisées
2. **Extensibilité** : Facile d'ajouter de nouvelles fonctionnalités
3. **Sécurité** : Système d'authentification robuste
4. **Performance** : Relations optimisées
5. **Maintenabilité** : Code bien structuré et documenté
6. **Scalabilité** : Architecture adaptée à la croissance

## Utilisation du Diagramme

Ce diagramme peut être utilisé avec **PlantUML** pour générer une représentation visuelle complète de l'architecture de votre application. Il peut être intégré dans :

- **Documentation technique**
- **Présentations**
- **Formation d'équipe**
- **Planification d'évolutions**

Pour générer le diagramme :
```bash
plantuml diagramme_classe_detaille.puml
``` 