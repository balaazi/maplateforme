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

echo "=== TEST CRÉATION ÉVÉNEMENT AVEC DOCUMENTS ===\n\n";

// 1. Créer un fichier de test
$testFile = 'test_document.txt';
file_put_contents($testFile, 'Ceci est un document de test pour vérifier l\'upload.');

echo "1. Fichier de test créé : {$testFile}\n";

// 2. Créer un événement de test
$eventData = [
    'title' => 'Test Document Upload - ' . date('Y-m-d H:i:s'),
    'description' => 'Événement de test pour vérifier l\'upload de documents',
    'date_heure' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'duree' => 60,
    'organizer_id' => 1,
    'created_by_id' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'archive' => 0
];

$connection->insert('event', $eventData);
$eventId = $connection->lastInsertId();

echo "2. Événement créé avec ID : {$eventId}\n";

// 3. Créer un document lié à l'événement
$documentData = [
    'file_name' => $testFile,
    'event_id' => $eventId,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

$connection->insert('document', $documentData);
$documentId = $connection->lastInsertId();

echo "3. Document créé avec ID : {$documentId}\n";

// 4. Vérifier que le document est bien lié
$document = $connection->executeQuery("
    SELECT d.id, d.file_name, d.event_id, e.title 
    FROM document d 
    LEFT JOIN event e ON d.event_id = e.id 
    WHERE d.id = ?
", [$documentId])->fetchAssociative();

if ($document) {
    echo "4. Vérification réussie :\n";
    echo "   - Document ID: {$document['id']}\n";
    echo "   - Fichier: {$document['file_name']}\n";
    echo "   - Événement: {$document['title']}\n";
} else {
    echo "4. ERREUR : Document non trouvé\n";
}

// 5. Vérifier les documents de l'utilisateur
$userDocuments = $connection->executeQuery("
    SELECT d.id, d.file_name, e.title, e.organizer_id
    FROM document d
    LEFT JOIN event e ON d.event_id = e.id
    WHERE e.organizer_id = 1
    ORDER BY d.id DESC
    LIMIT 5
")->fetchAllAssociative();

echo "\n5. Documents de l'utilisateur (organisateur) :\n";
echo "   Total: " . count($userDocuments) . "\n";
foreach ($userDocuments as $doc) {
    echo "   - Document: {$doc['file_name']}, Événement: {$doc['title']}\n";
}

// 6. Nettoyer le fichier de test
unlink($testFile);
echo "\n6. Fichier de test supprimé\n";

echo "\n=== FIN TEST ===\n";
