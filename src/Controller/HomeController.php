<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('redirect_after_login');
        }
        return $this->render('home/simple.html.twig');
    }

    #[Route('/accueil', name: 'app_home_accueil')]
    public function homeAccueil(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('redirect_after_login');
        }
        return $this->render('home/simple.html.twig');
    }
}