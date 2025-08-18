<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AutoArchiveService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Archive automatiquement tous les événements dont la date est dépassée depuis plus d'un jour
     */
    public function archiveCompletedEvents(): int
    {
        $now = new \DateTime();
        $oneDayAgo = (clone $now)->modify('-1 day');
        $archivedCount = 0;

        // Récupérer tous les événements non archivés
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.archive = :archived')
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult();

        foreach ($events as $event) {
            if ($this->isEventExpired($event, $oneDayAgo)) {
                $this->archiveEvent($event);
                $archivedCount++;
                
                $this->logger->info('Événement archivé automatiquement', [
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s'),
                    'duration' => $event->getDuree()
                ]);
            }
        }

        if ($archivedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info("Archivage automatique terminé : {$archivedCount} événement(s) archivé(s)");
        }

        return $archivedCount;
    }

    /**
     * Vérifie si un événement est expiré (date dépassée depuis plus d'un jour)
     */
    private function isEventExpired(Event $event, \DateTime $oneDayAgo): bool
    {
        $eventStart = $event->getDateHeure();
        $eventEnd = (clone $eventStart)->modify('+' . $event->getDuree() . ' minutes');
        
        return $eventEnd <= $oneDayAgo;
    }

    /**
     * Archive un événement spécifique
     */
    private function archiveEvent(Event $event): void
    {
        $event->setArchive(true);
        $this->entityManager->persist($event);
    }

    /**
     * Archive un événement par son ID
     */
    public function archiveEventById(int $eventId): bool
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event || $event->isArchive()) {
            return false;
        }

        $this->archiveEvent($event);
        $this->entityManager->flush();
        
        $this->logger->info('Événement archivé par ID', [
            'event_id' => $eventId,
            'event_title' => $event->getTitle()
        ]);

        return true;
    }

    /**
     * Vérifie et archive les événements expirés en temps réel
     * Cette méthode peut être appelée lors de l'accès aux listes d'événements
     */
    public function checkAndArchiveCompletedEvents(): int
    {
        return $this->archiveCompletedEvents();
    }
} 