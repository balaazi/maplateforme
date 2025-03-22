<?php
// src/Controller/PasswordController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class PasswordController extends AbstractController
{
    /**
     * @Route("/forgot-password", name="forgot_password")
     */
    public function forgotPassword(): Response
    {
        // Logique pour la page de récupération du mot de passe
        return $this->render('password/forgot.html.twig');
    }
}
