<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Enum\InvitationStatus;
use App\Repository\EventRepository;
use Psr\Log\LoggerInterface;

class ScheduleConflictService
{
    private EventRepository $eventRepository;
    private LoggerInterface $logger;

    public function __construct(
        EventRepository $eventRepository,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
    }

    /**
     * Vérifie s'il y a un conflit d'horaires pour un utilisateur avec un nouvel événement
     * 
     * @param User $user L'utilisateur à vérifier
     * @param Event $newEvent Le nouvel événement à vérifier
     * @return array|null Retourne un tableau avec les informations du conflit ou null s'il n'y en a pas
     */
    public function checkScheduleConflict(User $user, Event $newEvent): ?array
    {
        $conflictingEvent = $this->eventRepository->findConflictingEventForUser($user, $newEvent);
        
        if (!$conflictingEvent) {
            return null;
        }

        $this->logger->info('Conflit d\'horaires détecté', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'new_event_id' => $newEvent->getId(),
            'new_event_title' => $newEvent->getTitle(),
            'conflicting_event_id' => $conflictingEvent->getId(),
            'conflicting_event_title' => $conflictingEvent->getTitle()
        ]);

        return [
            'conflictingEvent' => $conflictingEvent,
            'newEvent' => $newEvent,
            'user' => $user,
            'conflictType' => 'schedule_overlap',
            'status' => InvitationStatus::CONFLICT->value,
            'message' => sprintf(
                'Vous participez déjà à l\'événement "%s" qui se déroule pendant la même période que "%s"',
                $conflictingEvent->getTitle(),
                $newEvent->getTitle()
            )
        ];
    }

    /**
     * Vérifie si un utilisateur peut participer à un événement sans conflit
     * 
     * @param User $user L'utilisateur à vérifier
     * @param Event $event L'événement à vérifier
     * @return bool True si l'utilisateur peut participer, false sinon
     */
    public function canUserParticipate(User $user, Event $event): bool
    {
        return $this->checkScheduleConflict($user, $event) === null;
    }

    /**
     * Retourne tous les conflits d'horaires pour un utilisateur donné
     * 
     * @param User $user L'utilisateur à vérifier
     * @param \DateTimeInterface $startDate Date de début de la période à vérifier
     * @param \DateTimeInterface $endDate Date de fin de la période à vérifier
     * @return array Liste des événements en conflit
     */
    public function getAllConflictsForPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // Cette méthode pourrait être implémentée pour vérifier une période complète
        // et retourner tous les conflits potentiels
        return [];
    }

    /**
     * Génère un message d'avertissement personnalisé pour un conflit
     * 
     * @param array $conflict Les informations du conflit
     * @return string Le message d'avertissement
     */
    public function generateConflictWarningMessage(array $conflict): string
    {
        $newEvent = $conflict['newEvent'];
        $conflictingEvent = $conflict['conflictingEvent'];
        
        return sprintf(
            'Vous participez déjà à un autre événement à cette date et heure. ' .
            'L\'événement "%s" (le %s de %s à %s) entre en conflit avec "%s" (le %s de %s à %s).',
            $conflictingEvent->getTitle(),
            $conflictingEvent->getDateHeure()->format('d/m/Y'),
            $conflictingEvent->getDateHeure()->format('H:i'),
            $conflictingEvent->getDateHeure()->modify('+' . $conflictingEvent->getDuree() . ' minutes')->format('H:i'),
            $newEvent->getTitle(),
            $newEvent->getDateHeure()->format('d/m/Y'),
            $newEvent->getDateHeure()->format('H:i'),
            $newEvent->getDateHeure()->modify('+' . $newEvent->getDuree() . ' minutes')->format('H:i')
        );
    }
}
