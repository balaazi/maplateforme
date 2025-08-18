# Diagramme des Relations entre Classes - Plateforme EventHub

## Vue d'ensemble des Relations

### 1. ENTITÉS PRINCIPALES

#### User (Utilisateur)
- **Relations One-to-Many :**
  - `User` → `Participation` (1 utilisateur peut avoir plusieurs participations)
  - `User` → `Reminder` (1 utilisateur peut avoir plusieurs rappels)
  - `User` → `Notification` (1 utilisateur peut recevoir plusieurs notifications)
  - `User` → `ResetPasswordRequest` (1 utilisateur peut avoir plusieurs demandes de réinitialisation)

- **Relations Many-to-One :**
  - `User` → `Departement` (plusieurs utilisateurs peuvent appartenir à 1 département)

- **Relations One-to-Many (côté Event) :**
  - `User` → `Event` (1 utilisateur peut organiser plusieurs événements - organizer)
  - `User` → `Event` (1 utilisateur peut créer plusieurs événements - createdBy)
  - `User` → `CollaborativeNote` (1 utilisateur peut créer plusieurs notes collaboratives)

#### Event (Événement)
- **Relations Many-to-One :**
  - `Event` → `User` (plusieurs événements peuvent être organisés par 1 utilisateur - organizer)
  - `Event` → `User` (plusieurs événements peuvent être créés par 1 utilisateur - createdBy)
  - `Event` → `Departement` (plusieurs événements peuvent appartenir à 1 département)
  - `Event` → `Salle` (plusieurs événements peuvent se dérouler dans 1 salle)

- **Relations One-to-Many :**
  - `Event` → `Participation` (1 événement peut avoir plusieurs participations)
  - `Event` → `Invitation` (1 événement peut avoir plusieurs invitations)
  - `Event` → `Document` (1 événement peut avoir plusieurs documents)
  - `Event` → `EventFile` (1 événement peut avoir plusieurs fichiers)
  - `Event` → `CollaborativeNote` (1 événement peut avoir plusieurs notes collaboratives)
  - `Event` → `Reminder` (1 événement peut avoir plusieurs rappels)
  - `Event` → `Notification` (1 événement peut générer plusieurs notifications)

#### Salle (Salle)
- **Relations One-to-Many :**
  - `Salle` → `Event` (1 salle peut accueillir plusieurs événements)
  - `Salle` → `Reservation` (1 salle peut avoir plusieurs réservations)
  - `Salle` → `GestionSalle` (1 salle peut avoir plusieurs gestions)

#### Departement (Département)
- **Relations One-to-Many :**
  - `Departement` → `User` (1 département peut avoir plusieurs utilisateurs)
  - `Departement` → `Event` (1 département peut organiser plusieurs événements)

### 2. ENTITÉS DE LIAISON

#### Participation (Table de liaison User-Event)
- **Relations Many-to-One :**
  - `Participation` → `User` (plusieurs participations peuvent appartenir à 1 utilisateur)
  - `Participation` → `Event` (plusieurs participations peuvent appartenir à 1 événement)

#### Invitation (Invitation externe)
- **Relations Many-to-One :**
  - `Invitation` → `Event` (plusieurs invitations peuvent appartenir à 1 événement)
  - `Invitation` → `Participant` (plusieurs invitations peuvent appartenir à 1 participant)

#### Participant (Participant externe)
- **Relations One-to-Many :**
  - `Participant` → `Invitation` (1 participant peut avoir plusieurs invitations)

### 3. ENTITÉS DE GESTION

#### Reminder (Rappel)
- **Relations Many-to-One :**
  - `Reminder` → `User` (plusieurs rappels peuvent appartenir à 1 utilisateur)
  - `Reminder` → `Event` (plusieurs rappels peuvent être liés à 1 événement)

#### Notification (Notification)
- **Relations Many-to-One :**
  - `Notification` → `User` (plusieurs notifications peuvent appartenir à 1 utilisateur)
  - `Notification` → `Event` (plusieurs notifications peuvent être liées à 1 événement)

#### Reservation (Réservation de salle)
- **Relations Many-to-One :**
  - `Reservation` → `Salle` (plusieurs réservations peuvent appartenir à 1 salle)

#### GestionSalle (Gestion des salles)
- **Relations Many-to-One :**
  - `GestionSalle` → `Salle` (plusieurs gestions peuvent appartenir à 1 salle)

### 4. ENTITÉS DE CONTENU

#### Document (Document)
- **Relations Many-to-One :**
  - `Document` → `Event` (plusieurs documents peuvent appartenir à 1 événement)

#### EventFile (Fichier d'événement)
- **Relations Many-to-One :**
  - `EventFile` → `Event` (plusieurs fichiers peuvent appartenir à 1 événement)

#### CollaborativeNote (Note collaborative)
- **Relations Many-to-One :**
  - `CollaborativeNote` → `Event` (plusieurs notes peuvent appartenir à 1 événement)
  - `CollaborativeNote` → `User` (plusieurs notes peuvent être créées par 1 utilisateur)

#### CalendarEvent (Événement calendrier)
- **Aucune relation directe** (entité autonome pour synchronisation Google Calendar)

### 5. ENTITÉS DE SÉCURITÉ

#### ResetPasswordRequest (Demande de réinitialisation de mot de passe)
- **Relations Many-to-One :**
  - `ResetPasswordRequest` → `User` (plusieurs demandes peuvent appartenir à 1 utilisateur)

## RÉSUMÉ DES RELATIONS PAR TYPE

### Relations One-to-Many (1:N)
- `User` → `Participation`, `Reminder`, `Notification`, `ResetPasswordRequest`
- `Event` → `Participation`, `Invitation`, `Document`, `EventFile`, `CollaborativeNote`, `Reminder`, `Notification`
- `Salle` → `Event`, `Reservation`, `GestionSalle`
- `Departement` → `User`, `Event`
- `Participant` → `Invitation`
- `User` → `Event` (organizer/createdBy)
- `User` → `CollaborativeNote`

### Relations Many-to-One (N:1)
- `Participation` → `User`, `Event`
- `Invitation` → `Event`, `Participant`
- `Reminder` → `User`, `Event`
- `Notification` → `User`, `Event`
- `Reservation` → `Salle`
- `GestionSalle` → `Salle`
- `Document` → `Event`
- `EventFile` → `Event`
- `CollaborativeNote` → `Event`, `User`
- `ResetPasswordRequest` → `User`
- `User` → `Departement`
- `Event` → `User` (organizer/createdBy), `Departement`, `Salle`

### Entités Autonomes
- `CalendarEvent` (pas de relations directes)

## HIÉRARCHIE DES ENTITÉS

### Niveau 1 - Entités Fondamentales
- `User` (utilisateurs)
- `Departement` (départements)
- `Salle` (salles)

### Niveau 2 - Entités Métier
- `Event` (événements)
- `Participant` (participants externes)

### Niveau 3 - Entités de Liaison
- `Participation` (liaison User-Event)
- `Invitation` (liaison Participant-Event)

### Niveau 4 - Entités de Gestion
- `Reminder` (rappels)
- `Notification` (notifications)
- `Reservation` (réservations)
- `GestionSalle` (gestion des salles)

### Niveau 5 - Entités de Contenu
- `Document` (documents)
- `EventFile` (fichiers)
- `CollaborativeNote` (notes collaboratives)
- `CalendarEvent` (événements calendrier)

### Niveau 6 - Entités de Sécurité
- `ResetPasswordRequest` (demandes de réinitialisation) 