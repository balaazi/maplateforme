<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Salle;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\GlobalNotificationService;
use App\Service\RealtimeNotificationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class RoleBasedNotificationService
{
    private const ADMIN_EMAIL = 'eventhub.contact.tunisie@gmail.com';
    private const FROM_EMAIL = 'nadiabalaazi@gmail.com';
    
    public function __construct(
        private NotificationService $notificationService,
        private GlobalNotificationService $globalNotificationService,
        private RealtimeNotificationService $realtimeNotificationService,
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie des notifications spécifiques selon le rôle pour un événement
     */
    public function notifyByRole(string $eventType, string $action, $entity, ?User $triggeringUser = null): void
    {
        try {
            // Récupérer tous les utilisateurs par rôle
            $admins = $this->getUsersByRole('ROLE_ADMIN');
            $organizers = $this->getUsersByRole('ROLE_ORGANISATEUR');
            $participants = $this->getUsersByRole('ROLE_PARTICIPANT');

            // Notifications pour les administrateurs
            $this->notifyAdmins($admins, $eventType, $action, $entity, $triggeringUser);

            // Notifications pour les organisateurs
            $this->notifyOrganizers($organizers, $eventType, $action, $entity, $triggeringUser);

            // Notifications pour les participants
            $this->notifyParticipants($participants, $eventType, $action, $entity, $triggeringUser);

            $this->logger->info('Notifications par rôle envoyées', [
                'event_type' => $eventType,
                'action' => $action,
                'entity' => get_class($entity),
                'admins_count' => count($admins),
                'organizers_count' => count($organizers),
                'participants_count' => count($participants)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi des notifications par rôle', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'action' => $action
            ]);
        }
    }

    /**
     * Notifications spécifiques pour les administrateurs
     */
    private function notifyAdmins(array $admins, string $eventType, string $action, $entity, ?User $triggeringUser): void
    {
        foreach ($admins as $admin) {
            if (!$admin->isNotifyByEmail()) {
                continue;
            }

            $notificationData = $this->prepareAdminNotification($eventType, $action, $entity, $triggeringUser);
            
            // Notification en base de données
            $this->notificationService->createNotification(
                $admin,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                $notificationData['event']
            );

            // Notification temps réel
            $this->realtimeNotificationService->createInstantNotification(
                $admin,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                [
                    'priority' => 'high',
                    'playSound' => true,
                    'persistToDatabase' => false
                ]
            );

            // Email spécifique admin
            $this->sendAdminEmail($admin, $notificationData);
        }
    }

    /**
     * Notifications spécifiques pour les organisateurs
     */
    private function notifyOrganizers(array $organizers, string $eventType, string $action, $entity, ?User $triggeringUser): void
    {
        foreach ($organizers as $organizer) {
            if (!$organizer->isNotifyByEmail()) {
                continue;
            }

            // Filtrer les notifications pertinentes pour l'organisateur
            if (!$this->isRelevantForOrganizer($organizer, $eventType, $action, $entity)) {
                continue;
            }

            $notificationData = $this->prepareOrganizerNotification($eventType, $action, $entity, $triggeringUser);
            
            // Notification en base de données
            $this->notificationService->createNotification(
                $organizer,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                $notificationData['event']
            );

            // Notification temps réel
            $this->realtimeNotificationService->createInstantNotification(
                $organizer,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                [
                    'priority' => 'normal',
                    'playSound' => false,
                    'persistToDatabase' => false
                ]
            );

            // Email spécifique organisateur
            $this->sendOrganizerEmail($organizer, $notificationData);
        }
    }

    /**
     * Notifications spécifiques pour les participants
     */
    private function notifyParticipants(array $participants, string $eventType, string $action, $entity, ?User $triggeringUser): void
    {
        foreach ($participants as $participant) {
            if (!$participant->isNotifyByEmail()) {
                continue;
            }

            // Filtrer les notifications pertinentes pour le participant
            if (!$this->isRelevantForParticipant($participant, $eventType, $action, $entity)) {
                continue;
            }

            $notificationData = $this->prepareParticipantNotification($eventType, $action, $entity, $triggeringUser);
            
            // Notification en base de données
            $this->notificationService->createNotification(
                $participant,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                $notificationData['event']
            );

            // Notification temps réel
            $this->realtimeNotificationService->createInstantNotification(
                $participant,
                $notificationData['title'],
                $notificationData['message'],
                $notificationData['type'],
                [
                    'priority' => 'normal',
                    'playSound' => false,
                    'persistToDatabase' => false
                ]
            );

            // Email spécifique participant
            $this->sendParticipantEmail($participant, $notificationData);
        }
    }

    /**
     * Prépare les données de notification pour les administrateurs
     */
    private function prepareAdminNotification(string $eventType, string $action, $entity, ?User $triggeringUser): array
    {
        $data = [
            'type' => $eventType . '_' . $action,
            'event' => $entity instanceof Event ? $entity : null,
            'entity' => $entity,
            'triggering_user' => $triggeringUser,
            'role' => 'admin'
        ];

        switch ($eventType) {
            case 'event':
                switch ($action) {
                    case 'created':
                        $data['title'] = '[ADMIN] Nouvel événement créé';
                        $data['message'] = sprintf(
                            "Un nouvel événement '%s' a été créé par %s pour le %s.",
                            $entity->getTitle(),
                            $triggeringUser ? $triggeringUser->getFullName() : 'un utilisateur',
                            $entity->getDateHeure()->format('d/m/Y à H:i')
                        );
                        break;
                    case 'updated':
                        $data['title'] = '[ADMIN] Événement modifié';
                        $data['message'] = sprintf(
                            "L'événement '%s' a été modifié par %s.",
                            $entity->getTitle(),
                            $triggeringUser ? $triggeringUser->getFullName() : 'un utilisateur'
                        );
                        break;
                    case 'cancelled':
                        $data['title'] = '[ADMIN] Événement annulé';
                        $data['message'] = sprintf(
                            "L'événement '%s' a été annulé par %s.",
                            $entity->getTitle(),
                            $triggeringUser ? $triggeringUser->getFullName() : 'un utilisateur'
                        );
                        break;
                }
                break;

            case 'salle':
                switch ($action) {
                    case 'created':
                        $data['title'] = '[ADMIN] Nouvelle salle créée';
                        $data['message'] = sprintf(
                            "Une nouvelle salle '%s' a été créée (capacité: %d).",
                            $entity->getNom(),
                            $entity->getCapacite()
                        );
                        break;
                    case 'updated':
                        $data['title'] = '[ADMIN] Salle modifiée';
                        $data['message'] = sprintf(
                            "La salle '%s' a été modifiée.",
                            $entity->getNom()
                        );
                        break;
                    case 'deleted':
                        $data['title'] = '[ADMIN] Salle supprimée';
                        $data['message'] = sprintf(
                            "La salle '%s' a été supprimée.",
                            $entity->getNom()
                        );
                        break;
                }
                break;

            case 'user':
                switch ($action) {
                    case 'registered':
                        $data['title'] = '[ADMIN] Nouvel utilisateur inscrit';
                        $data['message'] = sprintf(
                            "Un nouvel utilisateur %s (%s) s'est inscrit avec le rôle %s.",
                            $entity->getFullName(),
                            $entity->getEmail(),
                            $this->getRoleDisplayName($entity->getRoles()[0] ?? 'ROLE_PARTICIPANT')
                        );
                        break;
                    case 'role_changed':
                        $data['title'] = '[ADMIN] Rôle utilisateur modifié';
                        $data['message'] = sprintf(
                            "Le rôle de l'utilisateur %s a été modifié.",
                            $entity->getFullName()
                        );
                        break;
                }
                break;
        }

        return $data;
    }

    /**
     * Prépare les données de notification pour les organisateurs
     */
    private function prepareOrganizerNotification(string $eventType, string $action, $entity, ?User $triggeringUser): array
    {
        $data = [
            'type' => $eventType . '_' . $action,
            'event' => $entity instanceof Event ? $entity : null,
            'entity' => $entity,
            'triggering_user' => $triggeringUser,
            'role' => 'organizer'
        ];

        switch ($eventType) {
            case 'event':
                switch ($action) {
                    case 'created':
                        $data['title'] = 'Nouvel événement sur la plateforme';
                        $data['message'] = sprintf(
                            "Un nouvel événement '%s' a été créé pour le %s. Vous pourriez être intéressé par une collaboration.",
                            $entity->getTitle(),
                            $entity->getDateHeure()->format('d/m/Y à H:i')
                        );
                        break;
                    case 'updated':
                        $data['title'] = 'Événement modifié';
                        $data['message'] = sprintf(
                            "L'événement '%s' a été modifié. Vérifiez si cela affecte vos événements ou collaborations.",
                            $entity->getTitle()
                        );
                        break;
                    case 'cancelled':
                        $data['title'] = 'Événement annulé';
                        $data['message'] = sprintf(
                            "L'événement '%s' a été annulé. Cela peut libérer des créneaux pour vos propres événements.",
                            $entity->getTitle()
                        );
                        break;
                }
                break;

            case 'salle':
                switch ($action) {
                    case 'created':
                        $data['title'] = 'Nouvelle salle disponible';
                        $data['message'] = sprintf(
                            "Une nouvelle salle '%s' est maintenant disponible pour vos événements (capacité: %d).",
                            $entity->getNom(),
                            $entity->getCapacite()
                        );
                        break;
                    case 'updated':
                        $data['title'] = 'Salle modifiée';
                        $data['message'] = sprintf(
                            "La salle '%s' a été modifiée. Vérifiez si cela affecte vos événements programmés.",
                            $entity->getNom()
                        );
                        break;
                    case 'deleted':
                        $data['title'] = 'Salle supprimée';
                        $data['message'] = sprintf(
                            "La salle '%s' n'est plus disponible. Vérifiez vos événements programmés.",
                            $entity->getNom()
                        );
                        break;
                }
                break;
        }

        return $data;
    }

    /**
     * Prépare les données de notification pour les participants
     */
    private function prepareParticipantNotification(string $eventType, string $action, $entity, ?User $triggeringUser): array
    {
        $data = [
            'type' => $eventType . '_' . $action,
            'event' => $entity instanceof Event ? $entity : null,
            'entity' => $entity,
            'triggering_user' => $triggeringUser,
            'role' => 'participant'
        ];

        switch ($eventType) {
            case 'event':
                switch ($action) {
                    case 'created':
                        $data['title'] = 'Nouvel événement disponible';
                        $data['message'] = sprintf(
                            "Un nouvel événement '%s' est programmé pour le %s. Vous pouvez demander à y participer.",
                            $entity->getTitle(),
                            $entity->getDateHeure()->format('d/m/Y à H:i')
                        );
                        break;
                    case 'updated':
                        // Seulement si le participant est inscrit à l'événement
                        $data['title'] = 'Événement modifié';
                        $data['message'] = sprintf(
                            "L'événement '%s' auquel vous participez a été modifié. Consultez les nouvelles informations.",
                            $entity->getTitle()
                        );
                        break;
                    case 'cancelled':
                        // Seulement si le participant était inscrit à l'événement
                        $data['title'] = 'Événement annulé';
                        $data['message'] = sprintf(
                            "L'événement '%s' auquel vous étiez inscrit a été annulé. Nous vous en excusons.",
                            $entity->getTitle()
                        );
                        break;
                }
                break;
        }

        return $data;
    }

    /**
     * Détermine si une notification est pertinente pour un organisateur
     */
    private function isRelevantForOrganizer(User $organizer, string $eventType, string $action, $entity): bool
    {
        // Les organisateurs reçoivent des notifications sur :
        // - Nouveaux événements (pour collaboration)
        // - Modifications/suppressions de salles
        // - Modifications d'événements dans leurs salles favorites

        if ($eventType === 'salle') {
            return true; // Toujours pertinent pour les organisateurs
        }

        if ($eventType === 'event') {
            // Ne pas notifier l'organisateur de ses propres événements
            if ($entity instanceof Event && $entity->getOrganizer() === $organizer) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Détermine si une notification est pertinente pour un participant
     */
    private function isRelevantForParticipant(User $participant, string $eventType, string $action, $entity): bool
    {
        // Les participants reçoivent des notifications sur :
        // - Nouveaux événements (pour participation)
        // - Modifications/annulations d'événements auxquels ils participent

        if ($eventType === 'event') {
            if ($action === 'created') {
                return true; // Nouveaux événements disponibles
            }
            
            if ($action === 'updated' || $action === 'cancelled') {
                // Seulement si le participant est inscrit à l'événement
                if ($entity instanceof Event) {
                    foreach ($entity->getParticipations() as $participation) {
                        if ($participation->getUser() === $participant) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Récupère les utilisateurs par rôle
     */
    private function getUsersByRole(string $role): array
    {
        return $this->userRepository->findByRole($role);
    }

    /**
     * Envoie un email spécifique aux administrateurs
     */
    private function sendAdminEmail(User $admin, array $data): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($admin->getEmail())
                ->subject($data['title'])
                ->html($this->twig->render('emails/admin_role_notification.html.twig', $data));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email admin', [
                'error' => $e->getMessage(),
                'admin_id' => $admin->getId()
            ]);
        }
    }

    /**
     * Envoie un email spécifique aux organisateurs
     */
    private function sendOrganizerEmail(User $organizer, array $data): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($organizer->getEmail())
                ->subject($data['title'])
                ->html($this->twig->render('emails/organizer_role_notification.html.twig', $data));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email organisateur', [
                'error' => $e->getMessage(),
                'organizer_id' => $organizer->getId()
            ]);
        }
    }

    /**
     * Envoie un email spécifique aux participants
     */
    private function sendParticipantEmail(User $participant, array $data): void
    {
        try {
            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($participant->getEmail())
                ->subject($data['title'])
                ->html($this->twig->render('emails/participant_role_notification.html.twig', $data));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email participant', [
                'error' => $e->getMessage(),
                'participant_id' => $participant->getId()
            ]);
        }
    }

    /**
     * Convertit un rôle en nom d'affichage
     */
    private function getRoleDisplayName(string $role): string
    {
        return match($role) {
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_ORGANISATEUR' => 'Organisateur',
            'ROLE_PARTICIPANT' => 'Participant',
            default => 'Utilisateur'
        };
    }
} 