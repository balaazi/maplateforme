<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
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
            $notifications = $notificationService->getNotificationsForUser($user, 15);
            
            $data = array_map(function($notification) {
                return [
                    'id' => $notification->getId(),
                    'title' => $notification->getTitle(),
                    'message' => $notification->getMessage(),
                    'type' => $notification->getType(),
                    'isRead' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                    'timeAgo' => $notification->getTimeAgo(),
                    'icon' => $notification->getIcon(),
                    'typeColor' => $notification->getTypeColor()
                ];
            }, $notifications);
            
            return $this->json(['notifications' => $data]);
        } catch (\Exception $e) {
            return $this->json(['notifications' => []]);
        }
    }
    
    #[Route('/{id}/mark-read', name: 'api_notification_mark_read', methods: ['POST'])]
    public function markAsRead(int $id, NotificationService $notificationService, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false], 401);
        }
        
        try {
            $notificationRepository = $entityManager->getRepository(Notification::class);
            $notification = $notificationRepository->find($id);
            
            if (!$notification || $notification->getUser() !== $user) {
                return $this->json(['success' => false, 'error' => 'Notification not found'], 404);
            }
            
            $notificationService->markAsRead($notification);
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false], 401);
        }
        
        try {
            $notificationService->markAllAsReadForUser($user);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/delete', name: 'api_notification_delete', methods: ['DELETE'])]
    public function deleteNotification(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false], 401);
        }
        
        try {
            $notificationRepository = $entityManager->getRepository(Notification::class);
            $notification = $notificationRepository->find($id);
            
            if (!$notification || $notification->getUser() !== $user) {
                return $this->json(['success' => false, 'error' => 'Notification not found'], 404);
            }
            
            $entityManager->remove($notification);
            $entityManager->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/delete-all', name: 'api_notifications_delete_all', methods: ['DELETE'])]
    public function deleteAllNotifications(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['success' => false], 401);
        }
        
        try {
            $notificationRepository = $entityManager->getRepository(Notification::class);
            $notifications = $notificationRepository->findBy(['user' => $user]);
            
            foreach ($notifications as $notification) {
                $entityManager->remove($notification);
            }
            
            $entityManager->flush();
            
            return $this->json(['success' => true, 'deleted_count' => count($notifications)]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
} 