<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events')]
class EventApiController extends AbstractController
{
    #[Route('', name: 'api_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAll();

        $data = array_map(function (Event $event) {
            // Récupération de la date de début
        $start = \DateTime::createFromInterface($event->getDateHeure());

            // Clonage de l'objet DateTime et modification pour ajouter la durée
            $end = (clone $start)->modify('+' . $event->getDuree() . ' minutes');

            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
            ];
        }, $events);

        return $this->json($data);
    }
}
