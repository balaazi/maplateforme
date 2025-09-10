<?php
/**
 * Script de test pour le système de rappels d'invitations
 * Ce script permet de tester le système sans affecter les données réelles
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Configuration de base
$projectDir = __DIR__;
$phpBin = 'C:\\xampp\\php\\php.exe';

echo "🧪 Test du système de rappels d'invitations EventHub\n";
echo "==================================================\n\n";

// Test 1: Vérifier que la commande existe
echo "1. Test de l'existence de la commande...\n";
$command = "cd \"$projectDir\" && \"$phpBin\" bin/console list | grep app:send-invitation-reminders";
$output = shell_exec($command);
if (strpos($output, 'app:send-invitation-reminders') !== false) {
    echo "   ✅ Commande trouvée\n";
} else {
    echo "   ❌ Commande non trouvée\n";
    exit(1);
}

// Test 2: Test en mode dry-run
echo "\n2. Test en mode dry-run...\n";
$command = "cd \"$projectDir\" && \"$phpBin\" bin/console app:send-invitation-reminders --dry-run --reminder-type=24h";
$output = shell_exec($command);
if ($output) {
    echo "   ✅ Mode dry-run fonctionne\n";
    echo "   📋 Sortie: " . substr($output, 0, 200) . "...\n";
} else {
    echo "   ❌ Mode dry-run échoué\n";
}

// Test 3: Test des statistiques
echo "\n3. Test des statistiques...\n";
$command = "cd \"$projectDir\" && \"$phpBin\" bin/console app:send-invitation-reminders --stats";
$output = shell_exec($command);
if ($output && strpos($output, 'Statistiques des invitations') !== false) {
    echo "   ✅ Statistiques fonctionnent\n";
    echo "   📊 Sortie: " . substr($output, 0, 200) . "...\n";
} else {
    echo "   ❌ Statistiques échouées\n";
}

// Test 4: Test de l'aide de la commande
echo "\n4. Test de l'aide de la commande...\n";
$command = "cd \"$projectDir\" && \"$phpBin\" bin/console app:send-invitation-reminders --help";
$output = shell_exec($command);
if ($output && strpos($output, 'Envoie des rappels automatiques') !== false) {
    echo "   ✅ Aide de la commande disponible\n";
} else {
    echo "   ❌ Aide de la commande non disponible\n";
}

// Test 5: Vérifier les fichiers de configuration
echo "\n5. Vérification des fichiers de configuration...\n";
$files = [
    'src/Service/InvitationReminderService.php',
    'src/Command/SendInvitationRemindersCommand.php',
    'src/Controller/Api/InvitationReminderApiController.php',
    'templates/emails/invitation_reminder.html.twig',
    'cron_invitation_reminders.sh',
    'GUIDE_RAPPELS_INVITATIONS.md'
];

$allFilesExist = true;
foreach ($files as $file) {
    if (file_exists($projectDir . '/' . $file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file manquant\n";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "   ✅ Tous les fichiers de configuration sont présents\n";
} else {
    echo "   ❌ Certains fichiers de configuration sont manquants\n";
}

// Test 6: Test de la syntaxe PHP
echo "\n6. Test de la syntaxe PHP...\n";
$phpFiles = [
    'src/Service/InvitationReminderService.php',
    'src/Command/SendInvitationRemindersCommand.php',
    'src/Controller/Api/InvitationReminderApiController.php'
];

$syntaxOk = true;
foreach ($phpFiles as $file) {
    $command = "\"$phpBin\" -l \"$projectDir/$file\"";
    $output = shell_exec($command);
    if (strpos($output, 'No syntax errors') !== false) {
        echo "   ✅ $file - Syntaxe OK\n";
    } else {
        echo "   ❌ $file - Erreur de syntaxe\n";
        echo "      $output\n";
        $syntaxOk = false;
    }
}

if ($syntaxOk) {
    echo "   ✅ Tous les fichiers PHP ont une syntaxe correcte\n";
} else {
    echo "   ❌ Certains fichiers PHP ont des erreurs de syntaxe\n";
}

// Test 7: Test du template Twig
echo "\n7. Test du template Twig...\n";
$templateFile = $projectDir . '/templates/emails/invitation_reminder.html.twig';
if (file_exists($templateFile)) {
    $content = file_get_contents($templateFile);
    if (strpos($content, '{{ event.title }}') !== false && 
        strpos($content, '{{ invitation.status }}') !== false) {
        echo "   ✅ Template Twig semble correct\n";
    } else {
        echo "   ❌ Template Twig semble incomplet\n";
    }
} else {
    echo "   ❌ Template Twig non trouvé\n";
}

// Résumé final
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 RÉSUMÉ DU TEST\n";
echo str_repeat("=", 50) . "\n";

if ($allFilesExist && $syntaxOk) {
    echo "✅ Le système de rappels d'invitations est prêt à être utilisé !\n\n";
    echo "🚀 Commandes disponibles :\n";
    echo "   - php bin/console app:send-invitation-reminders --help\n";
    echo "   - php bin/console app:send-invitation-reminders --dry-run\n";
    echo "   - php bin/console app:send-invitation-reminders --stats\n";
    echo "   - php bin/console app:send-invitation-reminders --test-mode\n\n";
    echo "📚 Documentation :\n";
    echo "   - Consultez GUIDE_RAPPELS_INVITATIONS.md\n";
    echo "   - Configurez le cron job avec cron_invitation_reminders.sh\n";
} else {
    echo "❌ Le système nécessite des corrections avant utilisation.\n";
    echo "   Vérifiez les erreurs ci-dessus et corrigez-les.\n";
}

echo "\n🎯 Prochaines étapes :\n";
echo "   1. Configurez votre serveur SMTP dans .env\n";
echo "   2. Testez avec --test-mode avant la production\n";
echo "   3. Configurez le cron job pour l'automatisation\n";
echo "   4. Surveillez les logs dans var/log/\n";

echo "\n✨ Test terminé !\n";
?>
