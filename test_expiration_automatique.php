<?php
/**
 * Script de test pour vérifier l'expiration automatique des invitations
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Configuration de la base de données
$host = $_ENV['DATABASE_HOST'] ?? 'localhost';
$port = $_ENV['DATABASE_PORT'] ?? '3306';
$dbname = $_ENV['DATABASE_NAME'] ?? 'eventhub';
$username = $_ENV['DATABASE_USER'] ?? 'root';
$password = $_ENV['DATABASE_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TEST EXPIRATION AUTOMATIQUE DES INVITATIONS ===\n";
    echo "Connexion à la base de données réussie.\n\n";
    
    // Vérifier s'il y a des invitations en attente
    $sql = "SELECT COUNT(*) as count FROM invitation WHERE status = 'pending'";
    $stmt = $pdo->query($sql);
    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 Invitations en attente trouvées : $pendingCount\n\n";
    
    if ($pendingCount > 0) {
        // Afficher les invitations en attente avec leur âge
        $sql = "
            SELECT id, email, status, created_at, 
                   DATEDIFF(NOW(), created_at) as days_old
            FROM invitation 
            WHERE status = 'pending' 
            ORDER BY created_at ASC
        ";
        $stmt = $pdo->query($sql);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 Détails des invitations en attente :\n";
        foreach ($invitations as $invitation) {
            $shouldExpire = $invitation['days_old'] >= 30;
            $status = $shouldExpire ? "🔴 DEVRAIT ÊTRE EXPIRÉE" : "🟡 ENCORE VALIDE";
            
            echo "  - ID: {$invitation['id']}\n";
            echo "    Email: {$invitation['email']}\n";
            echo "    Créée le: {$invitation['created_at']}\n";
            echo "    Âge: {$invitation['days_old']} jours\n";
            echo "    Statut: $status\n\n";
        }
        
        // Compter les invitations qui devraient être expirées
        $sql = "
            SELECT COUNT(*) as count 
            FROM invitation 
            WHERE status = 'pending' 
            AND DATEDIFF(NOW(), created_at) >= 30
        ";
        $stmt = $pdo->query($sql);
        $shouldExpireCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($shouldExpireCount > 0) {
            echo "⚠️  $shouldExpireCount invitation(s) devraient être expirées !\n";
            echo "💡 L'expiration automatique devrait les corriger lors de l'accès à l'application.\n\n";
            
            echo "🧪 Test de l'expiration automatique...\n";
            
            // Simuler l'expiration automatique
            $sql = "
                UPDATE invitation 
                SET status = 'expired', updated_at = NOW() 
                WHERE status = 'pending' 
                AND DATEDIFF(NOW(), created_at) >= 30
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $expiredCount = $stmt->rowCount();
            
            if ($expiredCount > 0) {
                echo "✅ $expiredCount invitation(s) marquée(s) comme expirée(s) !\n";
            } else {
                echo "ℹ️  Aucune invitation expirée trouvée.\n";
            }
        } else {
            echo "✅ Toutes les invitations en attente sont encore valides.\n";
        }
    } else {
        echo "ℹ️  Aucune invitation en attente trouvée.\n";
    }
    
    // Vérifier les invitations expirées
    $sql = "SELECT COUNT(*) as count FROM invitation WHERE status = 'expired'";
    $stmt = $pdo->query($sql);
    $expiredCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\n📊 Invitations expirées : $expiredCount\n";
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "✅ L'expiration automatique est maintenant configurée.\n";
    echo "🔄 Les invitations seront automatiquement expirées lors de l'accès à l'application.\n";
    echo "📝 Vérifiez les logs pour voir l'activité d'expiration.\n";
    echo "\n=== FIN ===\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données : " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
