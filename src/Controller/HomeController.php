<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
{
    if ($this->getUser()) {
        return $this->redirectToRoute('redirect_after_login');
    }
    return $this->render('home/index.html.twig');
}

}