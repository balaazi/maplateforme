# 📊 RELATIONS D'ASSOCIATION ET CARDINALITÉS - DIAGRAMMES UML

## 🎯 Vue d'ensemble des relations d'association

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    SYSTÈME DE RELATIONS D'ASSOCIATION                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  Relations principales :                                                          │
│  • One-to-Many (1:N)     : Une entité → Plusieurs entités                        │
│  • Many-to-One (N:1)     : Plusieurs entités → Une entité                        │
│  • Many-to-Many (N:N)    : Plusieurs entités ↔ Plusieurs entités                 │
│  • One-to-One (1:1)      : Une entité ↔ Une entité                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 RELATIONS D'ASSOCIATION DÉTAILLÉES

### 1️⃣ **USER ↔ DEPARTEMENT** (Many-to-One)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : N:1                                                                │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │◄───────────────────│ DEPARTEMENT │                              │
│  │             │  N                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - nom       │                              │
│  │ - nom       │                    │ - contact   │                              │
│  │ - prenom    │                    │ - budget    │                              │
│  │ - departement│                   │ - users     │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  DÉTAILS :                                                                        │
│  • Un utilisateur appartient à UN SEUL département                               │
│  • Un département peut avoir PLUSIEURS utilisateurs                              │
│  • Clé étrangère : user.departement_id → departement.id                          │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 2️⃣ **USER ↔ PARTICIPATION** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
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
│  DÉTAILS :                                                                        │
│  • Un utilisateur peut avoir PLUSIEURES participations                           │
│  • Une participation appartient à UN SEUL utilisateur                            │
│  • Clé étrangère : participation.user_id → user.id                               │
│  • Cascade : REMOVE (suppression en cascade)                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 3️⃣ **EVENT ↔ USER (ORGANIZER)** (Many-to-One)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : N:1                                                                │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │◄───────────────────│    USER     │                              │
│  │             │  N                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - email     │                              │
│  │ - description│                   │ - nom       │                              │
│  │ - lieu      │                    │ - prenom    │                              │
│  │ - dateHeure │                    │ - roles     │                              │
│  │ - organizer │                    │ - events    │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  DÉTAILS :                                                                        │
│  • Un événement a UN SEUL organisateur                                           │
│  • Un utilisateur peut organiser PLUSIEURS événements                            │
│  • Clé étrangère : event.organizer_id → user.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 4️⃣ **EVENT ↔ SALLE** (Many-to-One)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : N:1                                                                │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │◄───────────────────│    SALLE    │                              │
│  │             │  N                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - nom       │                              │
│  │ - description│                   │ - capacite  │                              │
│  │ - lieu      │                    │ - disponible│                              │
│  │ - dateHeure │                    │ - equipement│                              │
│  │ - salle     │                    │ - events    │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  DÉTAILS :                                                                        │
│  • Un événement peut utiliser UNE SEULE salle                                    │
│  • Une salle peut accueillir PLUSIEURS événements                               │
│  • Clé étrangère : event.salle_id → salle.id                                    │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 5️⃣ **EVENT ↔ INVITATION** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
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
│  DÉTAILS :                                                                        │
│  • Un événement peut avoir PLUSIEURES invitations                                │
│  • Une invitation appartient à UN SEUL événement                                 │
│  • Clé étrangère : invitation.event_id → event.id                               │
│  • Cascade : PERSIST, REMOVE                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 6️⃣ **EVENT ↔ DOCUMENT** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
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
│  DÉTAILS :                                                                        │
│  • Un événement peut avoir PLUSIEURS documents                                   │
│  • Un document appartient à UN SEUL événement                                    │
│  • Clé étrangère : document.event_id → event.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 7️⃣ **EVENT ↔ PARTICIPATION** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
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
│  DÉTAILS :                                                                        │
│  • Un événement peut avoir PLUSIEURES participations                             │
│  • Une participation appartient à UN SEUL événement                              │
│  • Clé étrangère : participation.event_id → event.id                             │
│  • Cascade : ORPHAN_REMOVE (suppression des orphelins)                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 8️⃣ **SALLE ↔ RESERVATION** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
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
│  DÉTAILS :                                                                        │
│  • Une salle peut avoir PLUSIEURES réservations                                  │
│  • Une réservation appartient à UNE SEULE salle                                  │
│  • Clé étrangère : reservation.salle_id → salle.id                              │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 9️⃣ **USER ↔ REMINDER** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    USER     │───────────────────►│   REMINDER  │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - email     │                    │ - titre     │                              │
│  │ - nom       │                    │ - message   │                              │
│  │ - prenom    │                    │ - dateEcheance│                             │
│  │ - reminders │                    │ - statut    │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  DÉTAILS :                                                                        │
│  • Un utilisateur peut avoir PLUSIEURS rappels                                   │
│  • Un rappel appartient à UN SEUL utilisateur                                    │
│  • Clé étrangère : reminder.user_id → user.id                                   │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔟 **EVENT ↔ REMINDER** (One-to-Many)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CARDINALITÉ : 1:N                                                                │
│  ┌─────────────┐                    ┌─────────────┐                              │
│  │    EVENT    │───────────────────►│   REMINDER  │                              │
│  │             │  1                 │             │                              │
│  │ - id        │                    │ - id        │                              │
│  │ - titre     │                    │ - titre     │                              │
│  │ - description│                   │ - message   │                              │
│  │ - lieu      │                    │ - dateEcheance│                             │
│  │ - reminders │                    │ - statut    │                              │
│  └─────────────┘                    └─────────────┘                              │
│                                                                                   │
│  DÉTAILS :                                                                        │
│  • Un événement peut avoir PLUSIEURS rappels                                     │
│  • Un rappel peut être lié à UN SEUL événement                                   │
│  • Clé étrangère : reminder.event_id → event.id                                 │
│  • Cascade : Aucune                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📋 RÉSUMÉ DES CARDINALITÉS

### 🔢 **Relations One-to-Many (1:N)**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATIONS 1:N (Une entité → Plusieurs entités)                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • USER → PARTICIPATION        : 1 utilisateur → N participations                 │
│  • USER → REMINDER            : 1 utilisateur → N rappels                         │
│  • EVENT → INVITATION         : 1 événement → N invitations                       │
│  • EVENT → DOCUMENT           : 1 événement → N documents                         │
│  • EVENT → PARTICIPATION      : 1 événement → N participations                    │
│  • EVENT → REMINDER           : 1 événement → N rappels                           │
│  • SALLE → RESERVATION        : 1 salle → N réservations                          │
│  • DEPARTEMENT → USER         : 1 département → N utilisateurs                    │
│  • DEPARTEMENT → EVENT        : 1 département → N événements                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔢 **Relations Many-to-One (N:1)**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATIONS N:1 (Plusieurs entités → Une entité)                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • PARTICIPATION → USER        : N participations → 1 utilisateur                 │
│  • REMINDER → USER            : N rappels → 1 utilisateur                         │
│  • INVITATION → EVENT         : N invitations → 1 événement                       │
│  • DOCUMENT → EVENT           : N documents → 1 événement                         │
│  • PARTICIPATION → EVENT      : N participations → 1 événement                    │
│  • REMINDER → EVENT           : N rappels → 1 événement                           │
│  • RESERVATION → SALLE        : N réservations → 1 salle                          │
│  • USER → DEPARTEMENT         : N utilisateurs → 1 département                    │
│  • EVENT → DEPARTEMENT        : N événements → 1 département                      │
│  • EVENT → USER (ORGANIZER)   : N événements → 1 organisateur                     │
│  • EVENT → SALLE              : N événements → 1 salle                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔢 **Relations Many-to-Many (N:N)**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RELATIONS N:N (Plusieurs entités ↔ Plusieurs entités)                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • USER ↔ EVENT (via PARTICIPATION) : N utilisateurs ↔ N événements               │
│  • USER ↔ EVENT (via INVITATION)    : N utilisateurs ↔ N événements               │
│  • EVENT ↔ SALLE (via RESERVATION)  : N événements ↔ N salles                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎨 DIAGRAMME COMPLET DES RELATIONS

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMME COMPLET DES RELATIONS                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐    1:N    ┌─────────────┐    N:1    ┌─────────────┐            │
│  │    USER     │◄─────────►│PARTICIPATION│◄─────────►│    EVENT     │            │
│  │             │            │             │            │             │            │
│  │ - id        │            │ - id        │            │ - id        │            │
│  │ - email     │            │ - statut    │            │ - titre     │            │
│  │ - nom       │            │ - present   │            │ - description│           │
│  │ - prenom    │            │ - user      │            │ - lieu      │            │
│  │ - departement│           │ - event     │            │ - dateHeure │            │
│  └─────────────┘            └─────────────┘            │ - organizer │            │
│         │                           │                  │ - salle     │            │
│         │ 1:N                       │ 1:N              └─────────────┘            │
│         ▼                           ▼                         │                    │
│  ┌─────────────┐            ┌─────────────┐                  │ 1:N                │
│  │   REMINDER  │            │  INVITATION │                  ▼                    │
│  │             │            │             │            ┌─────────────┐            │
│  │ - id        │            │ - id        │            │  DOCUMENT   │            │
│  │ - titre     │            │ - statut    │            │             │            │
│  │ - message   │            │ - token     │            │ - id        │            │
│  │ - dateEcheance│          │ - event     │            │ - nom       │            │
│  │ - user      │            │ - participant│           │ - fichier   │            │
│  └─────────────┘            └─────────────┘            │ - event     │            │
│                                                         └─────────────┘            │
│  ┌─────────────┐    N:1    ┌─────────────┐    1:N    ┌─────────────┐            │
│  │DEPARTEMENT  │◄─────────►│    USER     │◄─────────►│    SALLE     │            │
│  │             │            │             │            │             │            │
│  │ - id        │            │ - id        │            │ - id        │            │
│  │ - nom       │            │ - email     │            │ - nom       │            │
│  │ - contact   │            │ - nom       │            │ - capacite  │            │
│  │ - budget    │            │ - prenom    │            │ - disponible│            │
│  │ - users     │            │ - departement│           │ - equipement│            │
│  └─────────────┘            └─────────────┘            └─────────────┘            │
│         │ 1:N                       │ 1:N                       │ 1:N            │
│         ▼                           ▼                           ▼                │
│  ┌─────────────┐            ┌─────────────┐            ┌─────────────┐            │
│  │    EVENT    │            │   REMINDER  │            │ RESERVATION │            │
│  │             │            │             │            │             │            │
│  │ - id        │            │ - id        │            │ - id        │            │
│  │ - titre     │            │ - titre     │            │ - dateDebut │            │
│  │ - description│           │ - message   │            │ - dateFin   │            │
│  │ - lieu      │            │ - dateEcheance│          │ - statut    │            │
│  │ - departement│           │ - user      │            │ - salle     │            │
│  └─────────────┘            └─────────────┘            └─────────────┘            │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔧 TYPES DE CASCADE

### 📋 **Cascade Types**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  TYPES DE CASCADE UTILISÉS                                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • PERSIST    : Propagation de la persistance                                    │
│  • REMOVE     : Suppression en cascade                                           │
│  • ORPHAN_REMOVE : Suppression des orphelins                                     │
│  • MERGE      : Fusion des entités                                               │
│  • DETACH     : Détachement des entités                                          │
│  • ALL        : Toutes les opérations                                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📊 **Détail des cascades par relation**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CASCADE PAR RELATION                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • USER → PARTICIPATION        : REMOVE (suppression en cascade)                  │
│  • EVENT → INVITATION          : PERSIST, REMOVE                                 │
│  • EVENT → PARTICIPATION       : ORPHAN_REMOVE                                   │
│  • EVENT → DOCUMENT            : Aucune cascade                                   │
│  • EVENT → REMINDER            : Aucune cascade                                   │
│  • SALLE → RESERVATION         : Aucune cascade                                   │
│  • USER → REMINDER             : Aucune cascade                                   │
│  • USER → DEPARTEMENT          : Aucune cascade                                   │
│  • EVENT → SALLE               : Aucune cascade                                   │
│  • EVENT → USER (ORGANIZER)    : Aucune cascade                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 POINTS CLÉS DES RELATIONS

### 🔑 **Clés étrangères principales**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  CLÉS ÉTRANGÈRES PRINCIPALES                                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • user.departement_id → departement.id                                          │
│  • participation.user_id → user.id                                                │
│  • participation.event_id → event.id                                              │
│  • event.organizer_id → user.id                                                   │
│  • event.salle_id → salle.id                                                     │
│  • event.departement_id → departement.id                                         │
│  • invitation.event_id → event.id                                                 │
│  • document.event_id → event.id                                                   │
│  • reminder.user_id → user.id                                                     │
│  • reminder.event_id → event.id                                                   │
│  • reservation.salle_id → salle.id                                                │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔄 **Intégrité référentielle**
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│  RÈGLES D'INTÉGRITÉ RÉFÉRENTIELLE                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  • Suppression d'un USER → Suppression des PARTICIPATION associées                │
│  • Suppression d'un EVENT → Suppression des INVITATION associées                  │
│  • Suppression d'un EVENT → Suppression des PARTICIPATION orphelines              │
│  • Modification d'un DEPARTEMENT → Mise à jour des USER associés                  │
│  • Modification d'une SALLE → Mise à jour des EVENT associés                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

*📝 Document créé pour détailler les relations d'association et cardinalités dans les diagrammes UML de l'application Symfony* 