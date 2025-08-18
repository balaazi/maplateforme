<?php
// test_dashboard_activity.php
// Script de test pour vérifier la logique du dashboard participant

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

// Créer le kernel Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

// Récupérer le container
$container = $kernel->getContainer();

try {
    echo "🧪 Test de la logique du dashboard participant\n";
    echo "=============================================\n\n";
    
    // Récupérer les services nécessaires
    $participationRepository = $container->get('doctrine')->getRepository('App\Entity\Participation');
    $notificationService = $container->get('App\Service\NotificationService');
    
    echo "✅ Services récupérés avec succès\n\n";
    
    // Simuler un utilisateur participant (premier utilisateur avec rôle participant)
    $userRepository = $container->get('doctrine')->getRepository('App\Entity\User');
    $participants = $userRepository->createQueryBuilder('u')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_PARTICIPANT%')
        ->setMaxResults(1)
        ->getQuery()
        ->getResult();
    
    if (empty($participants)) {
        echo "❌ Aucun utilisateur participant trouvé dans la base de données\n";
        exit(1);
    }
    
    $user = $participants[0];
    echo "👤 Utilisateur test : {$user->getPrenom()} {$user->getNom()} ({$user->getEmail()})\n\n";
    
    // Simuler la logique du contrôleur
    echo "🔍 Récupération des données...\n";
    
    // Nombre de notifications non lues
    $unreadNotificationsCount = $notificationService->getUnreadCountForUser($user);
    echo "  - Notifications non lues : $unreadNotificationsCount\n";
    
    // Nombre d'événements (non archivés)
    $participationsNonArchivees = $participationRepository->findByUserNonArchived($user);
    $eventsCount = count($participationsNonArchivees);
    echo "  - Événements (non archivés) : $eventsCount\n";
    
    // Nombre de documents
    $allParticipations = $participationRepository->findBy(['user' => $user]);
    $documentsCount = 0;
    foreach ($allParticipations as $participation) {
        $event = $participation->getEvent();
        if ($event === null) { continue; }
        $documentsCount += count($event->getDocuments());
    }
    echo "  - Documents : $documentsCount\n";
    
    // Récupération de l'activité récente
    echo "\n📊 Génération de l'activité récente...\n";
    $recentActivity = [];
    
    // Ajouter les participations récentes
    foreach (array_slice($allParticipations, 0, 5) as $participation) {
        $event = $participation->getEvent();
        if ($event === null) { continue; }
        
        $recentActivity[] = [
            'type' => 'participation',
            'title' => 'Participation à l\'événement',
            'description' => $event->getTitle(),
            'icon' => 'calendar-check',
            'color' => '#10b981',
            'date' => $participation->getCreatedAt(),
            'category' => 'events',
            'status' => $participation->getInvitationStatus(),
            'isPresent' => $participation->isPresent()
        ];
        
        echo "  ✅ Participation : {$event->getTitle()} (statut: {$participation->getInvitationStatus()}, présent: " . ($participation->isPresent() ? 'Oui' : 'Non') . ")\n";
    }
    
    // Ajouter les confirmations de présence
    foreach ($allParticipations as $participation) {
        if ($participation->isPresent()) {
            $event = $participation->getEvent();
            if ($event === null) { continue; }
            
            $recentActivity[] = [
                'type' => 'presence_confirmed',
                'title' => 'Présence confirmée',
                'description' => 'Vous avez confirmé votre participation à ' . $event->getTitle(),
                'icon' => 'check-circle',
                'color' => '#10b981',
                'date' => $participation->getCreatedAt(),
                'category' => 'presence'
            ];
            
            echo "  ✅ Présence confirmée : {$event->getTitle()}\n";
        }
    }
    
    // Ajouter les changements de statut d'invitation
    foreach ($allParticipations as $participation) {
        $event = $participation->getEvent();
        if ($event === null) { continue; }
        
        $status = $participation->getInvitationStatus();
        if ($status === 'accepté') {
            $recentActivity[] = [
                'type' => 'invitation_accepted',
                'title' => 'Invitation acceptée',
                'description' => 'Vous avez accepté l\'invitation à ' . $event->getTitle(),
                'icon' => 'check',
                'color' => '#3b82f6',
                'date' => $participation->getCreatedAt(),
                'category' => 'invitation'
            ];
            
            echo "  ✅ Invitation acceptée : {$event->getTitle()}\n";
        } elseif ($status === 'refusé') {
            $recentActivity[] = [
                'type' => 'invitation_declined',
                'title' => 'Invitation refusée',
                'description' => 'Vous avez refusé l\'invitation à ' . $event->getTitle(),
                'icon' => 'times',
                'color' => '#ef4444',
                'date' => $participation->getCreatedAt(),
                'category' => 'invitation'
            ];
            
            echo "  ❌ Invitation refusée : {$event->getTitle()}\n";
        }
    }
    
    // Trier par date
    usort($recentActivity, function($a, $b) {
        return $b['date'] <=> $a['date'];
    });
    
    // Limiter à 5 activités récentes
    $recentActivity = array_slice($recentActivity, 0, 5);
    
    echo "\n📋 Activités récentes générées (" . count($recentActivity) . " activités) :\n";
    foreach ($recentActivity as $index => $activity) {
        $dateStr = $activity['date'] ? $activity['date']->format('d/m/Y H:i') : 'Date inconnue';
        echo "  " . ($index + 1) . ". {$activity['title']}\n";
        echo "     Description : {$activity['description']}\n";
        echo "     Date : {$dateStr}\n";
        echo "     Type : {$activity['type']}\n";
        echo "     Icône : {$activity['icon']}\n";
        echo "     Couleur : {$activity['color']}\n";
        echo "     Catégorie : {$activity['category']}\n";
        echo "\n";
    }
    
    // Si pas d'activité récente, ajouter un message d'accueil
    if (empty($recentActivity)) {
        echo "⚠️  Aucune activité récente trouvée, ajout du message d'accueil...\n";
        $recentActivity[] = [
            'type' => 'welcome',
            'title' => 'Bienvenue sur EventHub !',
            'description' => 'Commencez par participer à des événements pour voir votre activité',
            'icon' => 'rocket',
            'color' => '#8b5cf6',
            'date' => new \DateTime(),
            'category' => 'welcome'
        ];
    }
    
    echo "✅ Test de la logique du dashboard participant réussi !\n";
    echo "📊 Résumé des données :\n";
    echo "  - Notifications : $unreadNotificationsCount\n";
    echo "  - Événements : $eventsCount\n";
    echo "  - Documents : $documentsCount\n";
    echo "  - Activités récentes : " . count($recentActivity) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
    echo "Trace :\n" . $e->getTraceAsString() . "\n";
} finally {
    // Arrêter le kernel
    if (isset($kernel)) {
        $kernel->shutdown();
    }
}
