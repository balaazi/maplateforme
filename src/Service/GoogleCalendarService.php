<?php

namespace App\Service;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleServiceCalendar;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Google\Service\Calendar\Event;
use App\Entity\CalendarEvent;
use App\Repository\CalendarEventRepository;

class GoogleCalendarService
{
    private GoogleClient $client;
    private $user;

    public function __construct(
        string $googleClientId,
        string $googleClientSecret,
        private UrlGeneratorInterface $router,
        private Security $security,
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private CalendarEventRepository $eventRepository
    ) {
        $this->user = $this->security->getUser();
        $this->initializeClient("1033191673793-51257rfro8soq2etien8hgovctl3igls.apps.googleusercontent.com", "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");
        //$this->initializeClient($googleClientId, $googleClientSecret);
        $this->handleExistingToken();
    }

    private function initializeClient(string $clientId, string $clientSecret): void
    {
        $this->client = new GoogleClient();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri('http://127.0.0.1:8000/google/callback');
        $this->client->addScope(GoogleServiceCalendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    private function handleExistingToken(): void
    {
        if ($this->user && method_exists($this->user, 'getGoogleToken')) {
            $token = $this->user->getGoogleToken();

            if ($token && is_string($token)) {
                try {
                    $decoded = $this->safeJsonDecode($token);

                    if (!is_array($decoded) || !isset($decoded['access_token'])) {
                        throw new \InvalidArgumentException("Le token est invalide : il manque 'access_token'.");
                    }

                    $this->client->setAccessToken($decoded);

                    if ($this->client->isAccessTokenExpired()) {
                        $this->refreshToken();
                    }
                } catch (\RuntimeException $e) {
                    error_log('Google token error: ' . $e->getMessage());
                    return;
                }
            }
        }
    }

    private function safeJsonDecode(string $json): array
    {
        $cleaned = trim($json);
        $cleaned = preg_replace('/[[:^print:]]/', '', $cleaned);
        $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
        $cleaned = iconv('UTF-8', 'UTF-8//IGNORE', $cleaned);

        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{(?:[^{}]|(?R))*\}/', $cleaned, $matches)) {
                $decoded = json_decode($matches[0], true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Erreur JSON : ' . json_last_error_msg());
            }
        }

        return $decoded;
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessTokenWithCode(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \Exception('Erreur d\'authentification Google: ' . $token['error']);
        }

        $tokenJson = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($this->user && method_exists($this->user, 'setGoogleToken')) {
            $this->user->setGoogleToken($tokenJson);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        $this->client->setAccessToken($token);
        return $token;
    }

    public function getCalendarService(): GoogleServiceCalendar
    {
        return new GoogleServiceCalendar($this->client);
    }

    public function refreshToken(): void
    {
        $refreshToken = $this->client->getRefreshToken();
        if (!$refreshToken) {
            throw new \RuntimeException('Pas de refresh token disponible');
        }

        $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            throw new \RuntimeException('Erreur de rafraîchissement du token: ' . $token['error']);
        }

        $tokenJson = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($this->user && method_exists($this->user, 'setGoogleToken')) {
            $this->user->setGoogleToken($tokenJson);
            $this->em->persist($this->user);
            $this->em->flush();
        }

        $this->client->setAccessToken($token);
    }

    public function exportToGoogleCalendar(CalendarEvent $localEvent): void
    {
        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken();
        }

        $calendar = $this->getCalendarService();

        $event = new Event([
            'summary'     => $localEvent->getTitle(),
            'description' => $localEvent->getDescription(),
            'start'       => ['dateTime' => $localEvent->getStart()->format(DATE_RFC3339)],
            'end'         => ['dateTime' => $localEvent->getEnd()->format(DATE_RFC3339)],
        ]);

        try {
            $createdEvent = $calendar->events->insert('primary', $event);
            $localEvent->setGoogleEventId($createdEvent->getId());
            $this->em->persist($localEvent);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'export vers Google Calendar: ' . $e->getMessage());
        }
    }

    public function isAuthenticated(): bool
    {
        try {
            if ($this->client->getAccessToken() && !$this->client->isAccessTokenExpired()) {
                return true;
            }

            if ($this->client->getRefreshToken()) {
                $this->refreshToken();
                return true;
            }
        } catch (\Exception $e) {
            error_log('Google authentication check failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Import events from Google Calendar to the local database
     *
     * @return int Number of events imported
     */
    public function importEventsFromGoogle(): int
    {
        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken();
        }

        $calendarService = $this->getCalendarService();
        $googleEvents = $calendarService->events->listEvents('primary', [
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'timeMin' => (new \DateTime('today'))->format(DATE_RFC3339),
        ]);

        $importCount = 0;
        foreach ($googleEvents->getItems() as $googleEvent) {
            // Skip events without an ID
            if (!$googleEvent->getId()) {
                continue;
            }

            // Check if this event already exists in our database
            $existingEvent = $this->eventRepository->findOneBy(['googleEventId' => $googleEvent->getId()]);

            if (!$existingEvent) {
                // Create new event
                $event = new CalendarEvent();
                $event->setGoogleEventId($googleEvent->getId());
                $importCount++;
            } else {
                // Update existing event
                $event = $existingEvent;
            }

            // Handle start and end dates (can be dateTime or date for all-day events)
            $startDateTime = null;
            if ($googleEvent->getStart()->getDateTime()) {
                $startDateTime = new \DateTime($googleEvent->getStart()->getDateTime());
            } elseif ($googleEvent->getStart()->getDate()) {
                $startDateTime = new \DateTime($googleEvent->getStart()->getDate());
            }

            $endDateTime = null;
            if ($googleEvent->getEnd()->getDateTime()) {
                $endDateTime = new \DateTime($googleEvent->getEnd()->getDateTime());
            } elseif ($googleEvent->getEnd()->getDate()) {
                $endDateTime = new \DateTime($googleEvent->getEnd()->getDate());
            }

            // Set event data
            $event->setTitle($googleEvent->getSummary() ?? 'Sans titre');
            $event->setDescription($googleEvent->getDescription() ?? '');

            if ($startDateTime) {
                $event->setStart($startDateTime);
            }

            if ($endDateTime) {
                $event->setEnd($endDateTime);
            }

            $this->em->persist($event);
        }

        $this->em->flush();
        return $importCount;
    }

    /**
     * Export events from local database to Google Calendar
     *
     * @return int Number of events exported
     */
    public function exportEventsToGoogle(): int
    {
        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken();
        }

        // Get all local events that don't have a Google Event ID
        $localEvents = $this->eventRepository->findBy(['googleEventId' => null]);

        $exportCount = 0;
        foreach ($localEvents as $localEvent) {
            try {
                $this->exportToGoogleCalendar($localEvent);
                $exportCount++;
            } catch (\Exception $e) {
                error_log('Failed to export event ' . $localEvent->getId() . ': ' . $e->getMessage());
                // Continue with the next event
            }
        }

        return $exportCount;
    }

    /**
     * Perform a two-way synchronization between Google Calendar and local database
     *
     * @return array With counts of imported and exported events
     */
    public function synchronizeCalendars(): array
    {
        if (!$this->isAuthenticated()) {
            throw new \RuntimeException('L\'utilisateur doit être authentifié pour synchroniser l\'agenda.');
        }

        // First import from Google
        $importCount = $this->importEventsFromGoogle();

        // Then export to Google
        $exportCount = $this->exportEventsToGoogle();

        return [
            'imported' => $importCount,
            'exported' => $exportCount
        ];
    }
}