<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EventExpirationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Vérifie si un événement est terminé (date + durée < maintenant)
     */
    public function isEventPassed(Event $event): bool
    {
        if (!$event->getDateHeure()) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $eventEnd = (clone $event->getDateHeure())->modify('+' . (int) $event->getDuree() . ' minutes');
        
        return $now > $eventEnd;
    }

    /**
     * Marque toutes les invitations en attente d'un événement comme expirées
     * si l'événement est déjà passé
     */
    public function expireInvitationsForPassedEvent(Event $event): int
    {
        if (!$this->isEventPassed($event)) {
            return 0;
        }

        $count = 0;
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getStatus() === InvitationStatus::PENDING->value) {
                $invitation->setStatus(InvitationStatus::EXPIRED->value);
                $invitation->setUpdatedAt(new \DateTime());
                $count++;

                $this->logger->info('Invitation marquée comme expirée (événement passé)', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail(),
                    'event_title' => $event->getTitle(),
                    'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s')
                ]);
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$count} invitations marquées comme expirées pour l'événement passé", [
                'event_id' => $event->getId(),
                'event_title' => $event->getTitle()
            ]);
        }

        return $count;
    }

    /**
     * Vérifie si une invitation spécifique est expirée car l'événement est passé
     */
    public function isInvitationExpiredDueToPassedEvent(Invitation $invitation): bool
    {
        $event = $invitation->getEvent();
        if (!$event || !$event->getDateHeure()) {
            return false;
        }

        return $this->isEventPassed($event);
    }

    /**
     * Marque une invitation comme expirée si l'événement est passé
     * Retourne true si l'invitation a été expirée
     */
    public function expireInvitationIfEventPassed(Invitation $invitation): bool
    {
        if ($invitation->getStatus() !== InvitationStatus::PENDING->value) {
            return false;
        }

        $event = $invitation->getEvent();
        if (!$event || !$event->getDateHeure()) {
            return false;
        }

        if ($this->isEventPassed($event)) {
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            $this->logger->info('Invitation marquée comme expirée (événement passé)', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'event_title' => $event->getTitle(),
                'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s')
            ]);

            return true;
        }

        return false;
    }
}
