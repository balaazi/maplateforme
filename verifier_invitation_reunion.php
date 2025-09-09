<?php
/**
 * Script pour vérifier spécifiquement l'invitation "Réunion" 
 * et diagnostiquer le problème d'affichage du statut
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/.env');
}

echo "🔍 Vérification de l'Invitation 'Réunion' - EventHub\n";
echo "==================================================\n\n";

try {
    // Initialiser le kernel Symfony
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    $container = $kernel->getContainer();
    $entityManager = $container->get('doctrine')->getManager();
    
    echo "✅ Connexion à la base de données établie\n\n";
    
    // Récupérer l'invitation "Réunion" (ID 34)
    $invitationRepo = $container->get('doctrine')->getRepository('App\Entity\Invitation');
    $invitation = $invitationRepo->find(34);
    
    if (!$invitation) {
        echo "❌ Invitation ID 34 (Réunion) non trouvée\n";
        exit;
    }
    
    $event = $invitation->getEvent();
    $eventTitle = $event ? $event->getTitle() : 'Événement inconnu';
    
    echo "📋 Invitation 'Réunion' trouvée:\n";
    echo "   - ID: {$invitation->getId()}\n";
    echo "   - Nom: {$invitation->getName()}\n";
    echo "   - Email: {$invitation->getEmail()}\n";
    echo "   - Événement: {$eventTitle}\n";
    echo "   - Statut actuel: '{$invitation->getStatus()}'\n";
    echo "   - Créée: {$invitation->getCreatedAt()->format('d/m/Y H:i:s')}\n";
    
    $updatedAt = $invitation->getUpdatedAt();
    if ($updatedAt) {
        echo "   - Mise à jour: {$updatedAt->format('d/m/Y H:i:s')}\n";
    } else {
        echo "   - Mise à jour: Jamais\n";
    }
    echo "\n";
    
    // Calculer l'âge de l'invitation
    $now = new \DateTime();
    $createdDate = $invitation->getCreatedAt();
    $daysDiff = $now->diff($createdDate)->days;
    $hoursDiff = $now->diff($createdDate)->h;
    
    echo "🔍 Analyse de l'expiration:\n";
    echo "   - Créée il y a: {$daysDiff} jour(s) et {$hoursDiff} heure(s)\n";
    echo "   - Délai d'expiration: 30 jours\n";
    
    if ($daysDiff >= 30) {
        echo "   - ⚠️ L'invitation devrait être expirée !\n\n";
        
        // Vérifier si le statut est correct
        if ($invitation->getStatus() !== 'expired') {
            echo "🔧 Correction du statut...\n";
            $oldStatus = $invitation->getStatus();
            $invitation->setStatus('expired');
            $invitation->setUpdatedAt(new \DateTime());
            
            // Sauvegarder
            $entityManager->flush();
            echo "   ✅ Statut changé de '{$oldStatus}' vers '{$invitation->getStatus()}'\n";
        } else {
            echo "   ✅ Le statut est déjà correct (EXPIRÉE)\n";
        }
        
    } else {
        echo "   - ✅ L'invitation n'est pas encore expirée\n";
        echo "   - Elle expirera dans " . (30 - $daysDiff) . " jour(s)\n";
        
        // Vérifier pourquoi elle affiche "EN ATTENTE" si elle n'est pas expirée
        if ($invitation->getStatus() === 'pending') {
            echo "   - ✅ Statut 'pending' correct pour une invitation non expirée\n";
        } else {
            echo "   - ⚠️ Statut inattendu: '{$invitation->getStatus()}'\n";
        }
    }
    
    echo "\n🔍 Vérification de l'affichage dans le template...\n";
    
    // Simuler la logique du template
    $status = $invitation->getStatus();
    $displayStatus = '';
    $statusClass = '';
    
    switch ($status) {
        case 'accepted':
            $displayStatus = 'ACCEPTÉE';
            $statusClass = 'bg-success';
            break;
        case 'declined':
            $displayStatus = 'REFUSÉE';
            $statusClass = 'bg-danger';
            break;
        case 'expired':
            $displayStatus = 'EXPIRÉE';
            $statusClass = 'bg-secondary';
            break;
        case 'conflict':
            $displayStatus = 'CONFLIT HORAIRE';
            $statusClass = 'bg-warning';
            break;
        case 'pending':
            $displayStatus = 'EN ATTENTE';
            $statusClass = 'bg-warning';
            break;
        default:
            $displayStatus = 'STATUT INCONNU';
            $statusClass = 'bg-secondary';
    }
    
    echo "   - Statut en base: '{$status}'\n";
    echo "   - Affichage template: '{$displayStatus}'\n";
    echo "   - Classe CSS: '{$statusClass}'\n";
    
    // Vérifier si l'invitation est réellement expirée selon la logique métier
    $expirationService = $container->get('App\Service\InvitationExpirationService');
    $isExpired = $expirationService->isInvitationExpired($invitation, 30);
    
    echo "\n🔍 Vérification du service d'expiration:\n";
    $isExpiredText = $isExpired ? 'OUI' : 'NON';
    echo "   - Invitation considérée comme expirée: {$isExpiredText}\n";
    
    if ($isExpired && $invitation->getStatus() !== 'expired') {
        echo "   - ⚠️ INCOHÉRENCE: L'invitation devrait être expirée mais le statut est '{$invitation->getStatus()}'\n";
        
        echo "\n🔧 Correction automatique...\n";
        $expirationService->expireInvitation($invitation);
        echo "   ✅ Invitation marquée comme expirée\n";
        
        // Vérifier la sauvegarde
        $entityManager->clear();
        $invitationCheck = $invitationRepo->find(34);
        echo "   - Vérification en base: statut = '{$invitationCheck->getStatus()}'\n";
        
        if ($invitationCheck->getStatus() === 'expired') {
            echo "   🎉 Correction réussie ! L'invitation est maintenant EXPIRÉE\n";
        } else {
            echo "   ❌ Correction échouée ! Le statut n'a pas été sauvegardé\n";
        }
    }
    
    echo "\n🔍 Vérification finale des statuts...\n";
    
    // Vérifier tous les statuts d'invitation
    $statuses = $invitationRepo->createQueryBuilder('i')
        ->select('i.status, COUNT(i.id) as count')
        ->groupBy('i.status')
        ->getQuery()
        ->getResult();
    
    echo "   Statuts actuels dans la base:\n";
    foreach ($statuses as $status) {
        $statusText = '';
        switch ($status['status']) {
            case 'pending':
                $statusText = 'EN ATTENTE';
                break;
            case 'accepted':
                $statusText = 'ACCEPTÉE';
                break;
            case 'declined':
                $statusText = 'REFUSÉE';
                break;
            case 'expired':
                $statusText = 'EXPIRÉE';
                break;
            case 'conflict':
                $statusText = 'CONFLIT HORAIRE';
                break;
            default:
                $statusText = $status['status'];
        }
        echo "   - {$statusText}: {$status['count']} invitation(s)\n";
    }
    
    echo "\n✅ Diagnostic terminé !\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
