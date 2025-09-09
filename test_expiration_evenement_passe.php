<?php
/**
 * Script de test pour vérifier l'expiration des invitations pour événements passés
 * Usage: php test_expiration_evenement_passe.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Event;
use App\Entity\Invitation;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test d'expiration d'invitations pour événements passés - EventHub\n";
echo "============================================================\n\n";

// Créer un kernel Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer les services nécessaires
$entityManager = $container->get('doctrine')->getManager();
$eventRepo = $entityManager->getRepository(Event::class);
$invitationRepo = $entityManager->getRepository(Invitation::class);
$eventExpirationService = $container->get(\App\Service\EventExpirationService::class);

// 1. Vérifier les événements passés
echo "1. Recherche d'événements passés...\n";
$now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
$fromDate = (clone $now)->modify("-30 days");
$events = $eventRepo->findEventsEndedBetween($fromDate, $now);

echo "   Trouvé " . count($events) . " événements passés\n\n";

if (count($events) > 0) {
    $event = $events[0];
    echo "   Exemple d'événement passé:\n";
    echo "   - ID: " . $event->getId() . "\n";
    echo "   - Titre: " . $event->getTitle() . "\n";
    echo "   - Date: " . $event->getDateHeure()->format('Y-m-d H:i:s') . "\n";
    echo "   - Durée: " . $event->getDuree() . " minutes\n";
    
    // 2. Vérifier les invitations en attente pour cet événement
    $pendingCount = 0;
    foreach ($event->getInvitations() as $invitation) {
        if ($invitation->getStatus() === InvitationStatus::PENDING->value) {
            $pendingCount++;
        }
    }
    
    echo "   - Invitations en attente: " . $pendingCount . "\n\n";
    
    // 3. Tester le service d'expiration
    if ($pendingCount > 0) {
        echo "2. Test du service EventExpirationService...\n";
        
        // Vérifier si l'événement est passé
        $isPassed = $eventExpirationService->isEventPassed($event);
        echo "   - Événement passé: " . ($isPassed ? "OUI" : "NON") . "\n";
        
        // Expirer les invitations
        $expiredCount = $eventExpirationService->expireInvitationsForPassedEvent($event);
        echo "   - Invitations expirées: " . $expiredCount . "\n\n";
        
        echo "3. Vérification des statuts après expiration...\n";
        $expiredCount = 0;
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getStatus() === InvitationStatus::EXPIRED->value) {
                $expiredCount++;
            }
        }
        echo "   - Invitations avec statut 'expired': " . $expiredCount . "\n";
    } else {
        echo "2. Aucune invitation en attente à expirer pour cet événement.\n";
    }
} else {
    echo "Aucun événement passé trouvé dans les 30 derniers jours.\n";
}

echo "\n✅ Test terminé!\n";
