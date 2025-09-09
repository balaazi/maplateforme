<?php
/**
 * Script pour forcer l'expiration de l'invitation "Réunion"
 * Usage: php forcer_expiration_reunion.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔧 Forçage de l'Expiration de l'Invitation 'Réunion' - EventHub\n";
echo "==============================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'invitation "Réunion" (ID 34)
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $invitation = $invitationRepo->find(34);
    
    if (!$invitation) {
        echo "❌ Invitation ID 34 (Réunion) non trouvée\n";
        exit;
    }
    
    $event = $invitation->getEvent();
    $eventTitle = $event ? $event->getTitle() : 'Événement inconnu';
    
    echo "📋 Invitation 'Réunion' trouvée:\n";
    echo "   - ID: {$invitation->getId()}\n";
    echo "   - Nom: {$invitation->getName()}\n";
    echo "   - Email: {$invitation->getEmail()}\n";
    echo "   - Événement: {$eventTitle}\n";
    echo "   - Statut actuel: '{$invitation->getStatus()}'\n";
    echo "   - Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n\n";
    
    // Forcer le changement de statut vers 'expired'
    echo "🔧 Changement forcé du statut...\n";
    $oldStatus = $invitation->getStatus();
    
    // Méthode 1: Utiliser setStatus() de l'entité
    try {
        $invitation->setStatus('expired');
        $invitation->setUpdatedAt(new \DateTime());
        
        // Sauvegarder
        $entityManager->flush();
        echo "   ✅ Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
        
    } catch (\Exception $e) {
        echo "   ⚠️ Erreur avec setStatus(): " . $e->getMessage() . "\n";
        echo "   🔧 Tentative avec requête SQL directe...\n";
        
        // Méthode 2: Requête SQL directe
        try {
            $connection = $entityManager->getConnection();
            $sql = "UPDATE invitation SET status = 'expired', updated_at = NOW() WHERE id = 34";
            $stmt = $connection->prepare($sql);
            $result = $stmt->executeQuery();
            
            if ($result->rowCount() > 0) {
                echo "   ✅ Statut changé via SQL direct\n";
                
                // Vérifier la sauvegarde
                $entityManager->clear();
                $invitationCheck = $invitationRepo->find(34);
                echo "   - Vérification en base: statut = '{$invitationCheck->getStatus()}'\n";
                
                if ($invitationCheck->getStatus() === 'expired') {
                    echo "   🎉 Changement réussi ! L'invitation est maintenant EXPIRÉE\n";
                } else {
                    echo "   ❌ Le statut n'a pas été sauvegardé\n";
                }
            } else {
                echo "   ❌ Aucune ligne modifiée\n";
            }
            
        } catch (\Exception $e2) {
            echo "   ❌ Erreur SQL: " . $e2->getMessage() . "\n";
        }
    }
    
    echo "\n🔍 Vérification finale...\n";
    
    // Vérifier tous les statuts d'invitation
    $statuses = $invitationRepo->createQueryBuilder('i')
        ->select('i.status, COUNT(i.id) as count')
        ->groupBy('i.status')
        ->getQuery()
        ->getResult();
    
    echo "   Statuts actuels dans la base:\n";
    foreach ($statuses as $status) {
        $statusText = match ($status['status']) {
            'pending' => 'EN ATTENTE',
            'accepted' => 'ACCEPTÉE',
            'declined' => 'REFUSÉE',
            'expired' => 'EXPIRÉE',
            'conflict' => 'CONFLIT HORAIRE',
            default => $status['status']
        };
        echo "   - {$statusText}: {$status['count']} invitation(s)\n";
    }
    
    echo "\n✅ Opération terminée !\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
