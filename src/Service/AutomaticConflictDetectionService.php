<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use App\Repository\InvitationRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AutomaticConflictDetectionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvitationRepository $invitationRepository,
        private EventRepository $eventRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Détecte et marque automatiquement les invitations en conflit d'horaires
     * @return int Nombre d'invitations marquées comme en conflit
     */
    public function detectAndMarkConflicts(): int
    {
        $this->logger->info('Début de la détection automatique des conflits d\'horaires');
        
        // Récupérer toutes les invitations en attente
        $pendingInvitations = $this->invitationRepository->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', InvitationStatus::PENDING->value)
            ->getQuery()
            ->getResult();

        if (empty($pendingInvitations)) {
            $this->logger->info('Aucune invitation en attente à vérifier');
            return 0;
        }

        $conflictCount = 0;
        
        foreach ($pendingInvitations as $invitation) {
            $event = $invitation->getEvent();
            if (!$event) {
                $this->logger->warning('Invitation sans événement associé', [
                    'invitation_id' => $invitation->getId()
                ]);
                continue;
            }

            // Vérifier s'il y a un conflit d'horaires
            if ($this->hasScheduleConflict($invitation)) {
                $this->markInvitationAsConflict($invitation);
                $conflictCount++;
                
                $this->logger->info('Conflit d\'horaires détecté et marqué automatiquement', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail(),
                    'event_title' => $event->getTitle(),
                    'event_date' => $event->getDateHeure()->format('Y-m-d H:i:s')
                ]);
            }
        }

        // Sauvegarder toutes les modifications
        if ($conflictCount > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$conflictCount} invitation(s) marquée(s) comme en conflit");
        }

        return $conflictCount;
    }

    /**
     * Vérifie s'il y a un conflit d'horaires pour une invitation
     */
    private function hasScheduleConflict(Invitation $invitation): bool
    {
        $event = $invitation->getEvent();
        $userEmail = $invitation->getEmail();
        
        if (!$event) {
            return false;
        }

        // Vérifier les conflits avec les événements déjà acceptés par cet utilisateur
        $conflictingEvent = $this->eventRepository->findConflictingEventForUserByEmail($userEmail, $event);
        
        return $conflictingEvent !== null;
    }

    /**
     * Marque une invitation comme étant en conflit
     */
    private function markInvitationAsConflict(Invitation $invitation): void
    {
        $invitation->setStatus(InvitationStatus::CONFLICT->value);
        $invitation->setUpdatedAt(new \DateTime());
        
        // Mettre à jour aussi la participation si elle existe
        $participation = $this->invitationRepository->findParticipationForInvitation($invitation);
        if ($participation) {
            $participation->setInvitationStatus(InvitationStatus::CONFLICT->value);
        }
    }

    /**
     * Détecte les conflits pour un utilisateur spécifique
     */
    public function detectConflictsForUser(string $userEmail): int
    {
        $userInvitations = $this->invitationRepository->createQueryBuilder('i')
            ->andWhere('i.email = :email')
            ->andWhere('i.status = :status')
            ->setParameter('email', $userEmail)
            ->setParameter('status', InvitationStatus::PENDING->value)
            ->getQuery()
            ->getResult();

        $conflictCount = 0;
        
        foreach ($userInvitations as $invitation) {
            if ($this->hasScheduleConflict($invitation)) {
                $this->markInvitationAsConflict($invitation);
                $conflictCount++;
            }
        }

        if ($conflictCount > 0) {
            $this->entityManager->flush();
        }

        return $conflictCount;
    }

    /**
     * Détecte les conflits pour un événement spécifique
     */
    public function detectConflictsForEvent(int $eventId): int
    {
        $eventInvitations = $this->invitationRepository->createQueryBuilder('i')
            ->andWhere('i.event = :eventId')
            ->andWhere('i.status = :status')
            ->setParameter('eventId', $eventId)
            ->setParameter('status', InvitationStatus::PENDING->value)
            ->getQuery()
            ->getResult();

        $conflictCount = 0;
        
        foreach ($eventInvitations as $invitation) {
            if ($this->hasScheduleConflict($invitation)) {
                $this->markInvitationAsConflict($invitation);
                $conflictCount++;
            }
        }

        if ($conflictCount > 0) {
            $this->entityManager->flush();
        }

        return $conflictCount;
    }
}
