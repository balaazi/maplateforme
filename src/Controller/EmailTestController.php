<?php
// src/Controller/EmailTestController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EmailTestController extends AbstractController
{
#[Route('/test-email', name: 'test_email')]
public function sendTestEmail(MailerInterface $mailer): Response
{
$email = (new Email())
->from('nadiabalaazi@gmail.com')
->to('wassimpiston@gmail.com') // Remplace par ton adresse réelle
->subject('Test Symfony Mailer')
->text('Ceci est un test d’envoi d’email depuis Symfony');

$mailer->send($email);

return new Response('Email envoyé avec succès (si tout va bien).');
}
}
