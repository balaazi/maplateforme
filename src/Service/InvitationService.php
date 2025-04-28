<?php
// src/Service/InvitationService.php
namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InvitationService
{
public function __construct(
private EntityManagerInterface $em,
private MailerInterface $mailer,
private UrlGeneratorInterface $urlGenerator,
) {}

public function sendInvitation(Event $event, User $user): void
{
$invitation = new Invitation();
$invitation->setEvent($event);
$invitation->setParticipant($user);
$invitation->setEmail($user->getEmail());
$invitation->setStatus(InvitationStatus::PENDING);
$this->em->persist($invitation);
$this->em->flush();

$link = $this->urlGenerator->generate('invitation_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL);

$email = (new Email())
->from('nadiabalaazi@gmail.com')
->to($user->getEmail())
->subject('Invitation à l’événement : ' . $event->getTitle())
->html("
Bonjour {$user->getFullName()},<br><br>
Vous êtes invité à l'événement : <strong>{$event->getTitle()}</strong>.<br>
Merci de répondre à votre invitation ici : <a href='{$link}'>Voir l'invitation</a><br><br>
L'équipe EvenHub.
");

$this->mailer->send($email);
}
public function cancelInvitation(Invitation $invitation): void
{
    $participant = $invitation->getParticipant();
    $event = $invitation->getEvent();

    if (!filter_var($participant->getEmail(), FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException("Adresse e-mail invalide");
    }

    $email = (new Email())
        ->from('nadiabalaazi@gmail.com')
        ->to($participant->getEmail())
        ->subject("❌ Invitation annulée : {$event->getTitle()}")
        ->html("
            <p>Bonjour {$participant->getPrenom()},</p>
            <p>L'invitation à l'événement <strong>{$event->getTitle()}</strong> a été annulée.</p>
            <p>Merci de votre compréhension.</p>
        ");

    $this->mailer->send($email);

    $this->em->remove($invitation);
    $this->em->flush();
}
}