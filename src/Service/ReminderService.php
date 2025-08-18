<?php

namespace App\Service;

use App\Entity\Reminder;
use App\Entity\User;
use App\Entity\Event;
use App\Repository\ReminderRepository;
use App\Service\NotificationService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class ReminderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReminderRepository $reminderRepository,
        private NotificationService $notificationService,
        private MailerService $mailerService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Crée un rappel automatique pour un événement
     */
    public function createEventReminder(
        Event $event, 
        User $user, 
        \DateTimeInterface $dueDate,
        array $options = []
    ): Reminder {
        // Vérifier s'il existe déjà un rappel similaire
        $conflictingReminders = $this->reminderRepository->findConflictingReminders(
            $user, 
            $event, 
            $dueDate, 
            $options['tolerance_minutes'] ?? 15
        );

        if (!empty($conflictingReminders)) {
            $this->logger->info('Rappel existant trouvé pour cet événement', [
                'user_id' => $user->getId(),
                'event_id' => $event->getId(),
                'due_date' => $dueDate->format('Y-m-d H:i:s')
            ]);
            return $conflictingReminders[0];
        }

        $reminder = new Reminder();
        $reminder->setTitle($options['title'] ?? "Rappel pour {$event->getTitle()}");
        $reminder->setDescription($options['description'] ?? $this->generateEventReminderDescription($event));
        $reminder->setDueDate($dueDate);
        $reminder->setUser($user);
        $reminder->setEvent($event);
        $reminder->setType($options['type'] ?? 'event_reminder');
        $reminder->setPriority($options['priority'] ?? 'normal');

        // Configuration des notifications selon les préférences utilisateur
        $reminder->setSendEmail($user->isNotifyByEmail() && ($options['send_email'] ?? true));
        $reminder->setShowNotification($user->isEnableVisualNotifications() && ($options['show_notification'] ?? true));
        $reminder->setPlaySound($user->isEnableSoundNotifications() && ($options['play_sound'] ?? true));

        // Métadonnées additionnelles
        $reminder->setMetadata([
            'event_title' => $event->getTitle(),
            'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s'),
            'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
            'created_automatically' => true,
            'reminder_minutes_before' => $this->calculateMinutesBefore($event->getDateHeure(), $dueDate)
        ]);

        $this->entityManager->persist($reminder);
        $this->entityManager->flush();

        $this->logger->info('Nouveau rappel créé', [
            'reminder_id' => $reminder->getId(),
            'user_id' => $user->getId(),
            'event_id' => $event->getId()
        ]);

        return $reminder;
    }

    /**
     * Crée des rappels automatiques pour tous les participants d'un événement
     */
    public function createRemindersForEvent(Event $event, int $minutesBefore = 60): array
    {
        $reminders = [];
        $dueDate = (clone $event->getDateHeure())->modify("-{$minutesBefore} minutes");

        // Rappel pour l'organisateur
        if ($event->getOrganizer()) {
            $reminders[] = $this->createEventReminder(
                $event,
                $event->getOrganizer(),
                $dueDate,
                [
                    'title' => "Rappel - {$event->getTitle()}",
                    'description' => "Vous organisez cet événement",
                    'type' => 'organizer_reminder',
                    'priority' => 'high'
                ]
            );
        }

        // Rappels pour les participants
        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser()) {
                $reminders[] = $this->createEventReminder(
                    $event,
                    $participation->getUser(),
                    $dueDate,
                    [
                        'title' => "Rappel - {$event->getTitle()}",
                        'description' => "Vous participez à cet événement",
                        'type' => 'participant_reminder',
                        'priority' => 'normal'
                    ]
                );
            }
        }

        // Rappels pour les invités avec invitations acceptées
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getStatus() === 'accepted' && $invitation->getInvite()) {
                $reminders[] = $this->createEventReminder(
                    $event,
                    $invitation->getInvite(),
                    $dueDate,
                    [
                        'title' => "Rappel - {$event->getTitle()}",
                        'description' => "Vous êtes invité à cet événement",
                        'type' => 'invite_reminder',
                        'priority' => 'normal'
                    ]
                );
            }
        }

        $this->logger->info('Rappels créés pour événement', [
            'event_id' => $event->getId(),
            'reminders_count' => count($reminders)
        ]);

        return $reminders;
    }

    /**
     * Vérifie et déclenche tous les rappels en attente
     */
    public function processPendingReminders(): array
    {
        $pendingReminders = $this->reminderRepository->findPendingReminders();
        $processedReminders = [];

        foreach ($pendingReminders as $reminder) {
            if ($reminder->shouldTrigger()) {
                try {
                    $this->triggerReminder($reminder);
                    $processedReminders[] = $reminder;
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors du déclenchement du rappel', [
                        'reminder_id' => $reminder->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if (!empty($processedReminders)) {
            $this->entityManager->flush();
            $this->logger->info('Rappels traités', [
                'count' => count($processedReminders)
            ]);
        }

        return $processedReminders;
    }

    /**
     * Déclenche un rappel spécifique
     */
    public function triggerReminder(Reminder $reminder): bool
    {
        try {
            // Marquer comme déclenché
            $reminder->trigger();

            $config = $reminder->getNotificationConfig();

            // Envoyer notification en base de données
            if ($config['showNotification']) {
                $this->notificationService->createNotification(
                    $reminder->getUser(),
                    $reminder->getTitle(),
                    $reminder->getFormattedMessage(),
                    'reminder',
                    $reminder->getEvent()
                );
            }

            // Envoyer email si nécessaire
            if ($config['sendEmail'] && $reminder->getEvent()) {
                $this->mailerService->sendReminderEmail($reminder->getUser(), $reminder->getEvent());
            }

            $this->logger->info('Rappel déclenché avec succès', [
                'reminder_id' => $reminder->getId(),
                'user_id' => $reminder->getUser()->getId(),
                'email_sent' => $config['sendEmail'],
                'notification_sent' => $config['showNotification']
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du déclenchement du rappel', [
                'reminder_id' => $reminder->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Trouve les rappels à venir pour un utilisateur
     */
    public function getUpcomingRemindersForUser(User $user, int $minutes = 60): array
    {
        return $this->reminderRepository->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.isDone = :done')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.dueDate BETWEEN :now AND :future')
            ->setParameter('user', $user)
            ->setParameter('done', false)
            ->setParameter('triggered', false)
            ->setParameter('now', new \DateTime())
            ->setParameter('future', (new \DateTime())->modify("+{$minutes} minutes"))
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les rappels à venir (sans filtrer par utilisateur)
     */
    public function getAllUpcomingReminders(int $minutes = 5): array
    {
        return $this->reminderRepository->createQueryBuilder('r')
            ->andWhere('r.isDone = :done')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.dueDate BETWEEN :now AND :future')
            ->setParameter('done', false)
            ->setParameter('triggered', false)
            ->setParameter('now', new \DateTime())
            ->setParameter('future', (new \DateTime())->modify("+{$minutes} minutes"))
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Annule tous les rappels pour un événement annulé
     */
    public function cancelRemindersForEvent(Event $event): int
    {
        $reminders = $this->reminderRepository->findRemindersByEvent($event);
        $cancelledCount = 0;

        foreach ($reminders as $reminder) {
            if (!$reminder->isTriggered()) {
                $reminder->markAsDone();
                $cancelledCount++;
            }
        }

        if ($cancelledCount > 0) {
            $this->entityManager->flush();
            $this->logger->info('Rappels annulés pour événement', [
                'event_id' => $event->getId(),
                'cancelled_count' => $cancelledCount
            ]);
        }

        return $cancelledCount;
    }

    /**
     * Nettoie les anciens rappels
     */
    public function cleanupOldReminders(int $daysOld = 30): int
    {
        $deletedCount = $this->reminderRepository->cleanupOldReminders($daysOld);
        
        $this->logger->info('Nettoyage des anciens rappels', [
            'deleted_count' => $deletedCount,
            'days_old' => $daysOld
        ]);

        return $deletedCount;
    }

    /**
     * Génère une description pour un rappel d'événement
     */
    private function generateEventReminderDescription(Event $event): string
    {
        $description = "N'oubliez pas votre événement '{$event->getTitle()}'";
        
        if ($event->getDateHeure()) {
            $description .= " prévu le " . $event->getDateHeure()->format('d/m/Y à H:i');
        }
        
        if ($event->getSalle()) {
            $description .= " en salle " . $event->getSalle()->getNom();
        }

        return $description;
    }

    /**
     * Calcule les minutes entre deux dates
     */
    private function calculateMinutesBefore(\DateTimeInterface $eventDate, \DateTimeInterface $reminderDate): int
    {
        $diff = $eventDate->getTimestamp() - $reminderDate->getTimestamp();
        return max(0, intval($diff / 60));
    }

    /**
     * Crée des rappels multiples selon un planning
     */
    public function createReminderSchedule(Event $event, array $schedule = [60, 15]): array
    {
        $reminders = [];
        
        foreach ($schedule as $minutesBefore) {
            $dueDate = (clone $event->getDateHeure())->modify("-{$minutesBefore} minutes");
            
            // Éviter les rappels dans le passé
            if ($dueDate > new \DateTime()) {
                $eventReminders = $this->createRemindersForEvent($event, $minutesBefore);
                $reminders = array_merge($reminders, $eventReminders);
            }
        }

        return $reminders;
    }

    /**
     * Obtient les statistiques des rappels pour un utilisateur
     */
    public function getUserReminderStats(User $user): array
    {
        $total = $this->reminderRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $triggered = $this->reminderRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.isTriggered = :triggered')
            ->setParameter('user', $user)
            ->setParameter('triggered', true)
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $this->reminderRepository->countActiveRemindersByUser($user);

        return [
            'total' => $total,
            'triggered' => $triggered,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($triggered / $total) * 100, 2) : 0
        ];
    }
} 