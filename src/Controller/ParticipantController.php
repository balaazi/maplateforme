<?php
// src/Controller/ParticipantController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ParticipationRepository;
use App\Repository\CollaborativeNoteRepository;
use App\Service\NotificationService;
use App\Service\AutoArchiveService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARTICIPANT')]
class ParticipantController extends AbstractController
{
    public function __construct(
        private AutoArchiveService $autoArchiveService
    ) {}

    #[Route('/dashboard', name: 'participant_dashboard')]
    public function dashboard(NotificationService $notificationService, ParticipationRepository $participationRepository): Response
    {
        $user = $this->getUser();
        $unreadNotificationsCount = $notificationService->getUnreadCountForUser($user);

        // Nombre réel d'événements (non archivés)
        $participationsNonArchivees = $participationRepository->findByUserNonArchived($user);
        $eventsCount = \count($participationsNonArchivees);

        // Nombre réel de documents liés aux événements de l'utilisateur (toutes participations)
        $allParticipations = $participationRepository->findBy(['user' => $user]);
        $documentsCount = 0;
        foreach ($allParticipations as $participation) {
            $event = $participation->getEvent();
            if ($event === null) { continue; }
            $documentsCount += \count($event->getDocuments());
        }

        
        // Récupération de l'activité récente du participant
        $recentActivity = [];
        
        // Ajouter les participations récentes (événements auxquels le participant s'est inscrit)
        foreach (array_slice($allParticipations, 0, 5) as $participation) {
            $event = $participation->getEvent();
            if ($event === null) { continue; }
            
            $recentActivity[] = [
                'type' => 'participation',
                'title' => 'Participation à l\'événement',
                'description' => $event->getTitle(),
                'icon' => 'calendar-check',
                'color' => '#10b981',
                'date' => $participation->getCreatedAt(),
                'category' => 'events',
                'status' => $participation->getInvitationStatus(),
                'isPresent' => $participation->isPresent()
            ];
        }
        
        // Ajouter les confirmations de présence
        foreach ($allParticipations as $participation) {
            if ($participation->isPresent()) {
                $event = $participation->getEvent();
                if ($event === null) { continue; }
                
                $recentActivity[] = [
                    'type' => 'presence_confirmed',
                    'title' => 'Présence confirmée',
                    'description' => 'Vous avez confirmé votre participation à ' . $event->getTitle(),
                    'icon' => 'check-circle',
                    'color' => '#10b981',
                    'date' => $participation->getCreatedAt(),
                    'category' => 'presence'
                ];
            }
        }
        
        // Ajouter les changements de statut d'invitation
        foreach ($allParticipations as $participation) {
            $event = $participation->getEvent();
            if ($event === null) { continue; }
            
            $status = $participation->getInvitationStatus();
            if ($status === 'accepté') {
                $recentActivity[] = [
                    'type' => 'invitation_accepted',
                    'title' => 'Invitation acceptée',
                    'description' => 'Vous avez accepté l\'invitation à ' . $event->getTitle(),
                    'icon' => 'check',
                    'color' => '#3b82f6',
                    'date' => $participation->getCreatedAt(),
                    'category' => 'invitation'
                ];
            } elseif ($status === 'refusé') {
                $recentActivity[] = [
                    'type' => 'invitation_declined',
                    'title' => 'Invitation refusée',
                    'description' => 'Vous avez refusé l\'invitation à ' . $event->getTitle(),
                    'icon' => 'times',
                    'color' => '#ef4444',
                    'date' => $participation->getCreatedAt(),
                    'category' => 'invitation'
                ];
            }
        }
        
        // Trier par date (plus récent en premier)
        usort($recentActivity, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        // Limiter à 5 activités récentes
        $recentActivity = array_slice($recentActivity, 0, 5);
        
        // Si pas d'activité récente, ajouter un message d'accueil
        if (empty($recentActivity)) {
            $recentActivity[] = [
                'type' => 'welcome',
                'title' => 'Bienvenue sur EventHub !',
                'description' => 'Commencez par participer à des événements pour voir votre activité',
                'icon' => 'rocket',
                'color' => '#8b5cf6',
                'date' => new \DateTime(),
                'category' => 'welcome'
            ];
        }
         
        return $this->render('participant/dashboard.html.twig', [
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'eventsCount' => $eventsCount,
            'documentsCount' => $documentsCount,
            'recentActivity' => $recentActivity,
        ]);
    }

    #[Route('/mes-evenements', name: 'participant_events')]
    public function events(ParticipationRepository $participationRepository): Response
    {
        // Archivage automatique des événements terminés
        $archivedCount = $this->autoArchiveService->checkAndArchiveCompletedEvents();
        if ($archivedCount > 0) {
            $this->addFlash('success', $archivedCount . ' événement(s) ont été archivés automatiquement.');
        }
        
        $participations = $participationRepository->findByUserNonArchived($this->getUser());

        return $this->render('participant/events.html.twig', [
            'participations' => $participations,
            'archivedCount' => $archivedCount
        ]);
    }

    #[Route('/documents', name: 'participant_documents')]
    public function documents(
        ParticipationRepository $participationRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response {
        // Archivage automatique des événements terminés
        $archivedCount = $this->autoArchiveService->checkAndArchiveCompletedEvents();
        if ($archivedCount > 0) {
            $this->addFlash('success', $archivedCount . ' événement(s) ont été archivés automatiquement.');
        }
        
        $user = $this->getUser();
        
        // Récupérer TOUTES les participations de l'utilisateur (même archivées)
        $allParticipations = $participationRepository->findBy(['user' => $user]);

        // Extraire les événements de ces participations
        $events = [];
        foreach ($allParticipations as $participation) {
            $events[] = $participation->getEvent();
        }
        
        // AJOUT : Récupérer aussi les événements créés par l'utilisateur
        $eventRepository = $entityManager->getRepository(\App\Entity\Event::class);
        $createdEvents = $eventRepository->findBy(['createdBy' => $user]);
        
        // Fusionner les événements (éviter les doublons)
        $allEventsIds = [];
        $allEvents = [];
        
        // Ajouter les événements de participation
        foreach ($events as $event) {
            if ($event && !in_array($event->getId(), $allEventsIds)) {
                $allEventsIds[] = $event->getId();
                $allEvents[] = $event;
            }
        }
        
        // Ajouter les événements créés
        foreach ($createdEvents as $event) {
            if ($event && !in_array($event->getId(), $allEventsIds)) {
                $allEventsIds[] = $event->getId();
                $allEvents[] = $event;
            }
        }

        // Récupérer tous les documents uploadés des événements
        $documents = [];
        foreach ($allEvents as $event) {
            $eventDocuments = $event->getDocuments();
            foreach ($eventDocuments as $document) {
                $documents[] = $document;
            }
        }

        return $this->render('participant/documents.html.twig', [
            'documents' => $documents,
            'participations' => $allParticipations,
            'archivedCount' => $archivedCount
        ]);
    }

    #[Route('/notifications', name: 'participant_notifications')]
    public function notifications(NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $notifications = $notificationService->getNotificationsForUser($user);
        
        return $this->render('participant/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }

    #[Route('/statistiques', name: 'participant_statistics')]
    public function statistics(ParticipationRepository $participationRepository): Response
    {
        // Archivage automatique des événements terminés
        $archivedCount = $this->autoArchiveService->checkAndArchiveCompletedEvents();
        if ($archivedCount > 0) {
            $this->addFlash('success', $archivedCount . ' événement(s) ont été archivés automatiquement.');
        }
        
        $user = $this->getUser();
        // Récupérer seulement les participations aux événements non annulés et non archivés
        $participations = $participationRepository->findByUserNonCancelledNonArchived($user);

        // Calculer les statistiques du participant
        $stats = [
            'total_events' => count($participations),
            'present' => 0,
            'absent' => 0,
            'upcoming' => 0,
            'past' => 0,
            'accepted' => 0,
            'declined' => 0,
            'pending' => 0,
        ];

        $now = new \DateTime();
        $eventsByCategory = [];

        foreach ($participations as $participation) {
            $event = $participation->getEvent();
            
            // Statistiques de présence
            if ($participation->isPresent()) {
                $stats['present']++;
            } else {
                $stats['absent']++;
            }

            // Événements futurs/passés
            if ($event->getDateHeure() > $now) {
                $stats['upcoming']++;
            } else {
                $stats['past']++;
            }

            // Statuts d'invitation
            switch ($participation->getInvitationStatus()) {
                case 'accepted':
                    $stats['accepted']++;
                    break;
                case 'declined':
                    $stats['declined']++;
                    break;
                default:
                    $stats['pending']++;
                    break;
            }

            // Répartition par catégorie
            $category = $event->getCategory() ?? 'Autre';
            if (!isset($eventsByCategory[$category])) {
                $eventsByCategory[$category] = 0;
            }
            $eventsByCategory[$category]++;
        }

        // Calculer les taux
        $stats['presence_rate'] = $stats['total_events'] > 0 ? 
            round(($stats['present'] / $stats['total_events']) * 100, 1) : 0;

        return $this->render('participant/statistics.html.twig', [
            'stats' => $stats,
            'eventsByCategory' => $eventsByCategory
        ]);
    }
}
