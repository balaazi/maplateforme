<?php
// src/Controller/ParticipantController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/participant')]  // Route pour la page du participant
class ParticipantController extends AbstractController
{
    #[Route('/', name: 'participant_dashboard')]  // Route pour le dashboard du participant
    public function dashboard(): Response
    {
        // Vérifie que l'utilisateur a le rôle 'ROLE_PARTICIPANT'
        $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');

        return $this->render('participant/dashboard.html.twig');
    }
}
