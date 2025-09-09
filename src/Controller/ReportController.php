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
use App\Enum\InvitationStatus;

#[IsGranted('ROLE_ORGANISATEUR')]
class ReportController extends AbstractController
{
    #[Route('/reports', name: 'reports_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('reports/dashboard.html.twig');
    }

    #[Route('/reports/attendance', name: 'reports_attendance', methods: ['GET'])]
    public function attendanceReport(
        Request $request,
        EventRepository $eventRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $user = $this->getUser();
        // Utiliser le repository avec filtrage automatique des événements archivés
        $events = $eventRepository->findByRole($user);

        // Filtrage par dates si demandé
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $eventType = $request->query->get('event_type');

        if ($startDate || $endDate || $eventType) {
            // Filtrer les événements déjà récupérés par date et type
            $filteredEvents = [];
            foreach ($events as $event) {
                $includeEvent = true;
                
                if ($startDate && $event->getDateHeure() < new \DateTime($startDate)) {
                    $includeEvent = false;
                }
                
                if ($endDate && $event->getDateHeure() > new \DateTime($endDate . ' 23:59:59')) {
                    $includeEvent = false;
                }
                
                if ($eventType && $event->getTypeEvenement() !== $eventType) {
                    $includeEvent = false;
                }
                
                if ($includeEvent) {
                    $filteredEvents[] = $event;
                }
            }
            $events = $filteredEvents;


        }

        // Calcul des statistiques de fréquentation
        $attendanceData = [];
        $totalInvited = 0;
        $totalPresent = 0;
        $totalAbsent = 0;

        foreach ($events as $event) {
            $invitations = $invitationRepository->findBy(['event' => $event]);
            $eventStats = [
                'event' => $event,
                'invited' => count($invitations),
                'present' => 0,
                'absent' => 0,
                'response_rate' => 0,
                'attendance_rate' => 0
            ];

            $responded = 0;
            foreach ($invitations as $invitation) {
                $totalInvited++;
                if ($invitation->getStatus() !== 'pending') {
                    $responded++;
                }
                
                if ($invitation->getStatus() === 'accepted') {
                    $eventStats['present']++;
                    $totalPresent++;
                } else {
                    $eventStats['absent']++;
                    $totalAbsent++;
                }
            }

            if ($eventStats['invited'] > 0) {
                $eventStats['response_rate'] = round(($responded / $eventStats['invited']) * 100, 1);
                $eventStats['attendance_rate'] = round(($eventStats['present'] / $eventStats['invited']) * 100, 1);
            }

            $attendanceData[] = $eventStats;
        }

        return $this->render('reports/attendance.html.twig', [
            'attendanceData' => $attendanceData,
            'totalInvited' => $totalInvited,
            'totalPresent' => $totalPresent,
            'totalAbsent' => $totalAbsent,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'event_type' => $eventType,
            ]
        ]);
    }

    #[Route('/reports/participation', name: 'reports_participation', methods: ['GET'])]
    public function participationReport(
        EventRepository $eventRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $user = $this->getUser();
        // Utiliser le repository avec filtrage automatique des événements archivés
        $events = $eventRepository->findByRole($user);

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
            $totalResponses = $participationStats['accepted'] + $participationStats['declined'];
            $participationStats['response_rate'] = round(($totalResponses / $participationStats['total_invitations']) * 100, 1);
            $participationStats['acceptance_rate'] = round(($participationStats['accepted'] / $participationStats['total_invitations']) * 100, 1);
        }

        // Calcul des taux par type d'événement
        foreach ($eventTypeStats as $type => &$stats) {
            if ($stats['total_invitations'] > 0) {
                $totalResponses = $stats['accepted'] + $stats['declined'];
                $stats['response_rate'] = round(($totalResponses / $stats['total_invitations']) * 100, 1);
                $stats['acceptance_rate'] = round(($stats['accepted'] / $stats['total_invitations']) * 100, 1);
            }
        }

        return $this->render('reports/participation.html.twig', [
            'participationStats' => $participationStats,
            'eventTypeStats' => $eventTypeStats
        ]);
    }

    #[Route('/reports/department', name: 'reports_department', methods: ['GET'])]
    public function departmentReport(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository
    ): Response {
        $user = $this->getUser();
        // Utiliser le repository avec filtrage automatique des événements archivés
        $events = $eventRepository->findByRole($user);

        $departmentStats = [];
        $specialtyStats = [];

        foreach ($events as $event) {
            $participations = $participationRepository->findBy(['event' => $event]);

            foreach ($participations as $participation) {
                $participant = $participation->getUser();
                if (!$participant) continue;

                $department = $participant->getDepartement()?->getNom() ?: 'Non spécifié';
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
                $departmentStats[$department]['total_participations']++;
                $specialtyStats[$specialty]['total_participations']++;

                // Présence
                if ($participation->isPresent()) {
                    $departmentStats[$department]['present']++;
                    $specialtyStats[$specialty]['present']++;
                } else {
                    $departmentStats[$department]['absent']++;
                    $specialtyStats[$specialty]['absent']++;
                }

                // Statut d'invitation
                $status = $participation->getInvitationStatus();
                switch ($status) {
                    case 'accepted':
                        $departmentStats[$department]['accepted']++;
                        $specialtyStats[$specialty]['accepted']++;
                        break;
                    case 'declined':
                        $departmentStats[$department]['declined']++;
                        $specialtyStats[$specialty]['declined']++;
                        break;
                    default:
                        $departmentStats[$department]['pending']++;
                        $specialtyStats[$specialty]['pending']++;
                        break;
                }
            }
        }

        // Calcul des taux pour chaque département
        foreach ($departmentStats as $dept => &$stats) {
            if ($stats['total_participations'] > 0) {
                $stats['presence_rate'] = round(($stats['present'] / $stats['total_participations']) * 100, 1);
                $stats['response_rate'] = round((($stats['accepted'] + $stats['declined']) / $stats['total_participations']) * 100, 1);
            }
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
        $events = $eventRepository->findByRole($user);
        
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
                  $event->getLieu() ?? 'Non défini',
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
        $events = $eventRepository->findByRole($user);
        
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
                    $participant->getDepartement()?->getNom() ?: 'Non spécifié',
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