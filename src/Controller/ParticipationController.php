<?php
namespace App\Controller;
use App\Entity\Event;
use App\Entity\Participation;
use App\Entity\CalendarEvent;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Enum\InvitationStatus;
use App\Service\GoogleCalendarService;

#[IsGranted('ROLE_PARTICIPANT')]
class ParticipationController extends AbstractController
{
    #[Route('/event/{id}/presence', name: 'event_presence', methods: ['GET'])]
    public function showPresence(
        Event $event,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() === $user) {
            // L'organisateur peut voir sa propre présence
            // Créer une participation fictive pour l'organisateur s'il n'en a pas
            $participation = $participationRepository->findOneBy([
                'event' => $event,
                'user' => $user
            ]);
            
            if (!$participation) {
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus(InvitationStatus::ACCEPTED->value);
                $participation->setIsPresent(true); // L'organisateur est présent par défaut
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
                $entityManager->flush();
            }
            
            // L'organisateur peut toujours marquer sa présence
            $today = new \DateTime('today');
            $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
            $canMarkPresence = true; // L'organisateur peut toujours marquer sa présence
            
            return $this->render('event/presence.html.twig', [
                'event' => $event,
                'participation' => $participation,
                'canMarkPresence' => $canMarkPresence,
                'eventDate' => $eventDate
            ]);
        }
        
        // Vérifier d'abord s'il existe une participation
        $participation = $participationRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Si pas de participation, vérifier s'il y a une invitation acceptée
        if (!$participation) {
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            if ($invitation) {
                // Créer une nouvelle participation
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus(InvitationStatus::ACCEPTED->value);
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
                $entityManager->flush();
            } else {
                $this->addFlash('error', 'Vous n\'êtes pas inscrit à cet événement');
                return $this->redirectToRoute('event_list');
            }
        }

        // Vérifier que c'est le jour de l'événement pour les participants
        $today = new \DateTime('today');
        $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
        $canMarkPresence = $eventDate <= $today;

        return $this->render('event/presence.html.twig', [
            'event' => $event,
            'participation' => $participation,
            'canMarkPresence' => $canMarkPresence,
            'eventDate' => $eventDate
        ]);
    }

    #[Route('/event/{id}/mark-presence', name: 'mark_presence', methods: ['POST'])]
    public function markPresence(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository,
        GoogleCalendarService $googleCalendarService
    ): Response {
        $user = $this->getUser();
        
        // Vérifier que c'est le jour de l'événement (sauf pour l'organisateur)
        $today = new \DateTime('today');
        $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
        
        // Vérifier si l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() === $user) {
            // L'organisateur peut marquer sa propre présence
            $participation = $participationRepository->findOneBy([
                'event' => $event,
                'user' => $user
            ]);
            
            if (!$participation) {
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus(InvitationStatus::ACCEPTED->value);
                $participation->setCreatedAt(new \DateTime());
                $entityManager->persist($participation);
            }
            
            // Récupérer la valeur de présence du formulaire
            $isPresent = $request->request->get('is_present') === 'true';
            
            // Mettre à jour la présence
            $participation->setIsPresent($isPresent);
            
            $entityManager->flush();
            
            // Si l'utilisateur a confirmé sa présence, ajouter l'événement à son agenda Google
            $agendaAjoute = false;
            error_log("DEBUG: isPresent = " . ($isPresent ? 'true' : 'false'));
            error_log("DEBUG: isAuthenticated = " . ($googleCalendarService->isAuthenticated() ? 'true' : 'false'));
            if ($isPresent && $googleCalendarService->isAuthenticated()) {
                try {
                    // Créer un événement de calendrier
                    $calendarEvent = new CalendarEvent();
                    $calendarEvent->setTitle($event->getTitle());
                    $calendarEvent->setDescription($event->getDescription());
                    $calendarEvent->setStart($event->getDateHeure());
                    
                    // Calculer l'heure de fin en fonction de la durée
                    $end = (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes');
                    $calendarEvent->setEnd($end);
                    
                    // Exporter vers Google Calendar
                    $googleCalendarService->exportToGoogleCalendar($calendarEvent);
                    $agendaAjoute = true;
                } catch (\Exception $e) {
                    // Log l'erreur pour le débogage
                    error_log("Erreur ajout agenda Google: " . $e->getMessage());
                    error_log("Trace: " . $e->getTraceAsString());
                }
            }

            // Retourner une réponse JSON
            return $this->json([
                'success' => true,
                'message' => $isPresent ? 'Présence confirmée' . ($agendaAjoute ? ' et événement ajouté à votre agenda' : '') : 'Absence confirmée',
                'isPresent' => $isPresent,
                'agendaAjoute' => $agendaAjoute
            ]);
        }
        
        // Vérifier la date pour les participants (pas pour l'organisateur)
        if ($eventDate > $today) {
            return $this->json([
                'success' => false,
                'message' => 'Vous pourrez marquer votre présence le jour de l\'événement (' . $event->getDateHeure()->format('d/m/Y') . ')'
            ], 403);
        }
        
        // Trouver la participation de l'utilisateur pour cet événement
        $participation = $participationRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Si pas de participation, vérifier s'il y a une invitation acceptée
        if (!$participation) {
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            if ($invitation) {
                // Créer une nouvelle participation
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus(InvitationStatus::ACCEPTED->value);
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas inscrit à cet événement'
                ], Response::HTTP_NOT_FOUND);
            }
        }

        // Récupérer la valeur de présence du formulaire
        $isPresent = $request->request->get('is_present') === 'true';
        
        // Mettre à jour la présence
        $participation->setIsPresent($isPresent);
        
        $entityManager->flush();
        
        // Si l'utilisateur a confirmé sa présence, ajouter l'événement à son agenda Google
        $agendaAjoute = false;
        error_log("DEBUG: isPresent = " . ($isPresent ? 'true' : 'false'));
        error_log("DEBUG: isAuthenticated = " . ($googleCalendarService->isAuthenticated() ? 'true' : 'false'));
        if ($isPresent && $googleCalendarService->isAuthenticated()) {
            try {
                // Créer un événement de calendrier
                $calendarEvent = new CalendarEvent();
                $calendarEvent->setTitle($event->getTitle());
                $calendarEvent->setDescription($event->getDescription());
                $calendarEvent->setStart($event->getDateHeure());
                
                // Calculer l'heure de fin en fonction de la durée
                $end = (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes');
                $calendarEvent->setEnd($end);
                
                // Exporter vers Google Calendar
                $googleCalendarService->exportToGoogleCalendar($calendarEvent);
                $agendaAjoute = true;
            } catch (\Exception $e) {
                // Log l'erreur pour le débogage
                error_log("Erreur ajout agenda Google: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());
            }
        }

        // Retourner une réponse JSON
        return $this->json([
            'success' => true,
            'message' => $isPresent ? 'Présence confirmée' . ($agendaAjoute ? ' et événement ajouté à votre agenda' : '') : 'Absence confirmée',
            'isPresent' => $isPresent,
            'agendaAjoute' => $agendaAjoute
        ]);
    }

    #[Route('/participation/update-presence', name: 'update_participation_presence', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function updateParticipationPresence(
        Request $request,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $participationId = $request->request->get('participation_id');
        $isPresent = $request->request->get('is_present') === 'true';

        if (!$participationId) {
            return $this->json([
                'success' => false,
                'message' => 'ID de participation manquant'
            ], 400);
        }

        $participation = $participationRepository->find($participationId);
        
        if (!$participation) {
            return $this->json([
                'success' => false,
                'message' => 'Participation non trouvée'
            ], 404);
        }

        $event = $participation->getEvent();
        $user = $this->getUser();

        // Vérifier que l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'success' => false,
                'message' => 'Seul l\'organisateur peut modifier la présence'
            ], 403);
        }

        // Vérifier que c'est le jour de l'événement
        $today = new \DateTime('today');
        $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
        
        if ($eventDate > $today) {
            return $this->json([
                'success' => false,
                'message' => 'La gestion de présence ne sera disponible que le jour de l\'événement (' . $event->getDateHeure()->format('d/m/Y') . ')'
            ], 403);
        }

        // Pas de validation de changement de statut pour permettre de re-marquer avec le même statut
        // Cela permet de corriger l'interface si elle est désynchronisée

        // Mettre à jour la présence et marquer comme validée
        $participation->setIsPresent($isPresent);
        $participation->setPresenceValidated(true);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => $isPresent ? 'Participant marqué présent' : 'Participant marqué absent',
            'isPresent' => $isPresent
        ]);
    }
    #[Route('/participation/reset-presence', name: 'reset_participation_presence', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function resetParticipationPresence(
        Request $request,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $participationId = $request->request->get('participation_id');

        if (!$participationId) {
            return $this->json([
                'success' => false,
                'message' => 'ID de participation manquant'
            ], 400);
        }

        $participation = $participationRepository->find($participationId);
        
        if (!$participation) {
            return $this->json([
                'success' => false,
                'message' => 'Participation non trouvée'
            ], 404);
        }

        $event = $participation->getEvent();
        $user = $this->getUser();

        // Vérifier que l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json([
                'success' => false,
                'message' => 'Seul l\'organisateur peut modifier la présence'
            ], 403);
        }

        // Réinitialiser la présence
        $participation->setPresenceValidated(false);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Statut de présence réinitialisé'
        ]);
    }
} 