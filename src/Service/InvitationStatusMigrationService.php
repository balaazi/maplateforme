<?php

namespace App\Service;

use App\Enum\InvitationStatus;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InvitationStatusMigrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParticipationRepository $participationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Migre tous les anciens statuts d'invitation vers les nouveaux
     * @return int Nombre de participations migrées
     */
    public function migrateOldStatuses(): int
    {
        $migrationMap = [
            'en_attente' => InvitationStatus::PENDING->value,
            'accepté' => InvitationStatus::ACCEPTED->value,
            'refusé' => InvitationStatus::DECLINED->value,
        ];

        $count = 0;
        
        foreach ($migrationMap as $oldStatus => $newStatus) {
            $participations = $this->participationRepository->findBy(['invitationStatus' => $oldStatus]);
            
            foreach ($participations as $participation) {
                $participation->setInvitationStatus($newStatus);
                $count++;
                
                $this->logger->info('Statut d\'invitation migré', [
                    'participation_id' => $participation->getId(),
                    'user_id' => $participation->getUser()?->getId(),
                    'event_id' => $participation->getEvent()?->getId(),
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info("{$count} participations migrées vers les nouveaux statuts");
        }

        return $count;
    }

    /**
     * Vérifie s'il y a des anciens statuts à migrer
     */
    public function hasOldStatuses(): bool
    {
        $oldStatuses = ['en_attente', 'accepté', 'refusé'];
        
        foreach ($oldStatuses as $oldStatus) {
            $count = $this->participationRepository->count(['invitationStatus' => $oldStatus]);
            if ($count > 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Retourne le nombre d'anciens statuts à migrer
     */
    public function getOldStatusesCount(): array
    {
        $oldStatuses = ['en_attente', 'accepté', 'refusé'];
        $counts = [];
        
        foreach ($oldStatuses as $oldStatus) {
            $counts[$oldStatus] = $this->participationRepository->count(['invitationStatus' => $oldStatus]);
        }
        
        return $counts;
    }
}
