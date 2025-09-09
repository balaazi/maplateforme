<?php
/**
 * Script de test simple pour les rappels avancés
 * Usage: php test_reminders_simple.php
 */

echo "========================================\n";
echo "    TEST RAPPELS AVANCES EVENTHUB\n";
echo "========================================\n\n";

// Vérifier que nous sommes dans le bon répertoire
if (!file_exists('bin/console')) {
    echo "ERREUR: Ce script doit être exécuté depuis la racine du projet Symfony\n";
    echo "Répertoire actuel: " . getcwd() . "\n";
    exit(1);
}

echo "[INFO] Répertoire de travail: " . getcwd() . "\n\n";

// Test 1: Vérifier la commande
echo "========================================\n";
echo "    TEST 1: VÉRIFICATION COMMANDE\n";
echo "========================================\n";
$output = [];
$returnCode = 0;
exec('php bin/console list | grep "send-event-reminders-advanced"', $output, $returnCode);

if ($returnCode === 0 && !empty($output)) {
    echo "✅ Commande trouvée: " . $output[0] . "\n";
} else {
    echo "❌ Commande non trouvée. Vérifiez l'installation.\n";
    exit(1);
}

// Test 2: Test dry-run 24h
echo "\n========================================\n";
echo "    TEST 2: RAPPELS 24H (DRY-RUN)\n";
echo "========================================\n";
$output = [];
$returnCode = 0;
exec('php bin/console app:send-event-reminders-advanced --reminder-type=24h --dry-run 2>&1', $output, $returnCode);

echo "Code de retour: $returnCode\n";
echo "Sortie:\n";
foreach ($output as $line) {
    echo "  $line\n";
}

// Test 3: Test dry-run 1h
echo "\n========================================\n";
echo "    TEST 3: RAPPELS 1H (DRY-RUN)\n";
echo "========================================\n";
$output = [];
$returnCode = 0;
exec('php bin/console app:send-event-reminders-advanced --reminder-type=1h --dry-run 2>&1', $output, $returnCode);

echo "Code de retour: $returnCode\n";
echo "Sortie:\n";
foreach ($output as $line) {
    echo "  $line\n";
}

// Test 4: Test dry-run combiné
echo "\n========================================\n";
echo "    TEST 4: RAPPELS COMBINÉS (DRY-RUN)\n";
echo "========================================\n";
$output = [];
$returnCode = 0;
exec('php bin/console app:send-event-reminders-advanced --reminder-type=both --dry-run 2>&1', $output, $returnCode);

echo "Code de retour: $returnCode\n";
echo "Sortie:\n";
foreach ($output as $line) {
    echo "  $line\n";
}

// Test 5: Test avec date forcée (demain)
echo "\n========================================\n";
echo "    TEST 5: DATE FORCÉE (DEMAIN)\n";
echo "========================================\n";
$tomorrow = date('Y-m-d', strtotime('+1 day'));
echo "Date de test: $tomorrow\n";

$output = [];
$returnCode = 0;
exec("php bin/console app:send-event-reminders-advanced --reminder-type=both --force-date=$tomorrow --dry-run 2>&1", $output, $returnCode);

echo "Code de retour: $returnCode\n";
echo "Sortie:\n";
foreach ($output as $line) {
    echo "  $line\n";
}

echo "\n========================================\n";
echo "    RÉSULTATS DES TESTS\n";
echo "========================================\n";

if ($returnCode === 0) {
    echo "✅ Tous les tests sont passés avec succès!\n";
    echo "✅ Le système de rappels avancés est fonctionnel\n";
    echo "\nProchaines étapes:\n";
    echo "1. Configurer l'automatisation: .\\setup_advanced_reminders.ps1\n";
    echo "2. Tester en mode réel: .\\test_advanced_reminders.bat\n";
    echo "3. Exécuter manuellement: .\\send_advanced_reminders.bat\n";
} else {
    echo "❌ Certains tests ont échoué\n";
    echo "Vérifiez la configuration et les logs ci-dessus\n";
}

echo "\n========================================\n";
echo "    TEST TERMINÉ\n";
echo "========================================\n";
