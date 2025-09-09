<?php
/**
 * Script de résolution des contraintes de clés étrangères
 */

echo "🔧 RÉSOLUTION DES CONTRAINTES DE CLÉS ÉTRANGÈRES\n";
echo "================================================\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=my_database", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // 1. Vérifier les contraintes existantes
    echo "1️⃣ VÉRIFICATION DES CONTRAINTES :\n";
    echo "----------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = 'my_database'
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, CONSTRAINT_NAME
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) > 0) {
        echo "Contraintes trouvées :\n";
        foreach ($constraints as $constraint) {
            echo "  - {$constraint['CONSTRAINT_NAME']}: {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
    } else {
        echo "❌ Aucune contrainte trouvée\n";
    }
    
    echo "\n";
    
    // 2. Vérifier les notifications liées à l'utilisateur
    echo "2️⃣ VÉRIFICATION DES NOTIFICATIONS :\n";
    echo "------------------------------------\n";
    
    // Demander l'ID de l'utilisateur à supprimer
    echo "Entrez l'ID de l'utilisateur à supprimer : ";
    $userId = trim(fgets(STDIN));
    
    if (empty($userId)) {
        echo "❌ ID utilisateur non fourni\n";
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Utilisateur avec l'ID $userId non trouvé\n";
        exit;
    }
    
    echo "✅ Utilisateur trouvé : {$user['email']} (ID: {$user['id']})\n\n";
    
    // Compter les notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notification WHERE user_id = ?");
    $stmt->execute([$userId]);
    $notificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "📊 Nombre de notifications : $notificationCount\n";
    
    if ($notificationCount > 0) {
        echo "\n3️⃣ SUPPRESSION DES NOTIFICATIONS :\n";
        echo "----------------------------------\n";
        
        echo "⚠️  ATTENTION : Suppression de $notificationCount notification(s)...\n";
        echo "Êtes-vous sûr ? (oui/non) : ";
        $confirm = trim(fgets(STDIN));
        
        if (strtolower($confirm) === 'oui') {
            // Supprimer les notifications
            $stmt = $pdo->prepare("DELETE FROM notification WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo "✅ $notificationCount notification(s) supprimée(s)\n";
            
            // Maintenant on peut supprimer l'utilisateur
            echo "\n4️⃣ SUPPRESSION DE L'UTILISATEUR :\n";
            echo "----------------------------------\n";
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            echo "✅ Utilisateur supprimé avec succès\n";
        } else {
            echo "❌ Suppression annulée\n";
        }
    } else {
        echo "✅ Aucune notification à supprimer\n";
        
        // Supprimer directement l'utilisateur
        echo "\n4️⃣ SUPPRESSION DE L'UTILISATEUR :\n";
        echo "----------------------------------\n";
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo "✅ Utilisateur supprimé avec succès\n";
    }
    
    echo "\n✅ OPÉRATION TERMINÉE\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR PDO : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
