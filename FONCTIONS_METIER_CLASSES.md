# FONCTIONS MÉTIER DÉTAILLÉES - MAPLATEFORME

## 📊 Vue d'ensemble des fonctions métier par classe

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONS MÉTIER PAR CLASSE                            │
│                              MAPLATEFORME                                        │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 🧑‍💼 USER (Utilisateur)

### Fonctions d'authentification et gestion de compte
- **`eraseCredentials()`** : Nettoyage des données sensibles temporaires
- **`getUserIdentifier()`** : Retourne l'identifiant unique (email)
- **`getFullName()`** : Retourne le nom complet (prénom + nom)
- **`__toString()`** : Représentation string de l'utilisateur

### Fonctions de gestion des participations
- **`addParticipation(Participation $participation)`** : Ajoute une participation
- **`removeParticipation(Participation $participation)`** : Supprime une participation

---

## 📅 EVENT (Événement)

### Fonctions de gestion des invitations
- **`addInvitation(Invitation $invitation)`** : Ajoute une invitation
- **`removeInvitation(Invitation $invitation)`** : Supprime une invitation

### Fonctions de gestion des documents
- **`addDocument(Document $document)`** : Ajoute un document
- **`removeDocument(Document $document)`** : Supprime un document

### Fonctions de gestion des fichiers
- **`addFile(EventFile $file)`** : Ajoute un fichier
- **`removeFile(EventFile $file)`** : Supprime un fichier

### Fonctions de gestion des notes collaboratives
- **`addCollaborativeNote(CollaborativeNote $note)`** : Ajoute une note collaborative
- **`removeCollaborativeNote(CollaborativeNote $note)`** : Supprime une note collaborative

### Fonctions de gestion des participations
- **`addParticipation(Participation $participation)`** : Ajoute une participation
- **`removeParticipation(Participation $participation)`** : Supprime une participation

### Fonctions de gestion des fichiers d'image
- **`setImageFile(?File $imageFile)`** : Définit le fichier image
- **`getImageFile()`** : Récupère le fichier image

---

## 🏢 SALLE (Salle de réunion)

### Fonctions de gestion des équipements
- **`addEquipement(string $equipement)`** : Ajoute un équipement
- **`removeEquipement(string $equipement)`** : Supprime un équipement
- **`hasEquipement(string $equipement)`** : Vérifie si un équipement existe

### Fonctions utilitaires
- **`getPrioriteLabel()`** : Retourne le libellé de priorité (Basse, Normale, Haute)
- **`getTypeLabel()`** : Retourne le libellé du type (Salle de réunion, Conférence, etc.)
- **`__toString()`** : Représentation string de la salle

---

## 🏛️ DEPARTEMENT (Département)

### Fonctions de gestion des utilisateurs
- **`addUser(User $user)`** : Ajoute un utilisateur au département
- **`removeUser(User $user)`** : Supprime un utilisateur du département

### Fonctions de gestion des événements
- **`addEvent(Event $event)`** : Ajoute un événement au département
- **`removeEvent(Event $event)`** : Supprime un événement du département

### Fonctions utilitaires
- **`__toString()`** : Représentation string du département

---

## 📧 INVITATION (Invitation)

### Fonctions de gestion des statuts
- **`isPending()`** : Vérifie si l'invitation est en attente
- **`isAccepted()`** : Vérifie si l'invitation est acceptée
- **`isDeclined()`** : Vérifie si l'invitation est refusée

### Fonctions de validation
- **`setStatus(string $status)`** : Définit le statut avec validation

---

## 👥 PARTICIPATION (Participation)

### Fonctions de gestion de présence
- **`isPresent()`** : Vérifie si le participant est présent
- **`setIsPresent(bool $isPresent)`** : Définit la présence

### Fonctions de gestion du statut
- **`setInvitationStatus(string $invitationStatus)`** : Définit le statut d'invitation

---

## 🔔 REMINDER (Rappel)

### Fonctions de déclenchement intelligent
- **`shouldTrigger()`** : Vérifie si le rappel doit être déclenché maintenant
- **`trigger()`** : Marque le rappel comme déclenché
- **`markAsDone()`** : Marque le rappel comme terminé

### Fonctions de gestion du temps
- **`getTimeUntilDue()`** : Retourne le temps restant avant le rappel
- **`isOverdue()`** : Vérifie si le rappel est en retard

### Fonctions de configuration
- **`getNotificationConfig()`** : Retourne la configuration de notification
- **`getFormattedMessage()`** : Retourne un message formaté pour l'affichage

---

## 📢 NOTIFICATION (Notification)

### Fonctions d'affichage
- **`getIcon()`** : Retourne l'icône selon le type de notification
- **`getTypeColor()`** : Retourne la couleur selon le type de notification
- **`getTimeAgo()`** : Retourne le temps écoulé ("il y a 2 heures")

### Fonctions de gestion
- **`isRead()`** : Vérifie si la notification est lue
- **`setIsRead(bool $isRead)`** : Définit le statut de lecture

---

## 📋 RESERVATION (Réservation)

### Fonctions de validation
- **`chevaucheAvec(DateTime $debut, DateTime $fin)`** : Vérifie si cette réservation chevauche avec une autre période
- **`estActive()`** : Vérifie si la réservation est active (confirmée et en cours)

### Fonctions de calcul
- **`getDuree()`** : Retourne la durée de la réservation
- **`getDureeEnHeures()`** : Retourne la durée en heures

### Fonctions utilitaires
- **`getStatutLabel()`** : Retourne le libellé du statut (Confirmée, En attente, etc.)

---

## 📄 DOCUMENT (Document)

### Fonctions de gestion des fichiers
- **`setFile(?File $file)`** : Définit le fichier avec mise à jour automatique
- **`getFile()`** : Récupère le fichier

---

## 📝 COLLABORATIVENOTE (Note collaborative)

### Fonctions de gestion du contenu
- **`setContent(string $content)`** : Définit le contenu de la note
- **`getContent()`** : Récupère le contenu de la note

### Fonctions de gestion des dates
- **`setUpdatedAt(DateTimeImmutable $updatedAt)`** : Met à jour la date de modification

---

## 👤 PARTICIPANT (Participant)

### Fonctions de gestion des invitations
- **`addInvitation(Invitation $invitation)`** : Ajoute une invitation
- **`removeInvitation(Invitation $invitation)`** : Supprime une invitation

---

## 🔐 RESETPASSWORDREQUEST (Demande de réinitialisation)

### Fonctions de sécurité
- **`isExpired()`** : Vérifie si la demande a expiré

---

## 📅 CALENDAREVENT (Événement calendrier)

### Fonctions de gestion des couleurs
- **`setBackgroundColor(string $backgroundColor)`** : Définit la couleur de fond
- **`setBorderColor(string $borderColor)`** : Définit la couleur de bordure
- **`setTextColor(string $textColor)`** : Définit la couleur du texte

### Fonctions de gestion du temps
- **`setAllDay(boolean $allDay)`** : Définit si l'événement dure toute la journée

---

## 🗂️ EVENTFILE (Fichier d'événement)

### Fonctions de gestion des métadonnées
- **`setFileSize(int $fileSize)`** : Définit la taille du fichier
- **`setMimeType(string $mimeType)`** : Définit le type MIME

---

## 🏢 GESTIONSALLE (Gestion de salle)

### Fonctions de gestion de disponibilité
- **`isDisponible()`** : Vérifie si la salle est disponible
- **`setDisponible(bool $disponible)`** : Définit la disponibilité

---

## 🔄 FONCTIONS MÉTIER AVANCÉES

### Système de Rappels Automatiques (Reminder)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONS INTELLIGENTES                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔔 shouldTrigger()                                                              │
│  - Vérifie si le rappel doit être déclenché maintenant                           │
│  - Prend en compte isTriggered et isDone                                        │
│  - Compare dueDate avec la date actuelle                                        │
│                                                                                   │
│  ⚡ trigger()                                                                    │
│  - Marque le rappel comme déclenché                                             │
│  - Met à jour triggeredAt avec la date actuelle                                 │
│  - Retourne l'instance pour chaînage                                            │
│                                                                                   │
│  ✅ markAsDone()                                                                │
│  - Marque le rappel comme terminé                                               │
│  - Met isDone à true                                                            │
│                                                                                   │
│  ⏰ getTimeUntilDue()                                                           │
│  - Calcule le temps restant avant le rappel                                     │
│  - Retourne un DateInterval ou null si passé                                   │
│                                                                                   │
│  ⚠️ isOverdue()                                                                │
│  - Vérifie si le rappel est en retard                                          │
│  - Prend en compte isDone et isTriggered                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Système de Notifications (Notification)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONS D'AFFICHAGE                                  │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🎨 getIcon()                                                                   │
│  - Retourne l'icône FontAwesome selon le type                                   │
│  - event_reminder → fas fa-clock                                                │
│  - event_update → fas fa-edit                                                   │
│  - event_cancel → fas fa-times-circle                                           │
│  - invitation → fas fa-envelope                                                 │
│  - welcome → fas fa-hand-wave                                                   │
│                                                                                   │
│  🌈 getTypeColor()                                                              │
│  - Retourne la couleur hexadécimale selon le type                               │
│  - event_reminder → #ffc107 (jaune)                                            │
│  - event_update → #17a2b8 (bleu)                                               │
│  - event_cancel → #dc3545 (rouge)                                              │
│  - invitation → #28a745 (vert)                                                  │
│  - welcome → #667eea (violet)                                                   │
│                                                                                   │
│  ⏱️ getTimeAgo()                                                               │
│  - Calcule le temps écoulé depuis création                                      │
│  - Retourne "À l'instant", "2 minutes", "1 heure", "3 jours"                   │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Gestion des Réservations (Reservation)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONS DE VALIDATION                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  🔄 chevaucheAvec(DateTime $debut, DateTime $fin)                              │
│  - Vérifie si cette réservation chevauche avec une autre période                │
│  - Utilise la logique : dateDebut < fin && dateFin > debut                      │
│  - Retourne true si chevauchement détecté                                       │
│                                                                                   │
│  ✅ estActive()                                                                 │
│  - Vérifie si la réservation est active                                         │
│  - Conditions : statut = 'confirmee' + dateDebut <= maintenant + dateFin > maintenant │
│  - Retourne true si la réservation est en cours                                │
│                                                                                   │
│  📊 getDuree()                                                                  │
│  - Calcule la durée entre dateDebut et dateFin                                  │
│  - Retourne un DateInterval                                                     │
│                                                                                   │
│  ⏰ getDureeEnHeures()                                                          │
│  - Convertit la durée en heures décimales                                       │
│  - Utile pour les calculs de facturation                                        │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### Gestion des Salles (Salle)
```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           FONCTIONS D'ÉQUIPEMENTS                                │
├─────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  ➕ addEquipement(string $equipement)                                           │
│  - Ajoute un équipement à la liste                                              │
│  - Évite les doublons                                                           │
│  - Met à jour le tableau d'équipements                                          │
│                                                                                   │
│  ➖ removeEquipement(string $equipement)                                        │
│  - Supprime un équipement de la liste                                           │
│  - Réindexe le tableau après suppression                                        │
│  - Retourne l'instance pour chaînage                                            │
│                                                                                   │
│  🔍 hasEquipement(string $equipement)                                          │
│  - Vérifie si un équipement est disponible                                      │
│  - Retourne true si l'équipement existe                                         │
│                                                                                   │
│  🏷️ getTypeLabel()                                                             │
│  - Retourne le libellé lisible du type                                          │
│  - reunion → "Salle de réunion"                                                │
│  - conference → "Salle de conférence"                                           │
│  - formation → "Salle de formation"                                             │
│  - bureau → "Bureau"                                                            │
│  - amphitheatre → "Amphithéâtre"                                                │
│  - workshop → "Atelier"                                                          │
│                                                                                   │
│  ⭐ getPrioriteLabel()                                                          │
│  - Retourne le libellé de priorité                                              │
│  - 1 → "Basse"                                                                  │
│  - 2 → "Normale"                                                                │
│  - 3 → "Haute"                                                                  │
│                                                                                   │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

## 📈 RÉSUMÉ DES FONCTIONS PAR CATÉGORIE

### 🔐 Sécurité et Authentification
- **User** : `eraseCredentials()`, `getUserIdentifier()`
- **ResetPasswordRequest** : `isExpired()`

### 📊 Gestion des Relations
- **User** : `addParticipation()`, `removeParticipation()`
- **Event** : `addInvitation()`, `removeInvitation()`, `addDocument()`, `removeDocument()`
- **Departement** : `addUser()`, `removeUser()`, `addEvent()`, `removeEvent()`

### ⏰ Gestion du Temps
- **Reminder** : `shouldTrigger()`, `trigger()`, `getTimeUntilDue()`, `isOverdue()`
- **Notification** : `getTimeAgo()`
- **Reservation** : `getDuree()`, `getDureeEnHeures()`

### 🎨 Interface Utilisateur
- **Notification** : `getIcon()`, `getTypeColor()`
- **Salle** : `getTypeLabel()`, `getPrioriteLabel()`
- **Reservation** : `getStatutLabel()`

### 🔄 Validation et Logique Métier
- **Invitation** : `isPending()`, `isAccepted()`, `isDeclined()`
- **Participation** : `isPresent()`
- **Reservation** : `chevaucheAvec()`, `estActive()`
- **Salle** : `hasEquipement()`

### 📁 Gestion des Fichiers
- **Event** : `setImageFile()`, `getImageFile()`
- **Document** : `setFile()`, `getFile()`
- **EventFile** : `setFileSize()`, `setMimeType()`

### 📝 Gestion du Contenu
- **CollaborativeNote** : `setContent()`, `getContent()`
- **Reminder** : `getFormattedMessage()`, `getNotificationConfig()`

Cette documentation présente toutes les fonctions métier principales de votre application, en excluant les getters/setters standards et en se concentrant sur les fonctionnalités spécifiques à votre domaine métier. 