<?php
/**
 * Script de correction automatique des statuts d'invitation
 * Usage: php fix_invitation_status.php
 */

echo "🔧 Correction Automatique des Statuts d'Invitation - EventHub\n";
echo "============================================================\n\n";

echo "🚀 Exécution de la correction via Symfony Console...\n\n";

// Exécuter la commande de diagnostic
$output = shell_exec('php bin/console app:diagnose-invitation-status --details 2>&1');
echo "📊 Diagnostic:\n";
echo $output . "\n";

// Exécuter la correction automatique
echo "🔧 Application des corrections...\n";
echo "Note: La correction sera appliquée automatiquement\n\n";

// Utiliser echo pour simuler une réponse "yes" à la confirmation
$fixCommand = 'echo "yes" | php bin/console app:diagnose-invitation-status --fix --details 2>&1';
$fixOutput = shell_exec($fixCommand);

echo "📋 Résultat de la correction:\n";
echo $fixOutput . "\n";

// Vérification finale
echo "🔍 Vérification finale...\n";
$finalCheck = shell_exec('php bin/console app:diagnose-invitation-status --details 2>&1');
echo "📊 État final:\n";
echo $finalCheck . "\n";

echo "🎉 Processus de correction terminé !\n";
echo "Vérifiez que les statuts d'invitation sont maintenant cohérents.\n";
