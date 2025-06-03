<?php
namespace App\Controller;
use App\Entity\Event;
use App\Entity\Participation;
use App\Repository\ParticipationRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PARTICIPANT')]
class ParticipationController extends AbstractController
{
    #[Route('/event/{id}/presence', name: 'event_presence', methods: ['GET'])]
    public function showPresence(
        Event $event,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Vérifier d'abord s'il existe une participation
        $participation = $participationRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Si pas de participation, vérifier s'il y a une invitation acceptée
        if (!$participation) {
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            if ($invitation) {
                // Créer une nouvelle participation
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus('accepté');
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
                $entityManager->flush();
            } else {
                $this->addFlash('error', 'Vous n\'êtes pas inscrit à cet événement');
                return $this->redirectToRoute('event_list');
            }
        }

        return $this->render('event/presence.html.twig', [
            'event' => $event,
            'participation' => $participation
        ]);
    }

    #[Route('/event/{id}/mark-presence', name: 'mark_presence', methods: ['POST'])]
    public function markPresence(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        ParticipationRepository $participationRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $user = $this->getUser();
        
        // Trouver la participation de l'utilisateur pour cet événement
        $participation = $participationRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Si pas de participation, vérifier s'il y a une invitation acceptée
        if (!$participation) {
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            if ($invitation) {
                // Créer une nouvelle participation
                $participation = new Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus('accepté');
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas inscrit à cet événement'
                ], Response::HTTP_NOT_FOUND);
            }
        }

        // Récupérer la valeur de présence du formulaire
        $isPresent = $request->request->get('is_present') === 'true';
        
        // Mettre à jour la présence
        $participation->setIsPresent($isPresent);
        
        $entityManager->flush();

        // Retourner une réponse JSON
        return $this->json([
            'success' => true,
            'message' => $isPresent ? 'Présence confirmée' : 'Absence confirmée',
            'isPresent' => $isPresent
        ]);
    }
} 