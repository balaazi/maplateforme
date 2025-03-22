<?php
namespace App\Service;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $router;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function sendInvitationEmail(string $toEmail, string $token): void
    {
        $link = $this->router->generate('register', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to($toEmail)
            ->subject('Invitation à s’inscrire')
            ->html("<p>Bonjour,</p><p>Vous avez été invité à vous inscrire. Cliquez sur le lien suivant :</p><p><a href='$link'>Inscription</a></p>");

        $this->mailer->send($email);
    }
}
