<?php
// src/Controller/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\UserType;
use App\Form\EditProfileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
public function edit(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
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

private function getUserByToken(string $token)
{
// Implémente la logique pour récupérer l'utilisateur avec le token
}
}
