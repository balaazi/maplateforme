<?php
/**
 * Script de diagnostic pour l'upload de documents
 * Usage: php test_upload_diagnostic.php
 */

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env');

echo "🔍 Diagnostic de l'upload de documents\n";
echo "====================================\n\n";

// Test de connexion à la base de données
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=my_database;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // 1. Vérifier la configuration VichUploader
    echo "📁 Configuration VichUploader :\n";
    
    // Vérifier le dossier d'upload
    $uploadDir = __DIR__ . '/public/uploads/documents';
    if (is_dir($uploadDir)) {
        echo "  ✅ Dossier d'upload existe : {$uploadDir}\n";
        echo "  📂 Contenu du dossier :\n";
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "    - {$file}\n";
            }
        }
    } else {
        echo "  ❌ Dossier d'upload n'existe pas : {$uploadDir}\n";
        echo "  💡 Création du dossier...\n";
        if (mkdir($uploadDir, 0755, true)) {
            echo "  ✅ Dossier créé avec succès\n";
        } else {
            echo "  ❌ Impossible de créer le dossier\n";
        }
    }
    echo "\n";
    
    // 2. Vérifier les permissions
    echo "🔐 Permissions :\n";
    if (is_writable($uploadDir)) {
        echo "  ✅ Dossier d'upload accessible en écriture\n";
    } else {
        echo "  ❌ Dossier d'upload non accessible en écriture\n";
        echo "  💡 Vérifiez les permissions du dossier\n";
    }
    echo "\n";
    
    // 3. Vérifier la table document
    echo "🗄️ Structure de la table document :\n";
    $stmt = $pdo->query("DESCRIBE document");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']}) - Null: {$column['Null']} - Key: {$column['Key']}\n";
    }
    echo "\n";
    
    // 4. Vérifier les événements récents
    echo "🎯 Événements récents :\n";
    $stmt = $pdo->query("SELECT id, title, organizer_id, created_by_id FROM event ORDER BY id DESC LIMIT 5");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($events as $event) {
        echo "  - ID: {$event['id']}, Titre: {$event['title']}, Organisateur: {$event['organizer_id']}, Créé par: {$event['created_by_id']}\n";
    }
    echo "\n";
    
    // 5. Test de création d'un document de test
    echo "🧪 Test de création d'un document :\n";
    
    // Créer un fichier de test temporaire
    $testFile = __DIR__ . '/test_document.txt';
    file_put_contents($testFile, 'Document de test pour diagnostic');
    
    if (file_exists($testFile)) {
        echo "  ✅ Fichier de test créé : {$testFile}\n";
        
        // Simuler l'upload en base
        try {
            $stmt = $pdo->prepare("INSERT INTO document (file_name, event_id, created_at) VALUES (?, ?, NOW())");
            $result = $stmt->execute(['test_document.txt', 10]); // Événement Formation Python ID 10
            
            if ($result) {
                echo "  ✅ Document de test inséré en base\n";
                
                // Vérifier l'insertion
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM document");
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "  📊 Total documents en base : {$count['total']}\n";
                
                // Supprimer le document de test
                $stmt = $pdo->prepare("DELETE FROM document WHERE file_name = ?");
                $stmt->execute(['test_document.txt']);
                echo "  🗑️ Document de test supprimé\n";
            } else {
                echo "  ❌ Erreur lors de l'insertion du document de test\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Erreur lors du test d'insertion : " . $e->getMessage() . "\n";
        }
        
        // Supprimer le fichier de test
        unlink($testFile);
        echo "  🗑️ Fichier de test supprimé\n";
    } else {
        echo "  ❌ Impossible de créer le fichier de test\n";
    }
    
    // 6. Vérifier les logs d'erreur
    echo "\n📋 Vérification des logs :\n";
    $logFile = __DIR__ . '/var/log/dev.log';
    if (file_exists($logFile)) {
        echo "  ✅ Fichier de log existe : {$logFile}\n";
        
        // Lire les dernières lignes du log
        $lines = file($logFile);
        $lastLines = array_slice($lines, -10);
        
        echo "  📝 Dernières lignes du log :\n";
        foreach ($lastLines as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                echo "    ❌ {$line}";
            } elseif (strpos($line, 'DEBUG') !== false) {
                echo "    🔍 {$line}";
            }
        }
    } else {
        echo "  ❌ Fichier de log non trouvé : {$logFile}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "📋 Trace : " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Diagnostic terminé\n";



