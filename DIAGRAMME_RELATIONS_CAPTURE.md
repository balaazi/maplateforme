# DIAGRAMME DE RELATIONS - MAPLATEFORME

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    DIAGRAMME DE RELATIONS UML                                    │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📊 DIAGRAMME DES RELATIONS

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - email: string                                                                  │
│  - nom: string                                                                    │
│  - prenom: string                                                                 │
│  - password: string                                                               │
│  - roles: array                                                                   │
│  - telephone: string                                                              │
│  - statutCompte: string                                                          │
│  - departement: Departement                                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + getUserIdentifier()                                                            │
│  + eraseCredentials()                                                             │
│  + getFullName()                                                                 │
│  + isActive()                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                ▲
                                │ hérite
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────┐    ┌───────────────┐    ┌───────────────┐
│ ADMINISTRATEUR│    │ ORGANISATEUR  │    │  PARTICIPANT  │
├───────────────┤    ├───────────────┤    ├───────────────┤
│ + inviterUser()│    │ + creerEvent()│    │ + rejoindreEvent()│
│ + suspendUser()│    │ + modifierEvent()│  │ + quitterEvent() │
│ + supprimerUser()│  │ + gererParticipants()│ │ + consulterDocuments()│
│ + gererPermissions()││ + cloturerEvent()│   │ + repondreInvitation()│
└───────────────┘    └───────────────┘    └───────────────┘
        │                       │                       │
        │                       │                       │
        └───────────────────────┼───────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               EVENT                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - titre: string                                                                  │
│  - description: string                                                            │
│  - lieu: string                                                                   │
│  - dateHeure: datetime                                                            │
│  - duree: int                                                                     │
│  - categorie: string                                                              │
│  - statut: string                                                                 │
│  - salle: Salle                                                                   │
│  - organizer: User                                                                │
│  - departement: Departement                                                       │
│  - createdBy: User                                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + creerEvent()                                                                   │
│  + modifierEvent()                                                                │
│  + supprimerEvent()                                                               │
│  + annulerEvent()                                                                 │
│  + genererRapport()                                                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ contient
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              DOCUMENT                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - nom: string                                                                    │
│  - type: string                                                                   │
│  - taille: float                                                                  │
│  - dateUpload: datetime                                                           │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + televerserDocument()                                                           │
│  + partagerDocument()                                                             │
│  + supprimerDocument()                                                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ génère
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             INVITATION                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - email: string                                                                  │
│  - nom: string                                                                    │
│  - statut: string                                                                 │
│  - token: string                                                                  │
│  - dateEnvoi: datetime                                                            │
│  - dateReponse: datetime                                                          │
│  - event: Event                                                                   │
│  - participant: Participant                                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + envoyerInvitation()                                                            │
│  + accepterInvitation()                                                           │
│  + refuserInvitation()                                                            │
│  + genererToken()                                                                 │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ génère
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           NOTIFICATION                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - titre: string                                                                  │
│  - message: string                                                                │
│  - type: string                                                                   │
│  - lu: boolean                                                                    │
│  - dateEnvoi: datetime                                                            │
│  - user: User                                                                     │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + envoyerNotification()                                                           │
│  + marquerCommeLu()                                                               │
│  + supprimerNotification()                                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ reçoit
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               USER                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ participe à
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           PARTICIPATION                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - statut: string                                                                 │
│  - dateParticipation: datetime                                                    │
│  - user: User                                                                     │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + rejoindreEvent()                                                               │
│  + quitterEvent()                                                                 │
│  + confirmerPresence()                                                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ suit
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               EVENT                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ utilise
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               SALLE                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - nom: string                                                                    │
│  - capacite: int                                                                  │
│  - equipements: array                                                             │
│  - disponibilite: boolean                                                         │
│  - horaires: array                                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + reserverSalle()                                                                │
│  + libererSalle()                                                                 │
│  + verifierDisponibilite()                                                        │
│  + ajouterEquipement()                                                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ contient
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RESERVATION                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - dateDebut: datetime                                                            │
│  - dateFin: datetime                                                              │
│  - reservePar: string                                                             │
│  - statut: string                                                                 │
│  - recurrence: string                                                             │
│  - salle: Salle                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + creerReservation()                                                             │
│  + annulerReservation()                                                           │
│  + modifierReservation()                                                          │
│  + verifierConflit()                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ appartient à
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          DEPARTEMENT                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - nom: string                                                                    │
│  - code: string                                                                   │
│  - contact: string                                                                │
│  - budget: float                                                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + gererBudget()                                                                  │
│  + ajouterMembre()                                                                │
│  + supprimerMembre()                                                              │
│  + genererRapport()                                                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ gère
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               USER                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ crée
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        COLLABORATIVENOTE                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - titre: string                                                                  │
│  - contenu: text                                                                  │
│  - dateCreation: datetime                                                         │
│  - event: Event                                                                   │
│  - createdBy: User                                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + creerNote()                                                                    │
│  + modifierNote()                                                                 │
│  + partagerNote()                                                                 │
│  + supprimerNote()                                                                │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ contient
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               EVENT                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ génère
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              RAPPORT                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - titre: string                                                                  │
│  - contenu: text                                                                  │
│  - tauxParticipation: float                                                       │
│  - dateGeneration: datetime                                                       │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + genererRapport()                                                               │
│  + exporterPDF()                                                                  │
│  + envoyerRapport()                                                               │
│  + calculerStatistiques()                                                         │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ synchronise avec
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                          GOOGLECALENDAR                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - googleEventId: string                                                          │
│  - dateSynchronisation: datetime                                                 │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + synchroniserAvecGoogle()                                                       │
│  + demanderPermissions()                                                          │
│  + supprimerSynchronisation()                                                     │
│  + mettreAJourEvent()                                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ gère
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               EVENT                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ reçoit
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              REMINDER                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  - id: int                                                                        │
│  - titre: string                                                                  │
│  - message: string                                                                │
│  - dateEcheance: datetime                                                         │
│  - statut: string                                                                 │
│  - type: string                                                                   │
│  - priorite: string                                                               │
│  - user: User                                                                     │
│  - event: Event                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  + creerRappel()                                                                  │
│  + envoyerRappel()                                                                │
│  + marquerCommeLu()                                                               │
│  + supprimerRappel()                                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                │
                                │ reçoit
                                ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                               USER                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  (voir définition ci-dessus)                                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 RÉSUMÉ DES RELATIONS PRINCIPALES

### Relations d'héritage
- **Administrateur** hérite de **User**
- **Organisateur** hérite de **User**  
- **Participant** hérite de **User**

### Relations d'association
- **User** → **Participation** (1:N) : Un utilisateur peut avoir plusieurs participations
- **Participation** → **Event** (N:1) : Une participation suit un événement
- **Organisateur** → **Event** (1:N) : Un organisateur organise plusieurs événements
- **Event** → **Document** (1:N) : Un événement contient plusieurs documents
- **Event** → **Invitation** (1:N) : Un événement génère plusieurs invitations
- **Invitation** → **Notification** (1:N) : Une invitation génère plusieurs notifications
- **Event** → **Rapport** (1:N) : Un événement génère plusieurs rapports
- **Event** → **GoogleCalendar** (1:1) : Un événement synchronise avec un calendrier Google
- **Event** → **CollaborativeNote** (1:N) : Un événement contient plusieurs notes collaboratives
- **Event** → **Reminder** (1:N) : Un événement génère plusieurs rappels
- **User** → **Notification** (1:N) : Un utilisateur reçoit plusieurs notifications
- **User** → **Reminder** (1:N) : Un utilisateur reçoit plusieurs rappels
- **Salle** → **Reservation** (1:N) : Une salle contient plusieurs réservations
- **Departement** → **User** (1:N) : Un département gère plusieurs utilisateurs
- **Event** → **Salle** (N:1) : Un événement utilise une salle
- **Event** → **Departement** (N:1) : Un événement appartient à un département

### Cardinalités
- **1:1** : Relation un-à-un (ex: Event ↔ GoogleCalendar)
- **1:N** : Relation un-à-plusieurs (ex: User → Participation)
- **N:1** : Relation plusieurs-à-un (ex: Participation → Event)
- **N:N** : Relation plusieurs-à-plusieurs (via Participation)

Ce diagramme montre toutes les relations entre les classes de votre application MaPlateforme, avec les cardinalités et les types de relations appropriés ! 