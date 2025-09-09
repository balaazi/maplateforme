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

    /**
     * Vérifie et expire automatiquement une invitation si elle est expirée
     * Cette méthode est appelée automatiquement lors de l'accès aux invitations
     */
    public function checkAndExpireInvitation(Invitation $invitation, int $daysExpiration = 30): bool
    {
        if ($invitation->getStatus() !== InvitationStatus::PENDING->value) {
            $this->logger->debug('Invitation non vérifiée - Statut non en attente', [
                'invitation_id' => $invitation->getId(),
                'status' => $invitation->getStatus()
            ]);
            return false;
        }

        if (!$invitation->getCreatedAt()) {
            $this->logger->warning('Invitation sans date de création', [
                'invitation_id' => $invitation->getId()
            ]);
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $expirationDate = (clone $invitation->getCreatedAt())->modify("+{$daysExpiration} days");

        // Ajouter des logs détaillés pour le débogage
        $event = $invitation->getEvent();
        $this->logger->debug('Vérification expiration invitation', [
            'invitation_id' => $invitation->getId(),
            'email' => $invitation->getEmail(),
            'event_type' => $event ? $event->getType() : 'inconnu',
            'event_title' => $event ? $event->getTitle() : 'inconnu',
            'created_at' => $invitation->getCreatedAt()->format('Y-m-d H:i:s'),
            'expiration_date' => $expirationDate->format('Y-m-d H:i:s'),
            'now' => $now->format('Y-m-d H:i:s')
        ]);

        if ($now > $expirationDate) {
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());

            $this->logger->info('Invitation automatiquement expirée lors de l\'accès', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'event_title' => $invitation->getEvent()?->getTitle(),
                'created_at' => $invitation->getCreatedAt()->format('Y-m-d H:i:s'),
                'expiration_date' => $expirationDate->format('Y-m-d H:i:s'),
                'now' => $now->format('Y-m-d H:i:s')
            ]);

            return true; // L'invitation a été expirée
        }

        return false; // L'invitation n'était pas expirée
    }

    /**
     * Vérifie et expire automatiquement une liste d'invitations
     * Cette méthode est appelée automatiquement lors de l'accès aux listes d'invitations
     */
    public function checkAndExpireInvitations(array $invitations, int $daysExpiration = 30): int
    {
        $expiredCount = 0;
        $hasChanges = false;

        foreach ($invitations as $invitation) {
            if ($this->checkAndExpireInvitation($invitation, $daysExpiration)) {
                $expiredCount++;
                $hasChanges = true;
            }
        }

        // Sauvegarder les changements si nécessaire
        if ($hasChanges) {
            $this->entityManager->flush();
            $this->logger->info("{$expiredCount} invitations automatiquement expirées lors de l'accès");
        }

        return $expiredCount;
    }
}
