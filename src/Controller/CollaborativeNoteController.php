<?php

namespace App\Controller;

use App\Entity\CollaborativeNote;
use App\Entity\Event;
use App\Form\CollaborativeNoteType;
use App\Repository\CollaborativeNoteRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collaborative-notes')]
#[IsGranted('ROLE_USER')]
class CollaborativeNoteController extends AbstractController
{
    private function canAccessEvent(Event $event): bool
    {
        $user = $this->getUser();
        
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

        return false;
    }

    #[Route('/event/{id}', name: 'app_collaborative_notes_list')]
    public function list(Event $event): Response
    {
        if (!$this->canAccessEvent($event)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        $notes = $event->getCollaborativeNotes();

        return $this->render('collaborative_note/list.html.twig', [
            'event' => $event,
            'notes' => $notes,
        ]);
    }

    #[Route('/event/{id}/new', name: 'app_collaborative_notes_new')]
    public function new(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canAccessEvent($event)) {
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
    public function edit(CollaborativeNote $note, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canAccessEvent($note->getEvent())) {
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
    public function delete(CollaborativeNote $note, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canAccessEvent($note->getEvent())) {
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