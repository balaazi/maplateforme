<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Service\InvitationReminderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/invitation-reminders', name: 'api_invitation_reminders_')]
class InvitationReminderApiController extends AbstractController
{
    public function __construct(
        private InvitationReminderService $invitationReminderService,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie des rappels pour un événement spécifique
     */
    #[Route('/event/{id}/send', name: 'send_for_event', methods: ['POST'])]
    public function sendRemindersForEvent(Event $event, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reminderType = $data['reminder_type'] ?? 'both';
            
            if (!in_array($reminderType, ['24h', '1h', 'both'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Type de rappel invalide. Utilisez: 24h, 1h, ou both'
                ], 400);
            }
            
            $results = [];
            
            if ($reminderType === '24h' || $reminderType === 'both') {
                $results['24h'] = $this->invitationReminderService->sendRemindersForEvent($event, '24h');
            }
            
            if ($reminderType === '1h' || $reminderType === 'both') {
                $results['1h'] = $this->invitationReminderService->sendRemindersForEvent($event, '1h');
            }
            
            $totalReminders = array_sum(array_column($results, 'reminders_sent'));
            $totalErrors = array_sum(array_column($results, 'errors'));
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d rappel(s) envoyé(s) pour l\'événement "%s"', $totalReminders, $event->getTitle()),
                'data' => [
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'results' => $results,
                    'total_reminders' => $totalReminders,
                    'total_errors' => $totalErrors
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur API rappels d\'invitations pour événement', [
                'event_id' => $event->getId(),
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des rappels: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envoie un rappel personnalisé à une invitation spécifique
     */
    #[Route('/invitation/{id}/send', name: 'send_for_invitation', methods: ['POST'])]
    public function sendCustomReminder(Invitation $invitation, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reminderType = $data['reminder_type'] ?? '24h';
            $customMessage = $data['custom_message'] ?? null;
            
            if (!in_array($reminderType, ['24h', '1h'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Type de rappel invalide. Utilisez: 24h ou 1h'
                ], 400);
            }
            
            $success = $this->invitationReminderService->sendCustomReminder($invitation, $reminderType, $customMessage);
            
            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => sprintf('Rappel %s envoyé à %s', $reminderType, $invitation->getEmail()),
                    'data' => [
                        'invitation_id' => $invitation->getId(),
                        'email' => $invitation->getEmail(),
                        'reminder_type' => $reminderType
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du rappel personnalisé'
                ], 500);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur API rappel personnalisé', [
                'invitation_id' => $invitation->getId(),
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du rappel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques des rappels d'invitations
     */
    #[Route('/stats', name: 'get_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->invitationReminderService->getReminderStats();
            
            return new JsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur API statistiques rappels', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traite tous les rappels programmés
     */
    #[Route('/process-scheduled', name: 'process_scheduled', methods: ['POST'])]
    public function processScheduledReminders(): JsonResponse
    {
        try {
            $results = $this->invitationReminderService->processScheduledReminders();
            
            $totalReminders = $results['24h']['reminders_sent'] + $results['1h']['reminders_sent'];
            $totalErrors = $results['24h']['errors'] + $results['1h']['errors'];
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('%d rappel(s) programmé(s) traité(s)', $totalReminders),
                'data' => [
                    'results' => $results,
                    'total_reminders' => $totalReminders,
                    'total_errors' => $totalErrors
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur API traitement rappels programmés', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du traitement des rappels programmés: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teste l'envoi de rappels pour une date spécifique
     */
    #[Route('/test', name: 'test_reminders', methods: ['POST'])]
    public function testReminders(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reminderType = $data['reminder_type'] ?? 'both';
            $testDate = $data['test_date'] ?? null;
            
            if (!in_array($reminderType, ['24h', '1h', 'both'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Type de rappel invalide. Utilisez: 24h, 1h, ou both'
                ], 400);
            }
            
            $parsedDate = $testDate ? new \DateTime($testDate) : null;
            $results = [];
            
            if ($reminderType === '24h' || $reminderType === 'both') {
                $results['24h'] = $this->invitationReminderService->sendRemindersForTimeRange('24h', $parsedDate);
            }
            
            if ($reminderType === '1h' || $reminderType === 'both') {
                $results['1h'] = $this->invitationReminderService->sendRemindersForTimeRange('1h', $parsedDate);
            }
            
            $totalReminders = array_sum(array_column($results, 'reminders_sent'));
            $totalErrors = array_sum(array_column($results, 'errors'));
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Test terminé: %d rappel(s) envoyé(s)', $totalReminders),
                'data' => [
                    'results' => $results,
                    'total_reminders' => $totalReminders,
                    'total_errors' => $totalErrors,
                    'test_date' => $testDate
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur API test rappels', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors du test des rappels: ' . $e->getMessage()
            ], 500);
        }
    }
}
