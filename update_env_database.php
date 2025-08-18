<?php
/**
 * Script pour mettre à jour la DATABASE_URL dans .env
 */

echo "🔧 Mise à jour de la configuration de base de données\n";
echo "====================================================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé\n";
    exit(1);
}

// Lire le contenu actuel
$content = file_get_contents($envFile);

// Remplacer la ligne DATABASE_URL
$oldUrl = 'DATABASE_URL="mysql://root:@127.0.0.1:3306/eventhub?serverVersion=8.0.32&charset=utf8mb4"';
$newUrl = 'DATABASE_URL="mysql://root:@127.0.0.1:3306/maplateforme?serverVersion=8.0.32&charset=utf8mb4"';

$content = str_replace($oldUrl, $newUrl, $content);

// Écrire le nouveau contenu
if (file_put_contents($envFile, $content)) {
    echo "✅ DATABASE_URL mise à jour vers 'maplateforme'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du fichier .env\n";
}

echo "\n📋 Test de connexion à la base de données :\n";

try {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'maplateforme';
    
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données 'maplateforme' réussie\n";
    
    // Vérifier si la table users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'users' trouvée\n";
        
        // Vérifier les colonnes
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 Colonnes de la table users :\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . "\n";
        }
    } else {
        echo "❌ Table 'users' non trouvée\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
} 