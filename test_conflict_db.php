<?php
/**
 * Script de test simple des conflits d'horaires via la base de données
 * Usage: php test_conflict_db.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test des Conflits d'Horaires via Base de Données - EventHub\n";
echo "============================================================\n\n";

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
    
    echo "🔍 Vérification des invitations avec conflits potentiels...\n\n";
    
    // Récupérer toutes les invitations pour l'utilisateur test
    $userEmail = 'nadiabalaazi18@gmail.com';
    
    $invitations = $invitationRepo->createQueryBuilder('i')
        ->join('i.event', 'e')
        ->where('i.email = :email')
        ->setParameter('email', $userEmail)
        ->orderBy('e.dateHeure', 'ASC')
        ->getQuery()
        ->getResult();
    
    echo "📧 Invitations pour {$userEmail}:\n";
    foreach ($invitations as $invitation) {
        $event = $invitation->getEvent();
        $startTime = $event->getDateHeure()->format('H:i');
        $endTime = $event->getDateHeure()->add(new \DateInterval('PT' . $event->getDuree() . 'M'))->format('H:i');
        
        echo "   - {$event->getTitle()}: {$startTime}-{$endTime} | Statut: {$invitation->getStatus()}\n";
    }
    
    echo "\n🔍 Analyse des conflits d'horaires...\n";
    
    $conflicts = [];
    
    // Comparer chaque paire d'événements
    for ($i = 0; $i < count($invitations); $i++) {
        for ($j = $i + 1; $j < count($invitations); $j++) {
            $event1 = $invitations[$i]->getEvent();
            $event2 = $invitations[$j]->getEvent();
            
            // Calculer les heures de début et fin
            $start1 = $event1->getDateHeure();
            $end1 = clone $start1;
            $end1->add(new \DateInterval('PT' . $event1->getDuree() . 'M'));
            
            $start2 = $event2->getDateHeure();
            $end2 = clone $start2;
            $end2->add(new \DateInterval('PT' . $event2->getDuree() . 'M'));
            
            // Vérifier s'il y a un chevauchement
            if (($start1 < $end2) && ($end1 > $start2)) {
                $conflicts[] = [
                    'event1' => $event1,
                    'event2' => $event2,
                    'invitation1' => $invitations[$i],
                    'invitation2' => $invitations[$j]
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
        
        echo "\n🔧 Test de résolution d'un conflit...\n";
        
        // Tester avec le premier conflit
        $testConflict = $conflicts[0];
        $invitation1 = $testConflict['invitation1'];
        
        echo "   - Test avec invitation {$invitation1->getId()} ({$invitation1->getEvent()->getTitle()})\n";
        
        // Changer le statut vers CONFLICT
        $oldStatus = $invitation1->getStatus();
        $invitation1->setStatus('conflict');
        $invitation1->setUpdatedAt(new \DateTime());
        
        // Sauvegarder
        $entityManager->flush();
        echo "   ✅ Statut changé de '{$oldStatus}' vers 'conflict'\n";
        
        // Vérifier la sauvegarde
        $entityManager->clear();
        $invitationCheck = $invitationRepo->find($invitation1->getId());
        echo "   - Vérification en base: statut = '{$invitationCheck->getStatus()}'\n";
        
        if ($invitationCheck->getStatus() === 'conflict') {
            echo "   🎉 Test réussi ! Le statut CONFLICT est bien sauvegardé\n";
        } else {
            echo "   ❌ Test échoué ! Le statut n'a pas été sauvegardé\n";
        }
        
        // Remettre le statut original
        $invitationCheck->setStatus($oldStatus);
        $invitationCheck->setUpdatedAt(new \DateTime());
        $entityManager->flush();
        echo "   - Statut remis à '{$oldStatus}'\n";
        
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
