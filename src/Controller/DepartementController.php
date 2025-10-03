<?php

namespace App\Controller;

use App\Entity\Departement;
use App\Form\DepartementType;
use App\Form\AssignUserToDepartementType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gestion-departements')]
#[IsGranted('ROLE_ORGANISATEUR')]
class DepartementController extends AbstractController
{
    #[Route('/', name: 'app_departement_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $departements = $entityManager->getRepository(Departement::class)->findBy([], ['nom' => 'ASC']);

        return $this->render('departement/index.html.twig', [
            'departements' => $departements,
        ]);
    }

    #[Route('/{id}/assign-user', name: 'app_departement_assign_user', methods: ['GET', 'POST'])]
    public function assignUser(Request $request, Departement $departement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AssignUserToDepartementType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User|null $user */
            $user = $form->get('user')->getData();
            if (!$user) {
                $this->addFlash('warning', 'Veuillez sélectionner un utilisateur.');
            } else {
                $user->setDepartement($departement);
                $entityManager->flush();
                $this->addFlash('success', sprintf('Utilisateur %s %s affecté au département "%s".',
                    $user->getPrenom(), $user->getNom(), $departement->getNom()
                ));
                return $this->redirectToRoute('app_departement_show', ['id' => $departement->getId()]);
            }
        }

        return $this->render('departement/assign_user.html.twig', [
            'departement' => $departement,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/new', name: 'app_departement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $departement = new Departement();
        $form = $this->createForm(DepartementType::class, $departement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departement->setUpdatedAt(new \DateTime());
            $entityManager->persist($departement);
            $entityManager->flush();

            $this->addFlash('success', 'Le département a été créé avec succès !');
            return $this->redirectToRoute('app_departement_index');
        }

        return $this->render('departement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_departement_show', methods: ['GET'])]
    public function show(Departement $departement): Response
    {
        return $this->render('departement/show.html.twig', [
            'departement' => $departement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_departement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Departement $departement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepartementType::class, $departement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $departement->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le département a été mis à jour avec succès !');
            return $this->redirectToRoute('app_departement_index');
        }

        return $this->render('departement/edit.html.twig', [
            'departement' => $departement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_departement_delete', methods: ['POST'])]
    public function delete(Request $request, Departement $departement, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $departement->getId(), $token)) {
            $entityManager->remove($departement);
            $entityManager->flush();
            $this->addFlash('success', 'Le département a été supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_departement_index');
    }
}