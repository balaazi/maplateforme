# 🔗 RELATIONS AVEC VERBES - DIAGRAMMES UML

## 🎯 RELATIONS PRINCIPALES AVEC VERBES

### 👥 **UTILISATEUR (USER)**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • USER "appartient à" DEPARTEMENT (1 seul)                                      │
│  • USER "participe à" PARTICIPATION (plusieurs)                                  │
│  • USER "reçoit" REMINDER (plusieurs)                                            │
│  • USER "reçoit" NOTIFICATION (plusieurs)                                        │
│  • USER "organise" EVENT (plusieurs)                                             │
│  • USER "crée" EVENT (plusieurs)                                                 │
│  • USER "demande" RESET_PASSWORD_REQUEST (plusieurs)                             │
│  • USER "a" CALENDAR_EVENT (plusieurs)                                           │
│  • USER "écrit" COLLABORATIVE_NOTE (plusieurs)                                   │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 USER → 1 DEPARTEMENT                                                        │
│  • 1 USER → N PARTICIPATIONS                                                     │
│  • 1 USER → N REMINDERS                                                          │
│  • 1 USER → N NOTIFICATIONS                                                      │
│  • 1 USER → N EVENTS (organisés)                                                 │
│  • 1 USER → N EVENTS (créés)                                                     │
│  • 1 USER → N RESET_PASSWORD_REQUESTS                                            │
│  • 1 USER → N CALENDAR_EVENTS                                                    │
│  • 1 USER → N COLLABORATIVE_NOTES                                                │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🎪 **ÉVÉNEMENT (EVENT)**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • EVENT "est organisé par" USER (1 seul organisateur)                           │
│  • EVENT "est créé par" USER (1 seul créateur)                                   │
│  • EVENT "se déroule dans" SALLE (1 seule salle)                                 │
│  • EVENT "appartient à" DEPARTEMENT (1 seul département)                         │
│  • EVENT "génère" INVITATION (plusieurs)                                         │
│  • EVENT "contient" DOCUMENT (plusieurs)                                         │
│  • EVENT "a" PARTICIPATION (plusieurs)                                           │
│  • EVENT "contient" EVENT_FILE (plusieurs)                                       │
│  • EVENT "a" COLLABORATIVE_NOTE (plusieurs)                                      │
│  • EVENT "génère" REMINDER (plusieurs)                                           │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 EVENT → 1 USER (organisateur)                                               │
│  • 1 EVENT → 1 USER (créateur)                                                   │
│  • 1 EVENT → 1 SALLE                                                             │
│  • 1 EVENT → 1 DEPARTEMENT                                                       │
│  • 1 EVENT → N INVITATIONS                                                       │
│  • 1 EVENT → N DOCUMENTS                                                         │
│  • 1 EVENT → N PARTICIPATIONS                                                    │
│  • 1 EVENT → N EVENT_FILES                                                       │
│  • 1 EVENT → N COLLABORATIVE_NOTES                                               │
│  • 1 EVENT → N REMINDERS                                                         │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏢 **DÉPARTEMENT (DEPARTEMENT)**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DEPARTEMENT                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • DEPARTEMENT "emploie" USER (plusieurs)                                        │
│  • DEPARTEMENT "organise" EVENT (plusieurs)                                      │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 DEPARTEMENT → N USERS                                                       │
│  • 1 DEPARTEMENT → N EVENTS                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🏠 **SALLE**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                SALLE                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • SALLE "accueille" EVENT (plusieurs)                                           │
│  • SALLE "a" RESERVATION (plusieurs)                                             │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 SALLE → N EVENTS                                                            │
│  • 1 SALLE → N RESERVATIONS                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📋 **PARTICIPATION**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPATION                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • PARTICIPATION "lie" USER (1 seul)                                             │
│  • PARTICIPATION "lie" EVENT (1 seul)                                            │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 PARTICIPATION → 1 USER                                                      │
│  • 1 PARTICIPATION → 1 EVENT                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📨 **INVITATION**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             INVITATION                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • INVITATION "invite à" EVENT (1 seul)                                          │
│  • INVITATION "est envoyée à" PARTICIPANT (1 seul)                               │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 INVITATION → 1 EVENT                                                        │
│  • 1 INVITATION → 1 PARTICIPANT                                                  │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📄 **DOCUMENT**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             DOCUMENT                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • DOCUMENT "appartient à" EVENT (1 seul)                                        │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 DOCUMENT → 1 EVENT                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 🔔 **RAPPEL (REMINDER)**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             REMINDER                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • REMINDER "est envoyé à" USER (1 seul)                                         │
│  • REMINDER "concerne" EVENT (1 seul)                                            │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 REMINDER → 1 USER                                                           │
│  • 1 REMINDER → 1 EVENT                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 📅 **RÉSERVATION (RESERVATION)**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            RESERVATION                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔗 RELATIONS AVEC VERBES :                                                      │
│                                                                                   │
│  • RESERVATION "réserve" SALLE (1 seule)                                         │
│                                                                                   │
│  📊 CARDINALITÉS :                                                               │
│  • 1 RESERVATION → 1 SALLE                                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎨 DIAGRAMME SIMPLIFIÉ AVEC VERBES

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMME DES RELATIONS AVEC VERBES                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ┌─────────────┐    "appartient à"    ┌─────────────┐                            │
│  │    USER     │◄─────────────────────│ DEPARTEMENT │                            │
│  │             │                      │             │                            │
│  └─────────────┘                      └─────────────┘                            │
│         │                                      │                                  │
│         │ "participe à"                        │ "organise"                       │
│         ▼                                      ▼                                  │
│  ┌─────────────┐                      ┌─────────────┐                            │
│  │PARTICIPATION│                      │    EVENT     │                            │
│  │             │                      │             │                            │
│  └─────────────┘                      └─────────────┘                            │
│         │                                      │                                  │
│         │ "lie"                                │ "se déroule dans"                │
│         ▼                                      ▼                                  │
│  ┌─────────────┐                      ┌─────────────┐                            │
│  │    EVENT    │                      │    SALLE     │                            │
│  │             │                      │             │                            │
│  └─────────────┘                      └─────────────┘                            │
│         │                                      │                                  │
│         │ "génère"                             │ "a"                              │
│         ▼                                      ▼                                  │
│  ┌─────────────┐                      ┌─────────────┐                            │
│  │  INVITATION │                      │ RESERVATION │                            │
│  │             │                      │             │                            │
│  └─────────────┘                      └─────────────┘                            │
│         │                                      │                                  │
│         │ "invite à"                           │ "réserve"                        │
│         ▼                                      ▼                                  │
│  ┌─────────────┐                      ┌─────────────┐                            │
│  │ PARTICIPANT │                      │    SALLE     │                            │
│  │             │                      │             │                            │
│  └─────────────┘                      └─────────────┘                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📊 RÉSUMÉ DES VERBES PAR RELATION

### 🔗 **Verbes principaux utilisés :**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        VERBES DES RELATIONS                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  👥 UTILISATEUR :                                                                │
│  • "appartient à" → DEPARTEMENT                                                  │
│  • "participe à" → PARTICIPATION                                                 │
│  • "reçoit" → REMINDER, NOTIFICATION                                             │
│  • "organise" → EVENT                                                            │
│  • "crée" → EVENT                                                                │
│  • "a" → CALENDAR_EVENT, COLLABORATIVE_NOTE                                      │
│                                                                                   │
│  🎪 ÉVÉNEMENT :                                                                  │
│  • "est organisé par" → USER                                                     │
│  • "est créé par" → USER                                                         │
│  • "se déroule dans" → SALLE                                                     │
│  • "appartient à" → DEPARTEMENT                                                  │
│  • "génère" → INVITATION, REMINDER                                               │
│  • "contient" → DOCUMENT, EVENT_FILE, COLLABORATIVE_NOTE                         │
│  • "a" → PARTICIPATION                                                           │
│                                                                                   │
│  🏢 DÉPARTEMENT :                                                                │
│  • "emploie" → USER                                                              │
│  • "organise" → EVENT                                                            │
│                                                                                   │
│  🏠 SALLE :                                                                      │
│  • "accueille" → EVENT                                                           │
│  • "a" → RESERVATION                                                             │
│                                                                                   │
│  📋 PARTICIPATION :                                                              │
│  • "lie" → USER, EVENT                                                           │
│                                                                                   │
│  📨 INVITATION :                                                                 │
│  • "invite à" → EVENT                                                            │
│  • "est envoyée à" → PARTICIPANT                                                 │
│                                                                                   │
│  📄 DOCUMENT :                                                                   │
│  • "appartient à" → EVENT                                                        │
│                                                                                   │
│  🔔 RAPPEL :                                                                     │
│  • "est envoyé à" → USER                                                         │
│  • "concerne" → EVENT                                                            │
│                                                                                   │
│  📅 RÉSERVATION :                                                                │
│  • "réserve" → SALLE                                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 EXEMPLES CONCRETS

### 📝 **Exemples de phrases avec verbes :**

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        EXEMPLES CONCRETS                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  👤 "Jean Dupont appartient au département Informatique"                         │
│  🎪 "La conférence Symfony est organisée par Marie Martin"                       │
│  🏠 "La salle A101 accueille la réunion hebdomadaire"                            │
│  📋 "Jean Dupont participe à la conférence Symfony"                              │
│  📨 "Une invitation est envoyée à Jean Dupont pour la conférence"                │
│  📄 "Le document 'Guide Symfony' appartient à la conférence"                     │
│  🔔 "Un rappel est envoyé à Jean Dupont pour la conférence"                      │
│  📅 "Une réservation réserve la salle A101 pour la conférence"                   │
│  🏢 "Le département Informatique emploie Jean Dupont"                            │
│  🎪 "La conférence se déroule dans la salle A101"                                │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

---

*📝 Document créé pour clarifier les relations avec les verbes qui les décrivent* 