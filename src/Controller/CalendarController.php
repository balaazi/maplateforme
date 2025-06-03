<?php

namespace App\Controller;

use App\Entity\CalendarEvent;
use App\Entity\Event;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/calendar', name: 'calendar_index')]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/oauth/connect', name: 'google_calendar_connect')]
    public function connectGoogle(GoogleCalendarService $googleCalendarService): Response
    {
        return $this->redirect($googleCalendarService->getAuthUrl());
    }

    #[Route('/oauth/callback', name: 'google_calendar_callback', methods: ['GET'])]
    public function handleCallback(Request $request, GoogleCalendarService $googleCalendarService): Response
    {
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) {
            $this->addFlash('error', 'Erreur Google: ' . $error);
            return $this->redirectToRoute('calendar_index');
        }

        if (!$code) {
            $this->addFlash('error', 'Code d\'autorisation manquant');
            return $this->redirectToRoute('calendar_index');
        }

        try {
            $googleCalendarService->fetchAccessTokenWithCode($code);
            $this->addFlash('success', 'Connexion à Google Calendar réussie');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de connexion: ' . $e->getMessage());
        }

        return $this->redirectToRoute('calendar_index');
    }

    #[Route('/calendar/sync', name: 'calendar_sync')]
    public function syncCalendar(GoogleCalendarService $googleCalendarService, EntityManagerInterface $em): Response
    {
        if (!$googleCalendarService->isAuthenticated()) {
            $this->requestStack->getSession()->set('intended_route', 'calendar_sync');
            return $this->redirectToRoute('google_calendar_connect');
        }

        try {
            $syncResults = $googleCalendarService->synchronizeCalendars();

            // Sync Events (Event -> CalendarEvent)
            $events = $em->getRepository(Event::class)->findAll();
            $exported = 0;

            foreach ($events as $event) {
                $start = $event->getDateHeure();
                $end = (clone $start)->modify('+' . $event->getDuree() . ' minutes');

                $calendarEvent = $em->getRepository(CalendarEvent::class)->findOneBy([
                    'title' => $event->getTitle(),
                    'start' => $start,
                    'end' => $end,
                ]);

                if (!$calendarEvent) {
                    $calendarEvent = new CalendarEvent();
                    $calendarEvent->setTitle($event->getTitle());
                    $calendarEvent->setDescription($event->getDescription());
                    $calendarEvent->setStart($start);
                    $calendarEvent->setEnd($end);

                    $em->persist($calendarEvent);
                    try {
                        $googleCalendarService->exportToGoogleCalendar($calendarEvent);
                        $exported++;
                    } catch (\Exception $e) {
                        // Ignorer les erreurs individuelles
                        continue;
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', sprintf(
                '✅ Synchronisation réussie: %d importés, %d exportés.',
                $syncResults['imported'],
                $exported
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de synchronisation: ' . $e->getMessage());
        }

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
                'googleEventId' => $e->getGoogleEventId(),
            ];
        }, $events);

        return $this->json($data);
    }

    #[Route('/calendar/export/{id}', name: 'export_event_to_google')]
    public function export(CalendarEvent $event, GoogleCalendarService $googleCalendarService): Response
    {
        try {
            $googleCalendarService->exportToGoogleCalendar($event);
            $this->addFlash('success', '✅ Événement exporté avec succès vers Google Calendar.');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Échec de l’export: ' . $e->getMessage());
        }

        return $this->redirectToRoute('calendar_index');
    }
    
}
