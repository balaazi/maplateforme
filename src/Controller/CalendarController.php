<?php
namespace App\Controller;

use App\Entity\CalendarEvent;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar_index')]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/calendar/connect-google', name: 'google_calendar_connect')]
    public function connectGoogle(GoogleCalendarService $googleService, Request $request): Response
    {
        // Génération d'un token state pour la protection CSRF
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('oauth_state', $state);
        
        return $this->redirect($googleService->getAuthUrl());
    }

    #[Route('/calendar/google-callback', name: 'google_calendar_callback')]
    public function googleCallback(
        Request $request,
        GoogleCalendarService $googleService,
        EntityManagerInterface $em
    ): Response {
        try {
            // Validation des paramètres OAuth
            if (!$request->query->has('code') || !$request->query->has('state')) {
                throw new AuthenticationException('Missing OAuth parameters');
            }

            // Vérification du state
            $sessionState = $request->getSession()->get('oauth_state');
            if (!$sessionState || $sessionState !== $request->query->get('state')) {
                throw new AuthenticationException('Invalid state parameter');
            }

            // Récupération du token
            $code = $request->query->get('code');
            $token = $googleService->fetchAccessTokenWithCode($code);
            
            // Synchronisation des événements
            $calendarService = $googleService->getCalendarService();
            $googleEvents = $calendarService->events->listEvents('primary', [
                'maxResults' => 250,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ])->getItems();

            // Gestion des doublons et mise à jour
            $existingEvents = $em->getRepository(CalendarEvent::class)->findAllGoogleIds();
            $batchSize = 20;
            $i = 0;

            foreach ($googleEvents as $googleEvent) {
                if (in_array($googleEvent->getId(), $existingEvents)) continue;

                $event = new CalendarEvent();
                $event
                    ->setTitle($googleEvent->getSummary() ?? '(Sans titre)')
                    ->setDescription($googleEvent->getDescription())
                    ->setStart(new \DateTime($googleEvent->getStart()->getDateTime() ?? $googleEvent->getStart()->getDate()))
                    ->setEnd(new \DateTime($googleEvent->getEnd()->getDateTime() ?? $googleEvent->getEnd()->getDate()))
                    ->setGoogleEventId($googleEvent->getId());

                $em->persist($event);

                if (($i % $batchSize) === 0) {
                    $em->flush();
                    $em->clear();
                }
                $i++;
            }

            $em->flush();
            $this->addFlash('success', 'Synchronisation réussie !');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de synchronisation : '.$e->getMessage());
        }

        return $this->redirectToRoute('calendar_index');
    }

    #[Route('/api/events', name: 'calendar_events')]
    public function getEvents(EntityManagerInterface $em): Response
    {
        try {
            $events = $em->getRepository(CalendarEvent::class)->createQueryBuilder('e')
                ->where('e.start >= :start')
                ->setParameter('start', new \DateTime('-6 months'))
                ->getQuery()
                ->getResult();

            $data = array_map(function(CalendarEvent $event) {
                return [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'start' => $event->getStart()->format(\DateTimeInterface::ATOM),
                    'end' => $event->getEnd()->format(\DateTimeInterface::ATOM),
                    'description' => $event->getDescription(),
                    'extendedProps' => [
                        'googleEventId' => $event->getGoogleEventId()
                    ]
                ];
            }, $events);

            return $this->json($data);

        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Erreur de récupération des événements'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}