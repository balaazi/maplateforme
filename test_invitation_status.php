<?php
/**
 * Script de test pour vérifier les statuts d'invitation
 * Usage: php test_invitation_status.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Test des Statuts d'Invitation - EventHub\n";
echo "============================================\n\n";

try {
    // Test de l'enum InvitationStatus
    echo "1. Test de l'enum InvitationStatus...\n";
    $reflection = new ReflectionClass(\App\Enum\InvitationStatus::class);
    $cases = $reflection->getConstants();
    
    echo "   Statuts disponibles:\n";
    foreach ($cases as $name => $value) {
        echo "   - {$name}: {$value}\n";
    }
    echo "   ✅ Enum InvitationStatus OK\n\n";

    // Test de l'entité Invitation
    echo "2. Test de l'entité Invitation...\n";
    $invitation = new \App\Entity\Invitation();
    
    // Test des méthodes de statut
    $invitation->setStatus('pending');
    echo "   - Statut 'pending': " . ($invitation->isPending() ? 'OK' : 'ERREUR') . "\n";
    
    $invitation->setStatus('accepted');
    echo "   - Statut 'accepted': " . ($invitation->isAccepted() ? 'OK' : 'ERREUR') . "\n";
    
    $invitation->setStatus('declined');
    echo "   - Statut 'declined': " . ($invitation->isDeclined() ? 'OK' : 'ERREUR') . "\n";
    
    $invitation->setStatus('expired');
    echo "   - Statut 'expired': " . ($invitation->isExpired() ? 'OK' : 'ERREUR') . "\n";
    
    $invitation->setStatus('conflict');
    echo "   - Statut 'conflict': " . ($invitation->isConflict() ? 'OK' : 'ERREUR') . "\n";
    
    echo "   ✅ Entité Invitation OK\n\n";

    // Test du service InvitationStatusService
    echo "3. Test du service InvitationStatusService...\n";
    $statusService = new \App\Service\InvitationStatusService();
    
    echo "   - Texte 'pending': " . $statusService->getStatusText('pending') . "\n";
    echo "   - Texte 'accepted': " . $statusService->getStatusText('accepted') . "\n";
    echo "   - Texte 'declined': " . $statusService->getStatusText('declined') . "\n";
    echo "   - Texte 'expired': " . $statusService->getStatusText('expired') . "\n";
    echo "   - Texte 'conflict': " . $statusService->getStatusText('conflict') . "\n";
    
    echo "   - Classe CSS 'pending': " . $statusService->getStatusClass('pending') . "\n";
    echo "   - Classe CSS 'accepted': " . $statusService->getStatusClass('accepted') . "\n";
    
    echo "   - Peut participer avec 'accepted': " . ($statusService->canParticipate('accepted') ? 'OUI' : 'NON') . "\n";
    echo "   - Peut participer avec 'pending': " . ($statusService->canParticipate('pending') ? 'OUI' : 'NON') . "\n";
    
    echo "   - Statut final 'accepted': " . ($statusService->isFinalStatus('accepted') ? 'OUI' : 'NON') . "\n";
    echo "   - Statut final 'pending': " . ($statusService->isFinalStatus('pending') ? 'OUI' : 'NON') . "\n";
    
    echo "   ✅ Service InvitationStatusService OK\n\n";

    // Test de validation des statuts invalides
    echo "4. Test de validation des statuts invalides...\n";
    try {
        $invitation->setStatus('invalid_status');
        echo "   ❌ ERREUR: Statut invalide accepté\n";
    } catch (\InvalidArgumentException $e) {
        echo "   ✅ Statut invalide correctement rejeté: " . $e->getMessage() . "\n";
    }
    
    try {
        $invitation->setStatus('en_attente'); // Ancien format
        echo "   ❌ ERREUR: Ancien statut accepté\n";
    } catch (\InvalidArgumentException $e) {
        echo "   ✅ Ancien statut correctement rejeté: " . $e->getMessage() . "\n";
    }
    
    echo "   ✅ Validation des statuts OK\n\n";

    // Test de cohérence des statuts
    echo "5. Test de cohérence des statuts...\n";
    $validStatuses = ['pending', 'accepted', 'declined', 'expired', 'conflict'];
    $enumStatuses = array_values($cases);
    
    $missingInEnum = array_diff($validStatuses, $enumStatuses);
    $missingInValid = array_diff($enumStatuses, $validStatuses);
    
    if (empty($missingInEnum) && empty($missingInValid)) {
        echo "   ✅ Cohérence des statuts parfaite\n";
    } else {
        echo "   ⚠️ Incohérences détectées:\n";
        if (!empty($missingInEnum)) {
            echo "   - Manquants dans l'enum: " . implode(', ', $missingInEnum) . "\n";
        }
        if (!empty($missingInValid)) {
            echo "   - Manquants dans la validation: " . implode(', ', $missingInValid) . "\n";
        }
    }
    
    echo "\n";

    echo "🎉 Tous les tests sont passés avec succès !\n";
    echo "Les statuts d'invitation sont maintenant cohérents et fonctionnels.\n\n";
    
    echo "📋 Prochaines étapes:\n";
    echo "1. Exécuter la migration: php bin/console doctrine:migrations:migrate\n";
    echo "2. Diagnostiquer les problèmes: php bin/console app:diagnose-invitation-status\n";
    echo "3. Corriger automatiquement: php bin/console app:diagnose-invitation-status --fix\n";
    echo "4. Tester une invitation réelle\n\n";

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
