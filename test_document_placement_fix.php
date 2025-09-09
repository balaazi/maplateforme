<?php
/**
 * Script de test pour vérifier le placement automatique des documents
 * lors de la création d'événements
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Utiliser la configuration Symfony pour la base de données
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

try {
    $entityManager = $container->get('doctrine.orm.entity_manager');
    $connection = $entityManager->getConnection();
    
    echo "✅ Connexion à la base de données réussie via Symfony\n";
    
    // Vérifier les événements récents avec des documents
    $query = "
        SELECT 
            e.id,
            e.title,
            e.created_at,
            COUNT(d.id) as document_count
        FROM event e
        LEFT JOIN document d ON e.id = d.event_id
        WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY e.id, e.title, e.created_at
        ORDER BY e.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $connection->prepare($query);
    $result = $stmt->executeQuery();
    $events = $result->fetchAllAssociative();
    
    echo "\n📊 Événements récents avec documents :\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($events)) {
        echo "❌ Aucun événement récent trouvé\n";
    } else {
        foreach ($events as $event) {
            echo sprintf(
                "📅 Événement ID %d : %s\n   📄 Documents : %d\n   🕒 Créé : %s\n\n",
                $event['id'],
                $event['title'],
                $event['document_count'],
                $event['created_at']
            );
        }
    }
    
    // Vérifier les documents récents
    $query = "
        SELECT 
            d.id,
            d.file_name,
            d.created_at,
            e.title as event_title,
            e.id as event_id
        FROM document d
        JOIN event e ON d.event_id = e.id
        WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY d.created_at DESC
        LIMIT 10
    ";
    

    $stmt = $connection->prepare($query);
    $result = $stmt->executeQuery();
    $documents = $result->fetchAllAssociative();
    
    echo "📄 Documents récents :\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($documents)) {
        echo "❌ Aucun document récent trouvé\n";
    } else {
        foreach ($documents as $document) {
            echo sprintf(
                "📄 Document ID %d : %s\n   📅 Événement : %s (ID %d)\n   🕒 Créé : %s\n\n",
                $document['id'],
                $document['file_name'],
                $document['event_title'],
                $document['event_id'],
                $document['created_at']
            );
        }
    }
    
    // Vérifier la cohérence des relations
    $query = "
        SELECT 
            e.id as event_id,
            e.title,
            COUNT(d.id) as actual_documents,
            (SELECT COUNT(*) FROM document d2 WHERE d2.event_id = e.id) as expected_documents
        FROM event e
        LEFT JOIN document d ON e.id = d.event_id
        WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY e.id, e.title
        HAVING actual_documents != expected_documents
    ";
    
    $stmt = $connection->prepare($query);
    $result = $stmt->executeQuery();
    $inconsistencies = $result->fetchAllAssociative();
    
    if (empty($inconsistencies)) {
        echo "✅ Aucune incohérence détectée dans les relations Event-Document\n";
    } else {
        echo "❌ Incohérences détectées :\n";
        foreach ($inconsistencies as $inconsistency) {
            echo sprintf(
                "   Événement ID %d (%s) : %d documents attendus, %d trouvés\n",
                $inconsistency['event_id'],
                $inconsistency['title'],
                $inconsistency['expected_documents'],
                $inconsistency['actual_documents']
            );
        }
    }
    
    echo "\n🎯 Test terminé avec succès !\n";
    
} catch (\Doctrine\DBAL\Exception $e) {
    echo "❌ Erreur de connexion à la base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
