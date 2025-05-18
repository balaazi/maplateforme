<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService
{
private MailerInterface $mailer;
private UrlGeneratorInterface $router;

public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router)
{
$this->mailer = $mailer;
$this->router = $router;
}

// ✅ Envoi d'une invitation avec lien
public function sendInvitationEmail(string $toEmail, string $token): void
{
// Génération du lien d'inscription
$link = $this->router->generate('register', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

// Création de l'email d'invitation
$email = (new Email())
->from('nadiabalaazi@gmail.com')
->to($toEmail)
->subject('Invitation à s’inscrire')
->html("<p>Bonjour,</p><p>Vous avez été invité à vous inscrire. Cliquez sur le lien suivant :</p><p><a href='$link'>Inscription</a></p>");

// Envoi de l'email
try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email d'invitation: " . $e->getMessage());
}
}

// ✅ Envoi d’un email de rappel automatique
public function sendReminderEmail(User $guest, Event $event): void
{
$email = (new TemplatedEmail())
->from('nadiabalaazi@gmail.com')
->to($guest->getEmail())
->subject("Rappel : " . $event->getTitle())
->htmlTemplate('invitation.html.twig')  // Assurez-vous que ce fichier existe
->context([
'event' => $event,
'guest' => $guest,
]);

// Envoi de l'email
try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email de rappel: " . $e->getMessage());
}
}
}
