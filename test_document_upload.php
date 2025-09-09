<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

// Configuration de la base de données
$dbParams = [
    'driver' => 'pdo_mysql',
    'host' => $_ENV['DATABASE_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DATABASE_PORT'] ?? '3306',
    'dbname' => $_ENV['DATABASE_NAME'] ?? 'my_database',
    'user' => $_ENV['DATABASE_USER'] ?? 'root',
    'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
];

// Créer la connexion DBAL
$connection = DriverManager::getConnection($dbParams);

echo "=== DIAGNOSTIC DOCUMENTS ===\n\n";

// 1. Vérifier les événements récents
echo "1. Événements récents :\n";
$events = $connection->executeQuery("SELECT id, title, organizer_id FROM event ORDER BY id DESC LIMIT 5")->fetchAllAssociative();
foreach ($events as $event) {
    $docCount = $connection->executeQuery("SELECT COUNT(*) FROM document WHERE event_id = ?", [$event['id']])->fetchOne();
    echo "   - ID: {$event['id']}, Titre: {$event['title']}, Documents: {$docCount}\n";
}

// 2. Vérifier les documents en base
echo "\n2. Documents en base de données :\n";
$documents = $connection->executeQuery("SELECT d.id, d.file_name, d.event_id, e.title FROM document d LEFT JOIN event e ON d.event_id = e.id")->fetchAllAssociative();
echo "   Total documents: " . count($documents) . "\n";
foreach ($documents as $doc) {
    echo "   - ID: {$doc['id']}, Fichier: {$doc['file_name']}, Événement: {$doc['title']}\n";
}

// 3. Vérifier l'utilisateur principal
echo "\n3. Utilisateur principal :\n";
$user = $connection->executeQuery("SELECT id, email, roles FROM users WHERE id = 1")->fetchAssociative();
if ($user) {
    echo "   - Email: {$user['email']}\n";
    echo "   - Rôles: {$user['roles']}\n";
} else {
    echo "   - Utilisateur ID 1 non trouvé\n";
}

// 4. Vérifier les participations
echo "\n4. Participations de l'utilisateur :\n";
$participations = $connection->executeQuery("
    SELECT p.event_id, p.invitation_status, e.title, 
           (SELECT COUNT(*) FROM document WHERE event_id = p.event_id) as doc_count
    FROM participation p 
    LEFT JOIN event e ON p.event_id = e.id 
    WHERE p.user_id = 1
")->fetchAllAssociative();
echo "   Total participations: " . count($participations) . "\n";
foreach ($participations as $participation) {
    echo "   - Événement: {$participation['title']}, Statut: {$participation['invitation_status']}, Documents: {$participation['doc_count']}\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
