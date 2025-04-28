<?php

namespace App\Controller;

use App\Service\GoogleClientService;
use App\Service\GoogleTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google\Service\Calendar as Google_Service_Calendar;
use Google\Service\Calendar\Event as Google_Event;

class GoogleController extends AbstractController
{
    private GoogleTokenService $googleTokenService;
    private GoogleClientService $googleClientService;

    public function __construct(GoogleTokenService $googleTokenService, GoogleClientService $googleClientService)
    {
        $this->googleTokenService = $googleTokenService;
        $this->googleClientService = $googleClientService;
    }

    // Route d'authentification Google
    #[Route('/google/auth', name: 'google_connect')]
    public function auth(): RedirectResponse
    {
        $client = $this->googleClientService->getClient();
        $authUrl = $client->createAuthUrl();
        return new RedirectResponse($authUrl);
    }

    // Route pour se connecter avec Google via /google/login
    #[Route('/google/login', name: 'google_login')]
    public function login(): RedirectResponse
    {
        // Redirige vers l'URL d'authentification Google
        return $this->redirectToRoute('google_connect');
    }

    // Route callback pour la redirection après l'authentification
    #[Route('/google/callback', name: 'google_callback')]
    public function callback(Request $request): RedirectResponse
    {
        $client = $this->googleClientService->getClient();
        $code = $request->query->get('code');

        if (!$code) {
            $errorMessage = 'Code d\'autorisation manquant. URL de la requête : ' . $request->getUri();
            throw new \Exception($errorMessage);
        }

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la récupération du token : ' . $e->getMessage());
        }

        if (isset($token['error'])) {
            throw new \Exception('Erreur lors de la récupération du token : ' . $token['error_description']);
        }

        $this->googleTokenService->storeTokens($token['access_token'], $token['refresh_token'] ?? null);

        return $this->redirectToRoute('google_events');
    }

    // Route pour afficher les événements
    #[Route('/google/events', name: 'google_events')]
    public function showEvents(): Response
    {
        $accessToken = $this->googleTokenService->getAccessToken();
        $refreshToken = $this->googleTokenService->getRefreshToken();

        if (!$accessToken || !$refreshToken) {
            return $this->redirectToRoute('google_connect');
        }

        $client = $this->googleClientService->getClient();
        $client->setAccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3599,
            'created' => time(),
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->googleTokenService->storeTokens($newToken['access_token'], $newToken['refresh_token'] ?? null);
        }

        $service = new Google_Service_Calendar($client);
        $calendarId = 'primary';

        $optParams = [
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => (new \DateTime())->format(\DateTime::RFC3339),
        ];

        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();

        return $this->render('google/events.html.twig', [
            'events' => $events,
        ]);
    }

    // Route pour ajouter un événement à Google Calendar
    #[Route('/google/add-event', name: 'google_add_event')]
    public function addEvent(Request $request): Response
    {
        $accessToken = $this->googleTokenService->getAccessToken();
        $refreshToken = $this->googleTokenService->getRefreshToken();

        if (!$accessToken || !$refreshToken) {
            return $this->redirectToRoute('google_connect');
        }

        $client = $this->googleClientService->getClient();
        $client->setAccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3599,
            'created' => time(),
        ]);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->googleTokenService->storeTokens($newToken['access_token'], $newToken['refresh_token'] ?? null);
        }

        $service = new Google_Service_Calendar($client);
        $calendarId = 'primary';

        $event = new Google_Event([
            'summary' => 'Réunion de travail',
            'location' => 'Bureau principal',
            'description' => 'Réunion pour discuter de l\'avancement du projet.',
            'start' => [
                'dateTime' => '2025-05-15T10:009:00',
                'timeZone' => 'Europe/Paris',
            ],
            'end' => [
                'dateTime' => '2025-05-15T11:10:00',
                'timeZone' => 'Europe/Paris',
            ],
            'attendees' => [
                ['email' => 'nadiabalaazi@gmail.com'],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ]);

        // Insérer l'événement dans le calendrier Google
        try {
            $event = $service->events->insert($calendarId, $event);
            return $this->redirectToRoute('google_events');
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'ajout de l\'événement: ' . $e->getMessage()]);
        }
    }
}
