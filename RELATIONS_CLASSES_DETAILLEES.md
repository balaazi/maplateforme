# RELATIONS ENTRE CLASSES - MAPLATEFORME

## 📊 Vue d'ensemble des relations

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS ENTRE CLASSES                                 │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 RELATIONS DÉTAILLÉES PAR CLASSE

### 🧑‍💼 USER (Utilisateur)

#### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER (1)                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ departement: Departement (N:1) ──┐                                         │
│  │  - Un utilisateur appartient à un département                               │
│  │  - Relation ManyToOne avec clé étrangère departement_id                     │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
│  ┌─ participations: Collection<Participation> (1:N) ──┐                        │
│  │  - Un utilisateur peut participer à plusieurs événements                     │
│  │  - Relation OneToMany avec cascade remove                                     │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ reminders: Collection<Reminder> (1:N) ──┐                                   │
│  │  - Un utilisateur peut avoir plusieurs rappels                              │
│  │  - Relation OneToMany                                                       │
│  └─────────────────────────────────────────────┘                                 │
│                                                                                   │
│  ┌─ notifications: Collection<Notification> (1:N) ──┐                           │
│  │  - Un utilisateur peut recevoir plusieurs notifications                     │
│  │  - Relation OneToMany                                                       │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ calendarEvents: Collection<CalendarEvent> (1:N) ──┐                        │
│  │  - Un utilisateur peut créer plusieurs événements de calendrier              │
│  │  - Relation OneToMany                                                       │
│  └─────────────────────────────────────────────────────────┘                     │
│                                                                                   │
│  ┌─ collaborativeNotes: Collection<CollaborativeNote> (1:N) ──┐                │
│  │  - Un utilisateur peut créer plusieurs notes collaboratives                  │
│  │  - Relation OneToMany via createdBy_id                                      │
│  └─────────────────────────────────────────────────────────────┘                 │
│                                                                                   │
│  ┌─ resetPasswordRequests: Collection<ResetPasswordRequest> (1:N) ──┐           │
│  │  - Un utilisateur peut avoir plusieurs demandes de réinitialisation         │
│  │  - Relation OneToMany                                                       │
│  └─────────────────────────────────────────────────────────────────────┘         │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📅 EVENT (Événement)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ salle: Salle (N:1) ──┐                                                     │
│  │  - Un événement peut se dérouler dans une salle                              │
│  │  - Relation ManyToOne avec clé étrangère salle_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ organizer: User (N:1) ──┐                                                   │
│  │  - Un événement est organisé par un utilisateur                              │
│  │  - Relation ManyToOne avec clé étrangère organizer_id                        │
│  └───────────────────────────┘                                                   │
│                                                                                   │
│  ┌─ departement: Departement (N:1) ──┐                                         │
│  │  - Un événement appartient à un département                                 │
│  │  - Relation ManyToOne avec clé étrangère departement_id                     │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
│  ┌─ createdBy: User (N:1) ──┐                                                   │
│  │  - Un événement est créé par un utilisateur                                  │
│  │  - Relation ManyToOne avec clé étrangère created_by_id                       │
│  └───────────────────────────┘                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

#### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ invitations: Collection<Invitation> (1:N) ──┐                               │
│  │  - Un événement peut avoir plusieurs invitations                             │
│  │  - Relation OneToMany avec cascade persist, remove                          │
│  └───────────────────────────────────────────────┘                               │
│                                                                                   │
│  ┌─ documents: Collection<Document> (1:N) ──┐                                   │
│  │  - Un événement peut avoir plusieurs documents                              │
│  │  - Relation OneToMany                                                     │
│  └─────────────────────────────────────────────┘                                 │
│                                                                                   │
│  ┌─ participations: Collection<Participation> (1:N) ──┐                        │
│  │  - Un événement peut avoir plusieurs participations                          │
│  │  - Relation OneToMany avec orphanRemoval true                               │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ files: Collection<EventFile> (1:N) ──┐                                      │
│  │  - Un événement peut avoir plusieurs fichiers                               │
│  │  - Relation OneToMany avec cascade persist, remove                          │
│  └──────────────────────────────────────────┘                                    │
│                                                                                   │
│  ┌─ collaborativeNotes: Collection<CollaborativeNote> (1:N) ──┐                │
│  │  - Un événement peut avoir plusieurs notes collaboratives                   │
│  │  - Relation OneToMany avec cascade persist, remove                          │
│  └─────────────────────────────────────────────────────────────┘                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 SALLE (Salle de réunion)

#### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                SALLE (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ reservations: Collection<Reservation> (1:N) ──┐                             │
│  │  - Une salle peut avoir plusieurs réservations                               │
│  │  - Relation OneToMany (implicite via salle_id dans Reservation)              │
│  └─────────────────────────────────────────────────┘                             │
│                                                                                   │
│  ┌─ events: Collection<Event> (1:N) ──┐                                         │
│  │  - Une salle peut accueillir plusieurs événements                            │
│  │  - Relation OneToMany (implicite via salle_id dans Event)                   │
│  └──────────────────────────────────────┘                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏛️ DEPARTEMENT (Département)

#### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DEPARTEMENT (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ users: Collection<User> (1:N) ──┐                                           │
│  │  - Un département peut avoir plusieurs utilisateurs                          │
│  │  - Relation OneToMany avec mappedBy 'departement'                           │
│  └────────────────────────────────────┘                                          │
│                                                                                   │
│  ┌─ events: Collection<Event> (1:N) ──┐                                         │
│  │  - Un département peut organiser plusieurs événements                        │
│  │  - Relation OneToMany avec mappedBy 'departement'                           │
│  └──────────────────────────────────────┘                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📧 INVITATION (Invitation)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             INVITATION (1)                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Une invitation concerne un événement                                      │
│  │  - Relation ManyToOne avec clé étrangère event_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ participant: Participant (N:1) ──┐                                          │
│  │  - Une invitation est envoyée à un participant                               │
│  │  - Relation ManyToOne avec clé étrangère participant_id                      │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 PARTICIPATION (Participation)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPATION (1)                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Une participation implique un utilisateur                                 │
│  │  - Relation ManyToOne avec clé étrangère user_id                             │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Une participation concerne un événement                                   │
│  │  - Relation ManyToOne avec clé étrangère event_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔔 REMINDER (Rappel)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                REMINDER (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Un rappel notifie un utilisateur                                          │
│  │  - Relation ManyToOne avec clé étrangère user_id                             │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Un rappel peut concerner un événement                                     │
│  │  - Relation ManyToOne avec clé étrangère event_id (nullable)                 │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📢 NOTIFICATION (Notification)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             NOTIFICATION (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Une notification est destinée à un utilisateur                            │
│  │  - Relation ManyToOne avec clé étrangère user_id                             │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Une notification peut concerner un événement                              │
│  │  - Relation ManyToOne avec clé étrangère event_id (nullable)                 │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📋 RESERVATION (Réservation)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             RESERVATION (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ salle: Salle (N:1) ──┐                                                      │
│  │  - Une réservation concerne une salle                                        │
│  │  - Relation ManyToOne avec clé étrangère salle_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📄 DOCUMENT (Document)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DOCUMENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Un document appartient à un événement                                     │
│  │  - Relation ManyToOne avec clé étrangère event_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📝 COLLABORATIVENOTE (Note collaborative)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        COLLABORATIVENOTE (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Une note collaborative appartient à un événement                          │
│  │  - Relation ManyToOne avec clé étrangère event_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ createdBy: User (N:1) ──┐                                                   │
│  │  - Une note collaborative est créée par un utilisateur                       │
│  │  - Relation ManyToOne avec clé étrangère created_by_id                       │
│  └───────────────────────────┘                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👤 PARTICIPANT (Participant)

#### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPANT (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ invitations: Collection<Invitation> (1:N) ──┐                               │
│  │  - Un participant peut recevoir plusieurs invitations                        │
│  │  - Relation OneToMany avec mappedBy 'participant'                           │
│  └───────────────────────────────────────────────┘                               │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔐 RESETPASSWORDREQUEST (Demande de réinitialisation)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        RESETPASSWORDREQUEST (1)                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Une demande de réinitialisation concerne un utilisateur                   │
│  │  - Relation ManyToOne avec clé étrangère user_id                             │
│  └───────────────────────┘                                                       │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📅 CALENDAREVENT (Événement calendrier)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            CALENDAREVENT (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Un événement de calendrier appartient à un utilisateur                    │
│  │  - Relation ManyToOne avec clé étrangère user_id                             │
│  └───────────────────────┘                                                       │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🗂️ EVENTFILE (Fichier d'événement)

#### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             EVENTFILE (1)                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Un fichier d'événement appartient à un événement                          │
│  │  - Relation ManyToOne avec clé étrangère event_id                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 GESTIONSALLE (Gestion de salle)

#### Relations
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            GESTIONSALLE (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  Aucune relation explicite avec d'autres classes                                │
│  - Entité autonome pour la gestion simplifiée des salles                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔄 RÉSUMÉ DES TYPES DE RELATIONS

### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS ONE-TO-MANY                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  User (1) → Participation (N)                                                    │
│  User (1) → Reminder (N)                                                        │
│  User (1) → Notification (N)                                                    │
│  User (1) → CalendarEvent (N)                                                   │
│  User (1) → CollaborativeNote (N)                                               │
│  User (1) → ResetPasswordRequest (N)                                            │
│                                                                                   │
│  Event (1) → Invitation (N)                                                     │
│  Event (1) → Document (N)                                                       │
│  Event (1) → Participation (N)                                                   │
│  Event (1) → EventFile (N)                                                      │
│  Event (1) → CollaborativeNote (N)                                              │
│  Event (1) → Reminder (N)                                                       │
│  Event (1) → Notification (N)                                                   │
│                                                                                   │
│  Salle (1) → Reservation (N)                                                    │
│  Salle (1) → Event (N)                                                          │
│                                                                                   │
│  Departement (1) → User (N)                                                     │
│  Departement (1) → Event (N)                                                    │
│                                                                                   │
│  Participant (1) → Invitation (N)                                               │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Relations Many-to-One (N:1)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS MANY-TO-ONE                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  User (N) → Departement (1)                                                     │
│                                                                                   │
│  Event (N) → Salle (1)                                                          │
│  Event (N) → User (1) [organizer]                                               │
│  Event (N) → User (1) [createdBy]                                               │
│  Event (N) → Departement (1)                                                    │
│                                                                                   │
│  Invitation (N) → Event (1)                                                     │
│  Invitation (N) → Participant (1)                                               │
│                                                                                   │
│  Participation (N) → User (1)                                                    │
│  Participation (N) → Event (1)                                                   │
│                                                                                   │
│  Reminder (N) → User (1)                                                        │
│  Reminder (N) → Event (1)                                                       │
│                                                                                   │
│  Notification (N) → User (1)                                                    │
│  Notification (N) → Event (1)                                                   │
│                                                                                   │
│  Reservation (N) → Salle (1)                                                    │
│                                                                                   │
│  Document (N) → Event (1)                                                       │
│                                                                                   │
│  CollaborativeNote (N) → Event (1)                                              │
│  CollaborativeNote (N) → User (1) [createdBy]                                   │
│                                                                                   │
│  ResetPasswordRequest (N) → User (1)                                            │
│                                                                                   │
│  CalendarEvent (N) → User (1)                                                   │
│                                                                                   │
│  EventFile (N) → Event (1)                                                      │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Relations Many-to-Many (N:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          RELATIONS MANY-TO-MANY                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  User ↔ Event (via Participation)                                                │
│  - Un utilisateur peut participer à plusieurs événements                        │
│  - Un événement peut avoir plusieurs participants                                │
│  - Table de liaison : Participation                                              │
│                                                                                   │
│  User ↔ Event (via Invitation)                                                   │
│  - Un utilisateur peut recevoir plusieurs invitations d'événements              │
│  - Un événement peut envoyer plusieurs invitations                              │
│  - Table de liaison : Invitation                                                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 POINTS CLÉS DES RELATIONS

### 🔑 Clés étrangères principales
- **`user_id`** : Référence vers User
- **`event_id`** : Référence vers Event
- **`salle_id`** : Référence vers Salle
- **`departement_id`** : Référence vers Departement
- **`participant_id`** : Référence vers Participant
- **`created_by_id`** : Référence vers User (créateur)
- **`organizer_id`** : Référence vers User (organisateur)

### 🔄 Cascades configurées
- **Cascade Remove** : User → Participation, Event → Invitation
- **Cascade Persist** : Event → Document, Event → EventFile
- **Orphan Removal** : Event → Participation

### 📊 Cardinalités
- **1:1** : Relations uniques (ResetPasswordRequest → User)
- **1:N** : Relations One-to-Many (User → Participation)
- **N:1** : Relations Many-to-One (Event → User)
- **N:N** : Relations Many-to-Many via tables de liaison

Cette architecture relationnelle permet une gestion complète et cohérente de toutes les entités de votre application MaPlateforme ! 