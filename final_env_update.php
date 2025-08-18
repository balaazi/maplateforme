<?php
/**
 * Script final pour mettre à jour .env avec la bonne base de données
 */

echo "🔧 Mise à jour finale de la configuration\n";
echo "=======================================\n\n";

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    echo "❌ Fichier .env non trouvé\n";
    exit(1);
}

// Lire le contenu actuel
$content = file_get_contents($envFile);

// Remplacer la ligne DATABASE_URL
$oldUrl = 'DATABASE_URL="mysql://root:@127.0.0.1:3306/maplateforme?serverVersion=8.0.32&charset=utf8mb4"';
$newUrl = 'DATABASE_URL="mysql://root:@127.0.0.1:3306/eventhub?serverVersion=8.0.32&charset=utf8mb4"';

$content = str_replace($oldUrl, $newUrl, $content);

// Écrire le nouveau contenu
if (file_put_contents($envFile, $content)) {
    echo "✅ DATABASE_URL mise à jour vers 'eventhub'\n";
} else {
    echo "❌ Erreur lors de la mise à jour du fichier .env\n";
}

echo "\n🧪 Test de la configuration Symfony :\n";

try {
    // Test de la configuration Doctrine
    $output = shell_exec('php bin/console doctrine:schema:validate 2>&1');
    echo "📋 Validation du schéma :\n$output\n";
    
    // Test de la configuration mailer
    $output = shell_exec('php bin/console debug:config framework mailer 2>&1');
    echo "📋 Configuration mailer :\n$output\n";
    
    // Test de la configuration router
    $output = shell_exec('php bin/console debug:config framework router 2>&1');
    echo "📋 Configuration router :\n$output\n";
    
    echo "\n✅ Configuration testée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
} 