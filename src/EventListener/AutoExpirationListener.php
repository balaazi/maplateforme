<?php

namespace App\EventListener;

use App\Repository\InvitationRepository;
use App\Service\InvitationExpirationNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Listener simple pour l'expiration automatique des invitations
 */
class AutoExpirationListener implements EventSubscriberInterface
{
    private static bool $hasChecked = false;
    private static int $lastCheckTime = 0;
    private const CHECK_INTERVAL = 300; // 5 minutes

    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private InvitationExpirationNotifier $expirationNotifier
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Ne traiter que les requêtes principales
        if (!$event->isMainRequest()) {
            return;
        }

        // Éviter les vérifications trop fréquentes
        $currentTime = time();
        if (self::$hasChecked && ($currentTime - self::$lastCheckTime) < self::CHECK_INTERVAL) {
            return;
        }

        // Vérifier seulement sur certaines routes importantes
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        
        if (!$this->shouldCheckRoute($route)) {
            return;
        }

        try {
            $this->checkAndExpireInvitations();
            self::$hasChecked = true;
            self::$lastCheckTime = $currentTime;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification automatique des invitations', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function shouldCheckRoute(?string $route): bool
    {
        $importantRoutes = [
            'invitation_index',
            'invitation_respond',
            'common_dashboard',
            'event_show',
            'event_list'
        ];

        return $route && in_array($route, $importantRoutes);
    }

    private function checkAndExpireInvitations(): void
    {
        try {
            // Récupérer toutes les invitations en attente
            $pendingInvitations = $this->invitationRepository->findBy(['status' => 'pending']);
            
            $expiredCount = 0;
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            
            foreach ($pendingInvitations as $invitation) {
                // Réduire le délai d'expiration à 1 jour pour le test
                if ($invitation->shouldBeExpired(1)) {
                    try {
                        // Utiliser le nouveau service pour mettre à jour le statut et envoyer l'e-mail
                        $this->expirationNotifier->notifyExpiration($invitation);
                        $expiredCount++;
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la notification d\'expiration', [
                            'invitation_id' => $invitation->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            if ($expiredCount > 0) {
                $this->entityManager->flush();
                $this->logger->info("Expiration automatique: {$expiredCount} invitations expirées");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'expiration automatique', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
