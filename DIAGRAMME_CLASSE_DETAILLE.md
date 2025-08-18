# DIAGRAMME DE CLASSE DÉTAILLÉ - MAPLATEFORME

## 📊 Vue d'ensemble de l'architecture

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           MAPLATEFORME - ARCHITECTURE                            │
│                           Application Symfony - Gestion d'événements             │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🏗️ ENTITÉS PRINCIPALES

### 1. 🧑‍💼 USER (Utilisateur)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - email: string (unique)                                                        │
│  - password: string                                                              │
│  - roles: array                                                                  │
│  - googleToken: string                                                           │
│  - nom: string                                                                   │
│  - prenom: string                                                                │
│  - dateNaissance: DateTime                                                       │
│  - notifyByEmail: boolean                                                        │
│  - notifyBySms: boolean                                                          │
│  - enableSoundNotifications: boolean                                             │
│  - enableVisualNotifications: boolean                                            │
│  - reminderFrequency: int                                                        │
│  - notificationPriority: string                                                  │
│  - photo: string                                                                 │
│  - societe: string                                                               │
│  - specialite: string                                                            │
│  - telephone: string                                                             │
│  - updatedAt: DateTime                                                           │
│  - createdAt: DateTime                                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - departement: Departement (ManyToOne)                                         │
│  - participations: Collection<Participation> (OneToMany)                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getEmail(): string                                                            │
│  + setEmail(string): User                                                        │
│  + getPassword(): string                                                         │
│  + setPassword(string): User                                                     │
│  + getRoles(): array                                                             │
│  + setRoles(array): User                                                         │
│  + getFullName(): string                                                         │
│  + getUserIdentifier(): string                                                   │
│  + eraseCredentials(): void                                                      │
│  + getDepartement(): Departement                                                 │
│  + setDepartement(Departement): User                                             │
│  + getParticipations(): Collection<Participation>                               │
│  + addParticipation(Participation): User                                         │
│  + removeParticipation(Participation): User                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 2. 📅 EVENT (Événement)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                EVENT                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - title: string                                                                 │
│  - description: string                                                            │
│  - lieu: string                                                                   │
│  - dateHeure: DateTime                                                            │
│  - duree: int                                                                    │
│  - category: string                                                              │
│  - status: string                                                                │
│  - googleDriveUrl: string                                                        │
│  - googleDriveFolderId: string                                                   │
│  - etherpadUrl: string                                                           │
│  - uploadedDocuments: array                                                      │
│  - fileName: string                                                              │
│  - updatedAt: DateTimeImmutable                                                  │
│  - archive: boolean                                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - salle: Salle (ManyToOne)                                                     │
│  - organizer: User (ManyToOne)                                                   │
│  - departement: Departement (ManyToOne)                                         │
│  - createdBy: User (ManyToOne)                                                   │
│  - invitations: Collection<Invitation> (OneToMany)                              │
│  - documents: Collection<Document> (OneToMany)                                  │
│  - participations: Collection<Participation> (OneToMany)                        │
│  - files: Collection<EventFile> (OneToMany)                                     │
│  - collaborativeNotes: Collection<CollaborativeNote> (OneToMany)                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getTitle(): string                                                            │
│  + setTitle(string): Event                                                       │
│  + getDescription(): string                                                      │
│  + setDescription(string): Event                                                 │
│  + getDateHeure(): DateTime                                                      │
│  + setDateHeure(DateTime): Event                                                 │
│  + getOrganizer(): User                                                          │
│  + setOrganizer(User): Event                                                     │
│  + getSalle(): Salle                                                             │
│  + setSalle(Salle): Event                                                        │
│  + getInvitations(): Collection<Invitation>                                     │
│  + addInvitation(Invitation): Event                                              │
│  + removeInvitation(Invitation): Event                                           │
│  + getDocuments(): Collection<Document>                                          │
│  + addDocument(Document): Event                                                   │
│  + removeDocument(Document): Event                                                │
│  + getParticipations(): Collection<Participation>                               │
│  + addParticipation(Participation): Event                                        │
│  + removeParticipation(Participation): Event                                     │
│  + isArchive(): boolean                                                          │
│  + setArchive(boolean): Event                                                    │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 3. 🏢 SALLE (Salle de réunion)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                SALLE                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - nom: string                                                                   │
│  - debutReservation: DateTime                                                    │
│  - finReservation: DateTime                                                      │
│  - capacite: int                                                                 │
│  - disponible: boolean                                                           │
│  - description: string                                                           │
│  - localisation: string                                                          │
│  - equipements: array                                                            │
│  - horairesParJour: array                                                        │
│  - type: string                                                                  │
│  - superficie: decimal                                                           │
│  - photo: string                                                                 │
│  - accessibilite: boolean                                                        │
│  - tarif: decimal                                                                │
│  - priorite: int                                                                 │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getNom(): string                                                              │
│  + setNom(string): Salle                                                         │
│  + getCapacite(): int                                                            │
│  + setCapacite(int): Salle                                                       │
│  + isDisponible(): boolean                                                       │
│  + setDisponible(boolean): Salle                                                 │
│  + getEquipements(): array                                                       │
│  + setEquipements(array): Salle                                                  │
│  + addEquipement(string): Salle                                                  │
│  + removeEquipement(string): Salle                                               │
│  + hasEquipement(string): boolean                                                │
│  + getType(): string                                                             │
│  + setType(string): Salle                                                        │
│  + getTypeLabel(): string                                                        │
│  + getPrioriteLabel(): string                                                    │
│  + __toString(): string                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 4. 🏛️ DEPARTEMENT (Département)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              DEPARTEMENT                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - nom: string                                                                   │
│  - code: string                                                                  │
│  - description: string                                                           │
│  - responsable: string                                                           │
│  - emailContact: string                                                          │
│  - telephone: string                                                             │
│  - localisation: string                                                          │
│  - budgetAnnuel: int                                                             │
│  - actif: boolean                                                                │
│  - createdAt: DateTime                                                           │
│  - updatedAt: DateTime                                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - users: Collection<User> (OneToMany)                                          │
│  - events: Collection<Event> (OneToMany)                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getNom(): string                                                              │
│  + setNom(string): Departement                                                  │
│  + getCode(): string                                                             │
│  + setCode(string): Departement                                                  │
│  + getBudgetAnnuel(): int                                                        │
│  + setBudgetAnnuel(int): Departement                                             │
│  + isActif(): boolean                                                            │
│  + setActif(boolean): Departement                                                │
│  + getUsers(): Collection<User>                                                  │
│  + addUser(User): Departement                                                    │
│  + removeUser(User): Departement                                                 │
│  + getEvents(): Collection<Event>                                                │
│  + addEvent(Event): Departement                                                  │
│  + removeEvent(Event): Departement                                               │
│  + __toString(): string                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📧 SYSTÈME DE COMMUNICATION

### 5. 📧 INVITATION (Invitation)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              INVITATION                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  CONSTANTES                                                                       │
│  + STATUS_PENDING: string                                                        │
│  + STATUS_ACCEPTED: string                                                       │
│  + STATUS_DECLINED: string                                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - email: string                                                                 │
│  - name: string                                                                  │
│  - status: string                                                                │
│  - token: string                                                                 │
│  - createdAt: DateTime                                                           │
│  - updatedAt: DateTime                                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - event: Event (ManyToOne)                                                      │
│  - participant: Participant (ManyToOne)                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getEmail(): string                                                            │
│  + setEmail(string): Invitation                                                  │
│  + getName(): string                                                             │
│  + setName(string): Invitation                                                   │
│  + getStatus(): string                                                           │
│  + setStatus(string): Invitation                                                 │
│  + getToken(): string                                                            │
│  + setToken(string): Invitation                                                  │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): Invitation                                                   │
│  + isPending(): boolean                                                          │
│  + isAccepted(): boolean                                                         │
│  + isDeclined(): boolean                                                         │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 6. 👥 PARTICIPATION (Participation)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              PARTICIPATION                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - invitationStatus: string                                                      │
│  - isPresent: boolean                                                            │
│  - createdAt: DateTime                                                           │
│  - feedback: string                                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - user: User (ManyToOne)                                                        │
│  - event: Event (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getUser(): User                                                               │
│  + setUser(User): Participation                                                  │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): Participation                                                │
│  + getInvitationStatus(): string                                                 │
│  + setInvitationStatus(string): Participation                                    │
│  + isPresent(): boolean                                                          │
│  + setIsPresent(boolean): Participation                                          │
│  + getFeedback(): string                                                         │
│  + setFeedback(string): Participation                                            │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔔 SYSTÈME DE NOTIFICATIONS

### 7. 🔔 REMINDER (Rappel)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                REMINDER                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - title: string                                                                 │
│  - description: string                                                            │
│  - dueDate: DateTime                                                              │
│  - isDone: boolean                                                               │
│  - isTriggered: boolean                                                          │
│  - createdAt: DateTime                                                            │
│  - triggeredAt: DateTime                                                          │
│  - type: string                                                                  │
│  - priority: string                                                              │
│  - sendEmail: boolean                                                            │
│  - showNotification: boolean                                                      │
│  - playSound: boolean                                                            │
│  - metadata: array                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - user: User (ManyToOne)                                                        │
│  - event: Event (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES INTELLIGENTES                                                          │
│  + getId(): int                                                                  │
│  + getTitle(): string                                                            │
│  + setTitle(string): Reminder                                                    │
│  + getDueDate(): DateTime                                                        │
│  + setDueDate(DateTime): Reminder                                                │
│  + shouldTrigger(): boolean                                                      │
│  + trigger(): Reminder                                                           │
│  + markAsDone(): Reminder                                                        │
│  + getNotificationConfig(): array                                                │
│  + getFormattedMessage(): string                                                 │
│  + getTimeUntilDue(): DateInterval                                               │
│  + isOverdue(): boolean                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 8. 📢 NOTIFICATION (Notification)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              NOTIFICATION                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - title: string                                                                 │
│  - message: string                                                               │
│  - isRead: boolean                                                               │
│  - createdAt: DateTime                                                            │
│  - type: string                                                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - user: User (ManyToOne)                                                        │
│  - event: Event (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getTitle(): string                                                            │
│  + setTitle(string): Notification                                                │
│  + getMessage(): string                                                          │
│  + setMessage(string): Notification                                              │
│  + isRead(): boolean                                                             │
│  + setIsRead(boolean): Notification                                              │
│  + getType(): string                                                             │
│  + setType(string): Notification                                                 │
│  + getUser(): User                                                               │
│  + setUser(User): Notification                                                   │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): Notification                                                 │
│  + getIcon(): string                                                             │
│  + getTypeColor(): string                                                        │
│  + getTimeAgo(): string                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📋 GESTION DES RESSOURCES

### 9. 📋 RESERVATION (Réservation)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              RESERVATION                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - dateDebut: DateTime                                                            │
│  - dateFin: DateTime                                                              │
│  - reservePar: string                                                             │
│  - motif: string                                                                 │
│  - statut: string                                                                │
│  - dateCreation: DateTime                                                         │
│  - nombreParticipants: int                                                        │
│  - notes: string                                                                 │
│  - contactTelephone: string                                                       │
│  - contactEmail: string                                                           │
│  - recurrente: boolean                                                           │
│  - typeRecurrence: string                                                        │
│  - finRecurrence: DateTime                                                        │
│  - dateModification: DateTime                                                     │
│  - modifiePar: string                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - salle: Salle (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getSalle(): Salle                                                             │
│  + setSalle(Salle): Reservation                                                  │
│  + getDateDebut(): DateTime                                                      │
│  + setDateDebut(DateTime): Reservation                                           │
│  + getDateFin(): DateTime                                                        │
│  + setDateFin(DateTime): Reservation                                             │
│  + chevaucheAvec(DateTime, DateTime): boolean                                    │
│  + estActive(): boolean                                                          │
│  + getStatutLabel(): string                                                      │
│  + getDuree(): DateInterval                                                      │
│  + getDureeEnHeures(): float                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 10. 📄 DOCUMENT (Document)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              DOCUMENT                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - file: File                                                                     │
│  - fileName: string                                                              │
│  - createdAt: DateTimeImmutable                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - event: Event (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + setFile(File): void                                                           │
│  + getFile(): File                                                               │
│  + setFileName(string): void                                                     │
│  + getFileName(): string                                                         │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): Document                                                     │
│  + getCreatedAt(): DateTimeImmutable                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 11. 📝 COLLABORATIVENOTE (Note collaborative)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           COLLABORATIVENOTE                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - title: string                                                                 │
│  - content: string                                                               │
│  - createdAt: DateTimeImmutable                                                   │
│  - updatedAt: DateTimeImmutable                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - event: Event (ManyToOne)                                                      │
│  - createdBy: User (ManyToOne)                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getTitle(): string                                                            │
│  + setTitle(string): CollaborativeNote                                           │
│  + getContent(): string                                                          │
│  + setContent(string): CollaborativeNote                                         │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): CollaborativeNote                                            │
│  + getCreatedBy(): User                                                          │
│  + setCreatedBy(User): CollaborativeNote                                         │
│  + getCreatedAt(): DateTimeImmutable                                             │
│  + getUpdatedAt(): DateTimeImmutable                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 👤 ENTITÉS SUPPORT

### 12. 👤 PARTICIPANT (Participant)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              PARTICIPANT                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - email: string                                                                 │
│  - nom: string                                                                   │
│  - prenom: string                                                                │
│  - telephone: string                                                             │
│  - createdAt: DateTime                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - invitations: Collection<Invitation> (OneToMany)                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getEmail(): string                                                            │
│  + setEmail(string): Participant                                                 │
│  + getNom(): string                                                              │
│  + setNom(string): Participant                                                   │
│  + getPrenom(): string                                                           │
│  + setPrenom(string): Participant                                                │
│  + getTelephone(): string                                                        │
│  + setTelephone(string): Participant                                             │
│  + getInvitations(): Collection<Invitation>                                      │
│  + addInvitation(Invitation): Participant                                        │
│  + removeInvitation(Invitation): Participant                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 13. 🔐 RESETPASSWORDREQUEST (Demande de réinitialisation)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        RESETPASSWORDREQUEST                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - token: string                                                                 │
│  - expiresAt: DateTime                                                            │
│  - createdAt: DateTime                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - user: User (ManyToOne)                                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getToken(): string                                                            │
│  + setToken(string): ResetPasswordRequest                                        │
│  + getExpiresAt(): DateTime                                                      │
│  + setExpiresAt(DateTime): ResetPasswordRequest                                  │
│  + getCreatedAt(): DateTime                                                      │
│  + setCreatedAt(DateTime): ResetPasswordRequest                                  │
│  + getUser(): User                                                               │
│  + setUser(User): ResetPasswordRequest                                           │
│  + isExpired(): boolean                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 14. 📅 CALENDAREVENT (Événement calendrier)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            CALENDAREVENT                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - title: string                                                                 │
│  - start: DateTime                                                                │
│  - end: DateTime                                                                  │
│  - allDay: boolean                                                               │
│  - backgroundColor: string                                                        │
│  - borderColor: string                                                            │
│  - textColor: string                                                              │
│  - createdAt: DateTime                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - user: User (ManyToOne)                                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getTitle(): string                                                            │
│  + setTitle(string): CalendarEvent                                               │
│  + getStart(): DateTime                                                          │
│  + setStart(DateTime): CalendarEvent                                             │
│  + getEnd(): DateTime                                                            │
│  + setEnd(DateTime): CalendarEvent                                               │
│  + isAllDay(): boolean                                                           │
│  + setAllDay(boolean): CalendarEvent                                             │
│  + getBackgroundColor(): string                                                  │
│  + setBackgroundColor(string): CalendarEvent                                     │
│  + getBorderColor(): string                                                      │
│  + setBorderColor(string): CalendarEvent                                         │
│  + getTextColor(): string                                                        │
│  + setTextColor(string): CalendarEvent                                           │
│  + getUser(): User                                                               │
│  + setUser(User): CalendarEvent                                                  │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 15. 🗂️ EVENTFILE (Fichier d'événement)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              EVENTFILE                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - fileName: string                                                              │
│  - filePath: string                                                              │
│  - fileSize: int                                                                 │
│  - mimeType: string                                                              │
│  - uploadedAt: DateTime                                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - event: Event (ManyToOne)                                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getFileName(): string                                                         │
│  + setFileName(string): EventFile                                                │
│  + getFilePath(): string                                                         │
│  + setFilePath(string): EventFile                                                │
│  + getFileSize(): int                                                            │
│  + setFileSize(int): EventFile                                                   │
│  + getMimeType(): string                                                         │
│  + setMimeType(string): EventFile                                                │
│  + getUploadedAt(): DateTime                                                     │
│  + setUploadedAt(DateTime): EventFile                                            │
│  + getEvent(): Event                                                             │
│  + setEvent(Event): EventFile                                                    │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 16. 🏢 GESTIONSALLE (Gestion de salle)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            GESTIONSALLE                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS                                                                       │
│  - id: int                                                                        │
│  - nom: string                                                                   │
│  - description: string                                                            │
│  - capacite: int                                                                 │
│  - disponible: boolean                                                            │
│  - createdAt: DateTime                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES PRINCIPALES                                                            │
│  + getId(): int                                                                  │
│  + getNom(): string                                                              │
│  + setNom(string): GestionSalle                                                  │
│  + getDescription(): string                                                       │
│  + setDescription(string): GestionSalle                                          │
│  + getCapacite(): int                                                            │
│  + setCapacite(int): GestionSalle                                                │
│  + isDisponible(): boolean                                                       │
│  + setDisponible(boolean): GestionSalle                                          │
│  + getCreatedAt(): DateTime                                                      │
│  + setCreatedAt(DateTime): GestionSalle                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔗 RELATIONS ENTRE ENTITÉS

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS PRINCIPALES                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  USER (1) ──────────────────────────────────────────────────────────────────────┐ │
│  │                                                                              │ │
│  ├── departement: DEPARTEMENT (N:1)                                            │ │
│  ├── participations: PARTICIPATION (1:N)                                       │ │
│  ├── reminders: REMINDER (1:N)                                                  │ │
│  ├── notifications: NOTIFICATION (1:N)                                          │ │
│  ├── calendarEvents: CALENDAREVENT (1:N)                                        │ │
│  ├── collaborativeNotes: COLLABORATIVENOTE (1:N)                               │ │
│  └── resetPasswordRequests: RESETPASSWORDREQUEST (1:N)                          │ │
│                                                                                   │ │
│  EVENT (1) ─────────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── salle: SALLE (N:1)                                                        │ │
│  ├── organizer: USER (N:1)                                                      │ │
│  ├── departement: DEPARTEMENT (N:1)                                            │ │
│  ├── createdBy: USER (N:1)                                                      │ │
│  ├── invitations: INVITATION (1:N)                                              │ │
│  ├── documents: DOCUMENT (1:N)                                                  │ │
│  ├── participations: PARTICIPATION (1:N)                                        │ │
│  ├── files: EVENTFILE (1:N)                                                     │ │
│  └── collaborativeNotes: COLLABORATIVENOTE (1:N)                               │ │
│                                                                                   │ │
│  SALLE (1) ─────────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── reservations: RESERVATION (1:N)                                            │ │
│                                                                                   │ │
│  DEPARTEMENT (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── users: USER (1:N)                                                          │ │
│  └── events: EVENT (1:N)                                                        │ │
│                                                                                   │ │
│  PARTICIPANT (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── invitations: INVITATION (1:N)                                              │ │
│                                                                                   │ │
│  INVITATION (1) ────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── event: EVENT (N:1)                                                         │ │
│  └── participant: PARTICIPANT (N:1)                                             │ │
│                                                                                   │ │
│  PARTICIPATION (1) ─────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: USER (N:1)                                                           │ │
│  └── event: EVENT (N:1)                                                         │ │
│                                                                                   │ │
│  REMINDER (1) ──────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: USER (N:1)                                                           │ │
│  └── event: EVENT (N:1)                                                         │ │
│                                                                                   │ │
│  NOTIFICATION (1) ──────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: USER (N:1)                                                           │ │
│  └── event: EVENT (N:1)                                                         │ │
│                                                                                   │ │
│  RESERVATION (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── salle: SALLE (N:1)                                                         │ │
│                                                                                   │ │
│  DOCUMENT (1) ──────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── event: EVENT (N:1)                                                         │ │
│                                                                                   │ │
│  COLLABORATIVENOTE (1) ─────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── event: EVENT (N:1)                                                         │ │
│  └── createdBy: USER (N:1)                                                      │ │
│                                                                                   │ │
│  EVENTFILE (1) ─────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── event: EVENT (N:1)                                                         │ │
│                                                                                   │ │
│  CALENDAREVENT (1) ─────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── user: USER (N:1)                                                           │ │
│                                                                                   │ │
│  RESETPASSWORDREQUEST (1) ──────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── user: USER (N:1)                                                           │ │
│                                                                                   │ │
│  GESTIONSALLE (1) ──────────────────────────────────────────────────────────────┘ │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🌟 FONCTIONNALITÉS AVANCÉES

### 🔄 Système de Rappels Automatiques
- **Déclenchement intelligent** : Vérification automatique des dates
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

## 🏗️ ARCHITECTURE TECHNIQUE

### Pattern MVC
- **Modèle** : Entités Doctrine
- **Vue** : Templates Twig
- **Contrôleur** : Contrôleurs Symfony

### ORM Doctrine
- **Mapping** : Annotations/Attributs
- **Relations** : One-to-Many, Many-to-One, Many-to-Many
- **Migrations** : Gestion des versions de base de données

### Services
- **EmailService** : Envoi d'emails
- **NotificationService** : Gestion des notifications
- **ReminderService** : Système de rappels
- **AutoArchiveService** : Archivage automatique

### Bundles Utilisés
- **VichUploader** : Upload de fichiers
- **Security** : Authentification et autorisation
- **Mailer** : Envoi d'emails
- **Messenger** : Traitement asynchrone

## 📈 POINTS FORTS DE L'ARCHITECTURE

1. **Modularité** : Entités bien séparées et spécialisées
2. **Extensibilité** : Facile d'ajouter de nouvelles fonctionnalités
3. **Sécurité** : Système d'authentification robuste
4. **Performance** : Relations optimisées
5. **Maintenabilité** : Code bien structuré et documenté
6. **Scalabilité** : Architecture adaptée à la croissance

## 🎯 RÉSUMÉ DES ENTITÉS

| Entité | Rôle | Relations principales |
|--------|------|----------------------|
| **User** | Utilisateur central | Departement, Participation, Reminder, Notification |
| **Event** | Événement principal | User, Salle, Departement, Invitation, Document |
| **Salle** | Salle de réunion | Event, Reservation |
| **Departement** | Organisation | User, Event |
| **Invitation** | Invitation | Event, Participant |
| **Participation** | Participation | User, Event |
| **Reminder** | Rappel | User, Event |
| **Notification** | Notification | User, Event |
| **Reservation** | Réservation | Salle |
| **Document** | Document | Event |
| **CollaborativeNote** | Note collaborative | Event, User |
| **Participant** | Participant externe | Invitation |
| **ResetPasswordRequest** | Sécurité | User |
| **CalendarEvent** | Calendrier | User |
| **EventFile** | Fichier | Event |
| **GestionSalle** | Gestion salle | - |

Cette architecture représente une application Symfony complète et bien structurée pour la gestion d'événements et de salles avec un système de notifications avancé. 