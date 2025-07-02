<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



class RegisterController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $user = new User();
        
        // 📨 Si un email est passé en GET (depuis une invitation), on le pré-remplit
        $prefilledEmail = $request->query->get('email');
        $prefilledRole = $request->query->get('role');
        
        if ($prefilledEmail) {
            $user->setEmail($prefilledEmail);
        }
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                $this->addFlash('error', 'Le mot de passe ne peut pas être vide.');
                return $this->redirectToRoute('app_register');
            }

            // Attribution du rôle depuis l'invitation ou rôle par défaut
            if ($prefilledRole && in_array($prefilledRole, ['ROLE_ADMIN', 'ROLE_ORGANISATEUR', 'ROLE_PARTICIPANT'])) {
                $user->setRoles([$prefilledRole]);
                $roleName = match($prefilledRole) {
                    'ROLE_ADMIN' => 'Administrateur',
                    'ROLE_ORGANISATEUR' => 'Organisateur',
                    'ROLE_PARTICIPANT' => 'Participant',
                    default => 'Utilisateur'
                };
                $this->addFlash('success', "🎯 Votre compte a été créé avec le rôle $roleName !");
            } else {
                // Rôle par défaut
                $user->setRoles(['ROLE_PARTICIPANT']);
            }



           
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
