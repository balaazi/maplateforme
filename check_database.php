<?php
/**
 * Script de vérification de la base de données
 */

echo "🔍 VÉRIFICATION DE LA BASE DE DONNÉES\n";
echo "=====================================\n\n";

try {
    // Connexion directe à MySQL
    $pdo = new PDO("mysql:host=localhost;dbname=eventhub", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion MySQL réussie\n\n";
    
    // Vérifier les tables
    echo "📋 TABLES DISPONIBLES :\n";
    echo "------------------------\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "  ❌ Aucune table trouvée\n";
    }
    
    echo "\n";
    
    // Vérifier spécifiquement la table document
    echo "📁 VÉRIFICATION TABLE DOCUMENT :\n";
    echo "--------------------------------\n";
    
    if (in_array('document', $tables)) {
        echo "✅ Table 'document' existe\n";
        
        // Compter les documents
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM document");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "📊 Nombre de documents : $count\n";
        
        if ($count > 0) {
            echo "\n📄 DÉTAILS DES DOCUMENTS :\n";
            $stmt = $pdo->query("SELECT * FROM document LIMIT 5");
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($documents as $doc) {
                echo "  - ID: " . $doc['id'] . ", Nom: " . $doc['file_name'] . ", Event ID: " . $doc['event_id'] . "\n";
            }
        }
    } else {
        echo "❌ Table 'document' N'EXISTE PAS\n";
        
        // Vérifier la structure de l'entité Document
        echo "\n🔍 VÉRIFICATION ENTITÉ DOCUMENT :\n";
        echo "----------------------------------\n";
        
        if (file_exists('src/Entity/Document.php')) {
            echo "✅ Fichier Document.php existe\n";
            
            // Lire le contenu pour vérifier les annotations
            $content = file_get_contents('src/Entity/Document.php');
            if (strpos($content, '#[ORM\Entity]') !== false) {
                echo "✅ Annotation #[ORM\Entity] trouvée\n";
            } else {
                echo "❌ Annotation #[ORM\Entity] manquante\n";
            }
            
            if (strpos($content, 'class Document') !== false) {
                echo "✅ Classe Document définie\n";
            } else {
                echo "❌ Classe Document manquante\n";
            }
        } else {
            echo "❌ Fichier Document.php manquant\n";
        }
    }
    
    echo "\n✅ VÉRIFICATION TERMINÉE\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR PDO : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
