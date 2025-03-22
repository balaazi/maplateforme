<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditType;
use App\Form\UserType;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/admin')]  // Route de base pour /admin
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]  // Cette route est /admin
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/index.html.twig', compact('users'));
    }

    #[Route('/edit/{id}', name: 'admin_edit')]  // Route /admin/edit/{id}
    public function edit(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_delete')]  // Route /admin/delete/{id}
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('admin_dashboard');
    }
    #[Route('/invite', name: 'admin_invite')]
    public function invite(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Email du participant'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer l\'invitation'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailData = $form->get('email')->getData();

            // Génération du lien d'inscription avec un token
            $token = bin2hex(random_bytes(32));
            $url = $this->generateUrl('app_register', ['token' => $token], true);

            // Envoi de l'email
            $email = (new Email())
                ->from('nadiabalaazi@gmail.com')
                ->to($emailData)
                ->subject('Invitation à s\'inscrire')
                ->html("<p>Vous êtes invité à vous inscrire. Cliquez ici : <a href=\"http://127.0.0.1:8000/register\">S'inscrire</a></p>");

            $mailer->send($email);

            $this->addFlash('success', 'Invitation envoyée avec succès !');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/invite.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
}
