<?php
/**
 * Script de test pour la détection des conflits d'horaires
 * Usage: php test_conflict_detection.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test de Détection des Conflits d'Horaires - EventHub\n";
echo "======================================================\n\n";

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
    
    echo "🔍 Vérification des invitations avec statut CONFLICT...\n\n";
    
    // Vérifier s'il y a des invitations avec le statut CONFLICT
    $conflictInvitations = $invitationRepo->createQueryBuilder('i')
        ->where('i.status = :status')
        ->setParameter('status', 'conflict')
        ->getQuery()
        ->getResult();
    
    if (!empty($conflictInvitations)) {
        echo "✅ Invitations avec statut CONFLICT trouvées:\n";
        foreach ($conflictInvitations as $invitation) {
            $eventTitle = $invitation->getEvent() ? $invitation->getEvent()->getTitle() : 'N/A';
            echo "   - ID {$invitation->getId()}: {$invitation->getEmail()} | Événement: {$eventTitle}\n";
        }
    } else {
        echo "ℹ️ Aucune invitation avec statut CONFLICT trouvée\n";
    }
    
    echo "\n🔍 Vérification des participations avec statut CONFLICT...\n";
    
    // Vérifier s'il y a des participations avec le statut CONFLICT
    $conflictParticipations = $participationRepo->createQueryBuilder('p')
        ->where('p.invitationStatus = :status')
        ->setParameter('status', 'conflict')
        ->getQuery()
        ->getResult();
    
    if (!empty($conflictParticipations)) {
        echo "✅ Participations avec statut CONFLICT trouvées:\n";
        foreach ($conflictParticipations as $participation) {
            $userEmail = $participation->getUser() ? $participation->getUser()->getEmail() : 'N/A';
            $eventTitle = $participation->getEvent() ? $participation->getEvent()->getTitle() : 'N/A';
            echo "   - ID {$participation->getId()}: {$userEmail} | Événement: {$eventTitle}\n";
        }
    } else {
        echo "ℹ️ Aucune participation avec statut CONFLICT trouvée\n";
    }
    
    echo "\n🔍 Test du service de détection de conflits...\n";
    
    // Récupérer le service de détection de conflits
    $scheduleConflictService = $container->get('App\Service\ScheduleConflictService');
    
    // Récupérer un utilisateur et un événement pour tester
    $user = $userRepo->findOneBy(['email' => 'nadiabalaazi18@gmail.com']);
    $event = $eventRepo->findOneBy(['title' => 'Séminaire']);
    
    if ($user && $event) {
        echo "✅ Test avec utilisateur: {$user->getEmail()} et événement: {$event->getTitle()}\n";
        
        try {
            $conflict = $scheduleConflictService->checkScheduleConflict($user, $event);
            
            if ($conflict) {
                echo "✅ Conflit d'horaires détecté !\n";
                echo "   - Événement en conflit: {$conflict['conflictingEvent']->getTitle()}\n";
                echo "   - Message: {$conflict['message']}\n";
            } else {
                echo "ℹ️ Aucun conflit d'horaires détecté\n";
            }
        } catch (\Exception $e) {
            echo "❌ Erreur lors de la vérification des conflits: {$e->getMessage()}\n";
        }
    } else {
        echo "⚠️ Impossible de tester - utilisateur ou événement manquant\n";
    }
    
    echo "\n🔍 Vérification de la cohérence des statuts...\n";
    
    // Vérifier la cohérence entre invitations et participations
    $inconsistencies = 0;
    $invitations = $invitationRepo->findAll();
    
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
                    echo "   ❌ Incohérence: Invitation {$invitation->getId()} ({$invitation->getStatus()}) ≠ Participation {$participation->getId()} ({$participation->getInvitationStatus()})\n";
                } else {
                    echo "   ✅ Cohérence: Invitation {$invitation->getId()} ({$invitation->getStatus()}) = Participation {$participation->getId()} ({$participation->getInvitationStatus()})\n";
                }
            }
        }
    }
    
    if ($inconsistencies === 0) {
        echo "   🎉 Toutes les invitations et participations sont cohérentes !\n";
    } else {
        echo "   ⚠️ {$inconsistencies} incohérence(s) détectée(s)\n";
    }
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "   - Invitations totales: " . count($invitations) . "\n";
    echo "   - Invitations CONFLICT: " . count($conflictInvitations) . "\n";
    echo "   - Participations CONFLICT: " . count($conflictParticipations) . "\n";
    echo "   - Incohérences: {$inconsistencies}\n";
    
    echo "\n💡 Pour tester un conflit d'horaires:\n";
    echo "   1. Créer deux événements à la même date/heure\n";
    echo "   2. Inviter le même utilisateur aux deux événements\n";
    echo "   3. Accepter la première invitation\n";
    echo "   4. Essayer d'accepter la deuxième (devrait créer un conflit)\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
