<?php

namespace App\Controller;

use App\Entity\GestionSalle;
use App\Form\GestionSalleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gestion-salle')]
#[IsGranted('ROLE_ORGANISATEUR')]
class GestionSalleController extends AbstractController
{
    #[Route('/', name: 'app_gestion_salle_index')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création du formulaire
        $gestionSalle = new GestionSalle();
        $form = $this->createForm(GestionSalleType::class, $gestionSalle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gestionSalle);
            $entityManager->flush();

            $this->addFlash('success', 'La gestion de salle a été enregistrée avec succès !');
            return $this->redirectToRoute('app_gestion_salle_index');
        }

        // Récupération de la liste des gestions de salles
        $gestionsSalles = $entityManager->getRepository(GestionSalle::class)->findBy([], ['dateGestion' => 'DESC']);

        return $this->render('gestion_salle/index.html.twig', [
            'form' => $form->createView(),
            'gestions_salles' => $gestionsSalles,
        ]);
    }
} 