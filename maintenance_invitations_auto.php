<?php
// Script de maintenance automatique des invitations expirées
require_once "vendor/autoload.php";

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Invitation;
use App\Enum\InvitationStatus;

$dotenv = new Dotenv();
$dotenv->loadEnv(".env");

$kernel = new \App\Kernel($_SERVER["APP_ENV"] ?? "dev", $_SERVER["APP_DEBUG"] ?? true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get("doctrine")->getManager();
$invitationRepo = $entityManager->getRepository(Invitation::class);

// Expirer toutes les invitations en attente de plus de 1 jour
$pendingInvitations = $invitationRepo->createQueryBuilder("i")
    ->andWhere("i.status = :status")
    ->setParameter("status", "pending")
    ->getQuery()
    ->getResult();

$expiredCount = 0;
foreach ($pendingInvitations as $invitation) {
    $createdDate = $invitation->getCreatedAt();
    $now = new \DateTime();
    $daysDiff = $now->diff($createdDate)->days;
    
    if ($daysDiff >= 1) {
        $invitation->setStatus(InvitationStatus::EXPIRED->value);
        $invitation->setUpdatedAt(new \DateTime());
        $expiredCount++;
    }
}

if ($expiredCount > 0) {
    $entityManager->flush();
    echo "[" . date("Y-m-d H:i:s") . "] {$expiredCount} invitations expirées automatiquement\n";
}
