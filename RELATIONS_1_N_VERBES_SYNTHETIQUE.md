# 🔗 SYNTHÈSE DES RELATIONS (1:N) AVEC VERBES

| Entité Source         | Verbe (1:N)         | Entité Cible         | Explication synthétique                                 |
|---------------------- |-------------------- |--------------------- |--------------------------------------------------------|
| Utilisateur           | participe à         | Participation        | Un utilisateur peut avoir plusieurs participations      |
| Utilisateur           | reçoit              | Notification         | Un utilisateur reçoit plusieurs notifications           |
| Utilisateur           | reçoit              | Reminder             | Un utilisateur reçoit plusieurs rappels                 |
| Utilisateur           | organise            | Événement            | Un utilisateur organise plusieurs événements            |
| Utilisateur           | crée                | Événement            | Un utilisateur crée plusieurs événements                |
| Utilisateur           | écrit               | CollaborativeNote    | Un utilisateur écrit plusieurs notes collaboratives     |
| Utilisateur           | a                   | Calendar Event       | Un utilisateur a plusieurs événements de calendrier     |
| Utilisateur           | demande             | Reservation          | Un utilisateur demande plusieurs réservations           |
| Utilisateur           | demande             | ResetPasswordRequest | Un utilisateur fait plusieurs demandes de réinitialisation |
| Administrateur        | gère                | Utilisateur          | Un administrateur gère plusieurs utilisateurs           |
| Administrateur        | gère                | Événement            | Un administrateur gère plusieurs événements             |
| Organisateur          | organise            | Événement            | Un organisateur organise plusieurs événements           |
| Organisateur          | invite              | Invitation           | Un organisateur envoie plusieurs invitations            |
| Organisateur          | écrit               | CollaborativeNote    | Un organisateur écrit plusieurs notes collaboratives    |
| Participation         | concerne            | Événement            | Une participation concerne plusieurs événements         |
| Événement             | génère              | Invitation           | Un événement génère plusieurs invitations               |
| Événement             | contient            | Document             | Un événement contient plusieurs documents               |
| Événement             | a                   | Participation        | Un événement a plusieurs participations                 |
| Événement             | contient            | CollaborativeNote    | Un événement contient plusieurs notes collaboratives    |
| Événement             | génère              | Reminder             | Un événement génère plusieurs rappels                   |
| Événement             | a                   | Reservation          | Un événement a plusieurs réservations                   |
| Document              | appartient à        | Événement            | Un document appartient à un événement                   |
| Invitation            | concerne            | Événement            | Une invitation concerne un événement                    |
| Invitation            | est envoyée à       | Utilisateur          | Une invitation est envoyée à un utilisateur             |
| Notification          | concerne            | Utilisateur          | Une notification concerne un utilisateur                |
| Reservation           | réserve             | Salle                | Une réservation réserve une salle                       |
| CollaborativeNote     | concerne            | Événement            | Une note collaborative concerne un événement            |
| Reminder              | concerne            | Utilisateur          | Un rappel concerne un utilisateur                       |
| Reminder              | concerne            | Événement            | Un rappel concerne un événement                         |
| Gestion Salle         | gère                | Salle                | Une gestion salle gère plusieurs salles                 |
| Calendar Event        | appartient à        | Utilisateur          | Un calendar event appartient à un utilisateur           |
| Salle                 | accueille           | Événement            | Une salle accueille plusieurs événements                |
| Salle                 | a                   | Reservation          | Une salle a plusieurs réservations                      |

---

*📝 Tableau synthétique des relations 1:N avec verbes pour les entités principales de l'application* 