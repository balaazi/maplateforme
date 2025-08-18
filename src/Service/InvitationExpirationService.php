<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InvitationExpirationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvitationRepository $invitationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Marque les invitations en attente comme expirées après un délai spécifié
     * @param int $daysExpiration Nombre de jours avant expiration (défaut: 30)
     * @return int Nombre d'invitations expirées
     */
    public function expireOldInvitations(int $daysExpiration = 30): int
    {
        $expirationDate = new \DateTime();
        $expirationDate->modify("-{$daysExpiration} days");

        $expiredInvitations = $this->invitationRepository->findExpiredInvitations($expirationDate);

        $count = 0;
        foreach ($expiredInvitations as $invitation) {
            if ($invitation->getStatus() === InvitationStatus::PENDING->value) {
                $invitation->setStatus(InvitationStatus::EXPIRED->value);
                $invitation->setUpdatedAt(new \DateTime());
                $count++;

                $this->logger->info('Invitation marquée comme expirée', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail(),
                    'event_title' => $invitation->getEvent()?->getTitle(),
                    'expired_date' => $expirationDate->format('Y-m-d H:i:s')
                ]);
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$count} invitations marquées comme expirées");
        }

        return $count;
    }

    /**
     * Vérifie si une invitation spécifique est expirée
     */
    public function isInvitationExpired(Invitation $invitation, int $daysExpiration = 30): bool
    {
        if ($invitation->getStatus() !== InvitationStatus::PENDING->value) {
            return false;
        }

        $expirationDate = new \DateTime();
        $expirationDate->modify("-{$daysExpiration} days");

        return $invitation->getCreatedAt() < $expirationDate;
    }

    /**
     * Marque une invitation spécifique comme expirée
     */
    public function expireInvitation(Invitation $invitation): void
    {
        if ($invitation->getStatus() === InvitationStatus::PENDING->value) {
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            $this->logger->info('Invitation marquée manuellement comme expirée', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'event_title' => $invitation->getEvent()?->getTitle()
            ]);
        }
    }
}
