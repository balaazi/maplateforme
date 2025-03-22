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
        // Si l'utilisateur est connecté, on le redirige en fonction de son rôle
        if ($this->getUser()) {
            return $this->redirectToRoute('redirect_after_login');
        }

        // Si l'utilisateur n'est pas connecté, afficher la page d'accueil
        return $this->render('home/index.html.twig');
    }
}
