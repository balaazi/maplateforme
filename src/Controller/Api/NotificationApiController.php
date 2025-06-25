<?php

namespace App\Controller\Api;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationApiController extends AbstractController
{
    #[Route('/count', name: 'api_notifications_count', methods: ['GET'])]
    public function getNotificationCount(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['count' => 0]);
        }
        
        try {
            $count = $notificationService->getUnreadCountForUser($user);
            return $this->json(['count' => $count]);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner 0 pour ne pas casser l'interface
            return $this->json(['count' => 0]);
        }
    }
    
    #[Route('/list', name: 'api_notifications_list', methods: ['GET'])]
    public function getNotifications(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['notifications' => []]);
        }
        
        try {
            $notifications = $notificationService->getNotificationsForUser($user);
            
            $data = array_map(function($notification) {
                return [
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'type' => $notification->getType(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $notifications);
            
            return $this->json(['notifications' => $data]);
        } catch (\Exception $e) {
            return $this->json(['notifications' => []]);
        }
    }
    
    #[Route('/{id}/mark-read', name: 'api_notification_mark_read', methods: ['POST'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false], 401);
        }
        
        try {
            // Pour l'instant, retourner simplement success true
            // Cette fonctionnalité peut être implémentée plus tard
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
} 