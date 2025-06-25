<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Event;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Crée une nouvelle notification pour un utilisateur
     */
    public function createNotification(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        ?Event $event = null
    ): Notification {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType($type);
        
        if ($event) {
            $notification->setEvent($event);
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une notification de rappel d'événement
     */
    public function createEventReminderNotification(User $user, Event $event): Notification
    {
        $title = "Rappel d'événement";
        $message = sprintf(
            "N'oubliez pas l'événement '%s' qui aura lieu le %s à %s.",
            $event->getTitle(),
            $event->getDateHeure()->format('d/m/Y'),
            $event->getDateHeure()->format('H:i')
        );

        return $this->createNotification($user, $title, $message, 'event_reminder', $event);
    }

    /**
     * Crée une notification de modification d'événement
     */
    public function createEventUpdateNotification(User $user, Event $event): Notification
    {
        $title = "Événement modifié";
        $message = sprintf(
            "L'événement '%s' a été modifié. Consultez les nouvelles informations.",
            $event->getTitle()
        );

        return $this->createNotification($user, $title, $message, 'event_update', $event);
    }

    /**
     * Crée une notification d'annulation d'événement
     */
    public function createEventCancelNotification(User $user, Event $event): Notification
    {
        $title = "Événement annulé";
        $message = sprintf(
            "L'événement '%s' prévu le %s a été annulé.",
            $event->getTitle(),
            $event->getDateHeure()->format('d/m/Y à H:i')
        );

        return $this->createNotification($user, $title, $message, 'event_cancel', $event);
    }

    /**
     * Crée une notification d'invitation
     */
    public function createInvitationNotification(User $user, Event $event): Notification
    {
        $title = "Nouvelle invitation";
        $message = sprintf(
            "Vous avez été invité(e) à l'événement '%s' le %s.",
            $event->getTitle(),
            $event->getDateHeure()->format('d/m/Y à H:i')
        );

        return $this->createNotification($user, $title, $message, 'invitation', $event);
    }

    /**
     * Crée une notification de bienvenue
     */
    public function createWelcomeNotification(User $user): Notification
    {
        $title = "Bienvenue sur EventHub !";
        $message = sprintf(
            "Bonjour %s ! Bienvenue sur EventHub. Vous pouvez maintenant gérer vos événements et participations.",
            $user->getPrenom()
        );

        return $this->createNotification($user, $title, $message, 'welcome');
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsReadForUser(User $user): void
    {
        $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Récupère les notifications d'un utilisateur
     */
    public function getNotificationsForUser(User $user, int $limit = 50): array
    {
        return $this->notificationRepository->findByUserOrderedByDate($user, $limit);
    }

    /**
     * Compte les notifications non lues d'un utilisateur
     */
    public function getUnreadCountForUser(User $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }

    /**
     * Nettoie les anciennes notifications
     */
    public function cleanOldNotifications(): void
    {
        $this->notificationRepository->deleteOldNotifications();
    }
} 