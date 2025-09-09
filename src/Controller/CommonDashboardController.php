<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditType;
use App\Form\InviteUserType;
use App\Repository\UserRepository;
use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\AutoExpirationService;

#[Route('/common-dashboard')]
#[IsGranted('ROLE_ORGANISATEUR')]
class CommonDashboardController extends AbstractController
{
    #[Route('/', name: 'common_dashboard')]
    public function index(
        UserRepository $userRepository, 
        EventRepository $eventRepository,
        EntityManagerInterface $em,
        AutoExpirationService $autoExpirationService
    ): Response
    {
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $isOrganisateur = in_array('ROLE_ORGANISATEUR', $user->getRoles());
        
        // Vérifier et expirer automatiquement les invitations expirées
        $expiredCount = $autoExpirationService->checkAndExecuteExpiration();
        if ($expiredCount > 0) {
            $this->addFlash('info', "{$expiredCount} invitation(s) automatiquement marquée(s) comme expirée(s).");
        }
        
        // Statistiques globales pour tous les utilisateurs
        $now = new \DateTime();
        
        // Statistiques des événements
        $totalEvents = $eventRepository->count(['archive' => false]);
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateHeure > :now')
            ->andWhere('e.archive = false')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Statistiques des participants
        $totalParticipations = $em->getRepository('App\Entity\Participation')->count([]);
        $totalInvitations = $em->getRepository('App\Entity\Invitation')->count([]);
        $participationRate = $totalInvitations > 0 ? round(($totalParticipations / $totalInvitations) * 100) : 0;
        
        // Statistiques pour administrateur
        $adminStats = [];
        if ($isAdmin) {
            $adminStats = [
                'total_users' => $userRepository->count([]),
                'total_events' => $eventRepository->count([]),
                'recent_users' => $userRepository->findBy([], ['id' => 'DESC'], 5),
                'active_events' => $totalEvents,
                'upcoming_events' => $upcomingEvents,
                'participation_rate' => $participationRate
            ];
        }
        
        // Statistiques pour organisateur
        $organisateurStats = [];
        if ($isOrganisateur) {
            $myEvents = $eventRepository->findBy(['organizer' => $user], ['dateHeure' => 'DESC'], 5);
            $myTotalEvents = $eventRepository->count(['organizer' => $user, 'archive' => false]);
            $myUpcomingEvents = $eventRepository->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.organizer = :user')
                ->andWhere('e.dateHeure > :now')
                ->andWhere('e.archive = false')
                ->setParameter('user', $user)
                ->setParameter('now', $now)
                ->getQuery()
                ->getSingleScalarResult();
            
            // Compter mes participants
            $myParticipants = $em->getRepository('App\Entity\Participation')
                ->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->join('p.event', 'e')
                ->where('e.organizer = :user')
                ->andWhere('e.archive = false')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();
            
            $organisateurStats = [
                'my_events' => $myEvents,
                'total_my_events' => $myTotalEvents,
                'upcoming_events' => $myUpcomingEvents,
                'total_participants' => $myParticipants
            ];
        }
        
        // Statistiques globales partagées
        $globalStats = [
            'total_events' => $totalEvents,
            'upcoming_events' => $upcomingEvents,
            'total_participants' => $totalParticipations,
            'participation_rate' => $participationRate
        ];
        
        return $this->render('common_dashboard/index.html.twig', [
            'is_admin' => $isAdmin,
            'is_organisateur' => $isOrganisateur,
            'admin_stats' => $adminStats,
            'organisateur_stats' => $organisateurStats,
            'global_stats' => $globalStats,
            'user' => $user,
        ]);
    }

    // Routes pour les fonctionnalités administrateur
    #[Route('/admin/users', name: 'common_admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('common_dashboard/admin_users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/edit/{id}', name: 'common_admin_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEdit(int $id, Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        $form = $this->createForm(EditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour avec succès !');
            return $this->redirectToRoute('common_admin_users');
        }

        return $this->render('common_dashboard/admin_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/delete/{id}', name: 'common_admin_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDelete(int $id, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        try {
            $userName = $user->getFullName();
            $participationsCount = $user->getParticipations()->count();
            
            $em->remove($user);
            $em->flush();

            if ($participationsCount > 0) {
                $this->addFlash('success', "✅ Utilisateur '$userName' supprimé avec succès ! ($participationsCount participation(s) supprimée(s))");
            } else {
                $this->addFlash('success', "✅ Utilisateur '$userName' supprimé avec succès !");
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('common_admin_users');
    }

    #[Route('/admin/invite', name: 'common_admin_invite')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminInvite(Request $request, MailerInterface $mailer, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(InviteUserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            
            $user = new User();
            $user->setEmail($userData['email']);
            // Séparer le nom complet en nom et prénom
            $nameParts = explode(' ', $userData['fullName'], 2);
            $user->setPrenom($nameParts[0]);
            $user->setNom($nameParts[1] ?? '');
            $user->setRoles([$userData['role']]);
            
            $hashedPassword = $passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();

            // Envoi de l'email d'invitation
            $email = (new Email())
                ->from('nadiabalaazi@gmail.com')
                ->to($userData['email'])
                ->subject('Invitation à rejoindre EventHub')
                ->html($this->renderView('emails/invitation.html.twig', [
                    'user' => $user,
                    'password' => $userData['password']
                ]));

            $mailer->send($email);

            $this->addFlash('success', 'Utilisateur invité avec succès !');
            return $this->redirectToRoute('common_admin_users');
        }

        return $this->render('common_dashboard/admin_invite.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Routes pour les fonctionnalités organisateur supprimées

    #[Route('/organisateur/statistics', name: 'common_organisateur_statistics')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function organisateurStatistics(): Response
    {
        return $this->render('common_dashboard/organisateur_statistics.html.twig');
    }

    #[Route('/organisateur/inviter/{userId}', name: 'common_organisateur_inviter', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function organisateurInvite(Request $request, int $userId, MailerInterface $mailer): Response
    {
        $email = $request->request->get('email');

        $invitationEmail = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to($email)
            ->subject('Invitation à un événement')
            ->html('<p>Tu es invité à participer à un événement. Clique ici pour plus de détails.</p>');

        $mailer->send($invitationEmail);

        $this->addFlash('success', 'Invitation envoyée avec succès!');
        return $this->redirectToRoute('common_dashboard');
    }
} 