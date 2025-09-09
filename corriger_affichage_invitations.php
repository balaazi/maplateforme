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

echo "🔧 CORRECTION DE L'AFFICHAGE DES INVITATIONS\n";
echo "===========================================\n\n";

try {
    // 1. Vérifier l'état actuel
    echo "📋 ÉTAT ACTUEL DES INVITATIONS :\n";
    echo "--------------------------------\n";
    
    $allInvitations = $invitationRepo->findAll();
    $statusCounts = [];
    
    foreach ($allInvitations as $invitation) {
        $status = $invitation->getStatus();
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    
    foreach ($statusCounts as $status => $count) {
        echo "   - '{$status}': {$count} invitation(s)\n";
    }
    
    echo "\n";
    
    // 2. Identifier les invitations problématiques
    echo "🔍 IDENTIFICATION DES PROBLÈMES :\n";
    echo "--------------------------------\n";
    
    $now = new \DateTime();
    $expirationDate = clone $now;
    $expirationDate->modify('-30 days');
    
    echo "   Date d'expiration: {$expirationDate->format('d/m/Y H:i:s')}\n";
    echo "   Date actuelle: {$now->format('d/m/Y H:i:s')}\n\n";
    
    $problematicInvitations = [];
    
    foreach ($allInvitations as $invitation) {
        $createdDate = $invitation->getCreatedAt();
        $daysDiff = $now->diff($createdDate)->days;
        
        // Vérifier les incohérences
        if ($invitation->getStatus() === 'pending' && $daysDiff >= 30) {
            $problematicInvitations[] = $invitation;
            echo "   ⚠️  ID {$invitation->getId()}: {$invitation->getEmail()} - Créée il y a {$daysDiff} jour(s) - DEVRAIT ÊTRE EXPIRÉE\n";
        } elseif ($invitation->getStatus() === 'expired' && $daysDiff < 30) {
            echo "   ⚠️  ID {$invitation->getId()}: {$invitation->getEmail()} - Créée il y a {$daysDiff} jour(s) - EXPIRÉE TROP TÔT\n";
        }
    }
    
    if (empty($problematicInvitations)) {
        echo "   ✅ Aucune invitation problématique trouvée\n";
    }
    
    echo "\n";
    
    // 3. Correction automatique
    if (!empty($problematicInvitations)) {
        echo "🔧 CORRECTION AUTOMATIQUE :\n";
        echo "--------------------------\n";
        
        foreach ($problematicInvitations as $invitation) {
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());
            
            echo "   ✅ ID {$invitation->getId()}: Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
        }
        
        // Sauvegarder les modifications
        $entityManager->flush();
        echo "   💾 Modifications sauvegardées en base\n";
    }
    
    // 4. Vérification des invitations en attente récentes
    echo "⏰ VÉRIFICATION DES INVITATIONS EN ATTENTE :\n";
    echo "--------------------------------------------\n";
    
    $pendingInvitations = $invitationRepo->createQueryBuilder('i')
        ->andWhere('i.status = :status')
        ->setParameter('status', 'pending')
        ->orderBy('i.createdAt', 'DESC')
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
    
    // 6. Nettoyage du cache
    echo "\n🧹 NETTOYAGE DU CACHE :\n";
    echo "------------------------\n";
    
    echo "   Vider le cache Symfony...\n";
    
    // Vider le cache
    $cacheDir = $kernel->getCacheDir();
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } elseif (is_dir($file)) {
                // Supprimer récursivement
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($file, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $child) {
                    if ($child->isDir()) {
                        rmdir($child->getRealPath());
                    } else {
                        unlink($child->getRealPath());
                    }
                }
                rmdir($file);
            }
        }
        echo "   ✅ Cache vidé avec succès\n";
    } else {
        echo "   ⚠️  Répertoire de cache non trouvé\n";
    }
    
    echo "\n";
    echo "🎉 CORRECTION TERMINÉE !\n";
    echo "========================\n";
    echo "   Maintenant, rechargez la page dans votre navigateur.\n";
    echo "   Les statuts devraient être correctement affichés.\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
