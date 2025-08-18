# DIAGRAMME DE CLASSE VISUEL - MAPLATEFORME

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           DIAGRAMME DE CLASSE UML                                │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                User                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - email: string                                                                   │
│ - password: string                                                                │
│ - roles: array                                                                    │
│ - googleToken: string                                                             │
│ - nom: string                                                                     │
│ - prenom: string                                                                  │
│ - dateNaissance: DateTime                                                         │
│ - notifyByEmail: boolean                                                          │
│ - notifyBySms: boolean                                                            │
│ - enableSoundNotifications: boolean                                               │
│ - enableVisualNotifications: boolean                                              │
│ - reminderFrequency: int                                                          │
│ - notificationPriority: string                                                    │
│ - photo: string                                                                   │
│ - societe: string                                                                 │
│ - specialite: string                                                              │
│ - telephone: string                                                               │
│ - updatedAt: DateTime                                                             │
│ - createdAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getEmail(): string                                                             │
│ + setEmail(string): User                                                         │
│ + getPassword(): string                                                          │
│ + setPassword(string): User                                                      │
│ + getRoles(): array                                                              │
│ + setRoles(array): User                                                          │
│ + getFullName(): string                                                          │
│ + getUserIdentifier(): string                                                     │
│ + eraseCredentials(): void                                                        │
│ + getDepartement(): Departement                                                  │
│ + setDepartement(Departement): User                                              │
│ + getParticipations(): Collection<Participation>                                 │
│ + addParticipation(Participation): User                                          │
│ + removeParticipation(Participation): User                                       │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Departement                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - nom: string                                                                     │
│ - code: string                                                                    │
│ - description: string                                                             │
│ - responsable: string                                                             │
│ - emailContact: string                                                            │
│ - telephone: string                                                               │
│ - localisation: string                                                            │
│ - budgetAnnuel: int                                                               │
│ - actif: boolean                                                                  │
│ - createdAt: DateTime                                                             │
│ - updatedAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getNom(): string                                                               │
│ + setNom(string): Departement                                                    │
│ + getCode(): string                                                              │
│ + setCode(string): Departement                                                   │
│ + getBudgetAnnuel(): int                                                         │
│ + setBudgetAnnuel(int): Departement                                              │
│ + isActif(): boolean                                                             │
│ + setActif(boolean): Departement                                                 │
│ + getUsers(): Collection<User>                                                   │
│ + addUser(User): Departement                                                     │
│ + removeUser(User): Departement                                                  │
│ + getEvents(): Collection<Event>                                                 │
│ + addEvent(Event): Departement                                                   │
│ + removeEvent(Event): Departement                                                │
│ + __toString(): string                                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                Event                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - title: string                                                                   │
│ - description: string                                                             │
│ - lieu: string                                                                   │
│ - dateHeure: DateTime                                                             │
│ - duree: int                                                                     │
│ - category: string                                                               │
│ - status: string                                                                 │
│ - googleDriveUrl: string                                                         │
│ - googleDriveFolderId: string                                                    │
│ - etherpadUrl: string                                                            │
│ - uploadedDocuments: array                                                       │
│ - fileName: string                                                               │
│ - updatedAt: DateTimeImmutable                                                   │
│ - archive: boolean                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getTitle(): string                                                             │
│ + setTitle(string): Event                                                        │
│ + getDescription(): string                                                       │
│ + setDescription(string): Event                                                  │
│ + getDateHeure(): DateTime                                                       │
│ + setDateHeure(DateTime): Event                                                  │
│ + getOrganizer(): User                                                           │
│ + setOrganizer(User): Event                                                      │
│ + getSalle(): Salle                                                              │
│ + setSalle(Salle): Event                                                         │
│ + getInvitations(): Collection<Invitation>                                       │
│ + addInvitation(Invitation): Event                                               │
│ + removeInvitation(Invitation): Event                                            │
│ + getDocuments(): Collection<Document>                                           │
│ + addDocument(Document): Event                                                    │
│ + removeDocument(Document): Event                                                 │
│ + getParticipations(): Collection<Participation>                                 │
│ + addParticipation(Participation): Event                                         │
│ + removeParticipation(Participation): Event                                      │
│ + isArchive(): boolean                                                           │
│ + setArchive(boolean): Event                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                Salle                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - nom: string                                                                     │
│ - debutReservation: DateTime                                                     │
│ - finReservation: DateTime                                                       │
│ - capacite: int                                                                  │
│ - disponible: boolean                                                            │
│ - description: string                                                            │
│ - localisation: string                                                           │
│ - equipements: array                                                             │
│ - horairesParJour: array                                                         │
│ - type: string                                                                   │
│ - superficie: decimal                                                            │
│ - photo: string                                                                  │
│ - accessibilite: boolean                                                         │
│ - tarif: decimal                                                                │
│ - priorite: int                                                                 │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getNom(): string                                                               │
│ + setNom(string): Salle                                                          │
│ + getCapacite(): int                                                             │
│ + setCapacite(int): Salle                                                        │
│ + isDisponible(): boolean                                                        │
│ + setDisponible(boolean): Salle                                                  │
│ + getEquipements(): array                                                        │
│ + setEquipements(array): Salle                                                   │
│ + addEquipement(string): Salle                                                   │
│ + removeEquipement(string): Salle                                                │
│ + hasEquipement(string): boolean                                                 │
│ + getType(): string                                                              │
│ + setType(string): Salle                                                         │
│ + getTypeLabel(): string                                                         │
│ + getPrioriteLabel(): string                                                     │
│ + __toString(): string                                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Invitation                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - email: string                                                                   │
│ - name: string                                                                    │
│ - status: string                                                                 │
│ - token: string                                                                  │
│ - createdAt: DateTime                                                             │
│ - updatedAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getEmail(): string                                                             │
│ + setEmail(string): Invitation                                                   │
│ + getName(): string                                                              │
│ + setName(string): Invitation                                                    │
│ + getStatus(): string                                                            │
│ + setStatus(string): Invitation                                                  │
│ + getToken(): string                                                             │
│ + setToken(string): Invitation                                                   │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): Invitation                                                    │
│ + getParticipant(): Participant                                                  │
│ + setParticipant(Participant): Invitation                                        │
│ + isPending(): boolean                                                           │
│ + isAccepted(): boolean                                                          │
│ + isDeclined(): boolean                                                          │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Participation                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - invitationStatus: string                                                       │
│ - isPresent: boolean                                                             │
│ - createdAt: DateTime                                                             │
│ - feedback: string                                                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getUser(): User                                                                │
│ + setUser(User): Participation                                                   │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): Participation                                                 │
│ + getInvitationStatus(): string                                                  │
│ + setInvitationStatus(string): Participation                                     │
│ + isPresent(): boolean                                                           │
│ + setIsPresent(boolean): Participation                                           │
│ + getFeedback(): string                                                          │
│ + setFeedback(string): Participation                                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                Reminder                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - title: string                                                                   │
│ - description: string                                                             │
│ - dueDate: DateTime                                                               │
│ - isDone: boolean                                                                │
│ - isTriggered: boolean                                                           │
│ - createdAt: DateTime                                                             │
│ - triggeredAt: DateTime                                                           │
│ - type: string                                                                   │
│ - priority: string                                                               │
│ - sendEmail: boolean                                                             │
│ - showNotification: boolean                                                       │
│ - playSound: boolean                                                             │
│ - metadata: array                                                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getTitle(): string                                                             │
│ + setTitle(string): Reminder                                                     │
│ + getDueDate(): DateTime                                                         │
│ + setDueDate(DateTime): Reminder                                                 │
│ + isDone(): boolean                                                              │
│ + setIsDone(boolean): Reminder                                                   │
│ + isTriggered(): boolean                                                         │
│ + setIsTriggered(boolean): Reminder                                              │
│ + shouldTrigger(): boolean                                                       │
│ + trigger(): Reminder                                                            │
│ + markAsDone(): Reminder                                                         │
│ + getNotificationConfig(): array                                                 │
│ + getFormattedMessage(): string                                                  │
│ + getTimeUntilDue(): DateInterval                                                │
│ + isOverdue(): boolean                                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Notification                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - title: string                                                                   │
│ - message: string                                                                │
│ - isRead: boolean                                                                │
│ - createdAt: DateTime                                                             │
│ - type: string                                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getTitle(): string                                                             │
│ + setTitle(string): Notification                                                 │
│ + getMessage(): string                                                           │
│ + setMessage(string): Notification                                               │
│ + isRead(): boolean                                                              │
│ + setIsRead(boolean): Notification                                               │
│ + getType(): string                                                              │
│ + setType(string): Notification                                                  │
│ + getUser(): User                                                                │
│ + setUser(User): Notification                                                    │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): Notification                                                  │
│ + getIcon(): string                                                              │
│ + getTypeColor(): string                                                         │
│ + getTimeAgo(): string                                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Reservation                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - dateDebut: DateTime                                                             │
│ - dateFin: DateTime                                                               │
│ - reservePar: string                                                             │
│ - motif: string                                                                  │
│ - statut: string                                                                 │
│ - dateCreation: DateTime                                                          │
│ - nombreParticipants: int                                                        │
│ - notes: string                                                                  │
│ - contactTelephone: string                                                        │
│ - contactEmail: string                                                           │
│ - recurrente: boolean                                                            │
│ - typeRecurrence: string                                                         │
│ - finRecurrence: DateTime                                                        │
│ - dateModification: DateTime                                                     │
│ - modifiePar: string                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getSalle(): Salle                                                              │
│ + setSalle(Salle): Reservation                                                   │
│ + getDateDebut(): DateTime                                                       │
│ + setDateDebut(DateTime): Reservation                                            │
│ + getDateFin(): DateTime                                                         │
│ + setDateFin(DateTime): Reservation                                              │
│ + chevaucheAvec(DateTime, DateTime): boolean                                     │
│ + estActive(): boolean                                                           │
│ + getStatutLabel(): string                                                       │
│ + getDuree(): DateInterval                                                       │
│ + getDureeEnHeures(): float                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Document                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - file: File                                                                      │
│ - fileName: string                                                               │
│ - createdAt: DateTimeImmutable                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + setFile(File): void                                                            │
│ + getFile(): File                                                                │
│ + setFileName(string): void                                                      │
│ + getFileName(): string                                                          │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): Document                                                      │
│ + getCreatedAt(): DateTimeImmutable                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        CollaborativeNote                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - title: string                                                                   │
│ - content: string                                                                │
│ - createdAt: DateTimeImmutable                                                   │
│ - updatedAt: DateTimeImmutable                                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getTitle(): string                                                             │
│ + setTitle(string): CollaborativeNote                                            │
│ + getContent(): string                                                           │
│ + setContent(string): CollaborativeNote                                          │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): CollaborativeNote                                             │
│ + getCreatedBy(): User                                                           │
│ + setCreatedBy(User): CollaborativeNote                                          │
│ + getCreatedAt(): DateTimeImmutable                                              │
│ + getUpdatedAt(): DateTimeImmutable                                              │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             Participant                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - email: string                                                                   │
│ - nom: string                                                                    │
│ - prenom: string                                                                 │
│ - telephone: string                                                              │
│ - createdAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getEmail(): string                                                             │
│ + setEmail(string): Participant                                                  │
│ + getNom(): string                                                               │
│ + setNom(string): Participant                                                    │
│ + getPrenom(): string                                                            │
│ + setPrenom(string): Participant                                                 │
│ + getTelephone(): string                                                         │
│ + setTelephone(string): Participant                                              │
│ + getInvitations(): Collection<Invitation>                                       │
│ + addInvitation(Invitation): Participant                                         │
│ + removeInvitation(Invitation): Participant                                      │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        ResetPasswordRequest                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - token: string                                                                  │
│ - expiresAt: DateTime                                                             │
│ - createdAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getToken(): string                                                             │
│ + setToken(string): ResetPasswordRequest                                         │
│ + getExpiresAt(): DateTime                                                       │
│ + setExpiresAt(DateTime): ResetPasswordRequest                                   │
│ + getCreatedAt(): DateTime                                                       │
│ + setCreatedAt(DateTime): ResetPasswordRequest                                   │
│ + getUser(): User                                                                │
│ + setUser(User): ResetPasswordRequest                                            │
│ + isExpired(): boolean                                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            CalendarEvent                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - title: string                                                                   │
│ - start: DateTime                                                                 │
│ - end: DateTime                                                                   │
│ - allDay: boolean                                                                │
│ - backgroundColor: string                                                         │
│ - borderColor: string                                                             │
│ - textColor: string                                                              │
│ - createdAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getTitle(): string                                                             │
│ + setTitle(string): CalendarEvent                                                │
│ + getStart(): DateTime                                                           │
│ + setStart(DateTime): CalendarEvent                                              │
│ + getEnd(): DateTime                                                             │
│ + setEnd(DateTime): CalendarEvent                                                │
│ + isAllDay(): boolean                                                            │
│ + setAllDay(boolean): CalendarEvent                                              │
│ + getBackgroundColor(): string                                                   │
│ + setBackgroundColor(string): CalendarEvent                                      │
│ + getBorderColor(): string                                                       │
│ + setBorderColor(string): CalendarEvent                                          │
│ + getTextColor(): string                                                         │
│ + setTextColor(string): CalendarEvent                                            │
│ + getUser(): User                                                                │
│ + setUser(User): CalendarEvent                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             EventFile                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - fileName: string                                                               │
│ - filePath: string                                                               │
│ - fileSize: int                                                                  │
│ - mimeType: string                                                               │
│ - uploadedAt: DateTime                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getFileName(): string                                                          │
│ + setFileName(string): EventFile                                                 │
│ + getFilePath(): string                                                          │
│ + setFilePath(string): EventFile                                                 │
│ + getFileSize(): int                                                             │
│ + setFileSize(int): EventFile                                                    │
│ + getMimeType(): string                                                          │
│ + setMimeType(string): EventFile                                                 │
│ + getUploadedAt(): DateTime                                                      │
│ + setUploadedAt(DateTime): EventFile                                             │
│ + getEvent(): Event                                                              │
│ + setEvent(Event): EventFile                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        │ 1
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            GestionSalle                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ - id: int                                                                         │
│ - nom: string                                                                    │
│ - description: string                                                             │
│ - capacite: int                                                                  │
│ - disponible: boolean                                                             │
│ - createdAt: DateTime                                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│ + getId(): int                                                                   │
│ + getNom(): string                                                               │
│ + setNom(string): GestionSalle                                                   │
│ + getDescription(): string                                                        │
│ + setDescription(string): GestionSalle                                           │
│ + getCapacite(): int                                                             │
│ + setCapacite(int): GestionSalle                                                 │
│ + isDisponible(): boolean                                                        │
│ + setDisponible(boolean): GestionSalle                                           │
│ + getCreatedAt(): DateTime                                                       │
│ + setCreatedAt(DateTime): GestionSalle                                           │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           RELATIONS PRINCIPALES                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  User (1) ──────────────────────────────────────────────────────────────────────┐ │
│  │                                                                              │ │
│  ├── departement: Departement (N:1)                                            │ │
│  ├── participations: Participation (1:N)                                        │ │
│  ├── reminders: Reminder (1:N)                                                  │ │
│  ├── notifications: Notification (1:N)                                          │ │
│  ├── calendarEvents: CalendarEvent (1:N)                                        │ │
│  ├── collaborativeNotes: CollaborativeNote (1:N)                                │ │
│  └── resetPasswordRequests: ResetPasswordRequest (1:N)                          │ │
│                                                                                   │ │
│  Event (1) ─────────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── salle: Salle (N:1)                                                        │ │
│  ├── organizer: User (N:1)                                                      │ │
│  ├── departement: Departement (N:1)                                            │ │
│  ├── createdBy: User (N:1)                                                      │ │
│  ├── invitations: Invitation (1:N)                                              │ │
│  ├── documents: Document (1:N)                                                  │ │
│  ├── participations: Participation (1:N)                                        │ │
│  ├── files: EventFile (1:N)                                                     │ │
│  └── collaborativeNotes: CollaborativeNote (1:N)                                │ │
│                                                                                   │ │
│  Salle (1) ─────────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── reservations: Reservation (1:N)                                            │ │
│                                                                                   │ │
│  Departement (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── users: User (1:N)                                                          │ │
│  └── events: Event (1:N)                                                        │ │
│                                                                                   │ │
│  Participant (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── invitations: Invitation (1:N)                                              │ │
│                                                                                   │ │
│  Invitation (1) ────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── event: Event (N:1)                                                         │ │
│  └── participant: Participant (N:1)                                             │ │
│                                                                                   │ │
│  Participation (1) ─────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: User (N:1)                                                           │ │
│  └── event: Event (N:1)                                                         │ │
│                                                                                   │ │
│  Reminder (1) ──────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: User (N:1)                                                           │ │
│  └── event: Event (N:1)                                                         │ │
│                                                                                   │ │
│  Notification (1) ──────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── user: User (N:1)                                                           │ │
│  └── event: Event (N:1)                                                         │ │
│                                                                                   │ │
│  Reservation (1) ───────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── salle: Salle (N:1)                                                         │ │
│                                                                                   │ │
│  Document (1) ──────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── event: Event (N:1)                                                         │ │
│                                                                                   │ │
│  CollaborativeNote (1) ─────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  ├── event: Event (N:1)                                                         │ │
│  └── createdBy: User (N:1)                                                      │ │
│                                                                                   │ │
│  EventFile (1) ─────────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── event: Event (N:1)                                                         │ │
│                                                                                   │ │
│  CalendarEvent (1) ─────────────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── user: User (N:1)                                                           │ │
│                                                                                   │ │
│  ResetPasswordRequest (1) ──────────────────────────────────────────────────────┤ │
│  │                                                                              │ │
│  └── user: User (N:1)                                                           │ │
│                                                                                   │ │
│  GestionSalle (1) ──────────────────────────────────────────────────────────────┘ │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONNALITÉS AVANCÉES                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔄 Système de Rappels Automatiques                                              │
│  - Déclenchement intelligent : Vérification automatique des dates                │
│  - Multi-canal : Email, notification, son                                       │
│  - Personnalisation : Fréquence et priorités par utilisateur                    │
│  - Métadonnées : Informations contextuelles                                     │
│                                                                                   │
│  📊 Gestion des Départements                                                     │
│  - Hiérarchie : Organisation des utilisateurs                                   │
│  - Budget : Suivi des budgets annuels                                           │
│  - Responsabilité : Gestion des responsables                                     │
│  - Activité : Statut actif/inactif                                              │
│                                                                                   │
│  🔐 Sécurité et Authentification                                                 │
│  - Rôles : Système de permissions                                               │
│  - Tokens : Sécurisation des invitations                                        │
│  - Réinitialisation : Processus sécurisé                                        │
│  - Google : Intégration OAuth                                                   │
│                                                                                   │
│  📱 Notifications Multi-canal                                                    │
│  - Email : Notifications par email                                              │
│  - SMS : Notifications par SMS                                                  │
│  - Interface : Notifications visuelles                                          │
│  - Son : Notifications sonores                                                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘ 