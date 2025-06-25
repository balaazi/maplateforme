<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/salle')]
class SalleRedirectController extends AbstractController
{
    #[Route('/', name: 'app_salle_redirect')]
    public function redirectToNewUrl(): Response
    {
        // Redirection permanente vers la nouvelle URL
        return $this->redirectToRoute('app_salle_index', [], 301);
    }
} 