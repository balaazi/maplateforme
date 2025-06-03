<?php
// src/Controller/StatsController.php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateTimeZone;

class StatsController extends AbstractController
{
    #[Route('/event/{id}/stats', name: 'event_stats', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function eventStats(
        int $id,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        // Vérifier si l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les statistiques de cet événement.');
        }

        // Convertir la date de l'événement au fuseau horaire de Paris
        $dateHeure = $event->getDateHeure();
        if ($dateHeure instanceof DateTime) {
            $dateHeure->setTimezone(new DateTimeZone('Europe/Paris'));
            $event->setDateHeure($dateHeure);
        }

        // Récupérer les invitations et les participations
        $invitations = $invitationRepository->findBy(['event' => $event]);
        $participations = $participationRepository->findBy(['event' => $event]);
        
        // Calculer les statistiques
        $stats = [
            'total' => count($invitations),
            'accepted' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'accepted')),
            'refused' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'declined')),
            'pending' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'pending')),
            'present' => count(array_filter($participations, fn($p) => $p->isPresent())),
        ];

        return $this->render('stats/event.html.twig', [
            'event' => $event,
            'stats' => $stats,
        ]);
    }

    #[Route('/organisateur/statistics', name: 'organisateur_statistics')]
    public function generalStats(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user]);

        // Initialisation des statistiques globales
        $globalStats = [
            'total_events' => count($events),
            'total_participations' => 0,
            'total_present' => 0,
            'accepted' => 0,
            'refused' => 0,
            'pending' => 0,
            'present' => 0,
        ];

        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);
            $globalStats['total_participations'] += count($participations);

            foreach ($participations as $p) {
                switch ($p->getInvitationStatus()) {
                    case 'accepté':
                        $globalStats['accepted']++;
                        break;
                    case 'refusé':
                        $globalStats['refused']++;
                        break;
                    case 'en_attente':
                        $globalStats['pending']++;
                        break;
                }

                if ($p->isPresent()) {
                    $globalStats['total_present']++;
                    $globalStats['present']++;
                }
            }
        }

        return $this->render('stats/general_stats.html.twig', [
            'stats' => $globalStats,
        ]);
    }

    #[Route('/organisateur/department-statistics', name: 'department_statistics')]
    public function departmentStats(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user]);

        // Initialisation des statistiques par département
        $departmentStats = [];

        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);

            foreach ($participations as $participation) {
                $participant = $participation->getUser();
                $department = $participant->getDepartement() ?? 'Non spécifié';

                if (!isset($departmentStats[$department])) {
                    $departmentStats[$department] = [
                        'total' => 0,
                        'present' => 0,
                        'absent' => 0,
                        'accepted' => 0,
                        'refused' => 0,
                        'pending' => 0,
                    ];
                }

                // Incrémenter les compteurs
                $departmentStats[$department]['total']++;

                // Compter les présences
                if ($participation->isPresent()) {
                    $departmentStats[$department]['present']++;
                } else {
                    $departmentStats[$department]['absent']++;
                }

                // Compter les statuts d'invitation
                switch ($participation->getInvitationStatus()) {
                    case 'accepté':
                        $departmentStats[$department]['accepted']++;
                        break;
                    case 'refusé':
                        $departmentStats[$department]['refused']++;
                        break;
                    case 'en_attente':
                        $departmentStats[$department]['pending']++;
                        break;
                }
            }
        }

        // Calculer les pourcentages pour chaque département
        foreach ($departmentStats as &$stats) {
            $stats['presence_rate'] = $stats['total'] > 0 
                ? round(($stats['present'] / $stats['total']) * 100, 2) 
                : 0;
            $stats['acceptance_rate'] = $stats['total'] > 0 
                ? round(($stats['accepted'] / $stats['total']) * 100, 2) 
                : 0;
        }

        return $this->render('stats/department_stats.html.twig', [
            'departmentStats' => $departmentStats,
        ]);
    }
}
