<?php
/**
 * Script de configuration de la base de données
 */

echo "🔧 CONFIGURATION DE LA BASE DE DONNÉES\n";
echo "=====================================\n\n";

try {
    // 1. Connexion à MySQL (sans base spécifique)
    echo "1️⃣ Connexion à MySQL...\n";
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion MySQL réussie\n\n";
    
    // 2. Vérifier si la base eventhub existe
    echo "2️⃣ Vérification de la base 'eventhub'...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'eventhub'");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($databases) > 0) {
        echo "✅ Base 'eventhub' existe déjà\n";
    } else {
        echo "❌ Base 'eventhub' n'existe pas\n";
        echo "🔧 Création de la base...\n";
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS eventhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Base 'eventhub' créée avec succès\n";
    }
    
    echo "\n";
    
    // 3. Se connecter à la base eventhub
    echo "3️⃣ Connexion à la base 'eventhub'...\n";
    $pdo = new PDO("mysql:host=localhost;dbname=eventhub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à 'eventhub' réussie\n\n";
    
    // 4. Vérifier les tables existantes
    echo "4️⃣ Vérification des tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Tables existantes :\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "❌ Aucune table trouvée\n";
    }
    
    echo "\n";
    
    // 5. Vérifier spécifiquement la table document
    echo "5️⃣ Vérification de la table 'document'...\n";
    if (in_array('document', $tables)) {
        echo "✅ Table 'document' existe\n";
        
        // Compter les documents
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM document");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "📊 Nombre de documents : $count\n";
    } else {
        echo "❌ Table 'document' n'existe pas\n";
        echo "💡 Cette table sera créée automatiquement par Symfony\n";
    }
    
    echo "\n";
    
    // 6. Vérifier la table event
    echo "6️⃣ Vérification de la table 'event'...\n";
    if (in_array('event', $tables)) {
        echo "✅ Table 'event' existe\n";
        
        // Compter les événements
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM event");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "📊 Nombre d'événements : $count\n";
    } else {
        echo "❌ Table 'event' n'existe pas\n";
    }
    
    echo "\n✅ CONFIGURATION TERMINÉE\n";
    echo "\n💡 PROCHAINES ÉTAPES :\n";
    echo "1. Exécuter : php bin/console doctrine:schema:update --force\n";
    echo "2. Vérifier : php bin/console doctrine:schema:validate\n";
    echo "3. Tester la création d'événements avec documents\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR PDO : " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "\n💡 SUGGESTION : Vérifiez que MySQL est démarré dans XAMPP\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "\n💡 SUGGESTION : La base de données n'existe pas encore\n";
    }
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
