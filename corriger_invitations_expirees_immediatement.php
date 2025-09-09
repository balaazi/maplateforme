<?php
/**
 * Script de correction immédiate des invitations expirées
 * Ce script utilise la commande Symfony pour corriger les invitations expirées
 */

echo "=== CORRECTION IMMÉDIATE DES INVITATIONS EXPIRÉES ===\n";
echo "Utilisation de la commande Symfony pour la correction...\n\n";

// Délai d'expiration (30 jours par défaut)
$daysExpiration = 30;

echo "Délai d'expiration : $daysExpiration jours\n";
echo "Recherche des invitations expirées...\n\n";

// Exécuter la commande Symfony
$command = "php bin/console app:expire-invitations --days=$daysExpiration";
echo "Exécution de la commande : $command\n\n";

$output = [];
$returnCode = 0;

exec($command, $output, $returnCode);

// Afficher le résultat
foreach ($output as $line) {
    echo $line . "\n";
}

if ($returnCode === 0) {
    echo "\n✅ Commande exécutée avec succès !\n";
    
    // Vérifier s'il y a eu des invitations expirées
    $outputText = implode("\n", $output);
    if (strpos($outputText, 'marquées comme expirées') !== false) {
        echo "🎉 Des invitations ont été marquées comme expirées.\n";
    } else {
        echo "ℹ️  Aucune invitation expirée trouvée.\n";
    }
} else {
    echo "\n❌ Erreur lors de l'exécution de la commande (code: $returnCode)\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "Pour éviter ce problème à l'avenir :\n";
echo "1. Exécutez : setup_automatic_expiration.bat\n";
echo "2. Ou configurez manuellement une tâche planifiée\n";
echo "3. Ou exécutez quotidiennement : php bin/console app:expire-invitations\n";
echo "\n=== FIN ===\n";
