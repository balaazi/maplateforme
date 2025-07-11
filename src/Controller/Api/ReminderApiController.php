<?php

namespace App\Controller\Api;

use App\Service\ReminderService;
use App\Service\RealtimeNotificationService;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/api/reminders')]
#[IsGranted('ROLE_USER')]
class ReminderApiController extends AbstractController
{
    public function __construct(
        private ReminderService $reminderService,
        private RealtimeNotificationService $realtimeNotificationService,
        private ReminderRepository $reminderRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Vérifie les rappels en attente pour l'utilisateur connecté
     */
    #[Route('/check', name: 'api_reminders_check', methods: ['GET'])]
    public function checkReminders(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            // Vérifier les rappels à venir dans les 5 prochaines minutes
            $upcomingReminders = $this->reminderService->getUpcomingRemindersForUser($user, 5);
            
            // Récupérer les notifications en attente
            $pendingNotifications = $this->realtimeNotificationService->getPendingNotificationsForUser($user);
            
            // Compter les rappels actifs
            $activeRemindersCount = $this->reminderRepository->countActiveRemindersByUser($user);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'upcoming_reminders' => array_map([$this, 'formatReminderForApi'], $upcomingReminders),
                    'pending_notifications' => $pendingNotifications,
                    'active_reminders_count' => $activeRemindersCount,
                    'has_urgent' => $this->hasUrgentReminders($upcomingReminders, $pendingNotifications)
                ],
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification des rappels', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la vérification des rappels'
            ], 500);
        }
    }

    /**
     * Traite manuellement les rappels en attente
     */
    #[Route('/process', name: 'api_reminders_process', methods: ['POST'])]
    public function processReminders(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            // Traiter les rappels en attente de manière globale
            $processedReminders = $this->reminderService->processPendingReminders();
            
            // Vérifier et traiter les rappels en temps réel
            $realtimeReminders = $this->realtimeNotificationService->checkAndProcessReminders();
            
            $userReminders = array_filter($processedReminders, function($reminder) use ($user) {
                return $reminder->getUser()->getId() === $user->getId();
            });
            
            return $this->json([
                'success' => true,
                'data' => [
                    'processed_count' => count($userReminders),
                    'processed_reminders' => array_map([$this, 'formatReminderForApi'], $userReminders),
                    'realtime_count' => count($realtimeReminders)
                ],
                'message' => count($userReminders) > 0 ? 
                    sprintf('%d rappel(s) traité(s)', count($userReminders)) : 
                    'Aucun rappel à traiter'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement des rappels', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du traitement des rappels'
            ], 500);
        }
    }

    /**
     * Récupère les rappels de l'utilisateur
     */
    #[Route('/list', name: 'api_reminders_list', methods: ['GET'])]
    public function listReminders(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            $onlyActive = $request->query->getBoolean('active', true);
            $reminders = $this->reminderRepository->findRemindersByUser($user, $onlyActive);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'reminders' => array_map([$this, 'formatReminderForApi'], $reminders),
                    'total_count' => count($reminders),
                    'active_only' => $onlyActive
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des rappels', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des rappels'
            ], 500);
        }
    }



    /**
     * Marque un rappel comme terminé
     */
    #[Route('/{id}/mark-done', name: 'api_reminder_mark_done', methods: ['POST'])]
    public function markReminderDone(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            $reminder = $this->reminderRepository->find($id);
            
            if (!$reminder) {
                return $this->json([
                    'success' => false,
                    'error' => 'Rappel non trouvé'
                ], 404);
            }
            
            if ($reminder->getUser() !== $user) {
                return $this->json([
                    'success' => false,
                    'error' => 'Accès non autorisé'
                ], 403);
            }
            
            $reminder->markAsDone();
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'data' => [
                    'reminder' => $this->formatReminderForApi($reminder),
                    'message' => 'Rappel marqué comme terminé'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la modification du rappel', [
                'user_id' => $user->getId(),
                'reminder_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la modification du rappel'
            ], 500);
        }
    }

    /**
     * Crée une notification urgente
     */
    #[Route('/urgent-notification', name: 'api_reminder_urgent_notification', methods: ['POST'])]
    public function createUrgentNotification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            $title = $data['title'] ?? 'Rappel urgent';
            $message = $data['message'] ?? 'Vous avez un rappel urgent.';
            $options = $data['options'] ?? [];
            
            $notification = $this->realtimeNotificationService->createUrgentReminder(
                $user,
                $title,
                $message,
                $options
            );
            
            return $this->json([
                'success' => true,
                'data' => [
                    'notification' => $notification,
                    'message' => 'Notification urgente créée'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la notification urgente', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la création de la notification urgente'
            ], 500);
        }
    }

    /**
     * Récupère les statistiques des rappels de l'utilisateur
     */
    #[Route('/stats', name: 'api_reminders_stats', methods: ['GET'])]
    public function getReminderStats(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        try {
            $stats = $this->reminderService->getUserReminderStats($user);
            $realtimeStats = $this->realtimeNotificationService->getRealtimeStats();
            
            return $this->json([
                'success' => true,
                'data' => [
                    'user_stats' => $stats,
                    'system_stats' => $realtimeStats
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des statistiques', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Nettoie les anciennes notifications
     */
    #[Route('/cleanup', name: 'api_reminders_cleanup', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cleanup(): JsonResponse
    {
        try {
            $cleanedReminders = $this->reminderService->cleanupOldReminders(30);
            $cleanedNotifications = $this->realtimeNotificationService->cleanupNotificationQueues(30);
            
            return $this->json([
                'success' => true,
                'data' => [
                    'cleaned_reminders' => $cleanedReminders,
                    'cleaned_notifications' => $cleanedNotifications,
                    'message' => sprintf(
                        '%d ancien(s) rappel(s) et %d notification(s) nettoyé(s)',
                        $cleanedReminders,
                        $cleanedNotifications
                    )
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du nettoyage', [
                'error' => $e->getMessage()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du nettoyage'
            ], 500);
        }
    }

    /**
     * Formate un rappel pour l'API
     */
    private function formatReminderForApi($reminder): array
    {
        return [
            'id' => $reminder->getId(),
            'title' => $reminder->getTitle(),
            'description' => $reminder->getDescription(),
            'due_date' => $reminder->getDueDate()->format('Y-m-d H:i:s'),
            'is_done' => $reminder->isDone(),
            'is_triggered' => $reminder->isTriggered(),
            'triggered_at' => $reminder->getTriggeredAt()?->format('Y-m-d H:i:s'),
            'type' => $reminder->getType(),
            'priority' => $reminder->getPriority(),
            'created_at' => $reminder->getCreatedAt()->format('Y-m-d H:i:s'),
            'event' => $reminder->getEvent() ? [
                'id' => $reminder->getEvent()->getId(),
                'title' => $reminder->getEvent()->getTitle(),
                'date' => $reminder->getEvent()->getDateHeure()->format('Y-m-d H:i:s'),
                'location' => $reminder->getEvent()->getSalle()?->getNom() ?? 'Non défini'
            ] : null,
            'config' => $reminder->getNotificationConfig(),
            'time_until_due' => $this->formatTimeUntilDue($reminder),
            'is_overdue' => $reminder->isOverdue(),
            'formatted_message' => $reminder->getFormattedMessage()
        ];
    }

    /**
     * Formate le temps restant jusqu'à l'échéance
     */
    private function formatTimeUntilDue($reminder): ?string
    {
        $interval = $reminder->getTimeUntilDue();
        
        if (!$interval) {
            return null;
        }
        
        if ($interval->d > 0) {
            return sprintf('%d jour(s)', $interval->d);
        } elseif ($interval->h > 0) {
            return sprintf('%d heure(s)', $interval->h);
        } elseif ($interval->i > 0) {
            return sprintf('%d minute(s)', $interval->i);
        } else {
            return 'Maintenant';
        }
    }

    /**
     * Vérifie s'il y a des rappels urgents
     */
    private function hasUrgentReminders(array $upcomingReminders, array $pendingNotifications): bool
    {
        // Vérifier les rappels urgents
        foreach ($upcomingReminders as $reminder) {
            if ($reminder->getPriority() === 'high' || $reminder->isOverdue()) {
                return true;
            }
        }
        
        // Vérifier les notifications urgentes
        foreach ($pendingNotifications as $notification) {
            if (($notification['priority'] ?? 'normal') === 'high' || 
                in_array($notification['type'], ['urgent_reminder', 'urgent'])) {
                return true;
            }
        }
        
        return false;
    }
} 