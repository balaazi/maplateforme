<?php

namespace App\Controller;

use App\Entity\CalendarEvent;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar_index')]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/calendar/connect-google', name: 'google_calendar_connect')]
    public function connectGoogle(GoogleCalendarService $googleService): Response
    {
        return $this->redirect($googleService->getAuthUrl());
    }

    #[Route('/calendar/google-callback', name: 'google_calendar_callback')]
    public function googleCallback(Request $request, GoogleCalendarService $googleService, EntityManagerInterface $em): Response
    {
        $code = $request->query->get('code');
        $googleService->fetchAccessTokenWithCode($code);

        $calendar = $googleService->getCalendarService();
        $events = $calendar->events->listEvents('primary', ['singleEvents' => true, 'orderBy' => 'startTime']);

        foreach ($events as $googleEvent) {
            $event = new CalendarEvent();
            $event->setTitle($googleEvent->getSummary() ?? 'Sans titre');
            $event->setDescription($googleEvent->getDescription());
            $event->setStart(new \DateTime($googleEvent->getStart()->getDateTime() ?? $googleEvent->getStart()->getDate()));
            $event->setEnd(new \DateTime($googleEvent->getEnd()->getDateTime() ?? $googleEvent->getEnd()->getDate()));
            $event->setGoogleEventId($googleEvent->getId());
            $em->persist($event);
        }

        $em->flush();
        $this->addFlash('success', 'Événements synchronisés !');
        return $this->redirectToRoute('calendar_index');
    }

     #[Route('/api/events', name: 'calendar_events')]
     public function getEvents(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(CalendarEvent::class)->findAll();
        $data = array_map(function ($e) {
            return [
                'title' => $e->getTitle(),
                'start' => $e->getStart()->format(\DateTimeInterface::ATOM),
                'end' => $e->getEnd()->format(\DateTimeInterface::ATOM),
                'description' => $e->getDescription(),
            ];
        }, $events);

        return $this->json($data);
    }
    #[Route('/calendar/export/{id}', name: 'export_event_to_google')]
    public function export(CalendarEvent $event, GoogleCalendarService $googleService): Response
    {
        $googleService->exportToGoogleCalendar($event);
        $this->addFlash('success', 'Événement exporté vers Google Calendar !');
        return $this->redirectToRoute('calendar_index');
    }

}
