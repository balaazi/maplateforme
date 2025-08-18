<?php
/**
 * Script de création du fichier .env pour EventHub
 * Résout le problème des notifications et emails non reçus
 */

echo "🔧 Création du fichier .env pour EventHub\n";
echo "==========================================\n\n";

// Contenu du fichier .env
$envContent = <<<ENV
# Configuration de base
APP_ENV=dev
APP_SECRET=votre_secret_ici_123456789

# Configuration de la base de données
DATABASE_URL="mysql://root:@127.0.0.1:3306/eventhub?serverVersion=8.0.32&charset=utf8mb4"

# Configuration du mailer pour Gmail
MAILER_DSN=smtp://nadiabalaazi@gmail.com:votre_mot_de_passe_app@smtp.gmail.com:587?encryption=tls&auth_mode=login

# Configuration des notifications
MAILER_FROM_EMAIL=nadiabalaazi@gmail.com
MAILER_FROM_NAME="EventHub Notifications"

# Configuration des logs
LOG_LEVEL=info

# Configuration de l'application
APP_URL=http://localhost:8000

# Configuration des rappels
REMINDER_DEFAULT_SCHEDULE=1440,120,30
REMINDER_CHECK_INTERVAL=300

# Configuration des notifications temps réel
NOTIFICATION_SOUND_ENABLED=true
NOTIFICATION_VISUAL_ENABLED=true
ENV;

// Vérifier si le fichier .env existe déjà
if (file_exists('.env')) {
    echo "⚠️  Le fichier .env existe déjà.\n";
    echo "Voulez-vous le remplacer ? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) !== 'y') {
        echo "❌ Opération annulée.\n";
        exit(1);
    }
}

// Créer le fichier .env
if (file_put_contents('.env', $envContent)) {
    echo "✅ Fichier .env créé avec succès !\n\n";
    
    echo "📋 Configuration ajoutée :\n";
    echo "   - MAILER_DSN configuré pour Gmail\n";
    echo "   - APP_SECRET défini\n";
    echo "   - DATABASE_URL configuré\n";
    echo "   - Paramètres de notifications activés\n\n";
    
    echo "🔧 Prochaines étapes :\n";
    echo "1. Configurez votre mot de passe Gmail dans .env\n";
    echo "2. Activez l'authentification à 2 facteurs sur Gmail\n";
    echo "3. Créez un mot de passe d'application\n";
    echo "4. Testez avec : php bin/console mailer:test votre-email@gmail.com\n\n";
    
    echo "📧 Pour configurer Gmail SMTP :\n";
    echo "1. Allez sur https://myaccount.google.com/security\n";
    echo "2. Activez 'Validation en 2 étapes'\n";
    echo "3. Allez sur https://myaccount.google.com/apppasswords\n";
    echo "4. Créez un mot de passe pour 'EventHub'\n";
    echo "5. Remplacez 'votre_mot_de_passe_app' dans .env\n\n";
    
    echo "🎯 Test immédiat :\n";
    echo "php test_email.php\n";
    
} else {
    echo "❌ Erreur lors de la création du fichier .env\n";
    echo "Vérifiez les permissions du répertoire.\n";
    exit(1);
} 