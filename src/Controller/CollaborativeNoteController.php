<?php

namespace App\Controller;

use App\Entity\CollaborativeNote;
use App\Entity\Event;
use App\Form\CollaborativeNoteType;
use App\Repository\CollaborativeNoteRepository;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Enum\InvitationStatus;

#[Route('/collaborative-notes')]
#[IsGranted('ROLE_PARTICIPANT')]
class CollaborativeNoteController extends AbstractController
{
    private function canAccessEvent(Event $event, EntityManagerInterface $entityManager = null): bool
    {
        $user = $this->getUser();
        
        // Correction : l'administrateur a toujours accès
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // L'organisateur a toujours accès
        if ($event->getOrganizer() === $user) {
            return true;
        }

        // Vérifier si l'utilisateur est un participant
        foreach ($event->getParticipations() as $participation) {
            if ($participation->getUser() === $user) {
                return true;
            }
        }

        // Si EntityManager est fourni, vérifier les invitations acceptées
        if ($entityManager) {
            $invitationRepository = $entityManager->getRepository(\App\Entity\Invitation::class);
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            if ($invitation) {
                // Créer automatiquement une participation pour l'invitation acceptée
                $participation = new \App\Entity\Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus(InvitationStatus::ACCEPTED->value);
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
                $entityManager->flush();
                
                return true;
            }
        }

        return false;
    }

    #[Route('/event/{id}', name: 'app_collaborative_notes_list')]
    public function list(int $id, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }
        
        if (!$this->canAccessEvent($event, $entityManager)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        $notes = $event->getCollaborativeNotes();

        return $this->render('collaborative_note/list.html.twig', [
            'event' => $event,
            'notes' => $notes,
        ]);
    }

    #[Route('/event/{id}/new', name: 'app_collaborative_notes_new')]
    public function new(int $id, Request $request, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }
        
        if (!$this->canAccessEvent($event, $entityManager)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        $note = new CollaborativeNote();
        $note->setEvent($event);
        $note->setCreatedBy($this->getUser());

        $form = $this->createForm(CollaborativeNoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($note);
            $entityManager->flush();

            $this->addFlash('success', 'Note créée avec succès.');
            return $this->redirectToRoute('app_collaborative_notes_list', ['id' => $event->getId()]);
        }

        return $this->render('collaborative_note/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collaborative_notes_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, CollaborativeNoteRepository $noteRepository): Response
    {
        $note = $noteRepository->find($id);
        if (!$note) {
            throw $this->createNotFoundException('Note non trouvée.');
        }
        
        if (!$this->canAccessEvent($note->getEvent(), $entityManager)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette note.');
        }

        $form = $this->createForm(CollaborativeNoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $note->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Note mise à jour avec succès.');
            return $this->redirectToRoute('app_collaborative_notes_list', ['id' => $note->getEvent()->getId()]);
        }

        return $this->render('collaborative_note/edit.html.twig', [
            'note' => $note,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_collaborative_notes_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager, CollaborativeNoteRepository $noteRepository): Response
    {
        $note = $noteRepository->find($id);
        if (!$note) {
            throw $this->createNotFoundException('Note non trouvée.');
        }
        
        if (!$this->canAccessEvent($note->getEvent(), $entityManager)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette note.');
        }

        if ($this->isCsrfTokenValid('delete'.$note->getId(), $request->request->get('_token'))) {
            $eventId = $note->getEvent()->getId();
            $entityManager->remove($note);
            $entityManager->flush();

            $this->addFlash('success', 'Note supprimée avec succès.');
            return $this->redirectToRoute('app_collaborative_notes_list', ['id' => $eventId]);
        }

        return $this->redirectToRoute('app_collaborative_notes_list', ['id' => $note->getEvent()->getId()]);
    }
} 