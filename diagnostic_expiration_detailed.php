<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Invitation;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$invitationRepo = $entityManager->getRepository(Invitation::class);

echo "🔍 DIAGNOSTIC DÉTAILLÉ DU SYSTÈME D'EXPIRATION\n";
echo "=============================================\n\n";

try {
    // 1. Vérifier toutes les invitations avec détails
    echo "📋 ÉTAT DÉTAILLÉ DES INVITATIONS :\n";
    echo "-----------------------------------\n";
    
    $allInvitations = $invitationRepo->findAll();
    
    if (empty($allInvitations)) {
        echo "   ❌ Aucune invitation trouvée en base\n";
        exit;
    }
    
    foreach ($allInvitations as $invitation) {
        $createdDate = $invitation->getCreatedAt();
        $now = new \DateTime();
        $daysDiff = $now->diff($createdDate)->days;
        
        echo "   ID {$invitation->getId()}: {$invitation->getEmail()} - {$invitation->getEvent()?->getTitle()}\n";
        echo "      Statut: '{$invitation->getStatus()}'\n";
        echo "      Créée: {$createdDate->format('d/m/Y H:i:s')} (il y a {$daysDiff} jour(s))\n";
        echo "      Mise à jour: " . ($invitation->getUpdatedAt() ? $invitation->getUpdatedAt()->format('d/m/Y H:i:s') : 'Jamais') . "\n";
        
        // Vérifier si elle devrait être expirée
        if ($invitation->getStatus() === 'pending' && $daysDiff >= 30) {
            echo "      ⚠️  DEVRAIT ÊTRE EXPIRÉE !\n";
        } elseif ($invitation->getStatus() === 'pending' && $daysDiff < 30) {
            echo "      ✅ Pas encore expirée (expirera dans " . (30 - $daysDiff) . " jour(s))\n";
        } elseif ($invitation->getStatus() === 'expired') {
            echo "      ✅ Déjà expirée\n";
        }
        echo "\n";
    }
    
    // 2. Test de la requête du repository
    echo "🔍 TEST DE LA REQUÊTE D'EXPIRATION :\n";
    echo "-----------------------------------\n";
    
    // Simuler la logique du service
    $expirationDate = new \DateTime();
    $expirationDate->modify("-30 days");
    
    echo "   Date d'expiration calculée: {$expirationDate->format('d/m/Y H:i:s')}\n";
    echo "   (30 jours avant aujourd'hui)\n\n";
    
    // Test de la requête du repository
    $expiredInvitations = $invitationRepo->findExpiredInvitations($expirationDate);
    echo "   Invitations trouvées par findExpiredInvitations(): " . count($expiredInvitations) . "\n";
    
    if (!empty($expiredInvitations)) {
        foreach ($expiredInvitations as $invitation) {
            echo "      - ID {$invitation->getId()}: {$invitation->getEmail()}\n";
        }
    }
    
    echo "\n";
    
    // 3. Test manuel de la requête
    echo "🔍 TEST MANUEL DE LA REQUÊTE :\n";
    echo "------------------------------\n";
    
    $manualQuery = $invitationRepo->createQueryBuilder('i')
        ->andWhere('i.status = :status')
        ->andWhere('i.createdAt < :expirationDate')
        ->setParameter('status', 'pending')
        ->setParameter('expirationDate', $expirationDate)
        ->orderBy('i.createdAt', 'ASC')
        ->getQuery();
    
    echo "   Requête SQL générée:\n";
    echo "   " . $manualQuery->getSQL() . "\n\n";
    
    echo "   Paramètres:\n";
    echo "   - status: pending\n";
    echo "   - expirationDate: {$expirationDate->format('d/m/Y H:i:s')}\n\n";
    
    $manualResults = $manualQuery->getResult();
    echo "   Résultats de la requête manuelle: " . count($manualResults) . "\n";
    
    if (!empty($manualResults)) {
        foreach ($manualResults as $invitation) {
            $createdDate = $invitation->getCreatedAt();
            $daysDiff = $now->diff($createdDate)->days;
            echo "      - ID {$invitation->getId()}: {$invitation->getEmail()} - Créée il y a {$daysDiff} jour(s)\n";
        }
    }
    
    echo "\n";
    
    // 4. Vérification des invitations en attente
    echo "⏰ INVITATIONS EN ATTENTE DÉTAILLÉES :\n";
    echo "-------------------------------------\n";
    
    $pendingInvitations = $invitationRepo->createQueryBuilder('i')
        ->andWhere('i.status = :status')
        ->setParameter('status', 'pending')
        ->orderBy('i.createdAt', 'ASC')
        ->getQuery()
        ->getResult();
    
    if (empty($pendingInvitations)) {
        echo "   ✅ Aucune invitation en attente trouvée\n";
    } else {
        echo "   Trouvé " . count($pendingInvitations) . " invitation(s) en attente:\n\n";
        
        foreach ($pendingInvitations as $invitation) {
            $createdDate = $invitation->getCreatedAt();
            $daysDiff = $now->diff($createdDate)->days;
            
            echo "   📧 ID {$invitation->getId()}: {$invitation->getEmail()}\n";
            echo "      Événement: {$invitation->getEvent()?->getTitle()}\n";
            echo "      Créée: {$createdDate->format('d/m/Y H:i:s')}\n";
            echo "      Âge: {$daysDiff} jour(s)\n";
            
            if ($daysDiff >= 30) {
                echo "      ⚠️  DEVRAIT ÊTRE EXPIRÉE !\n";
            } else {
                echo "      ✅ Pas encore expirée (expirera dans " . (30 - $daysDiff) . " jour(s))\n";
            }
            echo "\n";
        }
    }
    
    // 5. Test de la commande console
    echo "🧪 TEST DE LA COMMANDE CONSOLE :\n";
    echo "--------------------------------\n";
    
    echo "   Exécution de la commande d'expiration...\n";
    
    // Simuler l'appel au service
    $expirationService = $container->get(\App\Service\InvitationExpirationService::class);
    $count = $expirationService->expireOldInvitations(30);
    
    echo "   Résultat: {$count} invitation(s) expirée(s)\n\n";
    
    // 6. Vérification finale
    echo "🔍 VÉRIFICATION FINALE :\n";
    echo "------------------------\n";
    
    $finalStatusCounts = [];
    $finalInvitations = $invitationRepo->findAll();
    
    foreach ($finalInvitations as $invitation) {
        $status = $invitation->getStatus();
        $finalStatusCounts[$status] = ($finalStatusCounts[$status] ?? 0) + 1;
    }
    
    echo "   Statuts finaux dans la base:\n";
    foreach ($finalStatusCounts as $status => $count) {
        echo "   - '{$status}': {$count} invitation(s)\n";
    }
    
    echo "\n";
    echo "🎉 DIAGNOSTIC TERMINÉ !\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
