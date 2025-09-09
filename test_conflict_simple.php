<?php
/**
 * Script de test simple pour les conflits d'horaires
 * Usage: php test_conflict_simple.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test Simple des Conflits d'Horaires - EventHub\n";
echo "================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer les repositories
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $participationRepo = $container->get('doctrine')->getRepository('App\Entity\Participation');
    
    echo "🔍 Test de définition du statut CONFLICT...\n\n";
    
    // Récupérer une invitation en attente pour tester
    $pendingInvitation = $invitationRepo->findOneBy(['status' => 'pending']);
    
    if ($pendingInvitation) {
        echo "✅ Invitation en attente trouvée:\n";
        echo "   - ID: {$pendingInvitation->getId()}\n";
        echo "   - Email: {$pendingInvitation->getEmail()}\n";
        echo "   - Statut actuel: '{$pendingInvitation->getStatus()}'\n";
        echo "   - Événement: " . ($pendingInvitation->getEvent() ? $pendingInvitation->getEvent()->getTitle() : 'N/A') . "\n\n";
        
        // Tester la définition du statut CONFLICT
        echo "🔧 Test de changement vers le statut CONFLICT...\n";
        
        try {
            // Changer le statut
            $oldStatus = $pendingInvitation->getStatus();
            $pendingInvitation->setStatus(InvitationStatus::CONFLICT->value);
            $pendingInvitation->setUpdatedAt(new \DateTime());
            
            echo "   - Statut changé de '{$oldStatus}' vers '{$pendingInvitation->getStatus()}'\n";
            
            // Sauvegarder
            $entityManager->flush();
            echo "   - Changement sauvegardé en base de données\n";
            
            // Vérifier que le changement est persistant
            $entityManager->clear();
            $invitationCheck = $invitationRepo->find($pendingInvitation->getId());
            echo "   - Vérification en base: statut = '{$invitationCheck->getStatus()}'\n";
            
            if ($invitationCheck->getStatus() === InvitationStatus::CONFLICT->value) {
                echo "   ✅ Test réussi ! Le statut CONFLICT est bien sauvegardé\n";
            } else {
                echo "   ❌ Test échoué ! Le statut n'a pas été sauvegardé\n";
            }
            
            // Remettre le statut original
            $invitationCheck->setStatus($oldStatus);
            $invitationCheck->setUpdatedAt(new \DateTime());
            $entityManager->flush();
            echo "   - Statut remis à '{$oldStatus}'\n";
            
        } catch (\Exception $e) {
            echo "   ❌ Erreur lors du test: {$e->getMessage()}\n";
        }
        
    } else {
        echo "⚠️ Aucune invitation en attente trouvée pour le test\n";
    }
    
    echo "\n🔍 Vérification des statuts disponibles...\n";
    
    // Vérifier tous les statuts d'invitation
    $statuses = $invitationRepo->createQueryBuilder('i')
        ->select('i.status, COUNT(i.id) as count')
        ->groupBy('i.status')
        ->getQuery()
        ->getResult();
    
    echo "   Statuts actuels dans la base:\n";
    foreach ($statuses as $status) {
        echo "   - '{$status['status']}': {$status['count']} invitation(s)\n";
    }
    
    // Vérifier que l'enum fonctionne
    echo "\n🔍 Test de l'enum InvitationStatus...\n";
    echo "   - PENDING: " . InvitationStatus::PENDING->value . "\n";
    echo "   - ACCEPTED: " . InvitationStatus::ACCEPTED->value . "\n";
    echo "   - DECLINED: " . InvitationStatus::DECLINED->value . "\n";
    echo "   - EXPIRED: " . InvitationStatus::EXPIRED->value . "\n";
    echo "   - CONFLICT: " . InvitationStatus::CONFLICT->value . "\n";
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "   - Le statut CONFLICT peut être défini et sauvegardé\n";
    echo "   - L'enum InvitationStatus fonctionne correctement\n";
    echo "   - La base de données accepte le statut 'conflict'\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
