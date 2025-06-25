<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/calendar')]
#[IsGranted('ROLE_ORGANISATEUR')]
class CalendarApiController extends AbstractController
{
    #[Route('/events', name: 'api_calendar_events', methods: ['GET'])]
    public function getEvents(ReservationRepository $reservationRepository): JsonResponse
    {
        $events = $reservationRepository->getCalendarEvents();
        
        return new JsonResponse($events);
    }
} 