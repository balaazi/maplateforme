<?php
// src/Controller/StatsController.php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use App\Repository\DepartementRepository;
use App\Entity\User;
use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateTimeZone;
use App\Enum\InvitationStatus;

class StatsController extends AbstractController
{
    #[Route('/event/{id}/stats', name: 'event_stats', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function eventStats(
        int $id,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository,
        UserRepository $userRepository,
        DepartementRepository $departementRepository,
        EntityManagerInterface $entityManager
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
        // Compter les statuts d'invitation depuis les participations pour cohérence
        $acceptedFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::ACCEPTED));
        $declinedFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::DECLINED));
        $pendingFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::PENDING));
        $expiredFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::EXPIRED));
        
        // Compter aussi les invitations directes (sans participation)
        $invitationsWithoutParticipation = array_filter($invitations, function($invitation) use ($participations) {
            foreach ($participations as $participation) {
                if ($participation->getUser()->getEmail() === $invitation->getEmail()) {
                    return false; // Cette invitation a une participation correspondante
                }
            }
            return true; // Cette invitation n'a pas de participation correspondante
        });
        
        $baseStats = [
            'total_invitations' => count($invitations),
            'accepted' => $acceptedFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED)),
            'declined' => $declinedFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::DECLINED)),
            'pending' => $pendingFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::PENDING)),
            'expired' => $expiredFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::EXPIRED)),
            'present' => count(array_filter($participations, fn($p) => $p->isPresent())),
            'absent' => count(array_filter($participations, fn($p) => !$p->isPresent())),
            'total_participants' => count($participations),
        ];

        // === TAUX DE CONVERSION ===
        $conversionRates = [
            'response_rate' => $baseStats['total_invitations'] > 0 
                ? round((($baseStats['accepted'] + $baseStats['declined'] + $baseStats['expired']) / $baseStats['total_invitations']) * 100, 2) 
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
            'engagement_rate' => $baseStats['total_invitations'] > 0 
                ? round((($baseStats['accepted'] + $baseStats['declined']) / $baseStats['total_invitations']) * 100, 2) 
                : 0,
            'no_show_rate' => $baseStats['accepted'] > 0 
                ? round(($baseStats['absent'] / $baseStats['accepted']) * 100, 2) 
                : 0,
            'expired_rate' => $baseStats['total_invitations'] > 0 
                ? round(($baseStats['expired'] / $baseStats['total_invitations']) * 100, 2) 
                : 0,
        ];

        // === STATISTIQUES DE CONFLITS D'HORAIRE ===
        // Simuler la détection de conflits (à adapter selon votre logique)
        $conflictStats = $this->calculateConflictStats($event, $participations, $entityManager);

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
                case InvitationStatus::ACCEPTED:
                    $departmentStats[$department]['accepted']++;
                    break;
                case InvitationStatus::DECLINED:
                    $departmentStats[$department]['declined']++;
                    break;
                case InvitationStatus::PENDING:
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

        // === DONNÉES D'EXPORT ===
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

        // === MÉTRIQUES AVANCÉES ===
        $advancedMetrics = $this->calculateAdvancedMetrics($invitations, $participations, $event);
        
        // === ANALYSE PRÉDICTIVE ===
        $predictiveAnalysis = $this->calculatePredictiveAnalysis($invitations, $event);
        
        // === MÉTRIQUES DE TEMPS ===
        $timeMetrics = $this->calculateTimeMetrics($invitations, $event);

        return $this->render('stats/event_detailed.html.twig', [
            'event' => $event,
            'baseStats' => $baseStats,
            'conversionRates' => $conversionRates,
            'conflictStats' => $conflictStats,
            'departmentStats' => $departmentStats,
            'temporalStats' => $temporalStats,
            'comparisonStats' => $comparisonStats,
            'demographicStats' => $demographicStats,
            'chartData' => $chartData,
            'exportData' => $exportData,
            'advancedMetrics' => $advancedMetrics,
            'predictiveAnalysis' => $predictiveAnalysis,
            'timeMetrics' => $timeMetrics,
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
                
                if ($invitation->getStatus() !== InvitationStatus::PENDING) {
                    $responsesByDay[$day]++;
                }
                
                if ($invitation->getStatus() === InvitationStatus::ACCEPTED) {
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
            if ($invitation->getCreatedAt() && $invitation->getUpdatedAt() && $invitation->getStatus() !== InvitationStatus::PENDING) {
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
            $accepted = count(array_filter($invitations->toArray(), fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
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
    public function exportEventStats(
        int $id, 
        EventRepository $eventRepository, 
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $event = $eventRepository->find($id);
        if (!$event || $event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès non autorisé à cet événement.');
        }

        $participations = $participationRepository->findBy(['event' => $event]);
        $invitations = $invitationRepository->findBy(['event' => $event]);

        // Statistiques de base (cohérentes avec l'affichage)
        $acceptedFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::ACCEPTED));
        $declinedFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::DECLINED));
        $pendingFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::PENDING));
        $expiredFromParticipations = count(array_filter($participations, fn($p) => $p->getInvitationStatus() === InvitationStatus::EXPIRED));
        
        $invitationsWithoutParticipation = array_filter($invitations, function($invitation) use ($participations) {
            foreach ($participations as $participation) {
                if ($participation->getUser()->getEmail() === $invitation->getEmail()) {
                    return false;
                }
            }
            return true;
        });
        
        $baseStats = [
            'total_invitations' => count($invitations),
            'accepted' => $acceptedFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED)),
            'declined' => $declinedFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::DECLINED)),
            'pending' => $pendingFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::PENDING)),
            'expired' => $expiredFromParticipations + count(array_filter($invitationsWithoutParticipation, fn($i) => $i->getStatus() === InvitationStatus::EXPIRED)),
            'present' => count(array_filter($participations, fn($p) => $p->isPresent())),
            'absent' => count(array_filter($participations, fn($p) => !$p->isPresent())),
            'total_participants' => count($participations),
        ];

        // Métriques avancées
        $advancedMetrics = $this->calculateAdvancedMetrics($invitations, $participations, $event);
        $timeMetrics = $this->calculateTimeMetrics($invitations, $event);
        $predictiveAnalysis = $this->calculatePredictiveAnalysis($invitations, $event);

        // Données des participants
        $participantsData = [];
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $participantsData[] = [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'departement' => $user->getDepartement()?->getNom() ?? 'Non spécifié',
                'statut_invitation' => $participation->getInvitationStatus()?->value ?? 'Non défini',
                'presence' => $participation->isPresent() ? 'Présent' : 'Absent',
                'date_creation' => $participation->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        $currentUser = $this->getUser();
        $exportedBy = $currentUser instanceof User ? $currentUser->getEmail() : 'Utilisateur inconnu';

        return new JsonResponse([
            'event' => [
                'id' => $event->getId(),
                'titre' => $event->getTitle(),
                'date' => $event->getDateHeure()->format('Y-m-d H:i:s'),
                'lieu' => $event->getLieu() ?? 'Non spécifié',
                'salle' => $event->getSalle()?->getNom() ?? 'Non spécifiée',
                'organisateur' => $event->getOrganizer()->getPrenom() . ' ' . $event->getOrganizer()->getNom(),
            ],
            'statistics' => [
                'base_stats' => $baseStats,
                'advanced_metrics' => $advancedMetrics,
                'time_metrics' => $timeMetrics,
                'predictive_analysis' => $predictiveAnalysis,
            ],
            'participants' => $participantsData,
            'export_info' => [
                'generated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                'exported_by' => $exportedBy,
                'total_records' => count($participantsData),
            ]
        ], 200, [
            'Content-Disposition' => 'attachment; filename="statistiques_evenement_' . $event->getId() . '.json"'
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
                    case InvitationStatus::ACCEPTED:
                        $globalStats['accepted']++;
                        break;
                    case InvitationStatus::DECLINED:
                        $globalStats['refused']++;
                        break;
                    case InvitationStatus::PENDING:
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
                    case InvitationStatus::ACCEPTED:
                        $departmentStats[$department]['accepted']++;
                        break;
                    case InvitationStatus::DECLINED:
                        $departmentStats[$department]['refused']++;
                        break;
                    case InvitationStatus::PENDING:
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

    #[Route('/organisateur/advanced-statistics', name: 'advanced_statistics')]
    public function advancedStatistics(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user], ['dateHeure' => 'DESC']);

        // Statistiques globales avancées
        $advancedStats = [
            'total_events' => count($events),
            'total_invitations' => 0,
            'total_participations' => 0,
            'avg_acceptance_rate' => 0,
            'avg_presence_rate' => 0,
            'best_performing_event' => null,
            'worst_performing_event' => null,
            'monthly_trends' => [],
            'department_performance' => [],
            'response_time_analysis' => []
        ];

        $totalAcceptanceRates = [];
        $totalPresenceRates = [];
        $bestRate = 0;
        $worstRate = 100;
        $bestEvent = null;
        $worstEvent = null;

        // Analyser chaque événement
        foreach ($events as $event) {
            $invitations = $invitationRepository->findBy(['event' => $event]);
            $participations = $participationRepository->findBy(['event' => $event]);
            
            $accepted = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
            $present = count(array_filter($participations, fn($p) => $p->isPresent()));
            
            $totalInvitations = count($invitations);
            $totalParticipations = count($participations);
            
            $advancedStats['total_invitations'] += $totalInvitations;
            $advancedStats['total_participations'] += $totalParticipations;
            
            if ($totalInvitations > 0) {
                $acceptanceRate = ($accepted / $totalInvitations) * 100;
                $totalAcceptanceRates[] = $acceptanceRate;
                
                if ($acceptanceRate > $bestRate) {
                    $bestRate = $acceptanceRate;
                    $bestEvent = $event;
                }
                
                if ($acceptanceRate < $worstRate) {
                    $worstRate = $acceptanceRate;
                    $worstEvent = $event;
                }
            }
            
            if ($accepted > 0) {
                $presenceRate = ($present / $accepted) * 100;
                $totalPresenceRates[] = $presenceRate;
            }
        }

        $advancedStats['avg_acceptance_rate'] = count($totalAcceptanceRates) > 0 
            ? round(array_sum($totalAcceptanceRates) / count($totalAcceptanceRates), 2) 
            : 0;
        $advancedStats['avg_presence_rate'] = count($totalPresenceRates) > 0 
            ? round(array_sum($totalPresenceRates) / count($totalPresenceRates), 2) 
            : 0;
        $advancedStats['best_performing_event'] = $bestEvent;
        $advancedStats['worst_performing_event'] = $worstEvent;

        // Tendances mensuelles
        $monthlyData = [];
        foreach ($events as $event) {
            $month = $event->getDateHeure()->format('Y-m');
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'events' => 0,
                    'invitations' => 0,
                    'acceptances' => 0,
                    'presences' => 0
                ];
            }
            
            $monthlyData[$month]['events']++;
            $invitations = $invitationRepository->findBy(['event' => $event]);
            $participations = $participationRepository->findBy(['event' => $event]);
            
            $monthlyData[$month]['invitations'] += count($invitations);
            $monthlyData[$month]['acceptances'] += count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
            $monthlyData[$month]['presences'] += count(array_filter($participations, fn($p) => $p->isPresent()));
        }

        $advancedStats['monthly_trends'] = $monthlyData;

        return $this->render('stats/advanced_statistics.html.twig', [
            'advancedStats' => $advancedStats,
            'events' => $events
        ]);
    }

    
    private function calculateAdvancedMetrics(array $invitations, array $participations, $event): array
    {
        $now = new DateTime();
        $eventDate = $event->getDateHeure();
        
        // Calcul du taux d'efficacité de l'événement
        $totalInvited = count($invitations);
        $totalPresent = count(array_filter($participations, fn($p) => $p->isPresent()));
        $efficiencyRate = $totalInvited > 0 ? round(($totalPresent / $totalInvited) * 100, 2) : 0;
        
        // Calcul de la satisfaction estimée (basée sur la présence et les réponses rapides)
        $quickResponses = 0;
        $totalResponses = 0;
        
        foreach ($invitations as $invitation) {
            if ($invitation->getStatus() !== InvitationStatus::PENDING && $invitation->getUpdatedAt()) {
                $totalResponses++;
                $responseTime = $invitation->getCreatedAt()->diff($invitation->getUpdatedAt())->days;
                if ($responseTime <= 1) { // Réponse dans les 24h
                    $quickResponses++;
                }
            }
        }
        
        $satisfactionIndex = $totalResponses > 0 ? round(($quickResponses / $totalResponses) * 100, 2) : 0;
        
        // Score de popularité de l'événement
        $acceptedCount = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
        $popularityScore = $totalInvited > 0 ? round(($acceptedCount / $totalInvited) * 100, 2) : 0;
        
        return [
            'efficiency_rate' => $efficiencyRate,
            'satisfaction_index' => $satisfactionIndex,
            'popularity_score' => $popularityScore,
            'quick_response_rate' => $totalResponses > 0 ? round(($quickResponses / $totalResponses) * 100, 2) : 0,
            'event_attractiveness' => $this->calculateEventAttractiveness($invitations, $participations),
        ];
    }

    private function calculatePredictiveAnalysis(array $invitations, $event): array
    {
        $eventDate = $event->getDateHeure();
        $now = new DateTime();
        
        // Prédiction du taux de présence final
        $acceptedCount = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
        $currentPresent = count(array_filter($invitations, function($invitation) {
            // Chercher la participation correspondante
            return $invitation->getStatus() === InvitationStatus::ACCEPTED;
        }));
        
        // Estimation basée sur l'historique (simplifiée)
        $predictedAttendanceRate = $acceptedCount > 0 ? min(95, max(70, 85)) : 0; // Taux estimé entre 70% et 95%
        $predictedAttendees = round(($acceptedCount * $predictedAttendanceRate) / 100);
        
        // Probabilité de sur-réservation
        $capacite = $event->getSalle()?->getCapacite() ?? 100;
        $overbookingRisk = $acceptedCount > $capacite ? 'Élevé' : 'Faible';
        
        return [
            'predicted_attendance_rate' => $predictedAttendanceRate,
            'predicted_attendees' => $predictedAttendees,
            'overbooking_risk' => $overbookingRisk,
            'recommendation' => $this->generateRecommendation($invitations, $event),
        ];
    }

    private function calculateTimeMetrics(array $invitations, $event): array
    {
        $eventDate = $event->getDateHeure();
        $now = new DateTime();
        
        $responseTimes = [];
        $responsesByHour = [];
        
        foreach ($invitations as $invitation) {
            if ($invitation->getStatus() !== InvitationStatus::PENDING && $invitation->getUpdatedAt()) {
                $responseTime = $invitation->getCreatedAt()->diff($invitation->getUpdatedAt());
                $responseTimes[] = $responseTime->days * 24 + $responseTime->h;
                
                $hour = $invitation->getUpdatedAt()->format('H');
                $responsesByHour[$hour] = ($responsesByHour[$hour] ?? 0) + 1;
            }
        }
        
        $avgResponseTime = count($responseTimes) > 0 ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0;
        $fastestResponse = count($responseTimes) > 0 ? min($responseTimes) : 0;
        $slowestResponse = count($responseTimes) > 0 ? max($responseTimes) : 0;
        
        // Heure de pic des réponses
        $peakHour = count($responsesByHour) > 0 ? array_keys($responsesByHour, max($responsesByHour))[0] : null;
        
        return [
            'avg_response_time_hours' => $avgResponseTime,
            'fastest_response_hours' => $fastestResponse,
            'slowest_response_hours' => $slowestResponse,
            'peak_response_hour' => $peakHour,
            'responses_by_hour' => $responsesByHour,
            'time_to_event' => $eventDate > $now ? $now->diff($eventDate)->days : 0,
        ];
    }

    private function calculateEventAttractiveness(array $invitations, array $participations): string
    {
        $totalInvited = count($invitations);
        $acceptedCount = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
        $presentCount = count(array_filter($participations, fn($p) => $p->isPresent()));
        
        if ($totalInvited === 0) return 'Indéterminé';
        
        $acceptanceRate = ($acceptedCount / $totalInvited) * 100;
        $presenceRate = $acceptedCount > 0 ? ($presentCount / $acceptedCount) * 100 : 0;
        
        $overallScore = ($acceptanceRate + $presenceRate) / 2;
        
        if ($overallScore >= 80) return 'Très Attractif';
        if ($overallScore >= 60) return 'Attractif';
        if ($overallScore >= 40) return 'Modérément Attractif';
        return 'Peu Attractif';
    }

    private function generateRecommendation(array $invitations, $event): string
    {
        $pendingCount = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::PENDING));
        $acceptedCount = count(array_filter($invitations, fn($i) => $i->getStatus() === InvitationStatus::ACCEPTED));
        $totalInvited = count($invitations);
        
        $eventDate = $event->getDateHeure();
        $now = new DateTime();
        $daysUntilEvent = $eventDate > $now ? $now->diff($eventDate)->days : 0;
        
        if ($daysUntilEvent > 7 && $pendingCount > ($totalInvited * 0.3)) {
            return 'Envoyer des rappels aux participants n\'ayant pas encore répondu.';
        }
        
        if ($acceptedCount < ($totalInvited * 0.5)) {
            return 'Considérer une campagne de relance ou revoir l\'attractivité de l\'événement.';
        }
        
        if ($daysUntilEvent <= 3 && $pendingCount > 0) {
            return 'Envoyer des rappels urgents aux participants en attente.';
        }
        
        return 'L\'événement semble bien organisé. Continuer le suivi normal.';
    }

    /**
     * Calcule les statistiques de conflits d'horaire
     */
    private function calculateConflictStats(Event $event, array $participations, EntityManagerInterface $entityManager): array
    {
        $eventRepository = $entityManager->getRepository(Event::class);
        $conflictCount = 0;
        $conflictingUsers = [];
        
        // Récupérer tous les événements qui se chevauchent avec cet événement
        $eventStart = $event->getDateHeure();
        $eventEnd = clone $eventStart;
        $eventEnd->modify('+2 hours'); // Assumons 2h par défaut, à adapter selon votre logique
        
        // Rechercher les événements qui se chevauchent
        $conflictingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.id != :eventId')
            ->andWhere('e.dateHeure < :eventEnd')
            ->andWhere('DATE_ADD(e.dateHeure, 2, \'HOUR\') > :eventStart')
            ->setParameter('eventId', $event->getId())
            ->setParameter('eventStart', $eventStart)
            ->setParameter('eventEnd', $eventEnd)
            ->getQuery()
            ->getResult();
        
        // Pour chaque participation, vérifier s'il y a conflit
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $hasConflict = false;
            
            // Vérifier si l'utilisateur participe à un événement concurrent
            foreach ($conflictingEvents as $conflictingEvent) {
                $conflictingParticipations = $conflictingEvent->getParticipations();
                foreach ($conflictingParticipations as $conflictingParticipation) {
                    if ($conflictingParticipation->getUser()->getId() === $user->getId() && 
                        $conflictingParticipation->getInvitationStatus() === InvitationStatus::ACCEPTED) {
                        $hasConflict = true;
                        break 2;
                    }
                }
            }
            
            if ($hasConflict) {
                $conflictCount++;
                $conflictingUsers[] = $user->getEmail();
            }
        }
        
        $totalParticipants = count($participations);
        
        return [
            'total_conflicts' => $conflictCount,
            'conflicting_users' => $conflictingUsers,
            'conflict_rate' => $totalParticipants > 0 
                ? round(($conflictCount / $totalParticipants) * 100, 2) 
                : 0,
            'conflicting_events' => count($conflictingEvents),
        ];
    }
}
