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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // Route pour afficher le tableau de bord des utilisateurs
    #[Route('/', name: 'admin_dashboard')]
    public function index(UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        // Récupère tous les utilisateurs pour la liste
        $users = $userRepository->findAll();
        
        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    // Route pour afficher la liste des utilisateurs
    #[Route('/users', name: 'admin_users_list')]
    public function usersList(UserRepository $userRepository): Response
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
        try {
            // Informations de l'utilisateur pour le message
            $userName = $user->getFullName();
            $participationsCount = $user->getParticipations()->count();
            
            // Supprime l'utilisateur (et ses participations grâce au cascade)
            $em->remove($user);
            $em->flush();

            // Message de succès avec détails
            if ($participationsCount > 0) {
                $this->addFlash('success', "✅ Utilisateur '$userName' supprimé avec succès ! ($participationsCount participation(s) supprimée(s))");
            } else {
                $this->addFlash('success', "✅ Utilisateur '$userName' supprimé avec succès !");
            }
            
        } catch (\Exception $e) {
            // Gestion des erreurs de suppression
            $this->addFlash('error', '❌ Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // Route pour inviter un utilisateur
    #[Route('/invite', name: 'admin_invite')]
    public function invite(Request $request, MailerInterface $mailer, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(InviteUserType::class); // Crée le formulaire d'invitation
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Récupère les données du formulaire (array)
            $email = $data['email']; // Récupère l'email du tableau
            $role = $data['role']; // Récupère le rôle sélectionné

            // Vérifie si l'utilisateur existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', "❌ Un utilisateur avec cet email existe déjà !");
                return $this->redirectToRoute('admin_invite');
            }

            try {
                // Génère un mot de passe temporaire
                $temporaryPassword = bin2hex(random_bytes(8)); // 16 caractères hexadécimaux
                
                // Crée le nouvel utilisateur
                $user = new User();
                $user->setEmail($email);
                $user->setNom('Utilisateur');
                $user->setPrenom('Nouveau');
                $user->setTelephone('0000000000');
                $user->setSpecialite('À définir');
                $user->setRoles([$role]);
                
                // Hash le mot de passe temporaire
                $hashedPassword = $passwordHasher->hashPassword($user, $temporaryPassword);
                $user->setPassword($hashedPassword);
                
                // Sauvegarde l'utilisateur
                $em->persist($user);
                $em->flush();

                // Détermine le nom du rôle pour l'affichage
                $roleName = match($role) {
                    'ROLE_ADMIN' => 'Administrateur',
                    'ROLE_ORGANISATEUR' => 'Organisateur',
                    'ROLE_PARTICIPANT' => 'Participant',
                    default => 'Utilisateur'
                };

                                // Premier email : Identifiants pour l'utilisateur invité
                $userMessage = (new Email())
                    ->from('nadiabalaazi@gmail.com')
                    ->to($email)
                    ->subject('Vos identifiants de connexion - EventHub')
                    ->html($this->renderView('emails/invitation.html.twig', [
                        'email' => $email,
                        'temporaryPassword' => $temporaryPassword,
                        'roleName' => $roleName
                    ]));

                // Deuxième email : Email de confirmation (envoyé au même utilisateur)
                $confirmationMessage = (new Email())
                    ->from('nadiabalaazi@gmail.com')
                    ->to($email) // Même email que l'utilisateur invité
                    ->subject('🎉 Invitation EventHub')
                    ->html($this->renderView('emails/admin_notification.html.twig', [
                        'email' => $email,
                        'roleName' => $roleName,
                        'role' => $role
                    ]));

                                // Envoie les deux emails
                try {
                    $mailer->send($userMessage);
                    $this->addFlash('info', "📧 Email avec identifiants envoyé à $email");
                } catch (\Exception $e) {
                    $this->addFlash('error', "❌ Erreur envoi email utilisateur : " . $e->getMessage());
                }
                
                try {
                    $mailer->send($confirmationMessage);
                    $this->addFlash('info', "📧 Email de confirmation envoyé à $email");
                } catch (\Exception $e) {
                    $this->addFlash('error', "❌ Erreur envoi email confirmation : " . $e->getMessage());
                }

            // Ajoute un message flash de succès
                $this->addFlash('success', "✅ Invitation envoyée avec succès à $email avec le rôle $roleName ! Compte créé automatiquement.");
                
                // Redirection pour éviter la double soumission
            return $this->redirectToRoute('admin_invite');
                
            } catch (\Exception $e) {
                // Gestion des erreurs d'envoi d'email
                $this->addFlash('error', "❌ Erreur lors de l'envoi de l'invitation : " . $e->getMessage());
            }
        }

        return $this->render('admin/invite.html.twig', [
            'form' => $form->createView(), // Passe le formulaire à la vue
        ]);
    }


}
