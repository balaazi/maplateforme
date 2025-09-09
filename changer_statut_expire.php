<?php
/**
 * Script pour changer le statut d'une invitation de "EN ATTENTE" vers "EXPIRÉ"
 * Usage: php changer_statut_expire.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔧 Changement de Statut d'Invitation - EventHub\n";
echo "=============================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer toutes les invitations en attente
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $pendingInvitations = $invitationRepo->findBy(['status' => 'pending']);
    
    if (empty($pendingInvitations)) {
        echo "ℹ️ Aucune invitation en attente trouvée\n";
        exit;
    }
    
    echo "📋 Invitations en attente trouvées:\n";
    foreach ($pendingInvitations as $invitation) {
        $eventTitle = $invitation->getEvent() ? $invitation->getEvent()->getTitle() : 'Événement inconnu';
        $createdDate = $invitation->getCreatedAt()->format('d/m/Y H:i');
        $daysDiff = (new \DateTime())->diff($invitation->getCreatedAt())->days;
        
        echo "   - ID: {$invitation->getId()}\n";
        echo "     Nom: {$invitation->getName()}\n";
        echo "     Email: {$invitation->getEmail()}\n";
        echo "     Événement: {$eventTitle}\n";
        echo "     Créée le: {$createdDate} ({$daysDiff} jour(s) ago)\n";
        echo "     Statut actuel: {$invitation->getStatus()}\n";
        echo "\n";
    }
    
    // Demander quelle invitation modifier
    echo "🔍 Entrez l'ID de l'invitation à marquer comme expirée (ou 'all' pour toutes): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    if ($input === 'all') {
        // Marquer toutes les invitations en attente comme expirées
        echo "\n🔧 Marquage de toutes les invitations en attente comme expirées...\n";
        $count = 0;
        
        foreach ($pendingInvitations as $invitation) {
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus(InvitationStatus::EXPIRED->value);
            $invitation->setUpdatedAt(new \DateTime());
            $count++;
            
            echo "   ✅ Invitation ID {$invitation->getId()} ({$invitation->getName()}) marquée comme expirée\n";
        }
        
        if ($count > 0) {
            $entityManager->flush();
            echo "\n🎉 {$count} invitation(s) marquée(s) comme expirée(s) avec succès !\n";
        }
        
    } else {
        // Marquer une invitation spécifique
        $invitationId = (int) $input;
        $invitation = $invitationRepo->find($invitationId);
        
        if (!$invitation) {
            echo "❌ Invitation ID {$invitationId} non trouvée\n";
            exit;
        }
        
        if ($invitation->getStatus() !== 'pending') {
            echo "⚠️ L'invitation ID {$invitationId} n'est pas en attente (statut: {$invitation->getStatus()})\n";
            exit;
        }
        
        echo "\n🔧 Changement du statut de l'invitation ID {$invitationId}...\n";
        $oldStatus = $invitation->getStatus();
        $invitation->setStatus(InvitationStatus::EXPIRED->value);
        $invitation->setUpdatedAt(new \DateTime());
        
        // Sauvegarder
        $entityManager->flush();
        echo "   ✅ Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
        echo "   ✅ Invitation marquée comme expirée avec succès !\n";
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
    
    echo "\n✅ Opération terminée avec succès !\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📋 Trace: " . $e->getTraceAsString() . "\n";
}
