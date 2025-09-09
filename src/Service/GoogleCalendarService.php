<?php

namespace App\Service;

use App\Entity\CalendarEvent;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

class GoogleCalendarService
{
    private Client $client;
    private Calendar $calendarService;
    private EntityManagerInterface $em;
    private ManagerRegistry $registry;
    private Security $security;
    private string $tokenPath;
    private LoggerInterface $logger;

    public function __construct(
        ManagerRegistry $registry,
        Security $security,
        LoggerInterface $logger,
        string $googleClientId,
        string $googleClientSecret,
        string $googleRedirectUri,
        string $projectDir
    ) {
        $this->registry = $registry;
        $this->em = $registry->getManager();
        $this->security = $security;
        $this->logger = $logger;
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

    private function ensureEntityManagerIsOpen(): void
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->registry->resetManager();
        }
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

        // Vérifier l'EntityManager avant l'opération
        $this->ensureEntityManagerIsOpen();

        $googleEvent = new GoogleEvent([
            'summary' => $event->getTitle(),
            'description' => $event->getDescription(),
            'start' => ['dateTime' => $event->getStart()->format(\DateTimeInterface::RFC3339)],
            'end' => ['dateTime' => $event->getEnd()->format(\DateTimeInterface::RFC3339)],
        ]);

        try {
        $createdEvent = $this->calendarService->events->insert('primary', $googleEvent);
            $googleEventId = $createdEvent->getId();

            // Vérifier l'EntityManager avant persist et flush
            $this->ensureEntityManagerIsOpen();

            // Si l'entité a un ID, la récupérer depuis la base pour éviter les problèmes de détachement
            if ($event->getId()) {
                $managedEvent = $this->em->getRepository(CalendarEvent::class)->find($event->getId());
                if ($managedEvent) {
                    $managedEvent->setGoogleEventId($googleEventId);
                    $this->em->flush();
                } else {
                    // Si l'entité n'existe plus en base, la recréer
                    $event->setGoogleEventId($googleEventId);
                    $this->em->persist($event);
                    $this->em->flush();
                }
            } else {
                // Nouvelle entité
                $event->setGoogleEventId($googleEventId);
        $this->em->persist($event);
        $this->em->flush();
            }
        } catch (\Throwable $e) {
            // Gérer les erreurs et réinitialiser l'EntityManager si nécessaire
            $this->ensureEntityManagerIsOpen();
            throw new \Exception("Erreur lors de l'export vers Google Calendar: " . $e->getMessage());
        }
    }
    public function synchronizeCalendars(): array
    {
        // Debug: Vérifier l'authentification
        $this->logger->info("Début de synchronisation", [
            'is_authenticated' => $this->isAuthenticated(),
            'access_token_exists' => $this->client->getAccessToken() !== null
        ]);
        
        if (!$this->isAuthenticated()) {
            $this->logger->error("Synchronisation arrêtée: Non authentifié à Google");
            throw new \Exception("Non authentifié à Google.");
        }

        $imported = 0;
        $exported = 0;

        try {
            // Vérifier et réinitialiser l'EntityManager si nécessaire au début
            $this->ensureEntityManagerIsOpen();

            $googleEvents = $this->calendarService->events->listEvents('primary', [
                'timeMin' => (new \DateTime('-3 months'))->format(\DateTimeInterface::RFC3339),
                'timeMax' => (new \DateTime('+12 months'))->format(\DateTimeInterface::RFC3339),
            ]);

            // Debug: Afficher tous les événements récupérés
            $this->logger->info("Événements récupérés depuis Google Calendar", [
                'total_events' => count($googleEvents->getItems()),
                'time_min' => (new \DateTime('-3 months'))->format('Y-m-d H:i:s'),
                'time_max' => (new \DateTime('+12 months'))->format('Y-m-d H:i:s')
            ]);
            
            foreach ($googleEvents->getItems() as $index => $googleEvent) {
                $this->logger->info("Événement Google Calendar détecté", [
                    'index' => $index,
                    'id' => $googleEvent->getId(),
                    'title' => $googleEvent->getSummary(),
                    'start' => $googleEvent->getStart() ? $googleEvent->getStart()->getDateTime() : 'NULL',
                    'end' => $googleEvent->getEnd() ? $googleEvent->getEnd()->getDateTime() : 'NULL'
                ]);

                if (!$googleEvent->getStart() || !$googleEvent->getEnd()) {
                    $this->logger->warning("Événement ignoré (pas de start/end)", [
                        'id' => $googleEvent->getId(),
                        'title' => $googleEvent->getSummary()
                    ]);
                    continue;
                }

                // Vérifier l'EntityManager à chaque itération
                $this->ensureEntityManagerIsOpen();

                try {
                $existing = $this->em->getRepository(CalendarEvent::class)
                    ->findOneBy(['googleEventId' => $googleEvent->getId()]);

                if ($existing) {
                        $this->logger->info("Événement déjà existant", [
                            'id' => $googleEvent->getId(),
                            'title' => $googleEvent->getSummary()
                        ]);
                    continue;
                }
                    
                    $this->logger->info("Nouvel événement à importer", [
                        'id' => $googleEvent->getId(),
                        'title' => $googleEvent->getSummary(),
                        'start' => $googleEvent->getStart()->getDateTime(),
                        'end' => $googleEvent->getEnd()->getDateTime()
                    ]);

                $calendarEvent = new CalendarEvent();
                $calendarEvent->setTitle($googleEvent->getSummary() ?? '(Sans titre)');
                $calendarEvent->setDescription($googleEvent->getDescription() ?? '');
                $calendarEvent->setStart(new \DateTime($googleEvent->getStart()->getDateTime()));
                $calendarEvent->setEnd(new \DateTime($googleEvent->getEnd()->getDateTime()));
                $calendarEvent->setGoogleEventId($googleEvent->getId());
                $this->em->persist($calendarEvent);

                    $this->logger->info("CalendarEvent créé et persisté", [
                        'title' => $calendarEvent->getTitle(),
                        'google_id' => $calendarEvent->getGoogleEventId()
                    ]);

                    // Ne créer un Event que si on a un utilisateur connecté
                    $currentUser = $this->security->getUser();
                    if ($currentUser instanceof User && $currentUser->getId()) {
                        $managedUser = $this->em->getRepository(User::class)->find($currentUser->getId());
                        if ($managedUser) {
                $event = new Event();
                $event->setTitle($calendarEvent->getTitle());
                $event->setDescription($calendarEvent->getDescription());
                $event->setDateHeure($calendarEvent->getStart());
                $event->setDuree($calendarEvent->getStart()->diff($calendarEvent->getEnd())->i);

                            $event->setCategory('Synchronisé'); // Catégorie par défaut pour les événements importés de Google
                            $event->setOrganizer($managedUser);
                $event->setStatus('programmé');
                $this->em->persist($event);

                            $this->logger->info("Event créé et persisté", [
                                'title' => $event->getTitle(),
                                'organizer' => $managedUser->getId()
                            ]);
                        } else {
                            $this->logger->warning("Utilisateur managé non trouvé", [
                                'user_id' => $currentUser->getId()
                            ]);
                        }
                    } else {
                        $this->logger->info("Pas d'utilisateur connecté, création de Event ignorée", [
                            'current_user' => $currentUser ? get_class($currentUser) : 'null'
                        ]);
                    }

                    // Flush immédiatement pour éviter l'accumulation d'erreurs
                    $this->em->flush();
                $imported++;
                    
                    $this->logger->info("Import réussi", [
                        'title' => $calendarEvent->getTitle(),
                        'imported_count' => $imported
                    ]);
                } catch (\Throwable $ex) {
                    // Log l'erreur et continuer avec le prochain événement
                    $this->logger->error("Erreur lors de l'import de l'événement Google", [
                        'error' => $ex->getMessage(),
                        'google_event_id' => $googleEvent->getId(),
                        'trace' => $ex->getTraceAsString()
                    ]);
                    
                    // Vérifier si l'EntityManager est fermé après l'erreur
                    $this->ensureEntityManagerIsOpen();
                    continue;
                }
            }

            // Vérifier l'EntityManager avant la deuxième partie
            $this->ensureEntityManagerIsOpen();
            // a modifier
          //  $events = $this->em->getRepository(Event::class)->findByRole($currentUser);
            $events = $this->em->getRepository(Event::class)->findAll();
            foreach ($events as $e) {
                // Vérifier l'EntityManager à chaque itération
                $this->ensureEntityManagerIsOpen();

                try {
                $start = $e->getDateHeure();
                $end = (clone $start)->modify('+' . $e->getDuree() . ' minutes');
                $existingCalendarEvent = $this->em->getRepository(CalendarEvent::class)->findOneBy([
                    'title' => $e->getTitle(),
                    'start' => $start,
                    'end' => $end,
                ]);
                    
                if (!$existingCalendarEvent) {
                        // Créer un nouveau CalendarEvent et l'exporter
                    $calendarEvent = new CalendarEvent();
                    $calendarEvent->setTitle($e->getTitle());
                    $calendarEvent->setDescription($e->getDescription());
                    $calendarEvent->setStart($start);
                    $calendarEvent->setEnd($end);
                    $this->em->persist($calendarEvent);
                        
                    try {
                        $this->exportToGoogleCalendar($calendarEvent);
                        $exported++;
                            $this->logger->info("Nouvel événement exporté vers Google Calendar", [
                                'title' => $calendarEvent->getTitle(),
                                'start' => $calendarEvent->getStart()->format('Y-m-d H:i:s')
                            ]);
                        } catch (\Throwable $ex) {
                            // Log l'erreur et continuer
                            $this->logger->error("Erreur lors de l'export vers Google Calendar", [
                                'error' => $ex->getMessage(),
                                'trace' => $ex->getTraceAsString()
                            ]);
                        }
                    } elseif (!$existingCalendarEvent->getGoogleEventId()) {
                        // Le CalendarEvent existe mais n'a pas de google_event_id, l'exporter
                        try {
                            $this->exportToGoogleCalendar($existingCalendarEvent);
                            $exported++;
                            $this->logger->info("Événement existant exporté vers Google Calendar", [
                                'title' => $existingCalendarEvent->getTitle(),
                                'start' => $existingCalendarEvent->getStart()->format('Y-m-d H:i:s')
                            ]);
                    } catch (\Throwable $ex) {
                            // Log l'erreur et continuer
                            $this->logger->error("Erreur lors de l'export de l'événement existant vers Google Calendar", [
                                'error' => $ex->getMessage(),
                                'title' => $existingCalendarEvent->getTitle(),
                                'trace' => $ex->getTraceAsString()
                            ]);
                        }
                    } else {
                        $this->logger->info("Événement déjà synchronisé avec Google Calendar", [
                            'title' => $existingCalendarEvent->getTitle(),
                            'google_event_id' => $existingCalendarEvent->getGoogleEventId()
                        ]);
                    }
                } catch (\Throwable $ex) {
                    // Log l'erreur et continuer avec le prochain événement
                    $this->logger->error("Erreur lors du traitement de l'événement local", [
                        'error' => $ex->getMessage(),
                        'trace' => $ex->getTraceAsString()
                    ]);
                    
                    // Vérifier si l'EntityManager est fermé après l'erreur
                    $this->ensureEntityManagerIsOpen();
                    continue;
                }
            }

            // Flush final seulement si l'EntityManager est ouvert
            if ($this->em->isOpen()) {
            $this->em->flush();
            }

        } catch (\Throwable $e) {
            // Gérer les erreurs globales
            $this->ensureEntityManagerIsOpen();
            throw new \RuntimeException("Erreur critique : " . $e->getMessage());
        }
        
        return [
            'imported' => $imported,
            'exported' => $exported,
        ];
    }
}
