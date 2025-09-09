<?php
/**
 * Script de test d'email simple pour diagnostiquer le problème SMTP
 */

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

echo "🧪 Test d'envoi d'email SMTP\n";
echo "=============================\n\n";

try {
    // Charger les variables d'environnement
    if (file_exists('.env')) {
        $envContent = file_get_contents('.env');
        preg_match_all('/^([^#=]+)=(.*)$/m', $envContent, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $_ENV[trim($match[1])] = trim($match[2], '"');
        }
    }
    
    echo "✅ Variables d'environnement chargées\n";
    
    // Récupérer la configuration SMTP
    $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Non définie';
    echo "📧 MAILER_DSN: " . substr($mailerDsn, 0, 50) . "...\n";
    
    // Créer le transport
    $transport = Transport::fromDsn($mailerDsn);
    echo "✅ Transport SMTP créé\n";
    
    // Créer le mailer
    $mailer = new Mailer($transport);
    echo "✅ Mailer créé\n";
    
    // Créer l'email de test
    $email = (new Email())
        ->from('eventhub.contact.tunisie@gmail.com')
        ->to('eventhub.contact.tunisie@gmail.com')
        ->subject('🧪 Test Email EventHub - ' . date('Y-m-d H:i:s'))
        ->text('Ceci est un test d\'envoi d\'email depuis EventHub.')
        ->html('<h1>Test Email EventHub</h1><p>Ceci est un test d\'envoi d\'email depuis EventHub.</p><p>Date: ' . date('Y-m-d H:i:s') . '</p>');
    
    echo "✅ Email de test créé\n";
    
    // Envoyer l'email
    echo "📤 Envoi de l'email...\n";
    $mailer->send($email);
    
    echo "✅ Email envoyé avec succès !\n";
    echo "📧 Vérifiez votre boîte de réception : eventhub.contact.tunisie@gmail.com\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de l'envoi d'email:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . "\n";
    echo "   Ligne: " . $e->getLine() . "\n";
    
    // Afficher la trace complète pour le débogage
    echo "\n🔍 Trace complète:\n";
    echo $e->getTraceAsString() . "\n";
}
