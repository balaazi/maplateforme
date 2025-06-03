<?php
// src/Controller/ParticipantController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ParticipationRepository;
use App\Repository\CollaborativeNoteRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARTICIPANT')]
class ParticipantController extends AbstractController
{
    #[Route('/dashboard', name: 'participant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('participant/dashboard.html.twig');
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
    public function notifications(): Response
    {
        // TODO: Implémenter le système de notifications
        return $this->render('participant/notifications.html.twig', [
            'notifications' => []
        ]);
    }
}
