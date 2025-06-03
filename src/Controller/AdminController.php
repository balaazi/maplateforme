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
        $em->remove($user); // Supprime l'utilisateur
        $em->flush(); // Applique la suppression

        $this->addFlash('success', 'Utilisateur supprimé avec succès !'); // Message de succès
        return $this->redirectToRoute('admin_dashboard');
    }

    // Route pour inviter un utilisateur
    #[Route('/invite', name: 'admin_invite')]
    public function invite(Request $request, MailerInterface $mailer, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InviteUserType::class); // Crée le formulaire d'invitation
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData(); // Récupère les données du formulaire (array)
            $email = $data['email']; // Récupère l'email du tableau

            try {
                // Crée l'email d'invitation
            $message = (new Email())
                ->from('nadiabalaazi@gmail.com') // Remplacez par votre adresse email
                ->to($email)
                    ->subject('🎉 Invitation à rejoindre EventHub')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
                            <div style='background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;'>
                                <h1 style='margin: 0; font-size: 24px;'>🎉 Invitation EventHub</h1>
                                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Vous êtes invité à rejoindre notre plateforme</p>
                            </div>
                            
                            <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                                <h2 style='color: #333; margin-top: 0;'>Bonjour !</h2>
                                <p style='color: #666; line-height: 1.6; font-size: 16px;'>
                                    Nous avons le plaisir de vous inviter à rejoindre <strong>EventHub</strong>, 
                                    notre plateforme de gestion d'événements collaborative.
                                </p>
                                
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='http://127.0.0.1:8000/register' 
                                       style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                                              color: white; padding: 15px 30px; text-decoration: none; 
                                              border-radius: 25px; font-weight: bold; font-size: 16px;
                                              display: inline-block; box-shadow: 0 4px 15px rgba(40,167,69,0.3);'>
                                        🚀 Créer mon compte
                                    </a>
                                </div>
                                
                                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                    <h3 style='color: #4e73df; margin-top: 0;'>✨ Avec EventHub, vous pourrez :</h3>
                                    <ul style='color: #666; line-height: 1.6;'>
                                        <li>📅 Participer à des événements exclusifs</li>
                                        <li>🤝 Collaborer avec d'autres participants</li>
                                        <li>📝 Partager des notes en temps réel</li>
                                        <li>📊 Suivre vos participations</li>
                                    </ul>
                                </div>
                                
                                <p style='color: #888; font-size: 14px; text-align: center; margin-top: 30px;'>
                                    Si vous avez des questions, n'hésitez pas à nous contacter.<br>
                                    <strong>L'équipe EventHub</strong>
                                </p>
                            </div>
                            
                            <div style='text-align: center; margin-top: 20px; color: #999; font-size: 12px;'>
                                Cet email a été envoyé depuis EventHub • 
                                <a href='http://127.0.0.1:8000' style='color: #4e73df;'>Visiter le site</a>
                            </div>
                        </div>
                    ");

            // Envoie l'email
            $mailer->send($message);

            // Ajoute un message flash de succès
                $this->addFlash('success', "✅ Invitation envoyée avec succès à $email !");
                
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
