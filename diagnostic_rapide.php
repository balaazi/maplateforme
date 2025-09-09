<?php
/**
 * Diagnostic rapide des invitations
 * Usage: php diagnostic_rapide.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Diagnostic Rapide des Invitations - EventHub\n";
echo "==============================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer les repositories
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $eventRepo = $container->get('doctrine')->getRepository('App\Entity\Event');
    
    echo "🔍 Vérification des événements 'Formation js'...\n\n";
    
    // Chercher l'événement "Formation js"
    $events = $eventRepo->createQueryBuilder('e')
        ->where('e.title LIKE :title')
        ->setParameter('title', '%Formation js%')
        ->getQuery()
        ->getResult();
    
    if (!empty($events)) {
        echo "✅ Événements 'Formation js' trouvés:\n";
        foreach ($events as $event) {
            echo "   - ID {$event->getId()}: {$event->getTitle()}\n";
            echo "     Date: {$event->getDateHeure()->format('d/m/Y H:i')}\n";
            echo "     Durée: {$event->getDuree()} minutes\n\n";
            
            // Vérifier les invitations pour cet événement
            $invitations = $invitationRepo->createQueryBuilder('i')
                ->where('i.event = :event')
                ->setParameter('event', $event)
                ->orderBy('i.updatedAt', 'DESC')
                ->getQuery()
                ->getResult();
            
            if (!empty($invitations)) {
                echo "   📧 Invitations pour cet événement:\n";
                foreach ($invitations as $invitation) {
                    echo "     - ID {$invitation->getId()}: {$invitation->getEmail()}\n";
                    echo "       Statut: '{$invitation->getStatus()}'\n";
                    echo "       Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n";
                    echo "       Mise à jour: {$invitation->getUpdatedAt()->format('d/m/Y H:i:s')}\n\n";
                }
            } else {
                echo "   ⚠️ Aucune invitation trouvée pour cet événement\n\n";
            }
        }
    } else {
        echo "❌ Aucun événement 'Formation js' trouvé\n\n";
    }
    
    echo "🔍 Vérification de tous les statuts d'invitation...\n\n";
    
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
    
    echo "\n🔍 Vérification des invitations récentes...\n\n";
    
    // Vérifier les invitations récentes
    $recentInvitations = $invitationRepo->createQueryBuilder('i')
        ->join('i.event', 'e')
        ->orderBy('i.updatedAt', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();
    
    echo "   Dernières invitations mises à jour:\n";
    foreach ($recentInvitations as $invitation) {
        $event = $invitation->getEvent();
        echo "   - ID {$invitation->getId()}: {$event->getTitle()}\n";
        echo "     Email: {$invitation->getEmail()}\n";
        echo "     Statut: '{$invitation->getStatus()}'\n";
        echo "     Mise à jour: {$invitation->getUpdatedAt()->format('d/m/Y H:i:s')}\n\n";
    }
    
    echo "🎯 RÉSUMÉ:\n";
    echo "   - Événements 'Formation js': " . count($events) . "\n";
    echo "   - Total invitations: " . array_sum(array_column($statuses, 'count')) . "\n";
    echo "   - Statuts différents: " . count($statuses) . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
