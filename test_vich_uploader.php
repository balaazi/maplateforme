<?php
/**
 * Test de la configuration VichUploader
 */

echo "🔍 Test de la configuration VichUploader\n";
echo "=====================================\n\n";

// 1. Vérifier la configuration VichUploader
echo "📁 Configuration VichUploader :\n";

$configFile = __DIR__ . '/config/packages/vich_uploader.yaml';
if (file_exists($configFile)) {
    echo "  ✅ Fichier de configuration existe\n";
    $config = file_get_contents($configFile);
    echo "  📋 Contenu :\n";
    echo "    " . str_replace("\n", "\n    ", $config) . "\n";
} else {
    echo "  ❌ Fichier de configuration non trouvé\n";
}

echo "\n";

// 2. Vérifier le dossier d'upload
echo "📂 Dossier d'upload :\n";
$uploadDir = __DIR__ . '/public/uploads/documents';
if (is_dir($uploadDir)) {
    echo "  ✅ Dossier existe : {$uploadDir}\n";
    echo "  🔐 Permissions : " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    echo "  ✍️ Accessible en écriture : " . (is_writable($uploadDir) ? 'Oui' : 'Non') . "\n";
} else {
    echo "  ❌ Dossier n'existe pas\n";
}

echo "\n";

// 3. Vérifier les entités
echo "🏗️ Entités :\n";

$eventFile = __DIR__ . '/src/Entity/Event.php';
if (file_exists($eventFile)) {
    echo "  ✅ Entité Event existe\n";
    $content = file_get_contents($eventFile);
    if (strpos($content, 'created_by_id') !== false) {
        echo "  ✅ Annotation ORM pour created_by_id trouvée\n";
    } else {
        echo "  ❌ Annotation ORM pour created_by_id manquante\n";
    }
} else {
    echo "  ❌ Entité Event non trouvée\n";
}

$documentFile = __DIR__ . '/src/Entity/Document.php';
if (file_exists($documentFile)) {
    echo "  ✅ Entité Document existe\n";
    $content = file_get_contents($documentFile);
    if (strpos($content, 'Vich\\Uploadable') !== false) {
        echo "  ✅ Annotation VichUploadable trouvée\n";
    } else {
        echo "  ❌ Annotation VichUploadable manquante\n";
    }
    if (strpos($content, 'Vich\\UploadableField') !== false) {
        echo "  ✅ Annotation UploadableField trouvée\n";
    } else {
        echo "  ❌ Annotation UploadableField manquante\n";
    }
} else {
    echo "  ❌ Entité Document non trouvée\n";
}

echo "\n";

// 4. Vérifier le formulaire
echo "📝 Formulaire :\n";

$formFile = __DIR__ . '/src/Form/EventFormType.php';
if (file_exists($formFile)) {
    echo "  ✅ EventFormType existe\n";
    $content = file_get_contents($formFile);
    if (strpos($content, 'imageFile') !== false) {
        echo "  ✅ Champ imageFile trouvé\n";
    } else {
        echo "  ❌ Champ imageFile manquant\n";
    }
    if (strpos($content, 'FileType::class') !== false) {
        echo "  ✅ Type FileType utilisé\n";
    } else {
        echo "  ❌ Type FileType non utilisé\n";
    }
} else {
    echo "  ❌ EventFormType non trouvé\n";
}

echo "\n";

// 5. Vérifier le contrôleur
echo "🎮 Contrôleur :\n";

$controllerFile = __DIR__ . '/src/Controller/EventController.php';
if (file_exists($controllerFile)) {
    echo "  ✅ EventController existe\n";
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'DEBUG:') !== false) {
        echo "  ✅ Logs de debug trouvés\n";
    } else {
        echo "  ❌ Logs de debug manquants\n";
    }
    if (strpos($content, 'setFile') !== false) {
        echo "  ✅ Méthode setFile trouvée\n";
    } else {
        echo "  ❌ Méthode setFile manquante\n";
    }
} else {
    echo "  ❌ EventController non trouvé\n";
}

echo "\n🏁 Test terminé\n";



