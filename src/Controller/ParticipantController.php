<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; 
#[Route('/participant')] 
class ParticipantController extends AbstractController
{
    #[Route('/', name: 'participant_dashboard')]
    public function dashboard(): Response
    {
        // Vérifie que l'utilisateur a le rôle 'ROLE_PARTICIPANT'
        $this->denyAccessUnlessGranted('ROLE_PARTICIPANT');
    
        // Affichage des rôles pour débogage
        dump($this->getUser()->getRoles());
    
        // Rendu de la vue du dashboard pour les participants
        return $this->render('participant/dashboard.html.twig');
    }
    
}
