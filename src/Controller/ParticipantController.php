<?php
// src/Controller/ParticipantController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ParticipationRepository;
use App\Repository\CollaborativeNoteRepository;
use App\Service\NotificationService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARTICIPANT')]
class ParticipantController extends AbstractController
{
    #[Route('/dashboard', name: 'participant_dashboard')]
    public function dashboard(NotificationService $notificationService): Response
    {
        $user = $this->getUser();
        $unreadNotificationsCount = $notificationService->getUnreadCountForUser($user);
        
        return $this->render('participant/dashboard.html.twig', [
            'unreadNotificationsCount' => $unreadNotificationsCount
        ]);
    }

    #[Route('/mes-evenements', name: 'participant_events')]
    public function events(ParticipationRepository $participationRepository): Response
    {
        $participations = $participationRepository->findBy([
            'user' => $this->getUser()
        ]);

        return $this->render('participant/events.html.twig', [
            'participations' => $participations
        ]);
    }

    #[Route('/documents', name: 'participant_documents')]
    public function documents(ParticipationRepository $participationRepository, CollaborativeNoteRepository $noteRepository): Response
    {
        // Récupérer les événements auxquels participe l'utilisateur
        $participations = $participationRepository->findBy([
            'user' => $this->getUser()
        ]);

        // Extraire les événements de ces participations
        $events = [];
        foreach ($participations as $participation) {
            $events[] = $participation->getEvent();
        }

        // Récupérer toutes les notes collaboratives des événements du participant
        $collaborativeNotes = [];
        foreach ($events as $event) {
            $notes = $noteRepository->findBy(['event' => $event]);
            foreach ($notes as $note) {
                $collaborativeNotes[] = $note;
            }
        }

        return $this->render('participant/documents.html.twig', [
            'events' => $events,
            'collaborativeNotes' => $collaborativeNotes,
            'participations' => $participations
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
        $user = $this->getUser();
        $participations = $participationRepository->findBy(['user' => $user]);

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
        $stats['response_rate'] = $stats['total_events'] > 0 ? 
            round((($stats['accepted'] + $stats['declined']) / $stats['total_events']) * 100, 1) : 0;

        return $this->render('participant/statistics.html.twig', [
            'stats' => $stats,
            'eventsByCategory' => $eventsByCategory,
            'participations' => $participations
        ]);
    }
}
