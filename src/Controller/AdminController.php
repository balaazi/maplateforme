<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditType;
use App\Form\InviteUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\InvitationRepository;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // Route pour afficher le tableau de bord des utilisateurs
    #[Route('/', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll(); // Récupère tous les utilisateurs
        return $this->render('admin/index.html.twig', compact('users'));
    }

    // Route pour éditer un utilisateur existant
    #[Route('/edit/{id}', name: 'admin_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EditType::class, $user); // Crée le formulaire d'édition
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user); // Persiste les modifications
            $em->flush(); // Sauvegarde les modifications

            $this->addFlash('success', 'Utilisateur mis à jour avec succès !'); // Message de succès
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(), // Passe le formulaire à la vue
            'user' => $user,
        ]);
    }

    // Route pour supprimer un utilisateur
    #[Route('/delete/{id}', name: 'admin_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user); // Supprime l'utilisateur
        $em->flush(); // Applique la suppression

        $this->addFlash('success', 'Utilisateur supprimé avec succès !'); // Message de succès
        return $this->redirectToRoute('admin_dashboard');
    }

    // Route pour inviter un utilisateur
    #[Route('/invite', name: 'admin_invite')]
    public function invite(Request $request, MailerInterface $mailer, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InviteUserType::class); // Crée le formulaire d'emails
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Récupère les données du formulaire
            $email = $data->getEmail();// Récupère l'email de l'utilisateur

            // Crée l'email d'emails
            $message = (new Email())
                ->from('nadiabalaazi@gmail.com') // Remplacez par votre adresse email
                ->to($email)
                ->subject('Invitation à rejoindre notre plateforme')
                ->html('<p>Bonjour,</p><p>Vous êtes invité à rejoindre notre plateforme. Cliquez <a href="http://127.0.0.1:8000/register">ici</a> pour vous inscrire.</p>');

            // Envoie l'email
            $mailer->send($message);

            // Ajoute un message flash de succès
            $this->addFlash('success', "Invitation envoyée à $email !");
            return $this->redirectToRoute('admin_invite');
        }

        return $this->render('admin/invite.html.twig', [
            'form' => $form->createView(), // Passe le formulaire à la vue
        ]);
    }


}
