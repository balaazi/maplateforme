<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Reminder;
use App\Entity\Notification;
use App\Repository\ReminderRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RealtimeNotificationService
{
    private array $activeConnections = [];
    private array $userNotificationQueue = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReminderRepository $reminderRepository,
        private NotificationService $notificationService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Vérifie et traite les rappels en temps réel
     */
    public function checkAndProcessReminders(): array
    {
        $triggeredReminders = [];
        $pendingReminders = $this->reminderRepository->findUpcomingReminders(1); // 1 minute

        foreach ($pendingReminders as $reminder) {
            if ($reminder->shouldTrigger()) {
                $notificationData = $this->createReminderNotification($reminder);
                $this->queueNotificationForUser($reminder->getUser(), $notificationData);
                $triggeredReminders[] = $reminder;
                
                $this->logger->info('Rappel déclenché en temps réel', [
                    'reminder_id' => $reminder->getId(),
                    'user_id' => $reminder->getUser()->getId()
                ]);
            }
        }

        return $triggeredReminders;
    }

    /**
     * Crée une notification en temps réel pour un rappel
     */
    public function createReminderNotification(Reminder $reminder): array
    {
        $config = $reminder->getNotificationConfig();
        
        return [
            'id' => uniqid('reminder_'),
            'type' => 'reminder',
            'priority' => $reminder->getPriority(),
            'title' => $reminder->getTitle(),
            'message' => $reminder->getFormattedMessage(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'event' => $reminder->getEvent() ? [
                'id' => $reminder->getEvent()->getId(),
                'title' => $reminder->getEvent()->getTitle(),
                'date' => $reminder->getEvent()->getDateHeure()->format('Y-m-d H:i:s'),
                'location' => $reminder->getEvent()->getSalle()?->getNom() ?? 'Non défini'
            ] : null,
            'actions' => [
                'dismiss' => true,
                'snooze' => true,
                'viewEvent' => $reminder->getEvent() ? true : false
            ],
            'config' => [
                'showVisualAlert' => $config['showNotification'],
                'playSound' => $config['playSound'],
                'duration' => $this->calculateNotificationDuration($reminder->getPriority()),
                'soundFile' => $this->getSoundFile($reminder->getType(), $reminder->getPriority()),
                'style' => $this->getNotificationStyle($reminder->getType(), $reminder->getPriority())
            ],
            'metadata' => $reminder->getMetadata()
        ];
    }

    /**
     * Ajoute une notification à la queue d'un utilisateur
     */
    public function queueNotificationForUser(User $user, array $notificationData): void
    {
        $userId = $user->getId();
        
        if (!isset($this->userNotificationQueue[$userId])) {
            $this->userNotificationQueue[$userId] = [];
        }
        
        $this->userNotificationQueue[$userId][] = $notificationData;
        
        $this->logger->debug('Notification ajoutée à la queue', [
            'user_id' => $userId,
            'notification_type' => $notificationData['type'],
            'queue_size' => count($this->userNotificationQueue[$userId])
        ]);
    }

    /**
     * Récupère les notifications en attente pour un utilisateur
     */
    public function getPendingNotificationsForUser(User $user): array
    {
        $userId = $user->getId();
        $notifications = $this->userNotificationQueue[$userId] ?? [];
        
        // Vider la queue après récupération
        unset($this->userNotificationQueue[$userId]);
        
        // Ajouter également les nouvelles notifications de base de données
        $dbNotifications = $this->getRecentDatabaseNotifications($user);
        
        return array_merge($notifications, $dbNotifications);
    }

    /**
     * Crée une notification instantanée
     */
    public function createInstantNotification(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        array $options = []
    ): array {
        $notificationData = [
            'id' => uniqid('instant_'),
            'type' => $type,
            'priority' => $options['priority'] ?? 'normal',
            'title' => $title,
            'message' => $message,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'actions' => $options['actions'] ?? ['dismiss' => true],
            'config' => [
                'showVisualAlert' => $options['showVisualAlert'] ?? true,
                'playSound' => $options['playSound'] ?? false,
                'duration' => $options['duration'] ?? 5000,
                'soundFile' => $options['soundFile'] ?? $this->getSoundFile($type),
                'style' => $options['style'] ?? $this->getNotificationStyle($type)
            ],
            'metadata' => $options['metadata'] ?? []
        ];

        $this->queueNotificationForUser($user, $notificationData);
        
        // Créer aussi en base de données si demandé
        if ($options['persistToDatabase'] ?? true) {
            $this->notificationService->createNotification($user, $title, $message, $type);
        }

        return $notificationData;
    }

    /**
     * Crée une notification de rappel urgente
     */
    public function createUrgentReminder(
        User $user,
        string $title,
        string $message,
        array $options = []
    ): array {
        return $this->createInstantNotification($user, $title, $message, 'urgent_reminder', array_merge([
            'priority' => 'high',
            'playSound' => true,
            'duration' => 10000,
            'showVisualAlert' => true,
            'actions' => [
                'dismiss' => true,
                'snooze' => true,
                'acknowledge' => true
            ]
        ], $options));
    }



    /**
     * Récupère les notifications récentes de la base de données
     */
    private function getRecentDatabaseNotifications(User $user, int $minutes = 5): array
    {
        $since = (new \DateTime())->modify("-{$minutes} minutes");
        
        $notifications = $this->entityManager->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.createdAt >= :since')
            ->andWhere('n.isRead = :read')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->setParameter('read', false)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => 'db_' . $notification->getId(),
                'type' => $notification->getType(),
                'priority' => 'normal',
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'timestamp' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'event' => $notification->getEvent() ? [
                    'id' => $notification->getEvent()->getId(),
                    'title' => $notification->getEvent()->getTitle()
                ] : null,
                'actions' => ['dismiss' => true, 'markRead' => true],
                'config' => [
                    'showVisualAlert' => true,
                    'playSound' => false,
                    'duration' => 5000,
                    'style' => $this->getNotificationStyle($notification->getType())
                ],
                'databaseId' => $notification->getId()
            ];
        }

        return $result;
    }

    /**
     * Calcule la durée d'affichage selon la priorité
     */
    private function calculateNotificationDuration(string $priority): int
    {
        return match($priority) {
            'high' => 10000,     // 10 secondes
            'normal' => 5000,    // 5 secondes
            'low' => 3000,       // 3 secondes
            default => 5000
        };
    }

    /**
     * Retourne le fichier audio selon le type et la priorité
     */
    private function getSoundFile(string $type, string $priority = 'normal'): ?string
    {
        $sounds = [
            'reminder' => [
                'high' => '/sounds/urgent-reminder.mp3',
                'normal' => '/sounds/reminder.mp3',
                'low' => '/sounds/soft-reminder.mp3'
            ],
            'urgent_reminder' => [
                'high' => '/sounds/urgent-alert.mp3',
                'normal' => '/sounds/urgent-reminder.mp3',
                'low' => '/sounds/reminder.mp3'
            ],
            'event_update' => [
                'high' => '/sounds/update-important.mp3',
                'normal' => '/sounds/update.mp3',
                'low' => '/sounds/soft-update.mp3'
            ],
            'invitation' => [
                'high' => '/sounds/invitation-urgent.mp3',
                'normal' => '/sounds/invitation.mp3',
                'low' => '/sounds/soft-invitation.mp3'
            ],
            'info' => [
                'normal' => '/sounds/info.mp3',
                'low' => '/sounds/soft-info.mp3'
            ],

        ];

        return $sounds[$type][$priority] ?? $sounds[$type]['normal'] ?? null;
    }

    /**
     * Retourne le style CSS selon le type de notification
     */
    private function getNotificationStyle(string $type, string $priority = 'normal'): array
    {
        $baseStyles = [
            'reminder' => [
                'backgroundColor' => '#ffc107',
                'borderColor' => '#ffca2c',
                'textColor' => '#212529',
                'icon' => 'fas fa-clock'
            ],
            'urgent_reminder' => [
                'backgroundColor' => '#dc3545',
                'borderColor' => '#c82333',
                'textColor' => '#ffffff',
                'icon' => 'fas fa-exclamation-triangle',
                'animation' => 'pulse'
            ],
            'event_update' => [
                'backgroundColor' => '#17a2b8',
                'borderColor' => '#138496',
                'textColor' => '#ffffff',
                'icon' => 'fas fa-edit'
            ],
            'invitation' => [
                'backgroundColor' => '#28a745',
                'borderColor' => '#1e7e34',
                'textColor' => '#ffffff',
                'icon' => 'fas fa-envelope'
            ],
            'info' => [
                'backgroundColor' => '#6c757d',
                'borderColor' => '#545b62',
                'textColor' => '#ffffff',
                'icon' => 'fas fa-info-circle'
            ],

        ];

        $style = $baseStyles[$type] ?? $baseStyles['info'];
        
        // Modifications selon la priorité
        if ($priority === 'high') {
            $style['animation'] = 'bounce';
            $style['boxShadow'] = '0 0 20px rgba(220, 53, 69, 0.5)';
        }

        return $style;
    }

    /**
     * Nettoie les anciennes notifications en queue
     */
    public function cleanupNotificationQueues(int $maxAgeMinutes = 30): int
    {
        $cleaned = 0;
        $cutoff = (new \DateTime())->modify("-{$maxAgeMinutes} minutes");
        
        foreach ($this->userNotificationQueue as $userId => $notifications) {
            $this->userNotificationQueue[$userId] = array_filter($notifications, function($notification) use ($cutoff) {
                $notificationTime = new \DateTime($notification['timestamp']);
                return $notificationTime > $cutoff;
            });
            
            $cleaned += count($notifications) - count($this->userNotificationQueue[$userId]);
            
            if (empty($this->userNotificationQueue[$userId])) {
                unset($this->userNotificationQueue[$userId]);
            }
        }

        if ($cleaned > 0) {
            $this->logger->info('Nettoyage des queues de notifications', [
                'cleaned_count' => $cleaned
            ]);
        }

        return $cleaned;
    }

    /**
     * Obtient les statistiques des notifications en temps réel
     */
    public function getRealtimeStats(): array
    {
        $queuedNotifications = array_sum(array_map('count', $this->userNotificationQueue));
        $activeUsers = count($this->userNotificationQueue);
        
        return [
            'queued_notifications' => $queuedNotifications,
            'active_users' => $activeUsers,
            'average_queue_size' => $activeUsers > 0 ? round($queuedNotifications / $activeUsers, 2) : 0,
            'memory_usage' => memory_get_usage(true),
            'uptime' => $this->getServiceUptime()
        ];
    }

    /**
     * Retourne le temps de fonctionnement du service
     */
    private function getServiceUptime(): string
    {
        // Placeholder - in real implementation this would track service start time
        return 'N/A';
    }
} 