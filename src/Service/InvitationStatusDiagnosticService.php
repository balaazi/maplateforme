<?php

namespace App\Service;

use App\Enum\InvitationStatus;
use App\Repository\InvitationRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InvitationStatusDiagnosticService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvitationRepository $invitationRepository,
        private ParticipationRepository $participationRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Diagnostique les incohérences de statuts entre invitations et participations
     */
    public function diagnoseStatusInconsistencies(): array
    {
        $issues = [];
        
        // Vérifier les invitations avec des statuts invalides
        $invalidInvitations = $this->invitationRepository->createQueryBuilder('i')
            ->where('i.status NOT IN (:validStatuses)')
            ->setParameter('validStatuses', [
                InvitationStatus::PENDING->value,
                InvitationStatus::ACCEPTED->value,
                InvitationStatus::DECLINED->value,
                InvitationStatus::EXPIRED->value,
                InvitationStatus::CONFLICT->value
            ])
            ->getQuery()
            ->getResult();

        if (!empty($invalidInvitations)) {
            $issues['invalid_invitation_statuses'] = [
                'count' => count($invalidInvitations),
                'details' => array_map(fn($inv) => [
                    'id' => $inv->getId(),
                    'email' => $inv->getEmail(),
                    'invalid_status' => $inv->getStatus(),
                    'event_title' => $inv->getEvent()?->getTitle()
                ], $invalidInvitations)
            ];
        }

        // Vérifier les participations avec des statuts invalides
        $invalidParticipations = $this->participationRepository->createQueryBuilder('p')
            ->where('p.invitationStatus NOT IN (:validStatuses)')
            ->setParameter('validStatuses', [
                InvitationStatus::PENDING->value,
                InvitationStatus::ACCEPTED->value,
                InvitationStatus::DECLINED->value,
                InvitationStatus::EXPIRED->value,
                InvitationStatus::CONFLICT->value
            ])
            ->getQuery()
            ->getResult();

        if (!empty($invalidParticipations)) {
            $issues['invalid_participation_statuses'] = [
                'count' => count($invalidParticipations),
                'details' => array_map(fn($part) => [
                    'id' => $part->getId(),
                    'user_email' => $part->getUser()?->getEmail(),
                    'invalid_status' => $part->getInvitationStatus(),
                    'event_title' => $part->getEvent()?->getTitle()
                ], $invalidParticipations)
            ];
        }

        // Vérifier les incohérences entre invitation et participation
        $inconsistencies = $this->findStatusInconsistencies();
        if (!empty($inconsistencies)) {
            $issues['status_inconsistencies'] = [
                'count' => count($inconsistencies),
                'details' => $inconsistencies
            ];
        }

        return $issues;
    }

    /**
     * Trouve les incohérences de statuts entre invitations et participations
     */
    private function findStatusInconsistencies(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('i.id as invitation_id, i.email, i.status as invitation_status, p.id as participation_id, p.invitationStatus as participation_status, e.title as event_title')
            ->from('App\Entity\Invitation', 'i')
            ->leftJoin('App\Entity\Event', 'e', 'WITH', 'i.event = e')
            ->leftJoin('App\Entity\Participation', 'p', 'WITH', 'p.event = i.event AND p.user = (SELECT u FROM App\Entity\User u WHERE u.email = i.email)')
            ->where('i.status != p.invitationStatus OR p.invitationStatus IS NULL')
            ->andWhere('i.status IN (:validStatuses)')
            ->setParameter('validStatuses', [
                InvitationStatus::PENDING->value,
                InvitationStatus::ACCEPTED->value,
                InvitationStatus::DECLINED->value,
                InvitationStatus::EXPIRED->value,
                InvitationStatus::CONFLICT->value
            ]);

        return $qb->getQuery()->getResult();
    }

    /**
     * Corrige automatiquement les incohérences de statuts
     */
    public function fixStatusInconsistencies(): array
    {
        $fixed = [];
        
        // Corriger les invitations avec des statuts invalides
        $invalidInvitations = $this->invitationRepository->createQueryBuilder('i')
            ->where('i.status NOT IN (:validStatuses)')
            ->setParameter('validStatuses', [
                InvitationStatus::PENDING->value,
                InvitationStatus::ACCEPTED->value,
                InvitationStatus::DECLINED->value,
                InvitationStatus::EXPIRED->value,
                InvitationStatus::CONFLICT->value
            ])
            ->getQuery()
            ->getResult();

        foreach ($invalidInvitations as $invitation) {
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus(InvitationStatus::PENDING->value);
            $fixed['invalid_invitation_statuses'][] = [
                'id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'old_status' => $oldStatus,
                'new_status' => InvitationStatus::PENDING->value
            ];
        }

        // Corriger les participations avec des statuts invalides
        $invalidParticipations = $this->participationRepository->createQueryBuilder('p')
            ->where('p.invitationStatus NOT IN (:validStatuses)')
            ->setParameter('validStatuses', [
                InvitationStatus::PENDING->value,
                InvitationStatus::ACCEPTED->value,
                InvitationStatus::DECLINED->value,
                InvitationStatus::EXPIRED->value,
                InvitationStatus::CONFLICT->value
            ])
            ->getQuery()
            ->getResult();

        foreach ($invalidParticipations as $participation) {
            $oldStatus = $participation->getInvitationStatus();
            $participation->setInvitationStatus(InvitationStatus::PENDING->value);
            $fixed['invalid_participation_statuses'][] = [
                'id' => $participation->getId(),
                'user_email' => $participation->getUser()?->getEmail(),
                'old_status' => $oldStatus,
                'new_status' => InvitationStatus::PENDING->value
            ];
        }

        // Synchroniser les statuts entre invitation et participation
        $inconsistencies = $this->findStatusInconsistencies();
        foreach ($inconsistencies as $inconsistency) {
            if ($inconsistency['participation_id']) {
                $participation = $this->participationRepository->find($inconsistency['participation_id']);
                if ($participation) {
                    $oldStatus = $participation->getInvitationStatus();
                    $participation->setInvitationStatus($inconsistency['invitation_status']);
                    $fixed['synchronized_statuses'][] = [
                        'participation_id' => $inconsistency['participation_id'],
                        'invitation_id' => $inconsistency['invitation_id'],
                        'old_status' => $oldStatus,
                        'new_status' => $inconsistency['invitation_status']
                    ];
                }
            }
        }

        // Sauvegarder les corrections
        if (!empty($fixed)) {
            $this->entityManager->flush();
            $this->logger->info('Corrections de statuts appliquées', ['fixed' => $fixed]);
        }

        return $fixed;
    }

    /**
     * Génère un rapport de diagnostic complet
     */
    public function generateDiagnosticReport(): array
    {
        $issues = $this->diagnoseStatusInconsistencies();
        $totalIssues = array_sum(array_column($issues, 'count'));
        
        return [
            'summary' => [
                'total_issues' => $totalIssues,
                'issue_types' => array_keys($issues),
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ],
            'issues' => $issues,
            'recommendations' => $this->generateRecommendations($issues)
        ];
    }

    /**
     * Génère des recommandations basées sur les problèmes détectés
     */
    private function generateRecommendations(array $issues): array
    {
        $recommendations = [];
        
        if (!empty($issues)) {
            $recommendations[] = 'Exécuter fixStatusInconsistencies() pour corriger automatiquement les problèmes';
            $recommendations[] = 'Vérifier la cohérence des données après correction';
            $recommendations[] = 'Mettre à jour les templates pour afficher correctement tous les statuts';
        } else {
            $recommendations[] = 'Aucun problème détecté - les statuts sont cohérents';
        }
        
        return $recommendations;
    }
}
