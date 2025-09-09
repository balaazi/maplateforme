<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use App\Service\InvitationExpirationService;
use App\Service\InvitationExpirationNotificationService;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Initialiser Symfony
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$invitationRepo = $entityManager->getRepository(Invitation::class);

echo "🔍 DIAGNOSTIC DES INVITATIONS EXPIRÉES\n";
echo "=====================================\n\n";

try {
    // 1. Vérifier toutes les invitations
    echo "📋 ÉTAT ACTUEL DES INVITATIONS :\n";
    echo "--------------------------------\n";
    
    $allInvitations = $invitationRepo->findAll();
    $statusCounts = [];
    
    foreach ($allInvitations as $invitation) {
        $status = $invitation->getStatus();
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    
    foreach ($statusCounts as $status => $count) {
        echo "   - {$status}: {$count} invitation(s)\n";
    }
    
    echo "\n";
    
    // 2. Identifier les invitations qui devraient être expirées
    echo "⏰ INVITATIONS QUI DEVRaient ÊTRE EXPIRÉES :\n";
    echo "--------------------------------------------\n";
    
    $pendingInvitations = $invitationRepo->createQueryBuilder('i')
        ->andWhere('i.status = :status')
        ->setParameter('status', 'pending')
        ->getQuery()
        ->getResult();
    
    $shouldExpire = [];
    foreach ($pendingInvitations as $invitation) {
        $createdDate = $invitation->getCreatedAt();
        $now = new \DateTime();
        $daysDiff = $now->diff($createdDate)->days;
        
        if ($daysDiff >= 30) {
            $shouldExpire[] = $invitation;
            echo "   ⚠️  ID {$invitation->getId()} - {$invitation->getEmail()} - {$invitation->getEvent()?->getTitle()} - Créée il y a {$daysDiff} jour(s)\n";
        }
    }
    
    if (empty($shouldExpire)) {
        echo "   ✅ Aucune invitation en attente ne devrait être expirée\n";
    }
    
    echo "\n";
    
    // 3. Vérifier les invitations déjà expirées
    echo "🔒 INVITATIONS DÉJÀ EXPIRÉES :\n";
    echo "------------------------------\n";
    
    $expiredInvitations = $invitationRepo->createQueryBuilder('i')
        ->andWhere('i.status = :status')
        ->setParameter('status', 'expired')
        ->orderBy('i.updatedAt', 'DESC')
        ->getQuery()
        ->getResult();
    
    foreach ($expiredInvitations as $invitation) {
        $createdDate = $invitation->getCreatedAt();
        $updatedDate = $invitation->getUpdatedAt();
        $now = new \DateTime();
        $daysDiff = $now->diff($createdDate)->days;
        
        echo "   ✅ ID {$invitation->getId()} - {$invitation->getEmail()} - {$invitation->getEvent()?->getTitle()} - Expirée il y a " . $now->diff($updatedDate)->days . " jour(s)\n";
    }
    
    echo "\n";
    
    // 4. Correction automatique si nécessaire
    if (!empty($shouldExpire)) {
        echo "🔧 CORRECTION AUTOMATIQUE :\n";
        echo "--------------------------\n";
        
        $expirationService = $container->get(InvitationExpirationService::class);
        
        foreach ($shouldExpire as $invitation) {
            echo "   🔧 Correction de l'invitation ID {$invitation->getId()}...\n";
            
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());
            
            echo "      ✅ Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
        }
        
        // Sauvegarder les modifications
        $entityManager->flush();
        echo "      💾 Modifications sauvegardées en base\n";
        
        // Envoyer les notifications d'expiration
        // DÉSACTIVÉ - Aucun email d'expiration n'est envoyé
        echo "      📧 [DÉSACTIVÉ] Aucune notification d'expiration envoyée\n";
        echo "      ℹ️  Les statuts sont mis à jour automatiquement sans notification\n";
    }
    
    echo "\n";
    
    // 5. Vérification finale
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
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
