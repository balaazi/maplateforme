<?php

namespace App\Service;

use App\Service\InvitationExpirationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service qui gère l'expiration automatique des invitations
 * sans avoir besoin d'exécuter des commandes manuelles
 */
class AutoExpirationService
{
    private const EXPIRATION_CHECK_KEY = 'last_invitation_expiration_check';
    private const CHECK_INTERVAL_HOURS = 1; // Vérifier toutes les heures

    public function __construct(
        private InvitationExpirationService $expirationService,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag
    ) {}

    /**
     * Vérifie si l'expiration doit être exécutée et l'exécute si nécessaire
     */
    public function checkAndExecuteExpiration(): int
    {
        $lastCheck = $this->getLastExpirationCheck();
        $now = new \DateTime();
        
        // Vérifier si assez de temps s'est écoulé depuis la dernière vérification
        if ($lastCheck && $now->diff($lastCheck)->h < self::CHECK_INTERVAL_HOURS) {
            return 0; // Pas besoin de vérifier maintenant
        }

        try {
            $expiredCount = $this->expirationService->expireOldInvitations(30);
            
            if ($expiredCount > 0) {
                $this->logger->info("Expiration automatique exécutée: {$expiredCount} invitations expirées");
            }
            
            // Mettre à jour le timestamp de la dernière vérification
            $this->setLastExpirationCheck($now);
            
            return $expiredCount;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'expiration automatique', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Force l'exécution de l'expiration (utilisé par les contrôleurs)
     */
    public function forceExpirationCheck(): int
    {
        try {
            $expiredCount = $this->expirationService->expireOldInvitations(30);
            
            if ($expiredCount > 0) {
                $this->logger->info("Expiration forcée: {$expiredCount} invitations expirées");
            }
            
            return $expiredCount;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'expiration forcée', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Récupère la date de la dernière vérification d'expiration
     */
    private function getLastExpirationCheck(): ?\DateTime
    {
        try {
            $timestamp = $this->parameterBag->get(self::EXPIRATION_CHECK_KEY);
            return $timestamp ? new \DateTime($timestamp) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Met à jour la date de la dernière vérification d'expiration
     */
    private function setLastExpirationCheck(\DateTime $dateTime): void
    {
        try {
            // Stocker dans un fichier temporaire ou en base de données
            $cacheFile = sys_get_temp_dir() . '/eventhub_expiration_check.txt';
            file_put_contents($cacheFile, $dateTime->format('Y-m-d H:i:s'));
        } catch (\Exception $e) {
            $this->logger->warning('Impossible de sauvegarder le timestamp de vérification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère la date de la dernière vérification depuis le fichier de cache
     */
    public function getLastExpirationCheckFromCache(): ?\DateTime
    {
        try {
            $cacheFile = sys_get_temp_dir() . '/eventhub_expiration_check.txt';
            if (file_exists($cacheFile)) {
                $timestamp = file_get_contents($cacheFile);
                return new \DateTime($timestamp);
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de lecture du cache
        }
        
        return null;
    }
}
