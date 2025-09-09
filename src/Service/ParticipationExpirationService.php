<?php

namespace App\Service;

use App\Enum\InvitationStatus;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ParticipationExpirationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipationRepository $participationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Marque les participations en attente comme expirées après un délai spécifié
     * @param int $daysExpiration Nombre de jours avant expiration (défaut: 30)
     * @return int Nombre de participations expirées
     */
    public function expireOldParticipations(int $daysExpiration = 30): int
    {
        $expirationDate = new \DateTime();
        $expirationDate->modify("-{$daysExpiration} days");

        $expiredParticipations = $this->participationRepository->findExpiredParticipations($expirationDate);

        $count = 0;
        foreach ($expiredParticipations as $participation) {
            if ($participation->getInvitationStatus() === InvitationStatus::PENDING->value) {
                $participation->setInvitationStatus(InvitationStatus::EXPIRED->value);
                $count++;

                $this->logger->info('Participation marquée comme expirée', [
                    'participation_id' => $participation->getId(),
                    'user_id' => $participation->getUser()?->getId(),
                    'user_email' => $participation->getUser()?->getEmail(),
                    'event_id' => $participation->getEvent()?->getId(),
                    'event_title' => $participation->getEvent()?->getTitle(),
                    'expired_date' => $expirationDate->format('Y-m-d H:i:s')
                ]);
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$count} participations marquées comme expirées");
        }

        return $count;
    }

    /**
     * Vérifie si une participation spécifique est expirée
     */
    public function isParticipationExpired($participation, int $daysExpiration = 30): bool
    {
        if ($participation->getInvitationStatus() !== InvitationStatus::PENDING->value) {
            return false;
        }

        $expirationDate = new \DateTime();
        $expirationDate->modify("-{$daysExpiration} days");

        return $participation->getCreatedAt() < $expirationDate;
    }

    /**
     * Marque une participation spécifique comme expirée
     */
    public function expireParticipation($participation): void
    {
        if ($participation->getInvitationStatus() === InvitationStatus::PENDING->value) {
            $participation->setInvitationStatus(InvitationStatus::EXPIRED->value);

            $this->entityManager->flush();

            $this->logger->info('Participation marquée manuellement comme expirée', [
                'participation_id' => $participation->getId(),
                'user_id' => $participation->getUser()?->getId(),
                'user_email' => $participation->getUser()?->getEmail(),
                'event_id' => $participation->getEvent()?->getId(),
                'event_title' => $participation->getEvent()?->getTitle()
            ]);
        }
    }
}
