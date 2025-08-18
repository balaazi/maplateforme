# 🔗 RELATIONS (1:N) AVEC VERBES

## 🎯 RELATIONS ONE-TO-MANY (1:N) - DÉTAILLÉES

### 👥 **USER → PARTICIPATION** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "participe à" PARTICIPATION                                     │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│PARTICIPATION│                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - statut    │                              │
│  │ - nom       │                    │ - present   │                              │
│  │ - prenom    │                    │ - user      │                              │
│  │ - participations│                │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "participe à"                                                            │
│  • 1 USER peut participer à N PARTICIPATIONS                                     │
│  • 1 PARTICIPATION appartient à 1 USER                                           │
│  • Clé étrangère : participation.user_id → user.id                               │
│  • Cascade : REMOVE                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → REMINDER** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "reçoit" REMINDER                                               │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│   REMINDER  │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - message   │                              │
│  │ - prenom    │                    │ - dateEcheance│                             │
│  │ - reminders │                    │ - user      │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "reçoit"                                                                 │
│  • 1 USER peut recevoir N REMINDERS                                              │
│  • 1 REMINDER est reçu par 1 USER                                                │
│  • Clé étrangère : reminder.user_id → user.id                                   │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → NOTIFICATION** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "reçoit" NOTIFICATION                                           │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│ NOTIFICATION│                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - message   │                              │
│  │ - prenom    │                    │ - type      │                              │
│  │ - notifications│                 │ - user      │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "reçoit"                                                                 │
│  • 1 USER peut recevoir N NOTIFICATIONS                                          │
│  • 1 NOTIFICATION est reçue par 1 USER                                           │
│  • Clé étrangère : notification.user_id → user.id                               │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → EVENT (ORGANIZER)** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "organise" EVENT                                                │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│    EVENT    │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - description│                              │
│  │ - prenom    │                    │ - lieu      │                              │
│  │ - organizedEvents│               │ - organizer │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "organise"                                                               │
│  • 1 USER peut organiser N EVENTS                                                │
│  • 1 EVENT est organisé par 1 USER                                               │
│  • Clé étrangère : event.organizer_id → user.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → EVENT (CREATOR)** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "crée" EVENT                                                    │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│    EVENT    │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - description│                              │
│  │ - prenom    │                    │ - lieu      │                              │
│  │ - createdEvents│                 │ - createdBy │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "crée"                                                                   │
│  • 1 USER peut créer N EVENTS                                                    │
│  • 1 EVENT est créé par 1 USER                                                   │
│  • Clé étrangère : event.created_by_id → user.id                                │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → CALENDAR_EVENT** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "a" CALENDAR_EVENT                                              │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│CALENDAR_EVENT│                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - dateDebut │                              │
│  │ - prenom    │                    │ - dateFin   │                              │
│  │ - calendarEvents│                │ - user      │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "a"                                                                      │
│  • 1 USER peut avoir N CALENDAR_EVENTS                                           │
│  • 1 CALENDAR_EVENT appartient à 1 USER                                          │
│  • Clé étrangère : calendar_event.user_id → user.id                             │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → COLLABORATIVE_NOTE** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "écrit" COLLABORATIVE_NOTE                                      │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│COLLABORATIVE│                              │
│  │             │  1                 │    NOTE     │                              │
│  │ - id        │                    │             │                              │
│  │ - email     │                    │ - id        │                              │
│  │ - nom       │                    │ - titre     │                              │
│  │ - prenom    │                    │ - contenu   │                              │
│  │ - collaborativeNotes│            │ - user      │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "écrit"                                                                  │
│  • 1 USER peut écrire N COLLABORATIVE_NOTES                                      │
│  • 1 COLLABORATIVE_NOTE est écrite par 1 USER                                    │
│  • Clé étrangère : collaborative_note.user_id → user.id                         │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 👥 **USER → RESET_PASSWORD_REQUEST** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : USER "demande" RESET_PASSWORD_REQUEST                                │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│RESET_PASSWORD│                              │
│  │             │  1                 │  REQUEST    │                              │
│  │ - id        │                    │             │                              │
│  │ - email     │                    │ - id        │                              │
│  │ - nom       │                    │ - token     │                              │
│  │ - prenom    │                    │ - expiresAt │                              │
│  │ - resetPasswordRequests│         │ - user      │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "demande"                                                                │
│  • 1 USER peut demander N RESET_PASSWORD_REQUESTS                                │
│  • 1 RESET_PASSWORD_REQUEST est demandé par 1 USER                               │
│  • Clé étrangère : reset_password_request.user_id → user.id                     │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → INVITATION** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "génère" INVITATION                                            │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│  INVITATION │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - statut    │                              │
│  │ - description│                   │ - token     │                              │
│  │ - lieu      │                    │ - event     │                              │
│  │ - invitations│                   │ - participant│                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "génère"                                                                 │
│  • 1 EVENT peut générer N INVITATIONS                                            │
│  • 1 INVITATION est générée par 1 EVENT                                          │
│  • Clé étrangère : invitation.event_id → event.id                               │
│  • Cascade : PERSIST, REMOVE                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → DOCUMENT** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "contient" DOCUMENT                                            │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│  DOCUMENT   │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - nom       │                              │
│  │ - description│                   │ - fichier   │                              │
│  │ - lieu      │                    │ - type      │                              │
│  │ - documents │                    │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "contient"                                                               │
│  • 1 EVENT peut contenir N DOCUMENTS                                             │
│  • 1 DOCUMENT appartient à 1 EVENT                                               │
│  • Clé étrangère : document.event_id → event.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → PARTICIPATION** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "a" PARTICIPATION                                              │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│PARTICIPATION│                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - statut    │                              │
│  │ - description│                   │ - present   │                              │
│  │ - lieu      │                    │ - user      │                              │
│  │ - participations│                │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "a"                                                                      │
│  • 1 EVENT peut avoir N PARTICIPATIONS                                           │
│  • 1 PARTICIPATION appartient à 1 EVENT                                          │
│  • Clé étrangère : participation.event_id → event.id                             │
│  • Cascade : ORPHAN_REMOVE                                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → EVENT_FILE** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "contient" EVENT_FILE                                          │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│  EVENT_FILE │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - nom       │                              │
│  │ - description│                   │ - fichier   │                              │
│  │ - lieu      │                    │ - type      │                              │
│  │ - files     │                    │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "contient"                                                               │
│  • 1 EVENT peut contenir N EVENT_FILES                                           │
│  • 1 EVENT_FILE appartient à 1 EVENT                                             │
│  • Clé étrangère : event_file.event_id → event.id                               │
│  • Cascade : PERSIST, REMOVE                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → COLLABORATIVE_NOTE** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "a" COLLABORATIVE_NOTE                                         │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│COLLABORATIVE│                              │
│  │             │  1                 │    NOTE     │                              │
│  │ - id        │                    │             │                              │
│  │ - titre     │                    │ - id        │                              │
│  │ - description│                   │ - titre     │                              │
│  │ - lieu      │                    │ - contenu   │                              │
│  │ - collaborativeNotes│            │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "a"                                                                      │
│  • 1 EVENT peut avoir N COLLABORATIVE_NOTES                                      │
│  • 1 COLLABORATIVE_NOTE appartient à 1 EVENT                                     │
│  • Clé étrangère : collaborative_note.event_id → event.id                       │
│  • Cascade : PERSIST, REMOVE                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **EVENT → REMINDER** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : EVENT "génère" REMINDER                                              │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│   REMINDER  │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - titre     │                              │
│  │ - description│                   │ - message   │                              │
│  │ - lieu      │                    │ - dateEcheance│                             │
│  │ - reminders │                    │ - event     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "génère"                                                                 │
│  • 1 EVENT peut générer N REMINDERS                                              │
│  • 1 REMINDER est généré par 1 EVENT                                             │
│  • Clé étrangère : reminder.event_id → event.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 **DEPARTEMENT → USER** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : DEPARTEMENT "emploie" USER                                           │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │DEPARTEMENT  │───────────────────►│    USER     │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - nom       │                    │ - email     │                              │
│  │ - contact   │                    │ - nom       │                              │
│  │ - budget    │                    │ - prenom    │                              │
│  │ - users     │                    │ - departement│                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "emploie"                                                                │
│  • 1 DEPARTEMENT peut employer N USERS                                           │
│  • 1 USER est employé par 1 DEPARTEMENT                                          │
│  • Clé étrangère : user.departement_id → departement.id                          │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 **DEPARTEMENT → EVENT** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : DEPARTEMENT "organise" EVENT                                         │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │DEPARTEMENT  │───────────────────►│    EVENT    │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - nom       │                    │ - titre     │                              │
│  │ - contact   │                    │ - description│                              │
│  │ - budget    │                    │ - lieu      │                              │
│  │ - events    │                    │ - departement│                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "organise"                                                               │
│  • 1 DEPARTEMENT peut organiser N EVENTS                                         │
│  • 1 EVENT est organisé par 1 DEPARTEMENT                                        │
│  • Clé étrangère : event.departement_id → departement.id                         │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏠 **SALLE → EVENT** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : SALLE "accueille" EVENT                                              │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    SALLE    │───────────────────►│    EVENT    │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - nom       │                    │ - titre     │                              │
│  │ - capacite  │                    │ - description│                              │
│  │ - disponible│                    │ - lieu      │                              │
│  │ - events    │                    │ - salle     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "accueille"                                                              │
│  • 1 SALLE peut accueillir N EVENTS                                              │
│  • 1 EVENT se déroule dans 1 SALLE                                               │
│  • Clé étrangère : event.salle_id → salle.id                                    │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏠 **SALLE → RESERVATION** (1:N)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATION : SALLE "a" RESERVATION                                                │
│  CARDINALITÉ : 1:N                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    SALLE    │───────────────────►│ RESERVATION │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - nom       │                    │ - dateDebut │                              │
│  │ - capacite  │                    │ - dateFin   │                              │
│  │ - disponible│                    │ - statut    │                              │
│  │ - reservations│                  │ - salle     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  VERBE : "a"                                                                      │
│  • 1 SALLE peut avoir N RESERVATIONS                                             │
│  • 1 RESERVATION appartient à 1 SALLE                                            │
│  • Clé étrangère : reservation.salle_id → salle.id                              │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📊 RÉSUMÉ DES RELATIONS (1:N)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        RÉSUMÉ DES RELATIONS (1:N)                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  👥 USER (8 relations) :                                                         │
│  • "participe à" → PARTICIPATION                                                 │
│  • "reçoit" → REMINDER                                                           │
│  • "reçoit" → NOTIFICATION                                                       │
│  • "organise" → EVENT                                                            │
│  • "crée" → EVENT                                                                │
│  • "a" → CALENDAR_EVENT                                                          │
│  • "écrit" → COLLABORATIVE_NOTE                                                  │
│  • "demande" → RESET_PASSWORD_REQUEST                                            │
│                                                                                   │
│  🎪 EVENT (6 relations) :                                                        │
│  • "génère" → INVITATION                                                         │
│  • "contient" → DOCUMENT                                                         │
│  • "a" → PARTICIPATION                                                           │
│  • "contient" → EVENT_FILE                                                       │
│  • "a" → COLLABORATIVE_NOTE                                                      │
│  • "génère" → REMINDER                                                           │
│                                                                                   │
│  🏢 DEPARTEMENT (2 relations) :                                                  │
│  • "emploie" → USER                                                              │
│  • "organise" → EVENT                                                            │
│                                                                                   │
│  🏠 SALLE (2 relations) :                                                        │
│  • "accueille" → EVENT                                                           │
│  • "a" → RESERVATION                                                             │
│                                                                                   │
│  📊 TOTAL : 18 relations (1:N)                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 VERBES UTILISÉS DANS LES RELATIONS (1:N)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        VERBES DES RELATIONS (1:N)                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 VERBES PRINCIPAUX :                                                          │
│  • "participe à" : USER → PARTICIPATION                                          │
│  • "reçoit" : USER → REMINDER, NOTIFICATION                                      │
│  • "organise" : USER → EVENT, DEPARTEMENT → EVENT                                │
│  • "crée" : USER → EVENT                                                         │
│  • "a" : USER → CALENDAR_EVENT, EVENT → PARTICIPATION, EVENT → COLLABORATIVE_NOTE │
│  • "écrit" : USER → COLLABORATIVE_NOTE                                           │
│  • "demande" : USER → RESET_PASSWORD_REQUEST                                     │
│  • "génère" : EVENT → INVITATION, EVENT → REMINDER                               │
│  • "contient" : EVENT → DOCUMENT, EVENT → EVENT_FILE                             │
│  • "emploie" : DEPARTEMENT → USER                                                │
│  • "accueille" : SALLE → EVENT                                                   │
│                                                                                   │
│  📊 FRÉQUENCE DES VERBES :                                                       │
│  • "a" : 3 fois                                                                  │
│  • "reçoit" : 2 fois                                                             │
│  • "organise" : 2 fois                                                           │
│  • "génère" : 2 fois                                                             │
│  • "contient" : 2 fois                                                           │
│  • Autres verbes : 1 fois chacun                                                 │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

*📝 Document créé pour détailler toutes les relations avec cardinalité (1:N) et leurs verbes correspondants* 