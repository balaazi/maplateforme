<?php
// diagnostic_documents_simple.php - Diagnostic simple des documents

require_once 'check_database.php';

echo "=== DIAGNOSTIC DOCUMENTS UTILISATEUR ===\n\n";

try {
    // Connexion à la base de données
    $host = 'localhost';
    $dbname = 'eventhub';
    $username = 'root';
    $password = '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données établie\n\n";

    // 1. Vérifier le nombre total d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user");
    $userCount = $stmt->fetchColumn();
    echo "1. 👥 UTILISATEURS:\n";
    echo "   Total utilisateurs: $userCount\n\n";

    // 2. Vérifier le nombre total d'événements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM event");
    $eventCount = $stmt->fetchColumn();
    echo "2. 📅 ÉVÉNEMENTS:\n";
    echo "   Total événements: $eventCount\n\n";

    // 3. Vérifier le nombre total de documents
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM document");
    $documentCount = $stmt->fetchColumn();
    echo "3. 📄 DOCUMENTS:\n";
    echo "   Total documents: $documentCount\n\n";

    // 4. Vérifier le nombre total de participations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM participation");
    $participationCount = $stmt->fetchColumn();
    echo "4. 🎯 PARTICIPATIONS:\n";
    echo "   Total participations: $participationCount\n\n";

    // 5. Si pas de documents, analyser pourquoi
    if ($documentCount == 0) {
        echo "5. ⚠️ AUCUN DOCUMENT TROUVÉ - ANALYSE:\n";
        echo "   Raison probable: Aucun document n'a été uploadé lors de la création d'événements\n";
        echo "   Solution: Créer un événement et uploader des documents\n\n";
    } else {
        // Afficher les documents existants
        echo "5. 📋 DOCUMENTS EXISTANTS:\n";
        $stmt = $pdo->query("
            SELECT d.id, d.file_name, d.created_at, e.title as event_title, 
                   u.prenom, u.nom
            FROM document d 
            JOIN event e ON d.event_id = e.id 
            LEFT JOIN user u ON e.organizer_id = u.id
            ORDER BY d.created_at DESC
            LIMIT 10
        ");
        
        while ($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - Document: {$doc['file_name']}\n";
            echo "     Événement: {$doc['event_title']}\n";
            echo "     Organisateur: {$doc['prenom']} {$doc['nom']}\n";
            echo "     Créé: {$doc['created_at']}\n\n";
        }
    }

    // 6. Analyser un utilisateur spécifique (le premier trouvé)
    $stmt = $pdo->query("SELECT * FROM user LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "6. 🔍 ANALYSE UTILISATEUR SPÉCIFIQUE: {$user['prenom']} {$user['nom']} ({$user['email']})\n";
        
        // Participations de cet utilisateur
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM participation 
            WHERE user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $userParticipations = $stmt->fetchColumn();
        echo "   Participations: $userParticipations\n";
        
        // Événements créés par cet utilisateur
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM event 
            WHERE created_by_id = ?
        ");
        $stmt->execute([$user['id']]);
        $userCreatedEvents = $stmt->fetchColumn();
        echo "   Événements créés: $userCreatedEvents\n";
        
        // Événements organisés par cet utilisateur
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM event 
            WHERE organizer_id = ?
        ");
        $stmt->execute([$user['id']]);
        $userOrganizedEvents = $stmt->fetchColumn();
        echo "   Événements organisés: $userOrganizedEvents\n";
        
        // Documents accessibles via participations
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT d.id) as count
            FROM document d
            JOIN event e ON d.event_id = e.id
            JOIN participation p ON p.event_id = e.id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $documentsViaParticipation = $stmt->fetchColumn();
        echo "   Documents via participations: $documentsViaParticipation\n";
        
        // Documents des événements créés
        $stmt = $pdo->prepare("
            SELECT COUNT(d.id) as count
            FROM document d
            JOIN event e ON d.event_id = e.id
            WHERE e.created_by_id = ?
        ");
        $stmt->execute([$user['id']]);
        $documentsViaCreation = $stmt->fetchColumn();
        echo "   Documents via événements créés: $documentsViaCreation\n";
        
        // Documents des événements organisés
        $stmt = $pdo->prepare("
            SELECT COUNT(d.id) as count
            FROM document d
            JOIN event e ON d.event_id = e.id
            WHERE e.organizer_id = ?
        ");
        $stmt->execute([$user['id']]);
        $documentsViaOrganization = $stmt->fetchColumn();
        echo "   Documents via événements organisés: $documentsViaOrganization\n";
        
        $totalAccessibleDocuments = $documentsViaParticipation + $documentsViaCreation + $documentsViaOrganization;
        echo "   📊 TOTAL DOCUMENTS ACCESSIBLES: $totalAccessibleDocuments\n\n";
        
        if ($totalAccessibleDocuments == 0) {
            echo "   ⚠️ AUCUN DOCUMENT ACCESSIBLE - RAISONS POSSIBLES:\n";
            echo "      1. L'utilisateur n'a participé à aucun événement avec documents\n";
            echo "      2. L'utilisateur n'a créé aucun événement avec documents\n";
            echo "      3. L'utilisateur n'a organisé aucun événement avec documents\n\n";
        }
    }

    // 7. Vérifier le répertoire de stockage
    echo "7. 💾 VÉRIFICATION DU STOCKAGE:\n";
    $uploadDir = 'public/uploads/documents/';
    if (!is_dir($uploadDir)) {
        echo "   ❌ Répertoire '$uploadDir' n'existe pas\n";
        echo "   💡 Solution: Créer le répertoire avec les bonnes permissions\n\n";
    } else {
        echo "   ✅ Répertoire '$uploadDir' existe\n";
        $files = array_diff(scandir($uploadDir), ['.', '..']);
        echo "   Fichiers physiques: " . count($files) . "\n\n";
    }

    // 8. Recommandations finales
    echo "8. 💡 RECOMMANDATIONS:\n";
    if ($documentCount == 0) {
        echo "   🎯 POUR TESTER LE SYSTÈME:\n";
        echo "      1. Créer un nouvel événement\n";
        echo "      2. Uploader des documents lors de la création\n";
        echo "      3. Inviter des participants\n";
        echo "      4. Vérifier que les documents apparaissent dans 'Mes Documents'\n\n";
    }
    
    if (!is_dir($uploadDir)) {
        echo "   📁 CRÉER LE RÉPERTOIRE DE STOCKAGE:\n";
        echo "      mkdir -p public/uploads/documents\n";
        echo "      chmod 755 public/uploads/documents\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "=== FIN DU DIAGNOSTIC ===\n";
