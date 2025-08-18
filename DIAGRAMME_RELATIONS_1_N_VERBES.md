# 🎨 DIAGRAMME VISUEL DES RELATIONS (1:N) AVEC VERBES

## 🎯 DIAGRAMME PRINCIPAL

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMME DES RELATIONS (1:N) AVEC VERBES                      │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────┐    "possède"     ┌─────────────┐
│ UTILISATEUR │─────────────────►│PARTICIPATION│
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "reçoit"                       │ "concerne"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│ NOTIFICATION│                  │   ÉVÉNEMENT │
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "reçoit"                       │ "génère"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│   REMINDER  │                  │  INVITATION │
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "concerne"                      │ "est envoyée à"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│   ÉVÉNEMENT │                  │ UTILISATEUR │
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "contient"                      │ "organise"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│  DOCUMENT   │                  │   ÉVÉNEMENT │
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "appartient à"                  │ "contient"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│   ÉVÉNEMENT │                  │COLLABORATIVE│
│             │                  │    NOTE     │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "accueille"                     │ "concerne"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│    SALLE    │                  │   ÉVÉNEMENT │
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "possède"                       │ "possède"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│ RESERVATION │                  │PARTICIPATION│
│             │                  │             │
└─────────────┘                  └─────────────┘
       │                                 │
       │ "réserve"                       │ "appartient à"
       ▼                                 ▼
┌─────────────┐                  ┌─────────────┐
│    SALLE    │                  │ UTILISATEUR │
│             │                  │             │
└─────────────┘                  └─────────────┘
```

## 🔗 RELATIONS PAR ENTITÉ

### 👥 UTILISATEUR
```
┌─────────────┐
│ UTILISATEUR │
└─────────────┘
       │
       ├─ "possède" ──► PARTICIPATION
       ├─ "reçoit" ───► NOTIFICATION
       ├─ "reçoit" ───► REMINDER
       ├─ "organise" ─► ÉVÉNEMENT
       ├─ "crée" ─────► ÉVÉNEMENT
       ├─ "rédige" ───► COLLABORATIVE_NOTE
       ├─ "possède" ──► CALENDAR_EVENT
       ├─ "demande" ──► RESERVATION
       └─ "demande" ──► RESET_PASSWORD_REQUEST
```

### 🎪 ÉVÉNEMENT
```
┌─────────────┐
│  ÉVÉNEMENT  │
└─────────────┘
       │
       ├─ "génère" ────► INVITATION
       ├─ "contient" ──► DOCUMENT
       ├─ "possède" ───► PARTICIPATION
       ├─ "contient" ──► COLLABORATIVE_NOTE
       ├─ "génère" ────► REMINDER
       └─ "possède" ───► RESERVATION
```

### 🏠 SALLE
```
┌─────────────┐
│    SALLE    │
└─────────────┘
       │
       ├─ "accueille" ─► ÉVÉNEMENT
       └─ "possède" ───► RESERVATION
```

### 🏢 DÉPARTEMENT
```
┌─────────────┐
│ DÉPARTEMENT │
└─────────────┘
       │
       ├─ "emploie" ───► UTILISATEUR
       └─ "organise" ──► ÉVÉNEMENT
```

### 👑 ADMINISTRATEUR
```
┌─────────────┐
│ADMINISTRATEUR│
└─────────────┘
       │
       ├─ "gère" ─────► UTILISATEUR
       └─ "gère" ─────► ÉVÉNEMENT
```

### 🎯 ORGANISATEUR
```
┌─────────────┐
│ ORGANISATEUR│
└─────────────┘
       │
       ├─ "organise" ─► ÉVÉNEMENT
       ├─ "invite" ───► INVITATION
       └─ "rédige" ───► COLLABORATIVE_NOTE
```

## 📊 RÉSUMÉ DES VERBES

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        VERBES UTILISÉS DANS LES RELATIONS (1:N)                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 VERBES PRINCIPAUX :                                                          │
│  • "possède" : 4 fois (UTILISATEUR, ÉVÉNEMENT, SALLE, PARTICIPATION)             │
│  • "reçoit" : 2 fois (UTILISATEUR → NOTIFICATION, REMINDER)                      │
│  • "organise" : 2 fois (UTILISATEUR, ORGANISATEUR → ÉVÉNEMENT)                   │
│  • "génère" : 2 fois (ÉVÉNEMENT → INVITATION, REMINDER)                          │
│  • "contient" : 2 fois (ÉVÉNEMENT → DOCUMENT, COLLABORATIVE_NOTE)                │
│  • "gère" : 2 fois (ADMINISTRATEUR → UTILISATEUR, ÉVÉNEMENT)                     │
│  • "concerne" : 4 fois (PARTICIPATION, INVITATION, NOTIFICATION, REMINDER)       │
│  • "appartient à" : 3 fois (DOCUMENT, PARTICIPATION, CALENDAR_EVENT)             │
│  • "accueille" : 1 fois (SALLE → ÉVÉNEMENT)                                      │
│  • "emploie" : 1 fois (DÉPARTEMENT → UTILISATEUR)                                │
│  • "crée" : 1 fois (UTILISATEUR → ÉVÉNEMENT)                                     │
│  • "rédige" : 2 fois (UTILISATEUR, ORGANISATEUR → COLLABORATIVE_NOTE)            │
│  • "invite" : 1 fois (ORGANISATEUR → INVITATION)                                 │
│  • "demande" : 2 fois (UTILISATEUR → RESERVATION, RESET_PASSWORD_REQUEST)        │
│  • "est envoyée à" : 1 fois (INVITATION → UTILISATEUR)                           │
│  • "réserve" : 1 fois (RESERVATION → SALLE)                                      │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎨 DIAGRAMME SIMPLIFIÉ

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMME SIMPLIFIÉ DES RELATIONS (1:N)                        │
└─────────────────────────────────────────────────────────────────────────────────────┘

UTILISATEUR ──"possède"──► PARTICIPATION ──"concerne"──► ÉVÉNEMENT
     │                                                           │
     ├─"reçoit"──► NOTIFICATION                                 │
     ├─"reçoit"──► REMINDER                                     │
     ├─"organise"──► ÉVÉNEMENT ◄──"génère"── INVITATION        │
     ├─"crée"──► ÉVÉNEMENT ◄──"contient"── DOCUMENT            │
     ├─"rédige"──► COLLABORATIVE_NOTE ◄──"concerne"── ÉVÉNEMENT │
     ├─"possède"──► CALENDAR_EVENT                              │
     ├─"demande"──► RESERVATION ──"réserve"──► SALLE           │
     └─"demande"──► RESET_PASSWORD_REQUEST                      │
                                                               │
ADMINISTRATEUR ──"gère"──► UTILISATEUR                         │
ORGANISATEUR ──"organise"──► ÉVÉNEMENT                         │
DÉPARTEMENT ──"emploie"──► UTILISATEUR                         │
SALLE ──"accueille"──► ÉVÉNEMENT                               │
```

## 📋 LÉGENDE

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                LÉGENDE DU DIAGRAMME                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 FLÈCHES :                                                                     │
│  ──────────► : Relation 1:N (Une entité vers plusieurs autres)                   │
│                                                                                   │
│  📝 VERBES :                                                                      │
│  • "possède" : L'entité source possède plusieurs entités cibles                  │
│  • "reçoit" : L'entité source reçoit plusieurs entités cibles                    │
│  • "organise" : L'entité source organise plusieurs entités cibles                │
│  • "génère" : L'entité source génère plusieurs entités cibles                    │
│  • "contient" : L'entité source contient plusieurs entités cibles                │
│  • "gère" : L'entité source gère plusieurs entités cibles                        │
│  • "concerne" : L'entité source concerne plusieurs entités cibles                │
│  • "appartient à" : L'entité source appartient à plusieurs entités cibles        │
│  • "accueille" : L'entité source accueille plusieurs entités cibles              │
│  • "emploie" : L'entité source emploie plusieurs entités cibles                  │
│  • "crée" : L'entité source crée plusieurs entités cibles                        │
│  • "rédige" : L'entité source rédige plusieurs entités cibles                    │
│  • "invite" : L'entité source invite plusieurs entités cibles                    │
│  • "demande" : L'entité source demande plusieurs entités cibles                  │
│  • "est envoyée à" : L'entité source est envoyée à plusieurs entités cibles      │
│  • "réserve" : L'entité source réserve plusieurs entités cibles                  │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

*📝 Diagramme visuel des relations 1:N avec verbes pour une meilleure compréhension* 