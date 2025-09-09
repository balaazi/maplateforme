<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Reminder;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EventReminderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private ReminderRepository $reminderRepository,
        private ReminderService $reminderService,
        private NotificationService $notificationService,
        private MailerService $mailerService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Crée des rappels 24h avant pour tous les événements à venir dans la plage spécifiée
     */
    public function createRemindersForUpcomingEvents(int $daysAhead = 7): array
    {
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify("+{$daysAhead} days");
        
        // Récupérer tous les événements à venir dans la plage spécifiée
        $events = $this->eventRepository->findByDateRange($startDate, $endDate);
        
        $createdReminders = [];
        
        foreach ($events as $event) {
            // Ignorer les événements annulés
            if ($event->getStatus() === 'annulé') {
                continue;
            }
            
            // Créer des rappels 24h avant l'événement
            $eventReminders = $this->createRemindersForEvent($event, 24 * 60); // 24 heures en minutes
            $createdReminders = array_merge($createdReminders, $eventReminders);
        }
        
        $this->logger->info('Rappels 24h créés pour événements à venir', [
            'events_count' => count($events),
            'reminders_created' => count($createdReminders)
        ]);
        
        return $createdReminders;
    }

    /**
     * Crée des rappels 24h avant pour un événement spécifique
     */
    public function createRemindersForEvent(Event $event, int $minutesBefore = 1440): array
    {
        // Calculer la date d'échéance du rappel (24h avant l'événement)
        $eventDate = $event->getDateHeure();
        if (!$eventDate) {
            return [];
        }
        
        $dueDate = (clone $eventDate)->modify("-{$minutesBefore} minutes");
        
        // Si la date d'échéance est déjà passée, ne pas créer de rappel
        if ($dueDate <= new \DateTime()) {
            return [];
        }
        
        $reminders = [];
        
        // 1. Rappel pour l'organisateur
        if ($event->getOrganizer()) {
            $reminders[] = $this->createReminderForUser(
                $event,
                $event->getOrganizer(),
                $dueDate,
                'Rappel: événement demain - ' . $event->getTitle(),
                'Vous organisez cet événement demain'
            );
        }
        
        // 2. Rappels pour les participants (via participations)
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user && $user->getId() !== $event->getOrganizer()?->getId()) {
                $reminders[] = $this->createReminderForUser(
                    $event,
                    $user,
                    $dueDate,
                    'Rappel: événement demain - ' . $event->getTitle(),
                    'Vous participez à cet événement demain'
                );
            }
        }
        
        // 3. Rappels pour les invités avec invitations acceptées
        foreach ($event->getInvitations() as $invitation) {
            $invitedUser = $invitation->getInvite();
            if ($invitedUser && $invitation->getStatus() === 'accepted' && 
                $invitedUser->getId() !== $event->getOrganizer()?->getId()) {
                $reminders[] = $this->createReminderForUser(
                    $event,
                    $invitedUser,
                    $dueDate,
                    'Rappel: événement demain - ' . $event->getTitle(),
                    'Vous êtes invité à cet événement demain'
                );
            }
        }
        
        return array_filter($reminders);
    }
    
    /**
     * Crée un rappel pour un utilisateur spécifique
     */
    private function createReminderForUser(
        Event $event,
        User $user,
        \DateTimeInterface $dueDate,
        string $title,
        string $description
    ): ?Reminder {
        // Vérifier si un rappel similaire existe déjà
        $existingReminders = $this->reminderRepository->findConflictingReminders(
            $user,
            $event,
            $dueDate,
            30 // Tolérance de 30 minutes
        );
        
        if (!empty($existingReminders)) {
            return null;
        }
        
        try {
            $reminder = new Reminder();
            $reminder->setTitle($title);
            $reminder->setDescription($description);
            $reminder->setDueDate($dueDate);
            $reminder->setUser($user);
            $reminder->setEvent($event);
            $reminder->setType('event_reminder_24h');
            $reminder->setPriority('high');
            
            // Configuration des notifications selon les préférences utilisateur
            $reminder->setSendEmail($user->isNotifyByEmail());
            $reminder->setShowNotification($user->isEnableVisualNotifications());
            $reminder->setPlaySound($user->isEnableSoundNotifications());
            
            // Métadonnées additionnelles
            $reminder->setMetadata([
                'event_title' => $event->getTitle(),
                'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s'),
                'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
                'created_automatically' => true,
                'reminder_hours_before' => 24
            ]);
            
            $this->entityManager->persist($reminder);
            $this->entityManager->flush();
            
            $this->logger->info('Rappel 24h créé', [
                'reminder_id' => $reminder->getId(),
                'user_id' => $user->getId(),
                'event_id' => $event->getId()
            ]);
            
            return $reminder;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du rappel 24h', [
                'user_id' => $user->getId(),
                'event_id' => $event->getId(),
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Envoie les rappels 24h avant pour les événements du lendemain
     */
    public function sendDailyReminders(): array
    {
        // Récupérer les événements de demain
        $tomorrow = (new \DateTime())->modify('+1 day')->setTime(0, 0, 0);
        $afterTomorrow = (new \DateTime())->modify('+2 days')->setTime(0, 0, 0);
        
        $events = $this->eventRepository->findByDateRange($tomorrow, $afterTomorrow);
        $sentReminders = [];
        $usersNotified = [];
        
        foreach ($events as $event) {
            // Ignorer les événements annulés
            if ($event->getStatus() === 'annulé') {
                continue;
            }
            
            // 1. Notifier l'organisateur
            if ($event->getOrganizer()) {
                $organizer = $event->getOrganizer();
                $uniqueKey = $organizer->getId() . '_' . $event->getId();
                
                if (!in_array($uniqueKey, $usersNotified)) {
                    try {
                        if ($organizer->isNotifyByEmail()) {
                            $this->mailerService->sendReminderEmail($organizer, $event);
                        }
                        
                        if ($organizer->isEnableVisualNotifications()) {
                            $this->notificationService->createEventReminderNotification($organizer, $event);
                        }
                        
                        $usersNotified[] = $uniqueKey;
                        $sentReminders[] = [
                            'event' => $event->getTitle(),
                            'user' => $organizer->getFullName(),
                            'type' => 'organizer'
                        ];
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur envoi rappel 24h organisateur', [
                            'user' => $organizer->getFullName(),
                            'event' => $event->getTitle(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // 2. Notifier les participants
            foreach ($event->getParticipations() as $participation) {
                $user = $participation->getUser();
                if ($user && $user->getId() !== $event->getOrganizer()?->getId()) {
                    $uniqueKey = $user->getId() . '_' . $event->getId();
                    
                    if (!in_array($uniqueKey, $usersNotified)) {
                        try {
                            if ($user->isNotifyByEmail()) {
                                $this->mailerService->sendReminderEmail($user, $event);
                            }
                            
                            if ($user->isEnableVisualNotifications()) {
                                $this->notificationService->createEventReminderNotification($user, $event);
                            }
                            
                            $usersNotified[] = $uniqueKey;
                            $sentReminders[] = [
                                'event' => $event->getTitle(),
                                'user' => $user->getFullName(),
                                'type' => 'participant'
                            ];
                        } catch (\Exception $e) {
                            $this->logger->error('Erreur envoi rappel 24h participant', [
                                'user' => $user->getFullName(),
                                'event' => $event->getTitle(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // 3. Notifier les invités avec invitations acceptées
            foreach ($event->getInvitations() as $invitation) {
                $invitedUser = $invitation->getInvite();
                if ($invitedUser && $invitation->getStatus() === 'accepted' && 
                    $invitedUser->getId() !== $event->getOrganizer()?->getId()) {
                    $uniqueKey = $invitedUser->getId() . '_' . $event->getId();
                    
                    if (!in_array($uniqueKey, $usersNotified)) {
                        try {
                            if ($invitedUser->isNotifyByEmail()) {
                                $this->mailerService->sendReminderEmail($invitedUser, $event);
                            }
                            
                            if ($invitedUser->isEnableVisualNotifications()) {
                                $this->notificationService->createEventReminderNotification($invitedUser, $event);
                            }
                            
                            $usersNotified[] = $uniqueKey;
                            $sentReminders[] = [
                                'event' => $event->getTitle(),
                                'user' => $invitedUser->getFullName(),
                                'type' => 'invited'
                            ];
                        } catch (\Exception $e) {
                            $this->logger->error('Erreur envoi rappel 24h invité', [
                                'user' => $invitedUser->getFullName(),
                                'event' => $event->getTitle(),
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }
        
        $this->logger->info('Rappels 24h envoyés', [
            'events_count' => count($events),
            'users_notified' => count($usersNotified)
        ]);
        
        return $sentReminders;
    }
}
