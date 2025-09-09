<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "🔍 Test de création de documents\n";
echo "================================\n\n";

// Test simple avec PDO
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'eventhub') . ';charset=utf8mb4',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // 1. Vérifier la structure de la table document
    echo "1. Structure de la table document :\n";
    $stmt = $pdo->query("DESCRIBE document");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} : {$column['Type']}\n";
    }
    
    echo "\n";
    
    // 2. Vérifier les événements disponibles
    echo "2. Événements disponibles :\n";
    $stmt = $pdo->query("SELECT id, title FROM event ORDER BY id DESC LIMIT 5");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($events)) {
        echo "   ❌ Aucun événement trouvé\n";
    } else {
        echo "   ✅ " . count($events) . " événement(s) trouvé(s)\n";
        foreach ($events as $event) {
            echo "      - ID: {$event['id']}, Titre: {$event['title']}\n";
        }
    }
    
    echo "\n";
    
    // 3. Vérifier les documents existants
    echo "3. Documents existants :\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM document");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   📊 Total documents en base : {$count['total']}\n";
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT d.id, d.file_name, d.event_id, e.title FROM document d LEFT JOIN event e ON d.event_id = e.id ORDER BY d.id DESC LIMIT 5");
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($documents as $doc) {
            echo "      - ID: {$doc['id']}, Nom: {$doc['file_name']}, Événement: {$doc['title']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

echo "\n�� Test terminé\n";
