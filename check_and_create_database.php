<?php
/**
 * Script pour vérifier et créer la base de données
 */

echo "🔧 Vérification et création de la base de données\n";
echo "================================================\n\n";

try {
    // Connexion à MySQL sans spécifier de base de données
    $host = 'localhost';
    $user = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à MySQL réussie\n\n";
    
    // Lister les bases de données existantes
    echo "📋 Bases de données disponibles :\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($databases as $db) {
        echo "- $db\n";
    }
    
    echo "\n🔍 Recherche de la base de données EventHub :\n";
    
    $targetDatabase = null;
    $possibleNames = ['eventhub', 'maplateforme', 'my_database', 'event_hub'];
    
    foreach ($possibleNames as $name) {
        if (in_array($name, $databases)) {
            $targetDatabase = $name;
            echo "✅ Base de données trouvée : $name\n";
            break;
        }
    }
    
    if (!$targetDatabase) {
        echo "❌ Aucune base de données EventHub trouvée\n";
        echo "🔧 Création de la base de données 'eventhub'...\n";
        
        $pdo->exec("CREATE DATABASE eventhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $targetDatabase = 'eventhub';
        echo "✅ Base de données 'eventhub' créée\n";
    }
    
    // Se connecter à la base de données cible
    $pdo = new PDO("mysql:host=$host;dbname=$targetDatabase;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "\n📋 Tables dans la base de données '$targetDatabase' :\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ Aucune table trouvée\n";
    } else {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
    // Vérifier si la table users existe
    if (in_array('users', $tables)) {
        echo "\n📋 Structure de la table 'users' :\n";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Vérifier les colonnes manquantes
        $existingColumns = array_column($columns, 'Field');
        $missingColumns = [];
        
        $requiredColumns = [
            'enable_sound_notifications' => 'TINYINT(1) DEFAULT 1 NOT NULL',
            'enable_visual_notifications' => 'TINYINT(1) DEFAULT 1 NOT NULL',
            'reminder_frequency' => 'INT DEFAULT 1 NOT NULL',
            'notification_priority' => 'VARCHAR(20) DEFAULT "normal" NOT NULL'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $existingColumns)) {
                $missingColumns[$column] = $definition;
                echo "❌ Colonne manquante : $column\n";
            } else {
                echo "✅ Colonne présente : $column\n";
            }
        }
        
        if (!empty($missingColumns)) {
            echo "\n🔧 Ajout des colonnes manquantes :\n";
            
            foreach ($missingColumns as $column => $definition) {
                try {
                    $sql = "ALTER TABLE users ADD COLUMN $column $definition";
                    $pdo->exec($sql);
                    echo "✅ Ajouté : $column\n";
                } catch (PDOException $e) {
                    echo "❌ Erreur pour $column : " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "\n❌ Table 'users' non trouvée\n";
    }
    
    echo "\n✅ Base de données configurée : $targetDatabase\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
} 