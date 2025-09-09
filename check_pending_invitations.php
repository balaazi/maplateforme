<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$_SERVER['APP_RUNTIME_OPTIONS'] = [

    'project_dir' => __DIR__,
];

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$invitationRepository = $container->get('App\Repository\InvitationRepository');
$entityManager = $container->get('doctrine.orm.entity_manager');
$expirationNotifier = $container->get('App\Service\InvitationExpirationNotifier');
$logger = $container->get('logger');

try {
    // Récupérer toutes les invitations en attente
    $pendingInvitations = $invitationRepository->findBy(['status' => 'pending']);
    
    $expiredCount = 0;
    $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    
    foreach ($pendingInvitations as $invitation) {
        // Forcer la vérification avec un délai de 1 jour
        if ($invitation->shouldBeExpired(1)) {
            try {
                $expirationNotifier->notifyExpiration($invitation);
                $expiredCount++;
                echo sprintf(
                    "Invitation expirée - ID: %d, Email: %s\n",
                    $invitation->getId(),
                    $invitation->getEmail()
                );
            } catch (\Exception $e) {
                echo sprintf(
                    "Erreur lors de l'expiration - ID: %d, Email: %s, Erreur: %s\n",
                    $invitation->getId(),
                    $invitation->getEmail(),
                    $e->getMessage()
                );
            }
        } else {
            echo sprintf(
                "Invitation non expirée - ID: %d, Email: %s, Créée le: %s\n",
                $invitation->getId(),
                $invitation->getEmail(),
                $invitation->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }
    }
    
    if ($expiredCount > 0) {
        $entityManager->flush();
        echo "\nTotal des invitations expirées : " . $expiredCount . "\n";
    } else {
        echo "\nAucune invitation n'a été expirée.\n";
    }
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
