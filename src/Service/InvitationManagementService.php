<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class InvitationManagementService
{
    public function __construct(
        private InvitationExpirationService $expirationService,
        private AutomaticConflictDetectionService $conflictDetectionService,
        private LoggerInterface $logger
    ) {}

    /**
     * Gère automatiquement l'expiration des invitations ET la détection des conflits
     * @param int $daysExpiration Nombre de jours avant expiration (défaut: 30)
     * @return array Résultats des opérations
     */
    public function manageInvitationsAutomatically(int $daysExpiration = 30): array
    {
        $this->logger->info('Début de la gestion automatique des invitations');
        
        $results = [
            'expired' => 0,
            'conflicts' => 0,
            'total_processed' => 0
        ];

        try {
            // 1. Détecter et marquer les conflits d'horaires
            $this->logger->info('Étape 1: Détection des conflits d\'horaires');
            $results['conflicts'] = $this->conflictDetectionService->detectAndMarkConflicts();
            
            // 2. Marquer les invitations expirées
            $this->logger->info('Étape 2: Expiration des invitations anciennes');
            $results['expired'] = $this->expirationService->expireOldInvitations($daysExpiration);
            
            // 3. Calculer le total
            $results['total_processed'] = $results['expired'] + $results['conflicts'];
            
            $this->logger->info('Gestion automatique terminée', $results);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la gestion automatique des invitations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Gère les invitations pour un utilisateur spécifique
     * @param string $userEmail Email de l'utilisateur
     * @return array Résultats des opérations
     */
    public function manageInvitationsForUser(string $userEmail): array
    {
        $this->logger->info("Gestion des invitations pour l'utilisateur: {$userEmail}");
        
        $results = [
            'user_email' => $userEmail,
            'conflicts' => 0,
            'expired' => 0
        ];

        try {
            // Détecter les conflits pour cet utilisateur
            $results['conflicts'] = $this->conflictDetectionService->detectConflictsForUser($userEmail);
            
            // Note: L'expiration se fait globalement, pas par utilisateur
            $this->logger->info("Invitations gérées pour l'utilisateur {$userEmail}", $results);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la gestion des invitations pour {$userEmail}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Gère les invitations pour un événement spécifique
     * @param int $eventId ID de l'événement
     * @return array Résultats des opérations
     */
    public function manageInvitationsForEvent(int $eventId): array
    {
        $this->logger->info("Gestion des invitations pour l'événement ID: {$eventId}");
        
        $results = [
            'event_id' => $eventId,
            'conflicts' => 0,
            'expired' => 0
        ];

        try {
            // Détecter les conflits pour cet événement
            $results['conflicts'] = $this->conflictDetectionService->detectConflictsForEvent($eventId);
            
            // Note: L'expiration se fait globalement, pas par événement
            $this->logger->info("Invitations gérées pour l'événement ID {$eventId}", $results);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la gestion des invitations pour l'événement {$eventId}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Vérifie l'état général des invitations
     * @return array État des invitations
     */
    public function getInvitationsStatus(): array
    {
        $this->logger->info('Vérification de l\'état général des invitations');
        
        try {
            // Cette méthode pourrait être étendue pour fournir des statistiques
            // sur l'état des invitations (pending, accepted, declined, expired, conflict)
            return [
                'status' => 'ready',
                'message' => 'Service de gestion des invitations opérationnel'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du statut des invitations', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
}
