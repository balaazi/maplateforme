<?php
/**
 * Script pour afficher la liste complète de tous les employés
 * EventHub - MaPlateforme
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

try {
    // Créer le kernel Symfony
    $kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', $_SERVER['APP_DEBUG'] ?? false);
    $kernel->boot();
    
    // Récupérer l'EntityManager
    $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    
    // Récupérer tous les utilisateurs
    $userRepository = $entityManager->getRepository('App\Entity\User');
    $users = $userRepository->findAll();
    
    // Récupérer tous les départements pour l'affichage
    $departementRepository = $entityManager->getRepository('App\Entity\Departement');
    $departements = $departementRepository->findAll();
    $departementsMap = [];
    foreach ($departements as $dept) {
        $departementsMap[$dept->getId()] = $dept->getNom();
    }
    
    echo "==========================================\n";
    echo "    LISTE COMPLÈTE DES EMPLOYÉS\n";
    echo "           EventHub - MaPlateforme\n";
    echo "==========================================\n\n";
    
    if (empty($users)) {
        echo "❌ Aucun employé trouvé dans la base de données.\n";
        exit;
    }
    
    echo "📊 Total des employés : " . count($users) . "\n\n";
    
    // Statistiques par rôle
    $roleStats = [];
    $deptStats = [];
    
    foreach ($users as $user) {
        // Compter les rôles
        foreach ($user->getRoles() as $role) {
            $roleName = str_replace('ROLE_', '', $role);
            $roleStats[$roleName] = ($roleStats[$roleName] ?? 0) + 1;
        }
        
        // Compter par département
        $deptName = $user->getDepartement() ? $user->getDepartement()->getNom() : 'Non assigné';
        $deptStats[$deptName] = ($deptStats[$deptName] ?? 0) + 1;
    }
    
    echo "📈 STATISTIQUES PAR RÔLE :\n";
    foreach ($roleStats as $role => $count) {
        echo "   • $role : $count employé(s)\n";
    }
    
    echo "\n🏢 STATISTIQUES PAR DÉPARTEMENT :\n";
    foreach ($deptStats as $dept => $count) {
        echo "   • $dept : $count employé(s)\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "📋 LISTE DÉTAILLÉE DES EMPLOYÉS\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Afficher chaque employé
    foreach ($users as $index => $user) {
        $num = $index + 1;
        echo "👤 EMPLOYÉ #$num\n";
        echo str_repeat("-", 40) . "\n";
        echo "ID          : " . $user->getId() . "\n";
        echo "Nom complet : " . $user->getFullName() . "\n";
        echo "Email       : " . $user->getEmail() . "\n";
        echo "Téléphone   : " . ($user->getTelephone() ?: 'Non renseigné') . "\n";
        echo "Département : " . ($user->getDepartement() ? $user->getDepartement()->getNom() : 'Non assigné') . "\n";
        echo "Société     : " . ($user->getSociete() ?: 'Non renseignée') . "\n";
        echo "Spécialité  : " . ($user->getSpecialite() ?: 'Non renseignée') . "\n";
        echo "Date naiss. : " . ($user->getDateNaissance() ? $user->getDateNaissance()->format('d/m/Y') : 'Non renseignée') . "\n";
        echo "Rôles       : " . implode(', ', array_map(function($role) {
            return str_replace('ROLE_', '', $role);
        }, $user->getRoles())) . "\n";
        echo "Créé le     : " . $user->getCreatedAt()->format('d/m/Y à H:i') . "\n";
        echo "Modifié le  : " . ($user->getUpdatedAt() ? $user->getUpdatedAt()->format('d/m/Y à H:i') : 'Jamais') . "\n";
        
        // Préférences de notifications
        echo "Notifications:\n";
        echo "   • Email : " . ($user->isNotifyByEmail() ? '✅ Activé' : '❌ Désactivé') . "\n";
        echo "   • SMS  : " . ($user->isNotifyBySms() ? '✅ Activé' : '❌ Désactivé') . "\n";
        echo "   • Son  : " . ($user->isEnableSoundNotifications() ? '✅ Activé' : '❌ Désactivé') . "\n";
        echo "   • Visu : " . ($user->isEnableVisualNotifications() ? '✅ Activé' : '❌ Désactivé') . "\n";
        echo "   • Fréquence rappels : " . $user->getReminderFrequency() . " jour(s)\n";
        echo "   • Priorité : " . $user->getNotificationPriority() . "\n";
        
        // Photo de profil
        if ($user->getPhoto()) {
            echo "Photo       : " . $user->getPhoto() . "\n";
        }
        
        // Participations aux événements
        $participations = $user->getParticipations();
        if ($participations->count() > 0) {
            echo "Événements  : " . $participations->count() . " participation(s)\n";
        }
        
        echo "\n";
    }
    
    echo str_repeat("=", 80) . "\n";
    echo "✅ AFFICHAGE TERMINÉ - " . count($users) . " employé(s) listé(s)\n";
    echo str_repeat("=", 80) . "\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne   : " . $e->getLine() . "\n";
}
?>
