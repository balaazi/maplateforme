<?php
/**
 * Script pour vérifier l'événement ID 28 et ses participations
 * Usage: php verifier_evenement_28.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Vérification de l'Événement ID 28 - EventHub\n";
echo "================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'événement ID 28
    $eventRepo = $container->get('doctrine')->getRepository('App\Entity\Event');
    $event = $eventRepo->find(28);
    
    if (!$event) {
        echo "❌ Événement ID 28 non trouvé\n";
        exit;
    }
    
    echo "📋 Événement ID 28 trouvé:\n";
    echo "   - ID: {$event->getId()}\n";
    echo "   - Titre: {$event->getTitle()}\n";
    echo "   - Date: {$event->getDateHeure()->format('d/m/Y H:i')}\n";
    echo "   - Organisateur: {$event->getOrganizer()->getEmail()}\n\n";
    
    // Vérifier la date de l'événement
    $today = new \DateTime('today');
    $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
    
    echo "🔍 Vérification de la date:\n";
    echo "   - Date aujourd'hui: {$today->format('d/m/Y')}\n";
    echo "   - Date événement: {$eventDate->format('d/m/Y')}\n";
    echo "   - Événement dans le futur: " . ($eventDate > $today ? 'OUI' : 'NON') . "\n";
    
    if ($eventDate > $today) {
        echo "   - ⚠️ PROBLÈME: L'événement est dans le futur !\n";
        echo "   - La gestion de présence n'est disponible que le jour de l'événement\n";
    } else {
        echo "   - ✅ Date OK pour la gestion de présence\n";
    }
    echo "\n";
    
    // Récupérer les participations pour cet événement
    $participationRepo = $container->get('doctrine')->getRepository('App\Entity\Participation');
    $participations = $participationRepo->findBy(['event' => $event]);
    
    echo "📋 Participations trouvées: " . count($participations) . "\n";
    if (empty($participations)) {
        echo "   - Aucune participation trouvée\n";
    } else {
        foreach ($participations as $participation) {
            $user = $participation->getUser();
            
            echo "   - ID Participation: {$participation->getId()}\n";
            echo "     Utilisateur: {$user->getEmail()}\n";
            echo "     Statut invitation: {$participation->getInvitationStatus()}\n";
            echo "     Présence actuelle: " . ($participation->isPresent() ? 'PRÉSENT' : 'ABSENT') . "\n";
            echo "     Présence validée: " . ($participation->isPresenceValidated() ? 'OUI' : 'NON') . "\n\n";
        }
    }
    
    // Vérifier les invitations
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $invitations = $invitationRepo->findBy(['event' => $event]);
    
    echo "📧 Invitations trouvées: " . count($invitations) . "\n";
    if (empty($invitations)) {
        echo "   - Aucune invitation trouvée\n";
    } else {
        foreach ($invitations as $invitation) {
            echo "   - ID: {$invitation->getId()}\n";
            echo "     Email: {$invitation->getEmail()}\n";
            echo "     Nom: {$invitation->getName()}\n";
            echo "     Statut: {$invitation->getStatus()}\n";
            echo "     Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n";
            echo "     Mise à jour: " . ($invitation->getUpdatedAt() ? $invitation->getUpdatedAt()->format('d/m/Y H:i:s') : 'Jamais') . "\n\n";
        }
    }
    
    // Vérifier les permissions de l'utilisateur actuel
    echo "🔐 Vérification des permissions:\n";
    
    // Simuler un utilisateur organisateur (pour le test)
    $organizer = $event->getOrganizer();
    echo "   - Organisateur de l'événement: {$organizer->getEmail()}\n";
    echo "   - Rôles: " . implode(', ', $organizer->getRoles()) . "\n";
    
    // Vérifier si l'utilisateur peut modifier la présence
    $canModify = $event->getOrganizer() === $organizer;
    echo "   - Peut modifier la présence: " . ($canModify ? 'OUI' : 'NON') . "\n";
    
    echo "\n🔍 Analyse du problème:\n";
    
    if ($eventDate > $today) {
        echo "   ❌ PROBLÈME PRINCIPAL: L'événement est programmé pour le {$eventDate->format('d/m/Y')}\n";
        echo "   ❌ La gestion de présence n'est disponible que le jour de l'événement\n";
        echo "   ❌ C'est pourquoi le bouton 'ABSENT' ne fonctionne pas\n\n";
        
        echo "💡 SOLUTIONS:\n";
        echo "   1. Attendre le jour de l'événement\n";
        echo "   2. Modifier la date de l'événement pour aujourd'hui\n";
        echo "   3. Désactiver temporairement la vérification de date\n";
        
    } elseif (empty($participations)) {
        echo "   ❌ PROBLÈME PRINCIPAL: Aucune participation trouvée pour cet événement\n";
        echo "   ❌ Les boutons de présence ne peuvent pas fonctionner sans participation\n\n";
        
        echo "💡 SOLUTIONS:\n";
        echo "   1. Créer des participations pour les participants\n";
        echo "   2. Vérifier que les invitations sont acceptées\n";
        echo "   3. Synchroniser les invitations et participations\n";
        
    } else {
        echo "   ✅ L'événement est aujourd'hui ou dans le passé\n";
        echo "   ✅ Des participations existent\n";
        echo "   ✅ La gestion de présence devrait fonctionner\n";
        
        // Vérifier d'autres problèmes potentiels
        foreach ($participations as $participation) {
            if ($participation->isPresenceValidated()) {
                echo "   ⚠️ Participation ID {$participation->getId()} déjà validée\n";
                echo "   ⚠️ Utilisez le bouton 'Modifier' pour changer le statut\n";
            }
        }
    }
    
    echo "\n✅ Diagnostic terminé !\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
