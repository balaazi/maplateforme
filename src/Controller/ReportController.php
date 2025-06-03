<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ORGANISATEUR')]
class ReportController extends AbstractController
{
    #[Route('/reports', name: 'reports_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('reports/dashboard.html.twig');
    }

    #[Route('/reports/attendance', name: 'reports_attendance')]
    public function attendanceReport(
        Request $request,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): Response {
        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user], ['dateHeure' => 'DESC']);

        // Filtrage par dates si demandé
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $eventType = $request->query->get('event_type');

        if ($startDate || $endDate || $eventType) {
            $qb = $eventRepository->createQueryBuilder('e')
                ->where('e.organizer = :organizer')
                ->setParameter('organizer', $user);

            if ($startDate) {
                $qb->andWhere('e.dateHeure >= :startDate')
                   ->setParameter('startDate', new \DateTime($startDate));
            }

            if ($endDate) {
                $qb->andWhere('e.dateHeure <= :endDate')
                   ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
            }

            if ($eventType) {
                $qb->andWhere('e.typeEvenement = :eventType')
                   ->setParameter('eventType', $eventType);
            }

            $events = $qb->orderBy('e.dateHeure', 'DESC')->getQuery()->getResult();
        }

        // Calcul des statistiques de fréquentation
        $attendanceData = [];
        $totalInvited = 0;
        $totalPresent = 0;
        $totalAbsent = 0;

        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);
            $invited = count($participations);
            $present = count(array_filter($participations, fn($p) => $p->isPresent()));
            $absent = $invited - $present;

            $attendanceRate = $invited > 0 ? round(($present / $invited) * 100, 2) : 0;

            $attendanceData[] = [
                'event' => $event,
                'invited' => $invited,
                'present' => $present,
                'absent' => $absent,
                'attendance_rate' => $attendanceRate
            ];

            $totalInvited += $invited;
            $totalPresent += $present;
            $totalAbsent += $absent;
        }

        $globalAttendanceRate = $totalInvited > 0 ? round(($totalPresent / $totalInvited) * 100, 2) : 0;

        return $this->render('reports/attendance.html.twig', [
            'attendanceData' => $attendanceData,
            'totalInvited' => $totalInvited,
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'globalAttendanceRate' => $globalAttendanceRate,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'event_type' => $eventType
            ]
        ]);
    }

    #[Route('/reports/participation-analysis', name: 'reports_participation')]
    public function participationAnalysis(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user]);

        // Analyse des taux de participation
        $participationStats = [
            'total_events' => count($events),
            'total_invitations' => 0,
            'accepted' => 0,
            'declined' => 0,
            'pending' => 0,
            'response_rate' => 0,
            'acceptance_rate' => 0
        ];

        $eventTypeStats = [];

        foreach ($events as $event) {
            $invitations = $invitationRepository->findBy(['event' => $event]);
            $eventType = $event->getTypeEvenement() ?: 'Non spécifié';

            if (!isset($eventTypeStats[$eventType])) {
                $eventTypeStats[$eventType] = [
                    'total_events' => 0,
                    'total_invitations' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'pending' => 0
                ];
            }

            $eventTypeStats[$eventType]['total_events']++;

            foreach ($invitations as $invitation) {
                $participationStats['total_invitations']++;
                $eventTypeStats[$eventType]['total_invitations']++;

                switch ($invitation->getStatus()) {
                    case 'accepted':
                        $participationStats['accepted']++;
                        $eventTypeStats[$eventType]['accepted']++;
                        break;
                    case 'declined':
                        $participationStats['declined']++;
                        $eventTypeStats[$eventType]['declined']++;
                        break;
                    case 'pending':
                        $participationStats['pending']++;
                        $eventTypeStats[$eventType]['pending']++;
                        break;
                }
            }
        }

        // Calcul des taux
        if ($participationStats['total_invitations'] > 0) {
            $responded = $participationStats['accepted'] + $participationStats['declined'];
            $participationStats['response_rate'] = round(($responded / $participationStats['total_invitations']) * 100, 2);
            $participationStats['acceptance_rate'] = round(($participationStats['accepted'] / $participationStats['total_invitations']) * 100, 2);
        }

        // Calcul des taux par type d'événement
        foreach ($eventTypeStats as &$stats) {
            if ($stats['total_invitations'] > 0) {
                $responded = $stats['accepted'] + $stats['declined'];
                $stats['response_rate'] = round(($responded / $stats['total_invitations']) * 100, 2);
                $stats['acceptance_rate'] = round(($stats['accepted'] / $stats['total_invitations']) * 100, 2);
            } else {
                $stats['response_rate'] = 0;
                $stats['acceptance_rate'] = 0;
            }
        }

        return $this->render('reports/participation.html.twig', [
            'participationStats' => $participationStats,
            'eventTypeStats' => $eventTypeStats
        ]);
    }

    #[Route('/reports/department-analysis', name: 'reports_department')]
    public function departmentAnalysis(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $user]);

        $departmentStats = [];
        $specialtyStats = [];

        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);

            foreach ($participations as $participation) {
                $participant = $participation->getUser();
                if (!$participant) continue;

                $department = $participant->getDepartement() ?: 'Non spécifié';
                $specialty = $participant->getSpecialite() ?: 'Non spécifiée';

                // Statistiques par département
                if (!isset($departmentStats[$department])) {
                    $departmentStats[$department] = [
                        'total_participations' => 0,
                        'present' => 0,
                        'absent' => 0,
                        'accepted' => 0,
                        'declined' => 0,
                        'pending' => 0
                    ];
                }

                // Statistiques par spécialité
                if (!isset($specialtyStats[$specialty])) {
                    $specialtyStats[$specialty] = [
                        'total_participations' => 0,
                        'present' => 0,
                        'absent' => 0,
                        'accepted' => 0,
                        'declined' => 0,
                        'pending' => 0
                    ];
                }

                // Mise à jour des compteurs
                foreach ([$departmentStats[$department], $specialtyStats[$specialty]] as &$stats) {
                    $stats['total_participations']++;

                    if ($participation->isPresent()) {
                        $stats['present']++;
                    } else {
                        $stats['absent']++;
                    }

                    switch ($participation->getInvitationStatus()) {
                        case 'accepted':
                            $stats['accepted']++;
                            break;
                        case 'declined':
                            $stats['declined']++;
                            break;
                        case 'pending':
                            $stats['pending']++;
                            break;
                    }
                }
            }
        }

        // Calcul des taux pour chaque département
        foreach ($departmentStats as &$stats) {
            $stats['attendance_rate'] = $stats['total_participations'] > 0 
                ? round(($stats['present'] / $stats['total_participations']) * 100, 2) 
                : 0;
            $stats['response_rate'] = $stats['total_participations'] > 0 
                ? round((($stats['accepted'] + $stats['declined']) / $stats['total_participations']) * 100, 2) 
                : 0;
        }

        // Calcul des taux pour chaque spécialité
        foreach ($specialtyStats as &$stats) {
            $stats['attendance_rate'] = $stats['total_participations'] > 0 
                ? round(($stats['present'] / $stats['total_participations']) * 100, 2) 
                : 0;
            $stats['response_rate'] = $stats['total_participations'] > 0 
                ? round((($stats['accepted'] + $stats['declined']) / $stats['total_participations']) * 100, 2) 
                : 0;
        }

        return $this->render('reports/department.html.twig', [
            'departmentStats' => $departmentStats,
            'specialtyStats' => $specialtyStats
        ]);
    }

    #[Route('/reports/export/{type}', name: 'reports_export')]
    public function exportReport(
        string $type,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): Response {
        $user = $this->getUser();
        
        switch ($type) {
            case 'attendance':
                return $this->exportAttendanceCSV($eventRepository, $participationRepository, $user);
            case 'participation':
                return $this->exportParticipationCSV($eventRepository, $participationRepository, $user);
            default:
                throw $this->createNotFoundException('Type de rapport non trouvé');
        }
    }

    private function exportAttendanceCSV($eventRepository, $participationRepository, $user): Response
    {
        $events = $eventRepository->findBy(['organizer' => $user], ['dateHeure' => 'DESC']);
        
        $csvContent = "Événement,Date,Lieu,Invités,Présents,Absents,Taux de présence\n";
        
        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);
            $invited = count($participations);
            $present = count(array_filter($participations, fn($p) => $p->isPresent()));
            $absent = $invited - $present;
            $rate = $invited > 0 ? round(($present / $invited) * 100, 2) : 0;
            
            $csvContent .= sprintf(
                "%s,%s,%s,%d,%d,%d,%.2f%%\n",
                $event->getTitle(),
                $event->getDateHeure()->format('d/m/Y H:i'),
                $event->getLieu(),
                $invited,
                $present,
                $absent,
                $rate
            );
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport_frequentation_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    private function exportParticipationCSV($eventRepository, $participationRepository, $user): Response
    {
        $events = $eventRepository->findBy(['organizer' => $user], ['dateHeure' => 'DESC']);
        
        $csvContent = "Événement,Participant,Email,Département,Spécialité,Statut,Présent\n";
        
        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);
            
            foreach ($participations as $participation) {
                $participant = $participation->getUser();
                if (!$participant) continue;
                
                $csvContent .= sprintf(
                    "%s,%s %s,%s,%s,%s,%s,%s\n",
                    $event->getTitle(),
                    $participant->getNom(),
                    $participant->getPrenom(),
                    $participant->getEmail(),
                    $participant->getDepartement() ?: 'Non spécifié',
                    $participant->getSpecialite() ?: 'Non spécifiée',
                    $participation->getInvitationStatus() ?: 'Non défini',
                    $participation->isPresent() ? 'Oui' : 'Non'
                );
            }
        }

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport_participation_' . date('Y-m-d') . '.csv"');

        return $response;
    }
} 