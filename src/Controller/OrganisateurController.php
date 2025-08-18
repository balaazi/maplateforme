<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur')]
#[IsGranted('ROLE_ORGANISATEUR')]
class OrganisateurController extends AbstractController
{
    #[Route('/', name: 'organisateur_dashboard')]
    public function dashboard(
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            throw new \Exception('Utilisateur non connecté');
        }
        
        $now = new \DateTime();
        
        // Statistiques personnelles de l'organisateur - REQUÊTE SIMPLIFIÉE
        $myEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.organizer = :user')
            ->andWhere('e.archive = false')
            ->orderBy('e.dateHeure', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
        $totalEvents = count($myEvents);
        
        // REQUÊTE DE TEST : compter directement avec SQL
        $testCount = $eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.organizer = :user')
            ->andWhere('e.archive = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Si les deux comptages diffèrent, il y a un problème
        if ($totalEvents != $testCount) {
            dd("ERREUR: count() = $totalEvents, SQL = $testCount");
        }
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.organizer = :user')
            ->andWhere('e.dateHeure > :now')
            ->andWhere('e.archive = false')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Événements récents (5 derniers)
        $recentEvents = array_slice($myEvents, 0, 5);
        
        // Statistiques des participants
        $totalParticipants = $participationRepository->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.user)')
            ->join('p.event', 'e')
            ->where('e.organizer = :user')
            ->andWhere('e.archive = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Taux de participation
        $totalInvitations = $invitationRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->join('i.event', 'e')
            ->where('e.organizer = :user')
            ->andWhere('e.archive = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
        
        $participationRate = $totalInvitations > 0 ? round(($totalParticipants / $totalInvitations) * 100) : 0;
        
        // Événements par catégorie
        $eventsByCategory = [];
        foreach ($myEvents as $event) {
            $category = $event->getCategory() ?? 'Autre';
            if (!isset($eventsByCategory[$category])) {
                $eventsByCategory[$category] = 0;
            }
            $eventsByCategory[$category]++;
        }
        
        // Événements prioritaires (prochains)
        $priorityEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.organizer = :user')
            ->andWhere('e.dateHeure > :now')
            ->andWhere('e.archive = false')
            ->orderBy('e.dateHeure', 'ASC')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
        
        // Activité récente
        $recentActivity = [];
        
        // Ajouter les événements créés récemment
        foreach (array_slice($myEvents, 0, 3) as $event) {
            $recentActivity[] = [
                'type' => 'event_created',
                'title' => 'Événement créé',
                'description' => $event->getTitle(),
                'icon' => 'calendar-plus',
                'color' => '#4facfe',
                'date' => $event->getDateHeure(),
                'category' => 'events'
            ];
        }
        

        
        // Trier par date
        usort($recentActivity, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        // Si pas d'activité récente, ajouter un message
        if (empty($recentActivity)) {
            $recentActivity[] = [
                'type' => 'no_activity',
                'title' => 'Aucune activité récente',
                'description' => 'Commencez par créer des événements pour voir votre activité',
                'icon' => 'calendar-plus',
                'color' => '#8b5cf6',
                'date' => new \DateTime(),
                'category' => 'info'
            ];
        }
        
        return $this->render('organisateur/dashboard.html.twig', [
            'user' => $user,
            'stats' => [
                'total_events' => $totalEvents,
                'upcoming_events' => $upcomingEvents,
                'total_participants' => $totalParticipants,
                'participation_rate' => $participationRate,
                'events_by_category' => $eventsByCategory
            ],
            'recent_events' => $recentEvents,
            'priority_events' => $priorityEvents,
            'recent_activity' => $recentActivity
        ]);
    }
}
