<?php
// src/Controller/OrganisateurController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 

class OrganisateurController extends AbstractController
{
    #[Route('/organisateur', name: 'organisateur_dashboard')]
    public function dashboard(): Response
    {
        // Seul un utilisateur avec le rôle ROLE_ORGANISATEUR pourra accéder à cette page
        $this->denyAccessUnlessGranted('ROLE_ORGANISATEUR');

        return $this->render('organisateur/dashboard.html.twig');
    }
}
