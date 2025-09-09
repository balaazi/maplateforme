<?php
/**
 * Script pour forcer le changement de statut de présence
 * Usage: php forcer_changement_presence.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔧 Forçage du Changement de Statut de Présence - EventHub\n";
echo "=========================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'événement "Réunion" (ID 28)
    $eventRepo = $container->get('doctrine')->getRepository('App\Entity\Event');
    $event = $eventRepo->find(28);
    
    if (!$event) {
        echo "❌ Événement 'Réunion' non trouvé\n";
        exit;
    }
    
    echo "📋 Événement 'Réunion' trouvé:\n";
    echo "   - ID: {$event->getId()}\n";
    echo "   - Titre: {$event->getTitle()}\n";
    echo "   - Date: {$event->getDateHeure()->format('d/m/Y H:i')}\n\n";
    
    // Récupérer les participations
    $participationRepo = $container->get('doctrine')->getRepository('App\Entity\Participation');
    $participations = $participationRepo->findBy(['event' => $event]);
    
    echo "📋 Participations trouvées:\n";
    foreach ($participations as $participation) {
        $user = $participation->getUser();
        
        echo "   - ID Participation: {$participation->getId()}\n";
        echo "     Utilisateur: {$user->getEmail()}\n";
        echo "     Statut invitation: {$participation->getInvitationStatus()}\n";
        echo "     Présence actuelle: " . ($participation->isPresent() ? 'PRÉSENT' : 'ABSENT') . "\n";
        echo "     Présence validée: " . ($participation->isPresenceValidated() ? 'OUI' : 'NON') . "\n\n";
    }
    
    // Demander quelle participation modifier
    echo "🔧 Sélection de la participation à modifier:\n";
    echo "   Entrez l'ID de la participation (ou 'all' pour toutes): ";
    $input = trim(fgets(STDIN));
    
    if ($input === 'all') {
        // Modifier toutes les participations
        echo "\n🔧 Modification de toutes les participations...\n";
        
        foreach ($participations as $participation) {
            $oldStatus = $participation->isPresent() ? 'PRÉSENT' : 'ABSENT';
            
            // Inverser le statut de présence
            $newStatus = !$participation->isPresent();
            $participation->setIsPresent($newStatus);
            $participation->setPresenceValidated(false); // Réinitialiser la validation
            
            $newStatusText = $newStatus ? 'PRÉSENT' : 'ABSENT';
            echo "   - Participation ID {$participation->getId()}: {$oldStatus} → {$newStatusText}\n";
        }
        
        // Sauvegarder
        $entityManager->flush();
        echo "   ✅ Toutes les participations modifiées\n";
        
    } else {
        // Modifier une participation spécifique
        $participationId = (int) $input;
        $participation = $participationRepo->find($participationId);
        
        if (!$participation) {
            echo "❌ Participation ID {$participationId} non trouvée\n";
            exit;
        }
        
        if ($participation->getEvent()->getId() !== $event->getId()) {
            echo "❌ Cette participation n'appartient pas à l'événement 'Réunion'\n";
            exit;
        }
        
        echo "\n🔧 Modification de la participation ID {$participationId}...\n";
        $oldStatus = $participation->isPresent() ? 'PRÉSENT' : 'ABSENT';
        
        // Inverser le statut de présence
        $newStatus = !$participation->isPresent();
        $participation->setIsPresent($newStatus);
        $participation->setPresenceValidated(false); // Réinitialiser la validation
        
        // Sauvegarder
        $entityManager->flush();
        
        $newStatusText = $newStatus ? 'PRÉSENT' : 'ABSENT';
        echo "   ✅ Statut changé de '{$oldStatus}' vers '{$newStatusText}'\n";
        echo "   ✅ Validation réinitialisée\n";
    }
    
    echo "\n🔍 Vérification finale...\n";
    
    // Vérifier les statuts après modification
    $participationsUpdated = $participationRepo->findBy(['event' => $event]);
    
    foreach ($participationsUpdated as $participation) {
        $user = $participation->getUser();
        
        echo "   - ID Participation: {$participation->getId()}\n";
        echo "     Utilisateur: {$user->getEmail()}\n";
        echo "     Présence actuelle: " . ($participation->isPresent() ? 'PRÉSENT' : 'ABSENT') . "\n";
        echo "     Présence validée: " . ($participation->isPresenceValidated() ? 'OUI' : 'NON') . "\n\n";
    }
    
    echo "✅ Opération terminée !\n";
    echo "💡 Maintenant, testez les boutons de présence dans l'interface\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}










