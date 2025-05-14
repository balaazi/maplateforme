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
        // Vérifier si l'utilisateur est authentifié
        if (!$googleService->isAuthenticated()) {
            return $this->redirectToRoute('google_calendar_connect');
        }

        try {
            // Récupérer le code d'authentification
            $code = $request->query->get('code');
            $googleService->fetchAccessTokenWithCode($code);

            // Récupérer les événements de Google Calendar
            $calendar = $googleService->getCalendarService();
            $events = $calendar->events->listEvents('primary', ['singleEvents' => true, 'orderBy' => 'startTime']);

            // Synchroniser les événements dans la base de données
            foreach ($events as $googleEvent) {
                // Vérifier si l'événement existe déjà dans la base de données
                $existingEvent = $em->getRepository(CalendarEvent::class)->findOneBy(['googleEventId' => $googleEvent->getId()]);
                if (!$existingEvent) {
                    // Si l'événement n'existe pas, on l'ajoute
                    $event = new CalendarEvent();
                    $event->setTitle($googleEvent->getSummary() ?? 'Sans titre');
                    $event->setDescription($googleEvent->getDescription());
                    $event->setStart(new \DateTime($googleEvent->getStart()->getDateTime() ?? $googleEvent->getStart()->getDate()));
                    $event->setEnd(new \DateTime($googleEvent->getEnd()->getDateTime() ?? $googleEvent->getEnd()->getDate()));
                    $event->setGoogleEventId($googleEvent->getId());
                    $em->persist($event);
                }
            }

            // Sauvegarder les événements dans la base de données
            $em->flush();

            // Message de succès
            $this->addFlash('success', 'Événements synchronisés !');
            return $this->redirectToRoute('calendar_index');
        } catch (\Google_Service_Exception $e) {
            // Gérer les erreurs lors de la récupération des événements
            $this->addFlash('error', 'Erreur lors de la récupération des événements : ' . $e->getMessage());
            return $this->redirectToRoute('calendar_index');
        }
    }

    #[Route('/api/events', name: 'calendar_events')]
    public function getEvents(EntityManagerInterface $em): Response
    {
        // Récupérer tous les événements de la base de données
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
        // Exporter l'événement vers Google Calendar
        $googleService->exportToGoogleCalendar($event);

        // Message de succès
        $this->addFlash('success', 'Événement exporté vers Google Calendar !');
        return $this->redirectToRoute('calendar_index');
    }
    #[Route('/calendar/sync', name: 'calendar_sync')]
    public function syncCalendar(): Response
    {
        // Logique pour synchroniser l'agenda (par exemple, appel à Google Calendar)
        $this->addFlash('success', 'L’agenda a été synchronisé avec succès !');
        return $this->redirectToRoute('calendar_index');  // Redirige vers l'agenda
    }
}
