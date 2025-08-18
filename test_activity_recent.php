<?php
// test_activity_recent.php
// Script de test pour vérifier la logique d'activité récente

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

// Connexion à la base de données
$connectionParams = [
    'url' => $_ENV['DATABASE_URL'],
];

try {
    $connection = DriverManager::getConnection($connectionParams);
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Vérifier les données existantes
    echo "🔍 Vérification des données existantes...\n";
    
    // Compter les utilisateurs
    $userCount = $connection->executeQuery('SELECT COUNT(*) FROM users')->fetchOne();
    echo "👥 Nombre d'utilisateurs : $userCount\n";
    
    // Compter les événements
    $eventCount = $connection->executeQuery('SELECT COUNT(*) FROM event')->fetchOne();
    echo "📅 Nombre d\'événements : $eventCount\n";
    
    // Compter les participations
    $participationCount = $connection->executeQuery('SELECT COUNT(*) FROM participation')->fetchOne();
    echo "🎯 Nombre de participations : $participationCount\n";
    
    // Vérifier les participations avec détails
    if ($participationCount > 0) {
        echo "\n📊 Détails des participations :\n";
        $participations = $connection->executeQuery('
            SELECT 
                p.id,
                p.invitation_status,
                p.is_present,
                p.created_at,
                u.email as user_email,
                e.title as event_title
            FROM participation p
            JOIN users u ON p.user_id = u.id
            JOIN event e ON p.event_id = e.id
            ORDER BY p.created_at DESC
            LIMIT 10
        ')->fetchAllAssociative();
        
        foreach ($participations as $participation) {
            echo "  - ID: {$participation['id']} | ";
            echo "Utilisateur: {$participation['user_email']} | ";
            echo "Événement: {$participation['event_title']} | ";
            echo "Statut: {$participation['invitation_status']} | ";
            echo "Présent: " . ($participation['is_present'] ? 'Oui' : 'Non') . " | ";
            echo "Créé: {$participation['created_at']}\n";
        }
    }
    
    // Vérifier les utilisateurs avec rôle participant
    echo "\n👤 Utilisateurs avec rôle participant :\n";
    $participants = $connection->executeQuery('
        SELECT id, email, nom, prenom, roles
        FROM users 
        WHERE JSON_CONTAINS(roles, \'"ROLE_PARTICIPANT"\')
        LIMIT 5
    ')->fetchAllAssociative();
    
    foreach ($participants as $participant) {
        echo "  - {$participant['prenom']} {$participant['nom']} ({$participant['email']})\n";
    }
    
    // Test de la logique d'activité récente
    if ($participationCount > 0) {
        echo "\n🧪 Test de la logique d'activité récente :\n";
        
        // Simuler la logique du contrôleur
        $recentActivity = [];
        
        // Récupérer les participations récentes
        $recentParticipations = $connection->executeQuery('
            SELECT 
                p.id,
                p.invitation_status,
                p.is_present,
                p.created_at,
                e.title as event_title
            FROM participation p
            JOIN event e ON p.event_id = e.id
            ORDER BY p.created_at DESC
            LIMIT 5
        ')->fetchAllAssociative();
        
        foreach ($recentParticipations as $participation) {
            $recentActivity[] = [
                'type' => 'participation',
                'title' => 'Participation à l\'événement',
                'description' => $participation['event_title'],
                'icon' => 'calendar-check',
                'color' => '#10b981',
                'date' => $participation['created_at'],
                'category' => 'events',
                'status' => $participation['invitation_status'],
                'isPresent' => $participation['is_present']
            ];
        }
        
        // Ajouter les confirmations de présence
        foreach ($recentParticipations as $participation) {
            if ($participation['is_present']) {
                $recentActivity[] = [
                    'type' => 'presence_confirmed',
                    'title' => 'Présence confirmée',
                    'description' => 'Vous avez confirmé votre participation à ' . $participation['event_title'],
                    'icon' => 'check-circle',
                    'color' => '#10b981',
                    'date' => $participation['created_at'],
                    'category' => 'presence'
                ];
            }
        }
        
        // Trier par date
        usort($recentActivity, function($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        
        echo "  📋 Activités récentes générées :\n";
        foreach ($recentActivity as $activity) {
            echo "    - {$activity['title']}: {$activity['description']} ({$activity['date']})\n";
        }
        
        echo "\n✅ Test de la logique d'activité récente réussi !\n";
    } else {
        echo "\n⚠️  Aucune participation trouvée pour tester l'activité récente\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}
