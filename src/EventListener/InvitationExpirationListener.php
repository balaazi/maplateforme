<?php

namespace App\EventListener;

use App\Service\InvitationExpirationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Listener qui vérifie et expire automatiquement les invitations
 * à chaque requête HTTP (avec limitation pour éviter les performances)
 */
class InvitationExpirationListener implements EventSubscriberInterface
{
    private bool $hasCheckedExpiration = false;
    private const CHECK_INTERVAL = 300; // 5 minutes en secondes
    private ?int $lastCheckTime = null;

    public function __construct(
        private InvitationExpirationService $expirationService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne pas traiter les requêtes non principales (sous-requêtes)
        if (!$event->isMainRequest()) {
            return;
        }

        // Éviter les vérifications trop fréquentes pour les performances
        $currentTime = time();
        if ($this->lastCheckTime && ($currentTime - $this->lastCheckTime) < self::CHECK_INTERVAL) {
            return;
        }

        // Ne vérifier que sur certaines routes importantes
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        if (!$this->shouldCheckExpiration($route)) {
            return;
        }

        try {
            $this->checkAndExpireInvitations();
            $this->lastCheckTime = $currentTime;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification automatique des invitations expirées', [
                'error' => $e->getMessage(),
                'route' => $route
            ]);
        }
    }

    private function shouldCheckExpiration(?string $route): bool
    {
        // Routes où on veut vérifier l'expiration automatiquement
        $routesToCheck = [
            'invitation_index',
            'invitation_respond',
            'event_show',
            'event_list',
            'dashboard',
            'app_dashboard',
            'common_dashboard'
        ];

        return $route && in_array($route, $routesToCheck);
    }

    private function checkAndExpireInvitations(): void
    {
        try {
            // Utiliser la méthode existante du service pour expirer les invitations
            $expiredCount = $this->expirationService->expireOldInvitations(30);
            
            if ($expiredCount > 0) {
                $this->logger->info("Expiration automatique: {$expiredCount} invitations expirées via Event Listener");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'expiration automatique des invitations', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
