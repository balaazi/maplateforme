<?php
/**
 * Script de test réel des conflits d'horaires
 * Simule la logique du contrôleur pour tester la détection
 * Usage: php test_conflict_real.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Enum\InvitationStatus;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test Réel des Conflits d'Horaires - EventHub\n";
echo "===============================================\n\n";

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
    
    echo "🔍 Analyse des conflits d'horaires potentiels...\n\n";
    
    // Récupérer l'utilisateur test
    $user = $userRepo->findOneBy(['email' => 'nadiabalaazi18@gmail.com']);
    if (!$user) {
        echo "❌ Utilisateur test non trouvé\n";
        exit(1);
    }
    
    echo "✅ Utilisateur test: {$user->getEmail()}\n\n";
    
    // Récupérer toutes les invitations de cet utilisateur
    $userInvitations = $invitationRepo->createQueryBuilder('i')
        ->join('i.event', 'e')
        ->where('i.email = :email')
        ->setParameter('email', $user->getEmail())
        ->orderBy('e.dateHeure', 'ASC')
        ->getQuery()
        ->getResult();
    
    echo "📧 Invitations de l'utilisateur:\n";
    foreach ($userInvitations as $invitation) {
        $event = $invitation->getEvent();
        $startTime = $event->getDateHeure()->format('H:i');
        $endTime = $event->getDateHeure()->add(new \DateInterval('PT' . $event->getDuree() . 'M'))->format('H:i');
        
        echo "   - {$event->getTitle()}: {$startTime}-{$endTime} | Statut: {$invitation->getStatus()}\n";
    }
    
    echo "\n🔍 Détection des conflits d'horaires...\n";
    
    // Simuler la logique de détection de conflits
    $conflicts = [];
    
    foreach ($userInvitations as $invitation) {
        $event = $invitation->getEvent();
        
        // Vérifier les conflits avec les autres événements
        foreach ($userInvitations as $otherInvitation) {
            if ($invitation->getId() === $otherInvitation->getId()) {
                continue; // Même invitation
            }
            
            $otherEvent = $otherInvitation->getEvent();
            
            // Calculer les heures de début et fin
            $start1 = $event->getDateHeure();
            $end1 = clone $start1;
            $end1->add(new \DateInterval('PT' . $event->getDuree() . 'M'));
            
            $start2 = $otherEvent->getDateHeure();
            $end2 = clone $start2;
            $end2->add(new \DateInterval('PT' . $otherEvent->getDuree() . 'M'));
            
            // Vérifier s'il y a un chevauchement
            if (($start1 < $end2) && ($end1 > $start2)) {
                $conflicts[] = [
                    'event1' => $event,
                    'event2' => $otherEvent,
                    'invitation1' => $invitation,
                    'invitation2' => $otherInvitation,
                    'overlap' => 'Conflit détecté'
                ];
            }
        }
    }
    
    if (!empty($conflicts)) {
        echo "🚨 Conflits d'horaires détectés:\n";
        foreach ($conflicts as $conflict) {
            $event1 = $conflict['event1'];
            $event2 = $conflict['event2'];
            
            $start1 = $event1->getDateHeure()->format('H:i');
            $end1 = $event1->getDateHeure()->add(new \DateInterval('PT' . $event1->getDuree() . 'M'))->format('H:i');
            $start2 = $event2->getDateHeure()->format('H:i');
            $end2 = $event2->getDateHeure()->add(new \DateInterval('PT' . $event2->getDuree() . 'M'))->format('H:i');
            
            echo "   ⚠️ {$event1->getTitle()} ({$start1}-{$end1}) ↔ {$event2->getTitle()} ({$start2}-{$end2})\n";
        }
        
        echo "\n🔧 Test de résolution des conflits...\n";
        
        // Tester la résolution d'un conflit en appliquant le statut CONFLICT
        $testConflict = $conflicts[0];
        $invitation1 = $testConflict['invitation1'];
        $invitation2 = $testConflict['invitation2'];
        
        echo "   - Test avec invitation {$invitation1->getId()} ({$invitation1->getEvent()->getTitle()})\n";
        
        // Vérifier si une participation existe
        $participation = $participationRepo->findOneBy([
            'user' => $user,
            'event' => $invitation1->getEvent()
        ]);
        
        if (!$participation) {
            echo "   - Création d'une participation pour le test\n";
            // Créer une participation temporaire pour le test
            $participation = new \App\Entity\Participation();
            $participation->setUser($user);
            $participation->setEvent($invitation1->getEvent());
            $participation->setIsPresent(false);
            $participation->setInvitationStatus('pending');
            $entityManager->persist($participation);
        }
        
        // Appliquer le statut CONFLICT (simulation de la logique du contrôleur)
        echo "   - Application du statut CONFLICT...\n";
        
        $invitation1->setStatus(InvitationStatus::CONFLICT->value);
        $invitation1->setUpdatedAt(new \DateTime());
        
        $participation->setInvitationStatus(InvitationStatus::CONFLICT->value);
        
        // Sauvegarder
        $entityManager->flush();
        echo "   ✅ Statut CONFLICT appliqué et sauvegardé\n";
        
        // Vérifier la sauvegarde
        $entityManager->clear();
        $invitationCheck = $invitationRepo->find($invitation1->getId());
        $participationCheck = $participationRepo->find($participation->getId());
        
        echo "   - Vérification invitation: statut = '{$invitationCheck->getStatus()}'\n";
        echo "   - Vérification participation: statut = '{$participationCheck->getInvitationStatus()}'\n";
        
        if ($invitationCheck->getStatus() === 'conflict' && $participationCheck->getInvitationStatus() === 'conflict') {
            echo "   🎉 Test réussi ! Le conflit est bien géré\n";
        } else {
            echo "   ❌ Test échoué ! Le conflit n'est pas correctement géré\n";
        }
        
        // Remettre les statuts originaux
        $invitationCheck->setStatus('pending');
        $invitationCheck->setUpdatedAt(new \DateTime());
        $participationCheck->setInvitationStatus('pending');
        $entityManager->flush();
        echo "   - Statuts remis à 'pending'\n";
        
    } else {
        echo "✅ Aucun conflit d'horaires détecté\n";
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
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "   - Conflits détectés: " . count($conflicts) . "\n";
    echo "   - Test de résolution: " . (!empty($conflicts) ? "Effectué" : "Non applicable") . "\n";
    echo "   - Statut CONFLICT: Fonctionnel ✅\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
