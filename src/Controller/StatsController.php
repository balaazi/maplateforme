<?php
// src/Controller/StatsController.php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use App\Repository\DepartementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        InvitationRepository $invitationRepository,
        UserRepository $userRepository,
        DepartementRepository $departementRepository
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
        
        // === STATISTIQUES DE BASE ===
        $baseStats = [
            'total_invitations' => count($invitations),
            'accepted' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'accepted')),
            'declined' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'declined')),
            'pending' => count(array_filter($invitations, fn($i) => $i->getStatus() === 'pending')),
            'present' => count(array_filter($participations, fn($p) => $p->isPresent())),
            'absent' => count(array_filter($participations, fn($p) => !$p->isPresent())),
        ];

        // === TAUX DE CONVERSION ===
        $conversionRates = [
            'response_rate' => $baseStats['total_invitations'] > 0 
                ? round((($baseStats['accepted'] + $baseStats['declined']) / $baseStats['total_invitations']) * 100, 2) 
                : 0,
            'acceptance_rate' => $baseStats['total_invitations'] > 0 
                ? round(($baseStats['accepted'] / $baseStats['total_invitations']) * 100, 2) 
                : 0,
            'presence_rate' => $baseStats['accepted'] > 0 
                ? round(($baseStats['present'] / $baseStats['accepted']) * 100, 2) 
                : 0,
            'decline_rate' => $baseStats['total_invitations'] > 0 
                ? round(($baseStats['declined'] / $baseStats['total_invitations']) * 100, 2) 
                : 0,
        ];

        // === STATISTIQUES PAR DÉPARTEMENT ===
        $departmentStats = [];
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $department = $user->getDepartement()?->getNom() ?? 'Non spécifié';

            if (!isset($departmentStats[$department])) {
                $departmentStats[$department] = [
                    'total' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'pending' => 0,
                ];
            }

            $departmentStats[$department]['total']++;
            
            if ($participation->isPresent()) {
                $departmentStats[$department]['present']++;
            } else {
                $departmentStats[$department]['absent']++;
            }

            switch ($participation->getInvitationStatus()) {
                case 'accepté':
                    $departmentStats[$department]['accepted']++;
                    break;
                case 'refusé':
                    $departmentStats[$department]['declined']++;
                    break;
                case 'en_attente':
                    $departmentStats[$department]['pending']++;
                    break;
            }
        }

        // Calculer les pourcentages par département
        foreach ($departmentStats as &$stats) {
            $stats['presence_rate'] = $stats['total'] > 0 
                ? round(($stats['present'] / $stats['total']) * 100, 2) 
                : 0;
        }

        // === STATISTIQUES TEMPORELLES ===
        $temporalStats = $this->calculateTemporalStats($invitations, $event);

        // === COMPARAISON AVEC D'AUTRES ÉVÉNEMENTS ===
        $comparisonStats = $this->calculateComparisonStats($event, $eventRepository, $participationRepository);

        // === STATISTIQUES DÉMOGRAPHIQUES ===
        $demographicStats = $this->calculateDemographicStats($participations);

        // === DONNÉES POUR LES GRAPHIQUES ===
        $chartData = [
            'participation_pie' => [
                'labels' => ['Acceptés', 'Refusés', 'En attente'],
                'data' => [$baseStats['accepted'], $baseStats['declined'], $baseStats['pending']],
                'colors' => ['#10b981', '#ef4444', '#f59e0b']
            ],
            'presence_pie' => [
                'labels' => ['Présents', 'Absents'],
                'data' => [$baseStats['present'], $baseStats['absent']],
                'colors' => ['#059669', '#dc2626']
            ],
            'department_bar' => [
                'labels' => array_keys($departmentStats),
                'data' => array_column($departmentStats, 'total'),
                'colors' => ['#3b82f6', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444']
            ],
            'temporal_line' => $temporalStats['chart_data']
        ];

        return $this->render('stats/event_detailed.html.twig', [
            'event' => $event,
            'baseStats' => $baseStats,
            'conversionRates' => $conversionRates,
            'departmentStats' => $departmentStats,
            'temporalStats' => $temporalStats,
            'comparisonStats' => $comparisonStats,
            'demographicStats' => $demographicStats,
            'chartData' => $chartData,
        ]);
    }

    private function calculateTemporalStats(array $invitations, $event): array
    {
        $responsesByDay = [];
        $acceptancesByDay = [];
        $eventDate = $event->getDateHeure();
        
        // Analyser les réponses par jour
        foreach ($invitations as $invitation) {
            if ($invitation->getUpdatedAt()) {
                $day = $invitation->getUpdatedAt()->format('Y-m-d');
                
                if (!isset($responsesByDay[$day])) {
                    $responsesByDay[$day] = 0;
                }
                
                if ($invitation->getStatus() !== 'pending') {
                    $responsesByDay[$day]++;
                }
                
                if ($invitation->getStatus() === 'accepted') {
                    if (!isset($acceptancesByDay[$day])) {
                        $acceptancesByDay[$day] = 0;
                    }
                    $acceptancesByDay[$day]++;
                }
            }
        }

        // Calculer les statistiques temporelles
        $daysUntilEvent = $eventDate ? $eventDate->diff(new DateTime())->days : 0;
        $avgResponseTime = $this->calculateAverageResponseTime($invitations);
        
        return [
            'responses_by_day' => $responsesByDay,
            'acceptances_by_day' => $acceptancesByDay,
            'days_until_event' => $daysUntilEvent,
            'avg_response_time' => $avgResponseTime,
            'peak_response_day' => $this->findPeakResponseDay($responsesByDay),
            'chart_data' => [
                'labels' => array_keys($responsesByDay),
                'datasets' => [
                    [
                        'label' => 'Réponses',
                        'data' => array_values($responsesByDay),
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                    ],
                    [
                        'label' => 'Acceptations',
                        'data' => array_values($acceptancesByDay),
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                    ]
                ]
            ]
        ];
    }

    private function calculateAverageResponseTime(array $invitations): float
    {
        $responseTimes = [];
        foreach ($invitations as $invitation) {
            if ($invitation->getCreatedAt() && $invitation->getUpdatedAt() && $invitation->getStatus() !== 'pending') {
                $diff = $invitation->getUpdatedAt()->diff($invitation->getCreatedAt());
                $responseTimes[] = $diff->days * 24 + $diff->h + ($diff->i / 60);
            }
        }
        
        return count($responseTimes) > 0 ? array_sum($responseTimes) / count($responseTimes) : 0;
    }

    private function findPeakResponseDay(array $responsesByDay): ?string
    {
        if (empty($responsesByDay)) {
            return null;
        }
        
        return array_keys($responsesByDay, max($responsesByDay))[0];
    }

    private function calculateComparisonStats($event, EventRepository $eventRepository, ParticipationRepository $participationRepository): array
    {
        $user = $event->getOrganizer();
        $otherEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.organizer = :organizer')
            ->andWhere('e.id != :currentEvent')
            ->setParameter('organizer', $user)
            ->setParameter('currentEvent', $event->getId())
            ->getQuery()
            ->getResult();

        if (empty($otherEvents)) {
            return [
                'avg_acceptance_rate' => 0,
                'avg_presence_rate' => 0,
                'performance_vs_avg' => 'N/A',
                'best_event' => null,
                'total_compared_events' => 0
            ];
        }

        $totalAcceptanceRates = [];
        $totalPresenceRates = [];
        $bestEvent = null;
        $bestRate = 0;

        foreach ($otherEvents as $otherEvent) {
            $invitations = $otherEvent->getInvitations();
            $participations = $participationRepository->findBy(['event' => $otherEvent]);
            
            $totalInvitations = count($invitations);
            $accepted = count(array_filter($invitations->toArray(), fn($i) => $i->getStatus() === 'accepted'));
            $present = count(array_filter($participations, fn($p) => $p->isPresent()));
            
            if ($totalInvitations > 0) {
                $acceptanceRate = ($accepted / $totalInvitations) * 100;
                $totalAcceptanceRates[] = $acceptanceRate;
                
                if ($acceptanceRate > $bestRate) {
                    $bestRate = $acceptanceRate;
                    $bestEvent = $otherEvent;
                }
            }
            
            if ($accepted > 0) {
                $totalPresenceRates[] = ($present / $accepted) * 100;
            }
        }

        return [
            'avg_acceptance_rate' => count($totalAcceptanceRates) > 0 ? round(array_sum($totalAcceptanceRates) / count($totalAcceptanceRates), 2) : 0,
            'avg_presence_rate' => count($totalPresenceRates) > 0 ? round(array_sum($totalPresenceRates) / count($totalPresenceRates), 2) : 0,
            'best_event' => $bestEvent,
            'best_rate' => round($bestRate, 2),
            'total_compared_events' => count($otherEvents)
        ];
    }

    private function calculateDemographicStats(array $participations): array
    {
        $roleStats = [];
        $specialtyStats = [];
        
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            
            // Statistiques par rôle
            $roles = $user->getRoles();
            $mainRole = $this->getMainRole($roles);
            if (!isset($roleStats[$mainRole])) {
                $roleStats[$mainRole] = ['total' => 0, 'present' => 0];
            }
            $roleStats[$mainRole]['total']++;
            if ($participation->isPresent()) {
                $roleStats[$mainRole]['present']++;
            }
            
            // Statistiques par spécialité (si disponible)
            if (method_exists($user, 'getSpecialite')) {
                $specialty = $user->getSpecialite() ?? 'Non spécifiée';
                if (!isset($specialtyStats[$specialty])) {
                    $specialtyStats[$specialty] = ['total' => 0, 'present' => 0];
                }
                $specialtyStats[$specialty]['total']++;
                if ($participation->isPresent()) {
                    $specialtyStats[$specialty]['present']++;
                }
            }
        }

        return [
            'by_role' => $roleStats,
            'by_specialty' => $specialtyStats,
            'total_participants' => count($participations),
            'unique_departments' => count(array_unique(array_map(fn($p) => $p->getUser()->getDepartement()?->getNom() ?? 'Non spécifié', $participations)))
        ];
    }

    private function getMainRole(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles)) return 'Administrateur';
        if (in_array('ROLE_ORGANISATEUR', $roles)) return 'Organisateur';
        if (in_array('ROLE_PARTICIPANT', $roles)) return 'Participant';
        return 'Utilisateur';
    }

    #[Route('/event/{id}/stats/export', name: 'event_stats_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportEventStats(int $id, EventRepository $eventRepository, ParticipationRepository $participationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $event = $eventRepository->find($id);
        if (!$event || $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $participations = $participationRepository->findBy(['event' => $event]);
        $exportData = [];

        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $exportData[] = [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'departement' => $user->getDepartement()?->getNom() ?? 'Non spécifié',
                'statut_invitation' => $participation->getInvitationStatus(),
                'presence' => $participation->isPresent() ? 'Présent' : 'Absent',
                'date_creation' => $participation->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'event' => [
                'titre' => $event->getTitle(),
                'date' => $event->getDateHeure()->format('Y-m-d H:i:s'),
                'lieu' => $event->getLieu() ?? 'Non spécifié',
                'organisateur' => $event->getOrganizer()->getPrenom() . ' ' . $event->getOrganizer()->getNom(),
            ],
            'participants' => $exportData,
            'generated_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    // === MÉTHODES EXISTANTES CONSERVÉES ===
    
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
                $department = $participant->getDepartement()?->getNom() ?? 'Non spécifié';

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
