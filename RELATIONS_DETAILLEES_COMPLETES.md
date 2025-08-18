# RELATIONS DÉTAILLÉES COMPLÈTES - MAPLATEFORME

## 📊 Vue d'ensemble complète des relations

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    RELATIONS DÉTAILLÉES COMPLÈTES                                │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 RELATIONS PAR CLASSE AVEC DÉTAILS TECHNIQUES

### 🧑‍💼 USER (Utilisateur)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER (1)                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ departement: Departement (N:1) ──┐                                         │
│  │  - Clé étrangère : departement_id                                            │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Departement                                                       │
│  │  - InversedBy : users                                                        │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

#### Relations sortantes (OneToMany)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER (1)                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ participations: Collection<Participation> (1:N) ──┐                        │
│  │  - MappedBy : user                                                             │
│  │  - Cascade : remove                                                            │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : Participation                                                     │
│  │  - Méthodes : addParticipation(), removeParticipation()                      │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ reminders: Collection<Reminder> (1:N) ──┐                                   │
│  │  - MappedBy : user                                                           │
│  │  - Cascade : none                                                            │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Reminder                                                         │
│  │  - Méthodes : addReminder(), removeReminder()                               │
│  └─────────────────────────────────────────────┘                                 │
│                                                                                   │
│  ┌─ notifications: Collection<Notification> (1:N) ──┐                           │
│  │  - MappedBy : user                                                             │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : Notification                                                      │
│  │  - Méthodes : addNotification(), removeNotification()                        │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ calendarEvents: Collection<CalendarEvent> (1:N) ──┐                        │
│  │  - MappedBy : user                                                             │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : CalendarEvent                                                     │
│  │  - Méthodes : addCalendarEvent(), removeCalendarEvent()                      │
│  └─────────────────────────────────────────────────────────┘                     │
│                                                                                   │
│  ┌─ collaborativeNotes: Collection<CollaborativeNote> (1:N) ──┐                │
│  │  - MappedBy : createdBy                                                        │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : CollaborativeNote                                                 │
│  │  - Méthodes : addCollaborativeNote(), removeCollaborativeNote()              │
│  └─────────────────────────────────────────────────────────────┘                 │
│                                                                                   │
│  ┌─ resetPasswordRequests: Collection<ResetPasswordRequest> (1:N) ──┐           │
│  │  - MappedBy : user                                                             │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : ResetPasswordRequest                                              │
│  │  - Méthodes : addResetPasswordRequest(), removeResetPasswordRequest()        │
│  └─────────────────────────────────────────────────────────────────────┘         │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📅 EVENT (Événement)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ salle: Salle (N:1) ──┐                                                     │
│  │  - Clé étrangère : salle_id                                                  │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Salle                                                             │
│  │  - InversedBy : events                                                       │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ organizer: User (N:1) ──┐                                                   │
│  │  - Clé étrangère : organizer_id                                               │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : organizedEvents                                               │
│  └───────────────────────────┘                                                   │
│                                                                                   │
│  ┌─ departement: Departement (N:1) ──┐                                         │
│  │  - Clé étrangère : departement_id                                            │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Departement                                                       │
│  │  - InversedBy : events                                                        │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
│  ┌─ createdBy: User (N:1) ──┐                                                   │
│  │  - Clé étrangère : created_by_id                                              │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : createdEvents                                                 │
│  └───────────────────────────┘                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

#### Relations sortantes (OneToMany)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ invitations: Collection<Invitation> (1:N) ──┐                               │
│  │  - MappedBy : event                                                           │
│  │  - Cascade : persist, remove                                                  │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : Invitation                                                        │
│  │  - Méthodes : addInvitation(), removeInvitation()                            │
│  └───────────────────────────────────────────────┘                               │
│                                                                                   │
│  ┌─ documents: Collection<Document> (1:N) ──┐                                   │
│  │  - MappedBy : event                                                          │
│  │  - Cascade : none                                                            │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Document                                                         │
│  │  - Méthodes : addDocument(), removeDocument()                                │
│  └─────────────────────────────────────────────┘                                 │
│                                                                                   │
│  ┌─ participations: Collection<Participation> (1:N) ──┐                        │
│  │  - MappedBy : event                                                            │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : Participation                                                     │
│  │  - OrphanRemoval : true                                                       │
│  │  - Méthodes : addParticipation(), removeParticipation()                      │
│  └─────────────────────────────────────────────────────┘                         │
│                                                                                   │
│  ┌─ files: Collection<EventFile> (1:N) ──┐                                      │
│  │  - MappedBy : event                                                           │
│  │  - Cascade : persist, remove                                                  │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : EventFile                                                         │
│  │  - Méthodes : addFile(), removeFile()                                        │
│  └──────────────────────────────────────────┘                                    │
│                                                                                   │
│  ┌─ collaborativeNotes: Collection<CollaborativeNote> (1:N) ──┐                │
│  │  - MappedBy : event                                                           │
│  │  - Cascade : persist, remove                                                  │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : CollaborativeNote                                                 │
│  │  - Méthodes : addCollaborativeNote(), removeCollaborativeNote()              │
│  └─────────────────────────────────────────────────────────────┘                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 SALLE (Salle de réunion)

#### Relations sortantes (OneToMany)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                SALLE (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ reservations: Collection<Reservation> (1:N) ──┐                             │
│  │  - MappedBy : salle                                                            │
│  │  - Cascade : none                                                              │
│  │  - Fetch : LAZY                                                               │
│  │  - Target : Reservation                                                        │
│  │  - Méthodes : addReservation(), removeReservation()                           │
│  └─────────────────────────────────────────────────┘                             │
│                                                                                   │
│  ┌─ events: Collection<Event> (1:N) ──┐                                         │
│  │  - MappedBy : salle                                                           │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - Méthodes : addEvent(), removeEvent()                                      │
│  └──────────────────────────────────────┘                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏛️ DEPARTEMENT (Département)

#### Relations sortantes (OneToMany)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DEPARTEMENT (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ users: Collection<User> (1:N) ──┐                                           │
│  │  - MappedBy : departement                                                     │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - Méthodes : addUser(), removeUser()                                        │
│  └────────────────────────────────────┘                                          │
│                                                                                   │
│  ┌─ events: Collection<Event> (1:N) ──┐                                         │
│  │  - MappedBy : departement                                                     │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - Méthodes : addEvent(), removeEvent()                                      │
│  └──────────────────────────────────────┘                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📧 INVITATION (Invitation)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             INVITATION (1)                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : invitations                                                   │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ participant: Participant (N:1) ──┐                                          │
│  │  - Clé étrangère : participant_id                                             │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Participant                                                        │
│  │  - InversedBy : invitations                                                   │
│  └─────────────────────────────────────┘                                         │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 PARTICIPATION (Participation)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPATION (1)                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Clé étrangère : user_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : participations                                               │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : participations                                                │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔔 REMINDER (Rappel)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                REMINDER (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Clé étrangère : user_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : reminders                                                    │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : reminders                                                     │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📢 NOTIFICATION (Notification)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             NOTIFICATION (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Clé étrangère : user_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : notifications                                                │
│  └───────────────────────┘                                                       │
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = true                                               │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : notifications                                                 │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📋 RESERVATION (Réservation)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             RESERVATION (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ salle: Salle (N:1) ──┐                                                      │
│  │  - Clé étrangère : salle_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Salle                                                             │
│  │  - InversedBy : reservations                                                  │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📄 DOCUMENT (Document)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DOCUMENT (1)                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : documents                                                     │
│  └─────────────────────────┘                                                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📝 COLLABORATIVENOTE (Note collaborative)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        COLLABORATIVENOTE (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : collaborativeNotes                                            │
│  └─────────────────────────┘                                                     │
│                                                                                   │
│  ┌─ createdBy: User (N:1) ──┐                                                   │
│  │  - Clé étrangère : created_by_id                                              │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : collaborativeNotes                                            │
│  └───────────────────────────┘                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👤 PARTICIPANT (Participant)

#### Relations sortantes (OneToMany)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPANT (1)                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ invitations: Collection<Invitation> (1:N) ──┐                               │
│  │  - MappedBy : participant                                                     │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Invitation                                                        │
│  │  - Méthodes : addInvitation(), removeInvitation()                            │
│  └───────────────────────────────────────────────┘                               │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔐 RESETPASSWORDREQUEST (Demande de réinitialisation)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        RESETPASSWORDREQUEST (1)                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Clé étrangère : user_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : resetPasswordRequests                                        │
│  └───────────────────────┘                                                       │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📅 CALENDAREVENT (Événement calendrier)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            CALENDAREVENT (1)                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ user: User (N:1) ──┐                                                        │
│  │  - Clé étrangère : user_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : User                                                              │
│  │  - InversedBy : calendarEvents                                                │
│  └───────────────────────┘                                                       │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🗂️ EVENTFILE (Fichier d'événement)

#### Relations entrantes (ManyToOne)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             EVENTFILE (1)                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─ event: Event (N:1) ──┐                                                      │
│  │  - Clé étrangère : event_id                                                   │
│  │  - Contrainte : nullable = false                                              │
│  │  - Cascade : none                                                             │
│  │  - Fetch : LAZY                                                              │
│  │  - Target : Event                                                             │
│  │  - InversedBy : files                                                         │
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
│  - Pas de clés étrangères                                                       │
│  - Pas de collections                                                           │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔑 DÉTAILS TECHNIQUES DES RELATIONS

### Clés étrangères et contraintes
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           CLÉS ÉTRANGÈRES                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  user_id                    → User.id (INT, NOT NULL)                            │
│  event_id                   → Event.id (INT, NOT NULL)                           │
│  salle_id                   → Salle.id (INT, NULLABLE)                           │
│  departement_id             → Departement.id (INT, NULLABLE)                     │
│  participant_id             → Participant.id (INT, NULLABLE)                     │
│  created_by_id              → User.id (INT, NOT NULL)                            │
│  organizer_id               → User.id (INT, NOT NULL)                            │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Types de cascade
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              TYPES DE CASCADE                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  Cascade Remove :                                                                 │
│  - User → Participation (suppression en cascade)                                │
│  - Event → Invitation (suppression en cascade)                                  │
│  - Event → EventFile (suppression en cascade)                                   │
│  - Event → CollaborativeNote (suppression en cascade)                           │
│                                                                                   │
│  Cascade Persist :                                                               │
│  - Event → Invitation (persistance en cascade)                                  │
│  - Event → EventFile (persistance en cascade)                                   │
│  - Event → CollaborativeNote (persistance en cascade)                           │
│                                                                                   │
│  Orphan Removal :                                                                │
│  - Event → Participation (suppression des orphelins)                            │
│                                                                                   │
│  Aucune cascade :                                                                │
│  - Toutes les autres relations                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Stratégies de chargement (Fetch)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        STRATÉGIES DE CHARGEMENT                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  LAZY (chargement différé) :                                                     │
│  - Toutes les relations ManyToOne                                                │
│  - Toutes les relations OneToMany                                                │
│  - Avantage : Performance optimisée                                              │
│  - Inconvénient : Requêtes N+1 potentielles                                     │
│                                                                                   │
│  EAGER (chargement immédiat) :                                                  │
│  - Aucune relation configurée en EAGER                                          │
│  - Toutes les relations sont en LAZY par défaut                                 │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Méthodes de gestion des relations
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        MÉTHODES DE GESTION                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  Méthodes d'ajout :                                                             │
│  - addParticipation(Participation $participation)                               │
│  - addInvitation(Invitation $invitation)                                        │
│  - addDocument(Document $document)                                               │
│  - addFile(EventFile $file)                                                      │
│  - addCollaborativeNote(CollaborativeNote $note)                                │
│  - addUser(User $user)                                                           │
│  - addEvent(Event $event)                                                        │
│                                                                                   │
│  Méthodes de suppression :                                                       │
│  - removeParticipation(Participation $participation)                            │
│  - removeInvitation(Invitation $invitation)                                     │
│  - removeDocument(Document $document)                                            │
│  - removeFile(EventFile $file)                                                   │
│  - removeCollaborativeNote(CollaborativeNote $note)                             │
│  - removeUser(User $user)                                                        │
│  - removeEvent(Event $event)                                                     │
│                                                                                   │
│  Méthodes de récupération :                                                      │
│  - getParticipations(): Collection<Participation>                               │
│  - getInvitations(): Collection<Invitation>                                      │
│  - getDocuments(): Collection<Document>                                          │
│  - getFiles(): Collection<EventFile>                                             │
│  - getCollaborativeNotes(): Collection<CollaborativeNote>                       │
│  - getUsers(): Collection<User>                                                  │
│  - getEvents(): Collection<Event>                                                │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📊 RÉSUMÉ COMPLET DES RELATIONS

### Relations One-to-Many (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS ONE-TO-MANY                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  User (1) → Participation (N) [Cascade: remove]                                 │
│  User (1) → Reminder (N)                                                        │
│  User (1) → Notification (N)                                                    │
│  User (1) → CalendarEvent (N)                                                   │
│  User (1) → CollaborativeNote (N) [via createdBy]                              │
│  User (1) → ResetPasswordRequest (N)                                            │
│                                                                                   │
│  Event (1) → Invitation (N) [Cascade: persist, remove]                         │
│  Event (1) → Document (N)                                                       │
│  Event (1) → Participation (N) [OrphanRemoval: true]                           │
│  Event (1) → EventFile (N) [Cascade: persist, remove]                          │
│  Event (1) → CollaborativeNote (N) [Cascade: persist, remove]                  │
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
│  User (N) → Departement (1) [nullable: true]                                    │
│                                                                                   │
│  Event (N) → Salle (1) [nullable: true]                                         │
│  Event (N) → User (1) [organizer, nullable: false]                              │
│  Event (N) → User (1) [createdBy, nullable: false]                              │
│  Event (N) → Departement (1) [nullable: true]                                   │
│                                                                                   │
│  Invitation (N) → Event (1) [nullable: true]                                    │
│  Invitation (N) → Participant (1) [nullable: true]                              │
│                                                                                   │
│  Participation (N) → User (1) [nullable: false]                                 │
│  Participation (N) → Event (1) [nullable: false]                                │
│                                                                                   │
│  Reminder (N) → User (1) [nullable: false]                                      │
│  Reminder (N) → Event (1) [nullable: true]                                      │
│                                                                                   │
│  Notification (N) → User (1) [nullable: false]                                  │
│  Notification (N) → Event (1) [nullable: true]                                  │
│                                                                                   │
│  Reservation (N) → Salle (1) [nullable: false]                                  │
│                                                                                   │
│  Document (N) → Event (1) [nullable: false]                                     │
│                                                                                   │
│  CollaborativeNote (N) → Event (1) [nullable: false]                            │
│  CollaborativeNote (N) → User (1) [createdBy, nullable: false]                  │
│                                                                                   │
│  ResetPasswordRequest (N) → User (1) [nullable: false]                          │
│                                                                                   │
│  CalendarEvent (N) → User (1) [nullable: false]                                 │
│                                                                                   │
│  EventFile (N) → Event (1) [nullable: false]                                    │
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
│  - Table de liaison : Participation                                              │
│  - Clés : user_id, event_id                                                     │
│  - Contraintes : NOT NULL sur les deux clés                                     │
│  - Cascade : remove sur User → Participation                                     │
│  - OrphanRemoval : true sur Event → Participation                               │
│                                                                                   │
│  User ↔ Event (via Invitation)                                                   │
│  - Table de liaison : Invitation                                                 │
│  - Clés : event_id, participant_id                                              │
│  - Contraintes : nullable sur les deux clés                                     │
│  - Cascade : persist, remove sur Event → Invitation                             │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 POINTS CLÉS DE L'ARCHITECTURE

### 🔑 Gestion des clés étrangères
- **Contraintes NOT NULL** : Relations obligatoires
- **Contraintes NULLABLE** : Relations optionnelles
- **Index automatiques** : Performance optimisée
- **Contraintes d'intégrité** : Cohérence des données

### 🔄 Stratégies de cascade
- **Cascade Remove** : Suppression en cascade pour éviter les orphelins
- **Cascade Persist** : Persistance en cascade pour les nouvelles entités
- **Orphan Removal** : Suppression automatique des entités orphelines
- **Aucune cascade** : Contrôle manuel pour la plupart des relations

### 📊 Performance et optimisation
- **Fetch LAZY** : Chargement différé pour optimiser les performances
- **Collections** : Gestion efficace des collections avec Doctrine
- **Requêtes optimisées** : Éviter les requêtes N+1
- **Index de base de données** : Performance des jointures

Cette architecture relationnelle complète permet une gestion robuste et performante de toutes les entités de votre application MaPlateforme ! 