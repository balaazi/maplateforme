<?php
// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserType;
use App\Form\EditProfileType;
use App\Form\ChangePasswordFormType;
use App\Form\UserPreferencesType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\GlobalNotificationService;

class UserController extends AbstractController
{
// Afficher le profil de l'utilisateur
#[Route('/profile', name: 'app_profile')]
#[IsGranted('ROLE_PARTICIPANT')]
public function profile(UserRepository $userRepository): Response
{
$user = $this->getUser();

if (!$user) {
throw $this->createAccessDeniedException('Vous devez être connecté pour voir votre profil.');
}

return $this->render('user/profile.html.twig', [
'user' => $user,
]);
}

// Modifier le profil de l'utilisateur
#[Route('/user/{id}/edit', name: 'app_user_edit')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
public function edit(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, GlobalNotificationService $globalNotificationService): Response
{
$user = $userRepository->find($id);

if (!$user) {
throw $this->createNotFoundException('L\'utilisateur n\'existe pas.');
}

if ($user !== $this->getUser()) {
throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce profil.');
}

$form = $this->createForm(EditProfileType::class, $user);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
// Gestion de l'upload de la photo
$photoFile = $form->get('photoFile')->getData();
if ($photoFile) {
$newFilename = uniqid().'.'.$photoFile->guessExtension();
$uploadDir = $this->getParameter('photos_directory');
try {
$photoFile->move($uploadDir, $newFilename);
$user->setPhoto($newFilename);
$this->addFlash('success', 'Photo uploadée avec succès !');
} catch (FileException $e) {
$this->addFlash('error', 'Impossible de télécharger la photo: ' . $e->getMessage());
}
} else {
$this->addFlash('info', 'Aucune nouvelle photo sélectionnée.');
}

$entityManager->persist($user);
$entityManager->flush();

// Notification globale pour la modification du profil
try {
    $globalNotificationService->notifyPlatformModification('modifié', 'user', $user);
} catch (\Exception $e) {
    error_log('Erreur notification globale modification profil: ' . $e->getMessage());
}

$this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
return $this->redirectToRoute('app_profile');
} else {
// Afficher les erreurs de formulaire
foreach ($form->getErrors(true) as $error) {
$this->addFlash('error', $error->getMessage());
}
}

    return $this->render('user/edit_profile.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
}

// Modifier le mot de passe de l'utilisateur
#[Route('/user/change-password', name: 'app_user_change_password')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, GlobalNotificationService $globalNotificationService): Response
{
    /** @var User $user */
    $user = $this->getUser();
    
    if (!$user) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour changer votre mot de passe.');
    }

    error_log("=== DEBUT CHANGEMENT MOT DE PASSE ===");
    error_log("User ID: " . $user->getId());
    error_log("User Email: " . $user->getEmail());
    error_log("Current password hash: " . $user->getPassword());

    $form = $this->createForm(ChangePasswordFormType::class);
    $form->handleRequest($request);

    error_log("Form submitted: " . ($form->isSubmitted() ? 'YES' : 'NO'));
    
    if ($form->isSubmitted()) {
        error_log("Form valid: " . ($form->isValid() ? 'YES' : 'NO'));
        
        if ($form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            
            error_log("Plain password received (length): " . strlen($plainPassword));
            
            // Debug: Log l'ancien et le nouveau hash
            $oldPassword = $user->getPassword();
            
            // Encode the plain password
            $newHashedPassword = $userPasswordHasher->hashPassword($user, $plainPassword);
            error_log("New hashed password: " . $newHashedPassword);
            
            $user->setPassword($newHashedPassword);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                error_log("Password successfully saved to database");
                
                // Vérifier que le mot de passe a bien été sauvegardé
                $entityManager->refresh($user);
                error_log("Password in DB after refresh: " . $user->getPassword());
                
                // Notification globale pour le changement de mot de passe
                try {
                    $globalNotificationService->notifyPlatformModification('modifié', 'user', $user);
                } catch (\Exception $e) {
                    error_log('Erreur notification globale changement mot de passe: ' . $e->getMessage());
                }
                
            } catch (\Exception $e) {
                error_log("Error saving password: " . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la sauvegarde: ' . $e->getMessage());
                return $this->render('user/change_password.html.twig', [
                    'form' => $form->createView(),
                    'user' => $user,
                ]);
            }

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Veuillez vous reconnecter.');
            
            // Déconnecter l'utilisateur pour forcer une nouvelle connexion
            return $this->redirectToRoute('app_logout');
        } else {
            error_log("Form errors:");
            foreach ($form->getErrors(true) as $error) {
                error_log("  - " . $error->getMessage());
            }
        }
    }

    return $this->render('user/change_password.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
}

#[Route('/register/{token}', name: 'register')]
public function register(string $token): Response
{
// Logique pour valider le token
$user = $this->getUserByToken($token);

if (!$user) {
throw $this->createNotFoundException('Token invalide ou expiré.');
}

return $this->render('user/register.html.twig', [
'token' => $token,
]);
}

// Liste des utilisateurs (réservée aux administrateurs)
#[Route('/admin/users', name: 'app_user_list')]
#[IsGranted('ROLE_ADMIN')]
public function list(UserRepository $userRepository): Response
{
$users = $userRepository->findAll();
return $this->render('user/list.html.twig', [
'users' => $users,
]);
}

#[Route('/user/home', name: 'user_home')]
public function home(): Response
{
$user = $this->getUser();

if (!$user) {
return $this->redirectToRoute('app_login');
}

// Redirect based on user role
if (in_array('ROLE_ADMIN', $user->getRoles())) {
return $this->redirectToRoute('admin_dashboard');
} elseif (in_array('ROLE_ORGANISATEUR', $user->getRoles())) {
return $this->redirectToRoute('organisateur_dashboard');
} else {
return $this->redirectToRoute('participant_dashboard');
}
    }

    // Préférences de notifications de l'utilisateur
    #[Route('/user/preferences', name: 'app_user_preferences')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function preferences(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos préférences.');
        }

        $form = $this->createForm(UserPreferencesType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Mise à jour de la date de dernière modification
                $user->setUpdatedAt(new \DateTime());
                
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', '✅ Vos préférences de notifications ont été enregistrées avec succès !');
                
                // Optionnel : redirection pour éviter la re-soumission
                return $this->redirectToRoute('app_user_preferences');
                
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Erreur lors de la sauvegarde de vos préférences : ' . $e->getMessage());
            }
        }

        return $this->render('user/preferences.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    private function getUserByToken(string $token)
    {
        // Implémente la logique pour récupérer l'utilisateur avec le token
    }
}
