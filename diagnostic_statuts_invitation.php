<?php
/**
 * Script de diagnostic des statuts d'invitation
 * Exécuter avec: php diagnostic_statuts_invitation.php
 */

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

// Connexion à la base de données
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'eventhub';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 DIAGNOSTIC DES STATUTS D'INVITATION\n";
    echo "=====================================\n\n";
    
    // 1. Vérifier la table participation
    echo "📊 TABLE PARTICIPATION:\n";
    echo "------------------------\n";
    
    $stmt = $pdo->query("SELECT invitationStatus, COUNT(*) as count FROM participation GROUP BY invitationStatus");
    $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($participations as $row) {
        echo "- {$row['invitationStatus']}: {$row['count']} participations\n";
    }
    
    // 2. Vérifier la table invitation
    echo "\n📊 TABLE INVITATION:\n";
    echo "---------------------\n";
    
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM invitation GROUP BY status");
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($invitations as $row) {
        echo "- {$row['status']}: {$row['count']} invitations\n";
    }
    
    // 3. Vérifier les participations récentes
    echo "\n📊 PARTICIPATIONS RÉCENTES (dernières 24h):\n";
    echo "--------------------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.invitationStatus,
            p.createdAt,
            u.email,
            e.title as event_title
        FROM participation p
        JOIN users u ON p.user_id = u.id
        JOIN event e ON p.event_id = e.id
        WHERE p.createdAt >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ORDER BY p.createdAt DESC
        LIMIT 10
    ");
    
    $recentParticipations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentParticipations)) {
        echo "Aucune participation récente trouvée.\n";
    } else {
        foreach ($recentParticipations as $row) {
            echo "- ID: {$row['id']}, Statut: {$row['invitationStatus']}, Email: {$row['email']}, Événement: {$row['event_title']}, Créé: {$row['createdAt']}\n";
        }
    }
    
    // 4. Vérifier les participations qui devraient être expirées
    echo "\n📊 PARTICIPATIONS QUI DEVRaient ÊTRE EXPIRÉES:\n";
    echo "-----------------------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.invitationStatus,
            p.createdAt,
            u.email,
            e.title as event_title,
            DATEDIFF(NOW(), p.createdAt) as days_old
        FROM participation p
        JOIN users u ON p.user_id = u.id
        JOIN event e ON p.event_id = e.id
        WHERE p.invitationStatus = 'pending' 
        AND p.createdAt < DATE_SUB(NOW(), INTERVAL 1 DAY)
        ORDER BY p.createdAt ASC
        LIMIT 10
    ");
    
    $oldParticipations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($oldParticipations)) {
        echo "Aucune participation ancienne en attente trouvée.\n";
    } else {
        foreach ($oldParticipations as $row) {
            echo "- ID: {$row['id']}, Email: {$row['email']}, Événement: {$row['event_title']}, Créé: {$row['createdAt']}, Âge: {$row['days_old']} jours\n";
        }
    }
    
    // 5. Vérifier la structure des tables
    echo "\n📊 STRUCTURE DES TABLES:\n";
    echo "-------------------------\n";
    
    $stmt = $pdo->query("DESCRIBE participation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table PARTICIPATION:\n";
    foreach ($columns as $column) {
        if ($column['Field'] === 'invitationStatus') {
            echo "- {$column['Field']}: {$column['Type']} (Défaut: {$column['Default']})\n";
        }
    }
    
    $stmt = $pdo->query("DESCRIBE invitation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTable INVITATION:\n";
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            echo "- {$column['Field']}: {$column['Type']} (Défaut: {$column['Default']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n✅ Diagnostic terminé.\n";
