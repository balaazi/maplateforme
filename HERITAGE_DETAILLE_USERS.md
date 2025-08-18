# HÉRITAGE DÉTAILLÉ - HIÉRARCHIE DES UTILISATEURS

## 📊 DIAGRAMME D'HÉRITAGE COMPLET

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    HIÉRARCHIE D'HÉRITAGE DES UTILISATEURS                        │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🧑‍💼 CLASSE DE BASE : USER

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                USER                                               │
│                           (Classe de base)                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS DE BASE                                                              │
│  - id: int (Primary Key, Auto Increment)                                        │
│  - email: string (Unique, NOT NULL)                                             │
│  - nom: string (NOT NULL)                                                        │
│  - prenom: string (NOT NULL)                                                     │
│  - password: string (Hashed, NOT NULL)                                          │
│  - roles: array (JSON, NOT NULL)                                                 │
│  - telephone: string (NULLABLE)                                                  │
│  - statutCompte: string (ENUM: 'Actif', 'Suspendu', 'Supprimé')                 │
│  - dateCreation: datetime (NOT NULL)                                             │
│  - dateDerniereConnexion: datetime (NULLABLE)                                    │
│  - preferencesNotifications: array (JSON)                                        │
│  - departement: Departement (ManyToOne, NULLABLE)                               │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES D'AUTHENTIFICATION                                                     │
│  + getUserIdentifier(): string                                                    │
│  + eraseCredentials(): void                                                       │
│  + getRoles(): array                                                             │
│  + hasRole(string $role): boolean                                                │
│  + isActive(): boolean                                                           │
│  + isSuspended(): boolean                                                        │
│  + isDeleted(): boolean                                                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DE PROFIL                                                   │
│  + getFullName(): string                                                         │
│  + getInitiales(): string                                                        │
│  + updateProfile(array $data): void                                              │
│  + changePassword(string $newPassword): void                                     │
│  + updateLastLogin(): void                                                       │
│  + updateNotificationPreferences(array $preferences): void                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS                                                                        │
│  - departement: Departement (ManyToOne)                                          │
│  - participations: Collection<Participation> (OneToMany)                         │
│  - reminders: Collection<Reminder> (OneToMany)                                   │
│  - notifications: Collection<Notification> (OneToMany)                           │
│  - calendarEvents: Collection<CalendarEvent> (OneToMany)                         │
│  - collaborativeNotes: Collection<CollaborativeNote> (OneToMany)                 │
│  - resetPasswordRequests: Collection<ResetPasswordRequest> (OneToMany)           │
│  - organizedEvents: Collection<Event> (OneToMany)                               │
│  - createdEvents: Collection<Event> (OneToMany)                                  │
└─────────────────────────────────────────────────────────────────────────────────────┘
                                ▲
                                │ hérite de
                                │ (extends)
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐
│   ADMINISTRATEUR    │ │    ORGANISATEUR     │ │    PARTICIPANT      │
│   (Classe fille)    │ │   (Classe fille)    │ │   (Classe fille)    │
└─────────────────────┘ └─────────────────────┘ └─────────────────────┘
```

## 👑 ADMINISTRATEUR (Classe fille de User)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                            ADMINISTRATEUR                                         │
│                        (extends User)                                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS SPÉCIFIQUES                                                          │
│  - niveauAcces: string (ENUM: 'Super', 'Standard')                              │
│  - permissions: array (JSON)                                                     │
│  - datePromotion: datetime                                                       │
│  - superviseur: Administrateur (ManyToOne, NULLABLE)                            │
│  - administres: Collection<Administrateur> (OneToMany)                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES D'ADMINISTRATION SYSTÈME                                               │
│  + inviterUser(string $email, array $roles): User                               │
│  + suspendUser(User $user, string $raison): void                                │
│  + supprimerUser(User $user): void                                              │
│  + reactiverUser(User $user): void                                              │
│  + gererPermissions(User $user, array $permissions): void                       │
│  + assignerRole(User $user, string $role): void                                 │
│  + retirerRole(User $user, string $role): void                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES ÉVÉNEMENTS                                              │
│  + modifierEvent(Event $event, array $data): void                               │
│  + supprimerEvent(Event $event): void                                           │
│  + annulerEvent(Event $event, string $raison): void                             │
│  + approuverEvent(Event $event): void                                           │
│  + rejeterEvent(Event $event, string $raison): void                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES DÉPARTEMENTS                                            │
│  + creerDepartement(array $data): Departement                                   │
│  + modifierDepartement(Departement $departement, array $data): void             │
│  + supprimerDepartement(Departement $departement): void                         │
│  + assignerUserDepartement(User $user, Departement $departement): void          │
│  + retirerUserDepartement(User $user): void                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES SALLES                                                  │
│  + creerSalle(array $data): Salle                                               │
│  + modifierSalle(Salle $salle, array $data): void                              │
│  + supprimerSalle(Salle $salle): void                                           │
│  + gererReservationsSalle(Salle $salle): void                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE RAPPORTS ET STATISTIQUES                                            │
│  + genererRapportUtilisateurs(): array                                          │
│  + genererRapportEvenements(): array                                            │
│  + genererRapportDepartements(): array                                          │
│  + genererStatistiquesGlobales(): array                                         │
│  + exporterDonnees(string $format): string                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE CONFIGURATION SYSTÈME                                               │
│  + gererParametresSysteme(array $parametres): void                              │
│  + configurerNotifications(array $config): void                                  │
│  + gererBackup(): void                                                           │
│  + restaurerBackup(string $fichier): void                                       │
│  + gererLogs(): array                                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE SÉCURITÉ                                                            │
│  + auditerActivite(User $user): array                                           │
│  + detecterAnomalies(): array                                                    │
│  + bloquerIP(string $ip): void                                                  │
│  + debloquerIP(string $ip): void                                                │
│  + gererSessions(): array                                                        │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS SPÉCIFIQUES                                                           │
│  - superviseur: Administrateur (ManyToOne)                                      │
│  - administres: Collection<Administrateur> (OneToMany)                          │
│  - actionsAudit: Collection<AuditLog> (OneToMany)                               │
│  - rapportsGeneres: Collection<Rapport> (OneToMany)                             │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 ORGANISATEUR (Classe fille de User)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             ORGANISATEUR                                         │
│                         (extends User)                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS SPÉCIFIQUES                                                          │
│  - specialite: string (ENUM: 'Formation', 'Séminaire', 'Conférence', 'Réunion') │
│  - experience: int (années d'expérience)                                         │
│  - certifications: array (JSON)                                                  │
│  - bio: text (biographie)                                                        │
│  - tauxReussite: float (pourcentage)                                            │
│  - dateCertification: datetime                                                   │
│  - superviseur: Organisateur (ManyToOne, NULLABLE)                              │
│  - stagiaires: Collection<Organisateur> (OneToMany)                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES ÉVÉNEMENTS                                              │
│  + creerEvent(array $data): Event                                                │
│  + modifierEvent(Event $event, array $data): void                               │
│  + supprimerEvent(Event $event): void                                           │
│  + annulerEvent(Event $event, string $raison): void                             │
│  + reprogrammerEvent(Event $event, datetime $nouvelleDate): void                │
│  + cloturerEvent(Event $event): void                                            │
│  + archiverEvent(Event $event): void                                            │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES PARTICIPANTS                                            │
│  + gererParticipants(Event $event): void                                        │
│  + inviterParticipant(Event $event, string $email, string $nom): Invitation     │
│  + retirerParticipant(Event $event, User $participant): void                    │
│  + confirmerPresence(Event $event, User $participant): void                     │
│  + gererListeAttente(Event $event): void                                        │
│  + envoyerRappels(Event $event): void                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES DOCUMENTS                                               │
│  + ajouterDocument(Event $event, array $data): Document                         │
│  + modifierDocument(Document $document, array $data): void                      │
│  + supprimerDocument(Document $document): void                                  │
│  + partagerDocument(Document $document, array $users): void                     │
│  + gererPermissionsDocument(Document $document, array $permissions): void       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES SALLES                                                  │
│  + reserverSalle(Event $event, Salle $salle, datetime $debut, datetime $fin): Reservation │
│  + modifierReservation(Reservation $reservation, array $data): void             │
│  + annulerReservation(Reservation $reservation): void                           │
│  + verifierDisponibiliteSalle(Salle $salle, datetime $debut, datetime $fin): boolean │
│  + demanderSalleSpeciale(string $raison, array $specifications): void           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE COMMUNICATION                                                       │
│  + envoyerNotification(Event $event, string $message, array $destinataires): void │
│  + envoyerEmail(Event $event, string $sujet, string $contenu, array $destinataires): void │
│  + creerAnnonce(Event $event, string $titre, string $contenu): void             │
│  + gererQuestions(Event $event): void                                           │
│  + modererDiscussion(Event $event): void                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE RAPPORTS ET ANALYSES                                                │
│  + genererRapportParticipation(Event $event): array                             │
│  + analyserSatisfaction(Event $event): array                                    │
│  + calculerStatistiques(Event $event): array                                    │
│  + exporterDonneesEvent(Event $event, string $format): string                   │
│  + genererCertificats(Event $event): void                                       │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES NOTES COLLABORATIVES                                    │
│  + creerNoteCollaborative(Event $event, string $titre, string $contenu): CollaborativeNote │
│  + modifierNoteCollaborative(CollaborativeNote $note, array $data): void        │
│  + partagerNoteCollaborative(CollaborativeNote $note, array $users): void       │
│  + gererPermissionsNote(CollaborativeNote $note, array $permissions): void      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES RAPPELS                                                 │
│  + creerRappel(Event $event, User $user, datetime $dateEcheance): Reminder      │
│  + modifierRappel(Reminder $reminder, array $data): void                        │
│  + supprimerRappel(Reminder $reminder): void                                    │
│  + envoyerRappelsAutomatiques(Event $event): void                               │
│  + configurerRappelsAutomatiques(Event $event, array $config): void             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE SYNCHRONISATION                                                     │
│  + synchroniserGoogleCalendar(Event $event): void                               │
│  + importerEvenementsGoogle(): array                                            │
│  + exporterEvenementsGoogle(Event $event): void                                 │
│  + configurerIntegrationGoogle(array $config): void                             │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS SPÉCIFIQUES                                                           │
│  - superviseur: Organisateur (ManyToOne)                                        │
│  - stagiaires: Collection<Organisateur> (OneToMany)                             │
│  - eventsOrganises: Collection<Event> (OneToMany)                               │
│  - notesCollaboratives: Collection<CollaborativeNote> (OneToMany)               │
│  - rappelsCrees: Collection<Reminder> (OneToMany)                               │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 👥 PARTICIPANT (Classe fille de User)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                             PARTICIPANT                                          │
│                         (extends User)                                           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  PROPRIÉTÉS SPÉCIFIQUES                                                          │
│  - preferencesParticipation: array (JSON)                                        │
│  - historiqueParticipations: array (JSON)                                        │
│  - competences: array (JSON)                                                     │
│  - disponibilites: array (JSON)                                                  │
│  - preferencesNotifications: array (JSON)                                        │
│  - statutParticipation: string (ENUM: 'Actif', 'Inactif', 'Suspendu')           │
│  - dateInscription: datetime                                                     │
│  - mentor: Participant (ManyToOne, NULLABLE)                                     │
│  - mentores: Collection<Participant> (OneToMany)                                 │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE PARTICIPATION AUX ÉVÉNEMENTS                                        │
│  + rejoindreEvent(Event $event): Participation                                  │
│  + quitterEvent(Event $event): void                                             │
│  + confirmerPresence(Event $event): void                                        │
│  + annulerPresence(Event $event): void                                          │
│  + demanderParticipation(Event $event): void                                    │
│  + rejoindreListeAttente(Event $event): void                                    │
│  + quitterListeAttente(Event $event): void                                      │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES INVITATIONS                                             │
│  + repondreInvitation(Invitation $invitation, string $reponse): void            │
│  + accepterInvitation(Invitation $invitation): void                             │
│  + refuserInvitation(Invitation $invitation, string $raison): void              │
│  + demanderPlusInfo(Invitation $invitation, string $question): void             │
│  + proposerDateAlternative(Invitation $invitation, datetime $nouvelleDate): void │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE CONSULTATION DES DOCUMENTS                                          │
│  + consulterDocuments(Event $event): array                                      │
│  + telechargerDocument(Document $document): string                              │
│  + partagerDocument(Document $document, array $users): void                     │
│  + commenterDocument(Document $document, string $commentaire): void             │
│  + evaluerDocument(Document $document, int $note): void                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES NOTES COLLABORATIVES                                    │
│  + consulterNotesCollaboratives(Event $event): array                            │
│  + ajouterCommentaireNote(CollaborativeNote $note, string $commentaire): void   │
│  + proposerModificationNote(CollaborativeNote $note, string $modification): void │
│  + partagerNoteCollaborative(CollaborativeNote $note, array $users): void       │
│  + evaluerNoteCollaborative(CollaborativeNote $note, int $note): void           │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES RAPPELS                                                 │
│  + consulterRappels(): array                                                    │
│  + marquerRappelCommeLu(Reminder $reminder): void                              │
│  + supprimerRappel(Reminder $reminder): void                                    │
│  + configurerRappels(array $preferences): void                                  │
│  + desactiverRappels(): void                                                    │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DES NOTIFICATIONS                                           │
│  + consulterNotifications(): array                                              │
│  + marquerNotificationCommeLue(Notification $notification): void                │
│  + supprimerNotification(Notification $notification): void                      │
│  + configurerNotifications(array $preferences): void                            │
│  + desactiverNotifications(): void                                              │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE GESTION DU PROFIL                                                   │
│  + modifierPreferencesParticipation(array $preferences): void                   │
│  + ajouterCompetence(string $competence): void                                  │
│  + supprimerCompetence(string $competence): void                                │
│  + modifierDisponibilites(array $disponibilites): void                          │
│  + definirPreferencesNotifications(array $preferences): void                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE CALENDRIER                                                          │
│  + consulterCalendrier(): array                                                 │
│  + ajouterEvenementCalendrier(Event $event): void                               │
│  + supprimerEvenementCalendrier(Event $event): void                             │
│  + synchroniserCalendrierGoogle(): void                                         │
│  + exporterCalendrier(string $format): string                                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE RAPPORTS ET HISTORIQUE                                              │
│  + consulterHistoriqueParticipations(): array                                  │
│  + genererRapportParticipation(): array                                         │
│  + consulterStatistiques(): array                                               │
│  + exporterDonneesPersonnelles(string $format): string                          │
│  + demanderCertificatParticipation(Event $event): void                          │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  MÉTHODES DE COMMUNICATION                                                       │
│  + contacterOrganisateur(Event $event, string $message): void                   │
│  + poserQuestion(Event $event, string $question): void                          │
│  + signalerProbleme(Event $event, string $probleme): void                       │
│  + proposerAmelioration(Event $event, string $suggestion): void                 │
│  + partagerExperience(Event $event, string $experience): void                   │
├─────────────────────────────────────────────────────────────────────────────────────┤
│  RELATIONS SPÉCIFIQUES                                                           │
│  - mentor: Participant (ManyToOne)                                               │
│  - mentores: Collection<Participant> (OneToMany)                                 │
│  - participations: Collection<Participation> (OneToMany)                        │
│  - invitationsRecues: Collection<Invitation> (OneToMany)                        │
│  - rappelsRecus: Collection<Reminder> (OneToMany)                               │
│  - notificationsRecues: Collection<Notification> (OneToMany)                    │
│  - evenementsCalendrier: Collection<CalendarEvent> (OneToMany)                  │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🔄 DÉTAILS TECHNIQUES DE L'HÉRITAGE

### Polymorphisme et méthodes communes
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        MÉTHODES COMMUNES                                         │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  Toutes les classes héritent de User :                                           │
│                                                                                   │
│  🔐 AUTHENTIFICATION                                                             │
│  - getUserIdentifier(): string                                                    │
│  - eraseCredentials(): void                                                       │
│  - getRoles(): array                                                             │
│  - hasRole(string $role): boolean                                                │
│                                                                                   │
│  👤 GESTION DE PROFIL                                                            │
│  - getFullName(): string                                                         │
│  - updateProfile(array $data): void                                              │
│  - changePassword(string $newPassword): void                                     │
│  - updateLastLogin(): void                                                       │
│                                                                                   │
│  📧 NOTIFICATIONS                                                                │
│  - receiveNotification(Notification $notification): void                          │
│  - markNotificationAsRead(Notification $notification): void                       │
│  - deleteNotification(Notification $notification): void                           │
│                                                                                   │
│  🔔 RAPPELS                                                                      │
│  - receiveReminder(Reminder $reminder): void                                     │
│  - markReminderAsRead(Reminder $reminder): void                                  │
│  - deleteReminder(Reminder $reminder): void                                      │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Surcharge de méthodes (Override)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        SURCHARGE DE MÉTHODES                                     │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ADMINISTRATEUR                                                                   │
│  - hasRole(string $role): boolean                                                │
│    → Vérifie les permissions administrateur                                      │
│  - getRoles(): array                                                             │
│    → Retourne les rôles + permissions spéciales                                  │
│  - isActive(): boolean                                                           │
│    → Vérifie le statut administrateur                                            │
│                                                                                   │
│  ORGANISATEUR                                                                     │
│  - hasRole(string $role): boolean                                                │
│    → Vérifie les permissions organisateur                                        │
│  - getRoles(): array                                                             │
│    → Retourne les rôles + spécialités                                            │
│  - isActive(): boolean                                                           │
│    → Vérifie le statut organisateur                                              │
│                                                                                   │
│  PARTICIPANT                                                                      │
│  - hasRole(string $role): boolean                                                │
│    → Vérifie les permissions participant                                         │
│  - getRoles(): array                                                             │
│    → Retourne les rôles + préférences                                            │
│  - isActive(): boolean                                                           │
│    → Vérifie le statut participant                                               │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Contraintes et validations
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                        CONTRAINTES ET VALIDATIONS                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ADMINISTRATEUR                                                                   │
│  - niveauAcces: doit être 'Super' ou 'Standard'                                 │
│  - permissions: tableau JSON valide                                              │
│  - superviseur: peut être null (pour les super admins)                          │
│  - email: doit être unique dans le système                                       │
│                                                                                   │
│  ORGANISATEUR                                                                     │
│  - specialite: doit être dans l'enum défini                                      │
│  - experience: doit être >= 0                                                    │
│  - tauxReussite: doit être entre 0 et 100                                        │
│  - superviseur: peut être null (pour les organisateurs seniors)                 │
│  - email: doit être unique dans le système                                       │
│                                                                                   │
│  PARTICIPANT                                                                      │
│  - preferencesParticipation: tableau JSON valide                                 │
│  - competences: tableau JSON valide                                              │
│  - disponibilites: tableau JSON valide                                           │
│  - statutParticipation: doit être dans l'enum défini                            │
│  - mentor: peut être null                                                        │
│  - email: doit être unique dans le système                                       │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Relations d'héritage dans la base de données
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                    STRATÉGIE D'HÉRITAGE EN BASE                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  Table: user                                                                      │
│  - Toutes les propriétés de base de User                                         │
│  - Colonne 'discriminator' pour identifier le type                              │
│  - Colonne 'roles' contient les rôles spécifiques                               │
│                                                                                   │
│  Discriminateurs :                                                               │
│  - 'user' → User standard                                                        │
│  - 'admin' → Administrateur                                                      │
│  - 'organisateur' → Organisateur                                                 │
│  - 'participant' → Participant                                                   │
│                                                                                   │
│  Propriétés spécifiques stockées en JSON dans des colonnes dédiées :            │
│  - permissions (pour Administrateur)                                             │
│  - specialite, experience, certifications (pour Organisateur)                   │
│  - preferencesParticipation, competences (pour Participant)                     │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🎯 AVANTAGES DE CETTE HIÉRARCHIE

### Réutilisabilité du code
- **Méthodes communes** héritées de User
- **Évite la duplication** de code
- **Facilite la maintenance** et les évolutions

### Extensibilité
- **Ajout facile** de nouveaux types d'utilisateurs
- **Modularité** des fonctionnalités
- **Séparation claire** des responsabilités

### Sécurité
- **Contrôle d'accès** basé sur les rôles
- **Permissions granulaires** pour chaque type
- **Audit trail** pour les actions sensibles

### Flexibilité
- **Polymorphisme** pour traiter tous les types uniformément
- **Surcharge** pour personnaliser les comportements
- **Interface commune** pour l'authentification

Cette hiérarchie d'héritage permet une gestion complète et flexible des différents types d'utilisateurs dans votre application MaPlateforme ! 