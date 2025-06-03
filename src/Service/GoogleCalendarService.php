<?php

namespace App\Service;

use App\Entity\CalendarEvent;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Symfony\Bundle\SecurityBundle\Security;

class GoogleCalendarService
{
    private Client $client;
    private Calendar $calendarService;
    private EntityManagerInterface $em;
    private ManagerRegistry $registry;
    private Security $security;
    private string $tokenPath;

    public function __construct(
        ManagerRegistry $registry,
        Security $security,
        string $googleClientId,
        string $googleClientSecret,
        string $googleRedirectUri,
        string $projectDir
    ) {
        $this->registry = $registry;
        $this->em = $registry->getManager();
        $this->security = $security;
        $this->tokenPath = $projectDir . '/var/google-token.json';

        $this->client = new Client();
        $this->client->setApplicationName('MaPlateforme');
        $this->client->setClientId($googleClientId);
        $this->client->setClientSecret($googleClientSecret);
        $this->client->setRedirectUri($googleRedirectUri);
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $this->loadAccessTokenFromFile();
        $this->calendarService = new Calendar($this->client);
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }
    public function fetchAccessTokenWithCode(string $code): void
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        if (!isset($accessToken['access_token'])) {
            throw new \Exception('Token invalide reçu de Google');
        }
        $this->client->setAccessToken($accessToken);
        $this->storeAccessTokenToFile($accessToken);
    }
    public function isAuthenticated(): bool
    {
        return $this->client->getAccessToken() !== null;
    }
    private function storeAccessTokenToFile(array $accessToken): void
    {
        file_put_contents($this->tokenPath, json_encode($accessToken));
    }
    private function loadAccessTokenFromFile(): void
    {
        if (!file_exists($this->tokenPath)) {
            return;
        }

        $accessToken = json_decode(file_get_contents($this->tokenPath), true);
        if ($accessToken && isset($accessToken['access_token'])) {
            $this->client->setAccessToken($accessToken);
            if ($this->client->isAccessTokenExpired() && $this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                $this->storeAccessTokenToFile($newToken);
            }
        }
    }
    public function exportToGoogleCalendar(CalendarEvent $event): void
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception("L'utilisateur n'est pas authentifié à Google.");
        }

        $googleEvent = new GoogleEvent([
            'summary' => $event->getTitle(),
            'description' => $event->getDescription(),
            'start' => ['dateTime' => $event->getStart()->format(\DateTimeInterface::RFC3339)],
            'end' => ['dateTime' => $event->getEnd()->format(\DateTimeInterface::RFC3339)],
        ]);

        $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
        $event->setGoogleEventId($createdEvent->getId());

        $this->em->persist($event);
        $this->em->flush();
    }
    public function synchronizeCalendars(): array
    {
        if (!$this->isAuthenticated()) {
            throw new \Exception("Non authentifié à Google.");
        }

        $imported = 0;
        $exported = 0;

        try {
            $googleEvents = $this->calendarService->events->listEvents('primary', [
                'timeMin' => (new \DateTime('-1 month'))->format(\DateTimeInterface::RFC3339),
                'timeMax' => (new \DateTime('+6 months'))->format(\DateTimeInterface::RFC3339),
            ]);

            foreach ($googleEvents->getItems() as $googleEvent) {
                if (!$googleEvent->getStart() || !$googleEvent->getEnd()) {
                    continue;
                }

                if (!$this->em->isOpen()) {
                    $this->em = $this->registry->resetManager();
                }

                $existing = $this->em->getRepository(CalendarEvent::class)
                    ->findOneBy(['googleEventId' => $googleEvent->getId()]);

                if ($existing) {
                    continue;
                }

                $calendarEvent = new CalendarEvent();
                $calendarEvent->setTitle($googleEvent->getSummary() ?? '(Sans titre)');
                $calendarEvent->setDescription($googleEvent->getDescription() ?? '');
                $calendarEvent->setStart(new \DateTime($googleEvent->getStart()->getDateTime()));
                $calendarEvent->setEnd(new \DateTime($googleEvent->getEnd()->getDateTime()));
                $calendarEvent->setGoogleEventId($googleEvent->getId());
                $this->em->persist($calendarEvent);

                $event = new Event();
                $event->setTitle($calendarEvent->getTitle());
                $event->setDescription($calendarEvent->getDescription());
                $event->setDateHeure($calendarEvent->getStart());
                $event->setDuree($calendarEvent->getStart()->diff($calendarEvent->getEnd())->i);
                $event->setLieu($googleEvent->getLocation() ?? 'Lieu non défini');
                $event->setOrganizer($this->security->getUser()); // Peut être null en CLI
                $event->setStatus('programmé');
                $this->em->persist($event);

                $imported++;
            }

            $events = $this->em->getRepository(Event::class)->findAll();
            foreach ($events as $e) {
                $start = $e->getDateHeure();
                $end = (clone $start)->modify('+' . $e->getDuree() . ' minutes');
                $existingCalendarEvent = $this->em->getRepository(CalendarEvent::class)->findOneBy([
                    'title' => $e->getTitle(),
                    'start' => $start,
                    'end' => $end,
                ]);
                if (!$existingCalendarEvent) {
                    $calendarEvent = new CalendarEvent();
                    $calendarEvent->setTitle($e->getTitle());
                    $calendarEvent->setDescription($e->getDescription());
                    $calendarEvent->setStart($start);
                    $calendarEvent->setEnd($end);
                    $this->em->persist($calendarEvent);
                    try {
                        $this->exportToGoogleCalendar($calendarEvent);
                        $exported++;
                    } catch (\Throwable $ex) {
                        // Ignorer l'erreur, continuer
                    }
                }
            }
            $this->em->flush();
        } catch (\Throwable $e) {
            if (!$this->em->isOpen()) {
                $this->em = $this->registry->resetManager();
            }
            throw new \RuntimeException("Erreur critique : " . $e->getMessage());
        }
        return [
            'imported' => $imported,
            'exported' => $exported,
        ];
    }
}
