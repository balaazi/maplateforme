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
        
        // Calculer les statistiques réelles et avancées
        $totalUsers = count($users);
        $activeEvents = $em->getRepository('App\Entity\Event')->count(['archive' => false]);
        $totalInvitations = $em->getRepository('App\Entity\Invitation')->count([]);
        $totalParticipations = $em->getRepository('App\Entity\Participation')->count([]);
        
        // Calculer le taux de participation avec limite à 100%
        $participationRate = $totalInvitations > 0 ? min(round(($totalParticipations / $totalInvitations) * 100), 100) : 0;
        
        // Statistiques par rôle avec comptage précis
        $roleStats = [
            'admin' => 0,
            'organisateur' => 0,
            'participant' => 0
        ];
        
        foreach ($users as $user) {
            $hasRole = false;
            foreach ($user->getRoles() as $role) {
                $roleName = strtolower(str_replace('ROLE_', '', $role));
                if (isset($roleStats[$roleName])) {
                    $roleStats[$roleName]++;
                    $hasRole = true;
                }
            }
            // Si l'utilisateur n'a que ROLE_USER, le compter comme participant
            if (!$hasRole) {
                $roleStats['participant']++;
            }
        }
        
        // Événements récents (7 derniers jours) avec plus de détails
        $recentEvents = $em->getRepository('App\Entity\Event')
            ->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')
            ->addSelect('o')
            ->where('e.dateHeure >= :date')
            ->andWhere('e.archive = false')
            ->setParameter('date', new \DateTime('-7 days'))
            ->orderBy('e.dateHeure', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        // Statistiques de croissance (comparaison avec le mois dernier)
        $lastMonth = new \DateTime('-1 month');
        $lastMonthUsers = $em->getRepository('App\Entity\User')
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt < :lastMonth')
            ->setParameter('lastMonth', $lastMonth)
            ->getQuery()
            ->getSingleScalarResult();
        
        $userGrowth = $lastMonthUsers > 0 ? round((($totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100) : 0;
        
        // Événements par catégorie
        $eventsByCategory = $em->getRepository('App\Entity\Event')
            ->createQueryBuilder('e')
            ->select('e.category, COUNT(e.id) as count')
            ->where('e.archive = false')
            ->groupBy('e.category')
            ->getQuery()
            ->getResult();
        
        // Utilisateurs actifs ce mois-ci
        $activeUsersThisMonth = $em->getRepository('App\Entity\User')
            ->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('u.participations', 'p')
            ->leftJoin('p.event', 'e')
            ->where('e.dateHeure >= :startOfMonth')
            ->andWhere('e.archive = false')
            ->setParameter('startOfMonth', new \DateTime('first day of this month'))
            ->getQuery()
            ->getSingleScalarResult();
        
        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeEvents' => $activeEvents,
                'totalInvitations' => $totalInvitations,
                'participationRate' => $participationRate,
                'roleStats' => $roleStats,
                'recentEvents' => $recentEvents,
                'userGrowth' => $userGrowth,
                'eventsByCategory' => $eventsByCategory,
                'activeUsersThisMonth' => $activeUsersThisMonth
            ]
        ]);
    }

    // Route pour afficher la liste des utilisateurs
    #[Route('/users', name: 'admin_users_list')]
    public function usersList(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll(); // Récupère tous les utilisateurs
        return $this->render('admin/index.html.twig', compact('users'));
    }

    // Route pour les statistiques détaillées
    #[Route('/statistics', name: 'admin_statistics')]
    public function statistics(EntityManagerInterface $em): Response
    {
        // Statistiques avancées
        $totalUsers = $em->getRepository('App\Entity\User')->count([]);
        $totalEvents = $em->getRepository('App\Entity\Event')->count([]);
        $activeEvents = $em->getRepository('App\Entity\Event')->count(['archive' => false]);
        $archivedEvents = $em->getRepository('App\Entity\Event')->count(['archive' => true]);
        $totalInvitations = $em->getRepository('App\Entity\Invitation')->count([]);
        $totalParticipations = $em->getRepository('App\Entity\Participation')->count([]);
        
        // Événements par mois (6 derniers mois)
        $monthlyEvents = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $startOfMonth = new \DateTime($date->format('Y-m-01'));
            $endOfMonth = new \DateTime($date->format('Y-m-t'));
            
            $count = $em->getRepository('App\Entity\Event')
                ->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.dateHeure >= :start')
                ->andWhere('e.dateHeure <= :end')
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult();
            
            $monthlyEvents[$date->format('M Y')] = $count;
        }
        
        // Top organisateurs
        $topOrganizers = $em->getRepository('App\Entity\Event')
            ->createQueryBuilder('e')
            ->select('u.prenom, u.nom, COUNT(e.id) as eventCount')
            ->join('e.organizer', 'u')
            ->groupBy('u.id')
            ->orderBy('eventCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/statistics.html.twig', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalEvents' => $totalEvents,
                'activeEvents' => $activeEvents,
                'archivedEvents' => $archivedEvents,
                'totalInvitations' => $totalInvitations,
                'totalParticipations' => $totalParticipations,
                'monthlyEvents' => $monthlyEvents,
                'topOrganizers' => $topOrganizers
            ]
        ]);
    }

    // Route pour éditer un utilisateur existant
    #[Route('/edit/{id}', name: 'admin_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
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
    public function delete(int $id, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
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

    // Route pour supprimer plusieurs utilisateurs
    #[Route('/delete-multiple', name: 'admin_delete_multiple', methods: ['POST'])]
    public function deleteMultiple(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $userIds = $request->request->all('user_ids');
        
        if (empty($userIds)) {
            $this->addFlash('error', '❌ Aucun utilisateur sélectionné pour la suppression.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $deletedUsers = [];
        $errors = [];
        $totalParticipations = 0;

        foreach ($userIds as $id) {
            try {
                $user = $userRepository->find($id);
                
                if ($user) {
                    $userName = $user->getFullName();
                    $participationsCount = $user->getParticipations()->count();
                    $totalParticipations += $participationsCount;
                    
                    $em->remove($user);
                    $deletedUsers[] = $userName;
                } else {
                    $errors[] = "Utilisateur avec ID $id introuvable";
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de la suppression de l'utilisateur ID $id : " . $e->getMessage();
            }
        }

        try {
            $em->flush();
            
            if (!empty($deletedUsers)) {
                $message = "✅ " . count($deletedUsers) . " utilisateur(s) supprimé(s) avec succès : " . implode(', ', $deletedUsers);
                if ($totalParticipations > 0) {
                    $message .= " ($totalParticipations participation(s) supprimée(s))";
                }
                $this->addFlash('success', $message);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la sauvegarde : ' . $e->getMessage());
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', '❌ ' . $error);
            }
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // Route pour supprimer des utilisateurs spécifiques (IDs: 15, 25, 31)
    #[Route('/delete-specific-users', name: 'admin_delete_specific_users')]
    public function deleteSpecificUsers(EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $userIds = [15, 25, 31];
        $deletedUsers = [];
        $errors = [];
        $totalParticipations = 0;

        foreach ($userIds as $id) {
            try {
                $user = $userRepository->find($id);
                
                if ($user) {
                    $userName = $user->getFullName();
                    $participationsCount = $user->getParticipations()->count();
                    $totalParticipations += $participationsCount;
                    
                    $em->remove($user);
                    $deletedUsers[] = "$userName (ID: $id)";
                } else {
                    $errors[] = "Utilisateur avec ID $id introuvable";
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur lors de la suppression de l'utilisateur ID $id : " . $e->getMessage();
            }
        }

        try {
            $em->flush();
            
            if (!empty($deletedUsers)) {
                $message = "✅ " . count($deletedUsers) . " utilisateur(s) supprimé(s) avec succès : " . implode(', ', $deletedUsers);
                if ($totalParticipations > 0) {
                    $message .= " ($totalParticipations participation(s) supprimée(s))";
                }
                $this->addFlash('success', $message);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors de la sauvegarde : ' . $e->getMessage());
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', '❌ ' . $error);
            }
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    // Route pour exporter les données utilisateurs
    #[Route('/export-users', name: 'admin_export_users')]
    public function exportUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        $csvData = "Nom,Prenom,Email,Telephone,Roles\n";
        
        foreach ($users as $user) {
            $roles = implode(', ', array_map(function($role) {
                return str_replace('ROLE_', '', $role);
            }, $user->getRoles()));
            
            $csvData .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                $user->getNom(),
                $user->getPrenom(),
                $user->getEmail(),
                $user->getTelephone(),
                $roles
            );
        }
        
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    // Route pour nettoyer les données
    #[Route('/cleanup', name: 'admin_cleanup')]
    public function cleanup(EntityManagerInterface $em): Response
    {
        try {
            // Supprimer les événements archivés de plus de 30 jours
            $thirtyDaysAgo = new \DateTime('-30 days');
            $archivedEvents = $em->getRepository('App\Entity\Event')
                ->createQueryBuilder('e')
                ->where('e.archive = :archived')
                ->andWhere('e.dateHeure < :date')
                ->setParameter('archived', true)
                ->setParameter('date', $thirtyDaysAgo)
                ->getQuery()
                ->getResult();
            
            $deletedCount = 0;
            foreach ($archivedEvents as $event) {
                $em->remove($event);
                $deletedCount++;
            }
            
            $em->flush();
            
            $this->addFlash('success', "✅ Nettoyage terminé : $deletedCount événement(s) archivé(s) supprimé(s)");
            
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors du nettoyage : ' . $e->getMessage());
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
