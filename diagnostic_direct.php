<?php
// diagnostic_direct.php - Diagnostic direct des documents

echo "=== DIAGNOSTIC DOCUMENTS UTILISATEUR ===\n\n";

try {
    // Connexion directe à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=eventhub;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données établie\n\n";

    // 1. Statistiques générales
    echo "1. 📊 STATISTIQUES GÉNÉRALES:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM user");
    $userCount = $stmt->fetchColumn();
    echo "   Utilisateurs: $userCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM event");
    $eventCount = $stmt->fetchColumn();
    echo "   Événements: $eventCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM document");
    $documentCount = $stmt->fetchColumn();
    echo "   Documents: $documentCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM participation");
    $participationCount = $stmt->fetchColumn();
    echo "   Participations: $participationCount\n\n";

    // 2. Si aucun document, c'est le problème principal
    if ($documentCount == 0) {
        echo "2. ❌ PROBLÈME IDENTIFIÉ: AUCUN DOCUMENT EN BASE\n";
        echo "   Cela explique pourquoi 'Mes Documents' est vide\n\n";
        
        echo "   💡 SOLUTION:\n";
        echo "   1. Créer un événement\n";
        echo "   2. Uploader des documents lors de la création\n";
        echo "   3. Les documents apparaîtront automatiquement dans 'Mes Documents'\n\n";
    } else {
        echo "2. 📋 DOCUMENTS EXISTANTS:\n";
        $stmt = $pdo->query("
            SELECT d.id, d.file_name, d.created_at, e.title as event_title
            FROM document d 
            JOIN event e ON d.event_id = e.id 
            ORDER BY d.created_at DESC
            LIMIT 5
        ");
        
        while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$doc['file_name']} (Événement: {$doc['event_title']})\n";
        }
        echo "\n";
    }

    // 3. Vérifier la structure des tables
    echo "3. 🔍 VÉRIFICATION STRUCTURE:\n";
    
    // Vérifier la table document
    $stmt = $pdo->query("DESCRIBE document");
    $documentFields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Champs table 'document': " . implode(', ', $documentFields) . "\n";
    
    // Vérifier les relations
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'eventhub' 
        AND TABLE_NAME = 'document' 
        AND COLUMN_NAME = 'event_id'
    ");
    $relationExists = $stmt->fetchColumn() > 0;
    echo "   Relation document->event: " . ($relationExists ? "✅" : "❌") . "\n\n";

    // 4. Test de création de document simulé
    echo "4. 🧪 TEST SIMULATION:\n";
    echo "   Pour tester le système, voici ce qui devrait se passer:\n";
    echo "   1. EventController.php ligne 89: \$form->get('imageFile')->getData()\n";
    echo "   2. Boucle lignes 102-126: Création entité Document\n";
    echo "   3. ParticipantController.php ligne 229: Récupération documents\n";
    echo "   4. Template participant/documents.html.twig: Affichage\n\n";

    // 5. Vérifier le répertoire uploads
    echo "5. 💾 RÉPERTOIRE UPLOADS:\n";
    $uploadDir = 'public/uploads/documents/';
    if (!is_dir($uploadDir)) {
        echo "   ❌ Répertoire '$uploadDir' n'existe pas\n";
        echo "   💡 Créer avec: mkdir -p $uploadDir\n\n";
    } else {
        echo "   ✅ Répertoire '$uploadDir' existe\n";
        $files = array_diff(scandir($uploadDir), ['.', '..']);
        echo "   Fichiers: " . count($files) . "\n\n";
    }

    // 6. Recommandations d'action
    echo "6. 🎯 ACTIONS RECOMMANDÉES:\n";
    echo "   IMMÉDIAT:\n";
    echo "   1. Créer un événement de test\n";
    echo "   2. Uploader 1-2 fichiers lors de la création\n";
    echo "   3. Vérifier que les documents apparaissent\n\n";
    
    echo "   VÉRIFICATIONS:\n";
    echo "   1. Logs dans var/log/dev.log pour erreurs VichUploader\n";
    echo "   2. Permissions du répertoire uploads/\n";
    echo "   3. Configuration VichUploader dans config/\n\n";

} catch (PDOException $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
    echo "💡 Vérifiez que XAMPP est démarré et la base 'eventhub' existe\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "=== FIN DU DIAGNOSTIC ===\n";
