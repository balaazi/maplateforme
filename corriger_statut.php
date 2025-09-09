<?php
/**
 * Script de correction du statut d'invitation
 * Usage: php corriger_statut.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔧 Correction du Statut d'Invitation - EventHub\n";
echo "=============================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'invitation Formation js
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $invitation = $invitationRepo->find(8); // ID de l'invitation Formation js
    
    if ($invitation) {
        echo "✅ Invitation trouvée:\n";
        echo "   - ID: {$invitation->getId()}\n";
        echo "   - Email: {$invitation->getEmail()}\n";
        echo "   - Événement: " . ($invitation->getEvent() ? $invitation->getEvent()->getTitle() : 'N/A') . "\n";
        echo "   - Statut actuel: '{$invitation->getStatus()}'\n";
        echo "   - Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n";
        echo "   - Mise à jour: {$invitation->getUpdatedAt()->format('d/m/Y H:i:s')}\n\n";
        
        // Vérifier si l'invitation doit être expirée
        $createdDate = $invitation->getCreatedAt();
        $now = new \DateTime();
        $daysDiff = $now->diff($createdDate)->days;
        
        echo "🔍 Analyse de l'expiration:\n";
        echo "   - Créée il y a: {$daysDiff} jour(s)\n";
        echo "   - Délai d'expiration: 30 jours\n";
        
        if ($daysDiff >= 30) {
            echo "   - ⚠️ L'invitation devrait être expirée !\n\n";
            
            // Corriger le statut
            echo "🔧 Correction du statut...\n";
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());
            
            // Sauvegarder
            $entityManager->flush();
            echo "   ✅ Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
            
            // Vérifier la sauvegarde
            $entityManager->clear();
            $invitationCheck = $invitationRepo->find(8);
            echo "   - Vérification en base: statut = '{$invitationCheck->getStatus()}'\n";
            
            if ($invitationCheck->getStatus() === 'expired') {
                echo "   🎉 Correction réussie ! L'invitation est maintenant EXPIRÉE\n";
            } else {
                echo "   ❌ Correction échouée ! Le statut n'a pas été sauvegardé\n";
            }
            
        } else {
            echo "   - ✅ L'invitation n'est pas encore expirée\n";
            echo "   - Elle expirera dans " . (30 - $daysDiff) . " jour(s)\n";
        }
        
    } else {
        echo "❌ Invitation ID 8 non trouvée\n";
    }
    
    echo "\n🔍 Vérification finale des statuts...\n";
    
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
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
