<?php
/**
 * Script pour diagnostiquer le problème de changement de statut de présence
 * Usage: php diagnostic_presence_reunion.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Diagnostic du Problème de Présence - EventHub\n";
echo "================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'événement "Réunion"
    $eventRepo = $container->get('doctrine')->getRepository('App\Entity\Event');
    $event = $eventRepo->findOneBy(['title' => 'Réunion']);
    
    if (!$event) {
        echo "❌ Événement 'Réunion' non trouvé\n";
        exit;
    }
    
    echo "📋 Événement 'Réunion' trouvé:\n";
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
    
    echo "📋 Participations trouvées:\n";
    foreach ($participations as $participation) {
        $user = $participation->getUser();
        $invitation = $participation->getInvitation();
        
        echo "   - ID Participation: {$participation->getId()}\n";
        echo "     Utilisateur: {$user->getEmail()}\n";
        echo "     Statut invitation: {$participation->getInvitationStatus()}\n";
        echo "     Présence actuelle: " . ($participation->isPresent() ? 'PRÉSENT' : 'ABSENT') . "\n";
        echo "     Présence validée: " . ($participation->isPresenceValidated() ? 'OUI' : 'NON') . "\n";
        
        if ($invitation) {
            echo "     Invitation ID: {$invitation->getId()}\n";
            echo "     Statut invitation: {$invitation->getStatus()}\n";
        }
        echo "\n";
    }
    
    // Vérifier les invitations
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $invitations = $invitationRepo->findBy(['event' => $event]);
    
    echo "📧 Invitations trouvées:\n";
    foreach ($invitations as $invitation) {
        echo "   - ID: {$invitation->getId()}\n";
        echo "     Email: {$invitation->getEmail()}\n";
        echo "     Nom: {$invitation->getName()}\n";
        echo "     Statut: {$invitation->getStatus()}\n";
        echo "     Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n";
        echo "     Mise à jour: " . ($invitation->getUpdatedAt() ? $invitation->getUpdatedAt()->format('d/m/Y H:i:s') : 'Jamais') . "\n\n";
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
        echo "   ❌ PROBLÈME PRINCIPAL: L'événement 'Réunion' est programmé pour le {$eventDate->format('d/m/Y')}\n";
        echo "   ❌ La gestion de présence n'est disponible que le jour de l'événement\n";
        echo "   ❌ C'est pourquoi le bouton 'ABSENT' ne fonctionne pas\n\n";
        
        echo "💡 SOLUTIONS:\n";
        echo "   1. Attendre le jour de l'événement\n";
        echo "   2. Modifier la date de l'événement pour aujourd'hui\n";
        echo "   3. Désactiver temporairement la vérification de date\n";
        
    } else {
        echo "   ✅ L'événement est aujourd'hui ou dans le passé\n";
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
