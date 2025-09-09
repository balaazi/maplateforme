<?php
/**
 * Script de vérification des conflits d'horaires
 * Usage: php verifier_conflit.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Vérification des Conflits d'Horaires - EventHub\n";
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
    
    echo "🔍 Vérification des invitations avec statut CONFLICT...\n\n";
    
    // Vérifier les invitations avec statut CONFLICT
    $conflictInvitations = $invitationRepo->createQueryBuilder('i')
        ->join('i.event', 'e')
        ->where('i.status = :status')
        ->setParameter('status', 'conflict')
        ->getQuery()
        ->getResult();
    
    if (!empty($conflictInvitations)) {
        echo "✅ Invitations avec statut CONFLICT trouvées:\n";
        foreach ($conflictInvitations as $invitation) {
            $event = $invitation->getEvent();
            $startTime = $event->getDateHeure()->format('H:i');
            $endTime = $event->getDateHeure()->add(new \DateInterval('PT' . $event->getDuree() . 'M'))->format('H:i');
            
            echo "   - ID {$invitation->getId()}: {$event->getTitle()}\n";
            echo "     Email: {$invitation->getEmail()}\n";
            echo "     Heures: {$startTime}-{$endTime}\n";
            echo "     Statut: {$invitation->getStatus()}\n";
            echo "     Mis à jour: {$invitation->getUpdatedAt()->format('H:i:s')}\n\n";
        }
    } else {
        echo "ℹ️ Aucune invitation avec statut CONFLICT trouvée\n";
    }
    
    echo "🔍 Vérification des participations avec statut CONFLICT...\n\n";
    
    // Vérifier les participations avec statut CONFLICT
    $conflictParticipations = $participationRepo->createQueryBuilder('p')
        ->join('p.event', 'e')
        ->join('p.user', 'u')
        ->where('p.invitationStatus = :status')
        ->setParameter('status', 'conflict')
        ->getQuery()
        ->getResult();
    
    if (!empty($conflictParticipations)) {
        echo "✅ Participations avec statut CONFLICT trouvées:\n";
        foreach ($conflictParticipations as $participation) {
            $event = $participation->getEvent();
            $user = $participation->getUser();
            $startTime = $event->getDateHeure()->format('H:i');
            $endTime = $event->getDateHeure()->add(new \DateInterval('PT' . $event->getDuree() . 'M'))->format('H:i');
            
            echo "   - ID {$participation->getId()}: {$event->getTitle()}\n";
            echo "     Utilisateur: {$user->getEmail()}\n";
            echo "     Heures: {$startTime}-{$endTime}\n";
            echo "     Statut: {$participation->getInvitationStatus()}\n\n";
        }
    } else {
        echo "ℹ️ Aucune participation avec statut CONFLICT trouvée\n";
    }
    
    echo "🔍 Vérification de la cohérence invitation/participation...\n\n";
    
    // Vérifier la cohérence
    $inconsistencies = 0;
    $invitations = $invitationRepo->findAll();
    
    foreach ($invitations as $invitation) {
        $event = $invitation->getEvent();
        $user = $entityManager->getRepository('App\Entity\User')->findOneBy(['email' => $invitation->getEmail()]);
        
        if ($user) {
            $participation = $participationRepo->findOneBy([
                'user' => $user,
                'event' => $event
            ]);
            
            if ($participation) {
                if ($invitation->getStatus() !== $participation->getInvitationStatus()) {
                    $inconsistencies++;
                    echo "   ❌ Incohérence: Invitation {$invitation->getId()} ({$invitation->getStatus()}) ≠ Participation {$participation->getId()} ({$participation->getInvitationStatus()})\n";
                } else {
                    echo "   ✅ Cohérence: Invitation {$invitation->getId()} ({$invitation->getStatus()}) = Participation {$participation->getId()} ({$participation->getInvitationStatus()})\n";
                }
            } else {
                echo "   ⚠️ Invitation {$invitation->getId()} ({$invitation->getStatus()}) ↔ Aucune participation trouvée\n";
            }
        }
    }
    
    if ($inconsistencies === 0) {
        echo "   🎉 Toutes les invitations et participations sont cohérentes !\n";
    } else {
        echo "   ⚠️ {$inconsistencies} incohérence(s) détectée(s)\n";
    }
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "   - Invitations CONFLICT: " . count($conflictInvitations) . "\n";
    echo "   - Participations CONFLICT: " . count($conflictParticipations) . "\n";
    echo "   - Incohérences: {$inconsistencies}\n";
    
    if (count($conflictInvitations) > 0) {
        echo "\n💡 Le système de détection des conflits fonctionne parfaitement !\n";
        echo "   L'interface devrait afficher le statut CONFLICT après rafraîchissement.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
