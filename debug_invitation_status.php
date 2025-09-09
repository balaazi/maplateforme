<?php
/**
 * Script de debug des statuts d'invitation
 * Usage: php debug_invitation_status.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Debug des Statuts d'Invitation - EventHub\n";
echo "============================================\n\n";

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
    $userRepo = $container->get('doctrine')->getRepository('App\Entity\User');
    $eventRepo = $container->get('doctrine')->getRepository('App\Entity\Event');
    
    echo "🔍 Analyse détaillée des statuts...\n\n";
    
    // 1. Vérifier toutes les invitations
    echo "1. 📧 INVITATIONS:\n";
    $invitations = $invitationRepo->findAll();
    foreach ($invitations as $invitation) {
        $eventTitle = $invitation->getEvent() ? $invitation->getEvent()->getTitle() : 'N/A';
        echo "   - ID {$invitation->getId()}: {$invitation->getEmail()} | Statut: '{$invitation->getStatus()}' | Événement: {$eventTitle}\n";
    }
    
    echo "\n2. 👥 PARTICIPATIONS:\n";
    $participations = $participationRepo->findAll();
    foreach ($participations as $participation) {
        $userEmail = $participation->getUser() ? $participation->getUser()->getEmail() : 'N/A';
        $eventTitle = $participation->getEvent() ? $participation->getEvent()->getTitle() : 'N/A';
        echo "   - ID {$participation->getId()}: {$userEmail} | Statut: '{$participation->getInvitationStatus()}' | Événement: {$eventTitle}\n";
    }
    
    echo "\n3. 🔗 CORRESPONDANCES:\n";
    foreach ($invitations as $invitation) {
        $event = $invitation->getEvent();
        if ($event) {
            $user = $userRepo->findOneBy(['email' => $invitation->getEmail()]);
            if ($user) {
                $participation = $participationRepo->findOneBy([
                    'user' => $user,
                    'event' => $event
                ]);
                
                if ($participation) {
                    $statusMatch = $invitation->getStatus() === $participation->getInvitationStatus();
                    $statusIcon = $statusMatch ? '✅' : '❌';
                    echo "   {$statusIcon} Invitation {$invitation->getId()} ({$invitation->getStatus()}) ↔ Participation {$participation->getId()} ({$participation->getInvitationStatus()}) | Événement: {$event->getTitle()}\n";
                } else {
                    echo "   ⚠️  Invitation {$invitation->getId()} ({$invitation->getStatus()}) ↔ Aucune participation trouvée | Événement: {$event->getTitle()}\n";
                }
            } else {
                echo "   ❓ Invitation {$invitation->getId()} ({$invitation->getStatus()}) ↔ Utilisateur non trouvé pour {$invitation->getEmail()}\n";
            }
        }
    }
    
    echo "\n4. 🚨 PROBLÈMES DÉTECTÉS:\n";
    
    // Vérifier les statuts invalides
    $validStatuses = ['pending', 'accepted', 'declined', 'expired', 'conflict'];
    
    $invalidInvitations = array_filter($invitations, function($inv) use ($validStatuses) {
        return !in_array($inv->getStatus(), $validStatuses);
    });
    
    if (!empty($invalidInvitations)) {
        echo "   - Invitations avec statut invalide:\n";
        foreach ($invalidInvitations as $inv) {
            echo "     * ID {$inv->getId()}: '{$inv->getStatus()}' (email: {$inv->getEmail()})\n";
        }
    }
    
    $invalidParticipations = array_filter($participations, function($part) use ($validStatuses) {
        return !in_array($part->getInvitationStatus(), $validStatuses);
    });
    
    if (!empty($invalidParticipations)) {
        echo "   - Participations avec statut invalide:\n";
        foreach ($invalidParticipations as $part) {
            $userEmail = $part->getUser() ? $part->getUser()->getEmail() : 'N/A';
            echo "     * ID {$part->getId()}: '{$part->getInvitationStatus()}' (utilisateur: {$userEmail})\n";
        }
    }
    
    // Vérifier les incohérences
    $inconsistencies = 0;
    foreach ($invitations as $invitation) {
        $event = $invitation->getEvent();
        if ($event) {
            $user = $userRepo->findOneBy(['email' => $invitation->getEmail()]);
            if ($user) {
                $participation = $participationRepo->findOneBy([
                    'user' => $user,
                    'event' => $event
                ]);
                
                if ($participation && $invitation->getStatus() !== $participation->getInvitationStatus()) {
                    $inconsistencies++;
                    echo "   - Incohérence: Invitation {$invitation->getId()} ({$invitation->getStatus()}) ≠ Participation {$participation->getId()} ({$participation->getInvitationStatus()})\n";
                }
            }
        }
    }
    
    if ($inconsistencies === 0) {
        echo "   ✅ Aucune incohérence détectée\n";
    }
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "   - Invitations: " . count($invitations) . "\n";
    echo "   - Participations: " . count($participations) . "\n";
    echo "   - Statuts invalides: " . (count($invalidInvitations) + count($invalidParticipations)) . "\n";
    echo "   - Incohérences: {$inconsistencies}\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
