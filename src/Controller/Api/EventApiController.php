<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/events')]
#[IsGranted('ROLE_USER')]
class EventApiController extends AbstractController
{
    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Filtrer les événements selon le rôle de l'utilisateur
        if ($this->isGranted('ROLE_ORGANISATEUR') || $this->isGranted('ROLE_ADMIN')) {
            // ADMIN: voit tous les événements / ORGANISATEUR: voit ses événements + participations
            $events = $eventRepository->findByRole($user);
        } else {
            // Les participants ne voient que les événements qu'ils ont acceptés
            $events = $eventRepository->findAcceptedEventsForParticipant($user);
        }

        $data = array_map(function (Event $event) use ($user) {
            // Récupération de la date de début
            $start = \DateTime::createFromInterface($event->getDateHeure());

            // Clonage de l'objet DateTime et modification pour ajouter la durée
            $end = (clone $start)->modify('+' . $event->getDuree() . ' minutes');

            // Déterminer le type d'événement pour le style
            $type = $this->getEventType($event);
            
            // Déterminer le rôle de l'utilisateur pour cet événement
            $userRole = $this->getUserRoleForEvent($event, $user);
            
            // Pour les participants, les événements acceptés sont toujours en vert mais gardent leur type
            $isAcceptedParticipant = !$this->isGranted('ROLE_ORGANISATEUR') && !$this->isGranted('ROLE_ADMIN');
            
            // Générer l'URL uniquement si l'utilisateur a accès à l'événement
            $eventData = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'description' => $event->getDescription(),
                'extendedProps' => [
                    'type' => $type, // Garder le type d'origine
                    'originalType' => $type, // Type d'origine pour référence
                    'organizer' => $event->getOrganizer() ? $event->getOrganizer()->getNom() . ' ' . $event->getOrganizer()->getPrenom() : 'Non défini',
                    'lieu' => $event->getLieu(),
                    'role' => $userRole,
                    'isAccepted' => $isAcceptedParticipant
                ]
            ];
            
            // Ajouter l'URL pour tous les événements visibles par l'utilisateur
            $eventData['url'] = $this->generateUrl('event_show', ['id' => $event->getId()]);
            

            
            return $eventData;
        }, $events);

        return $this->json($data);
    }

    /**
     * Détermine le type d'événement basé sur son titre ou sa description
     */
    private function getEventType(Event $event): string
    {
        $title = strtolower($event->getTitle());
        $description = strtolower($event->getDescription() ?? '');
        
        if (strpos($title, 'formation') !== false || strpos($description, 'formation') !== false) {
            return 'formation';
        }
        if (strpos($title, 'réunion') !== false || strpos($title, 'reunion') !== false || 
            strpos($description, 'réunion') !== false || strpos($description, 'reunion') !== false) {
            return 'reunion';
        }

        if (strpos($title, 'séminaire') !== false || strpos($title, 'seminaire') !== false ||
            strpos($description, 'séminaire') !== false || strpos($description, 'seminaire') !== false) {
            return 'seminaire';
        }
        
        return 'formation'; // Type par défaut
    }

    /**
     * Détermine le rôle de l'utilisateur pour cet événement spécifique
     */
    private function getUserRoleForEvent(Event $event, $user): string
    {
        // Si l'utilisateur est le créateur de l'événement
        if ($event->getCreatedBy() === $user) {
            return 'créateur';
        }
        
        // Si l'utilisateur est administrateur, il peut voir tous les événements
        if ($this->isGranted('ROLE_ADMIN')) {
            return 'administrateur';
        }
        
        // Si l'utilisateur est un participant simple, il a accepté l'événement
        if (!$this->isGranted('ROLE_ORGANISATEUR')) {
            return 'participant_accepté';
        }
        
        // Pour les organisateurs qui visualisent des événements qu'ils n'ont pas créés
        return 'organisateur_observateur';
    }
}
