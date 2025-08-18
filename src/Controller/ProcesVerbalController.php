<?php

namespace App\Controller;

use App\Entity\ProcesVerbal;
use App\Entity\Event;
use App\Entity\ActionPV;
use App\Entity\User;
use App\Form\ProcesVerbalType;
use App\Form\ProcesVerbalShareType;
use App\Repository\ProcesVerbalRepository;
use App\Repository\EventRepository;
use App\Service\ProcesVerbalExportService;
use App\Service\ProcesVerbalShareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/proces-verbal')]
#[IsGranted('ROLE_ORGANISATEUR')]
class ProcesVerbalController extends AbstractController
{
    #[Route('/create/{eventId}', name: 'proces_verbal_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        EventRepository $eventRepo,
        int $eventId
    ): Response {
        $event = $eventRepo->find($eventId);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable');
            return $this->redirectToRoute('event_list');
        }

        // Vérifier que c'est bien un événement de type réunion
        if ($event->getCategory() !== 'Réunion') {
            $this->addFlash('error', 'Les procès-verbaux ne sont disponibles que pour les événements de type réunion');
            return $this->redirectToRoute('event_show', ['id' => $eventId]);
        }

        // Vérifier que l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Seul l\'organisateur de la réunion peut créer le procès-verbal');
            return $this->redirectToRoute('event_show', ['id' => $eventId]);
        }

        // Vérifier qu'il n'y a pas déjà un PV pour cet événement
        $existingPV = $em->getRepository(ProcesVerbal::class)->findByEvent($event);
        if ($existingPV) {
            $this->addFlash('info', 'Un procès-verbal existe déjà pour cette réunion');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $existingPV->getId()]);
        }

        $procesVerbal = new ProcesVerbal();
        $procesVerbal->setEvent($event);
        $procesVerbal->setRedacteur($this->getUser());
        $procesVerbal->setDateHeure($event->getDateHeure());

        // Préremplir la liste des participants avec les invités
        $participants = [];
        foreach ($event->getInvitations() as $invitation) {
            // Récupérer l'utilisateur via l'email de l'invitation
            $user = $em->getRepository(User::class)->findOneBy(['email' => $invitation->getEmail()]);
            if ($user) {
                $participants[] = $user->getPrenom() . ' ' . $user->getNom();
            } else {
                // Si pas d'utilisateur trouvé, utiliser le nom de l'invitation
                $participants[] = $invitation->getName();
            }
        }
        $procesVerbal->setParticipants(implode("\n", $participants));

        $form = $this->createForm(ProcesVerbalType::class, $procesVerbal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $procesVerbal->setDateModification(new \DateTime());
            
            $em->persist($procesVerbal);
            $em->flush();

            $this->addFlash('success', 'Procès-verbal créé avec succès');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        return $this->render('proces_verbal/create.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'procesVerbal' => $procesVerbal
        ]);
    }

    #[Route('/show/{id}', name: 'proces_verbal_show', methods: ['GET'])]
    public function show(ProcesVerbal $procesVerbal): Response
    {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        return $this->render('proces_verbal/show.html.twig', [
            'procesVerbal' => $procesVerbal,
            'event' => $procesVerbal->getEvent()
        ]);
    }

    #[Route('/edit/{id}', name: 'proces_verbal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        ProcesVerbal $procesVerbal
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Seul le rédacteur peut modifier ce procès-verbal');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        // Vérifier que le PV n'est pas finalisé
        if ($procesVerbal->isFinalise()) {
            $this->addFlash('error', 'Ce procès-verbal est finalisé et ne peut plus être modifié');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        $form = $this->createForm(ProcesVerbalType::class, $procesVerbal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $procesVerbal->setDateModification(new \DateTime());
            
            $em->flush();

            $this->addFlash('success', 'Procès-verbal modifié avec succès');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        return $this->render('proces_verbal/edit.html.twig', [
            'form' => $form->createView(),
            'procesVerbal' => $procesVerbal,
            'event' => $procesVerbal->getEvent()
        ]);
    }

    #[Route('/list', name: 'proces_verbal_list', methods: ['GET'])]
    public function list(ProcesVerbalRepository $pvRepo): Response
    {
        $user = $this->getUser();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            $procesVerbaux = $pvRepo->findAll();
        } else {
            $procesVerbaux = $pvRepo->findByRedacteur($user);
        }

        return $this->render('proces_verbal/list.html.twig', [
            'procesVerbaux' => $procesVerbaux
        ]);
    }

    #[Route('/delete/{id}', name: 'proces_verbal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $em,
        ProcesVerbal $procesVerbal
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Seul le rédacteur peut supprimer ce procès-verbal');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        // Vérifier que le PV n'est pas finalisé
        if ($procesVerbal->isFinalise()) {
            $this->addFlash('error', 'Ce procès-verbal est finalisé et ne peut pas être supprimé');
            return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$procesVerbal->getId(), $request->request->get('_token'))) {
            $eventId = $procesVerbal->getEvent()->getId();
            $em->remove($procesVerbal);
            $em->flush();
            
            $this->addFlash('success', 'Procès-verbal supprimé avec succès');
            return $this->redirectToRoute('event_show', ['id' => $eventId]);
        }

        $this->addFlash('error', 'Token CSRF invalide');
        return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
    }

    #[Route('/export/pdf/{id}', name: 'proces_verbal_export_pdf', methods: ['GET'])]
    public function exportPdf(
        ProcesVerbal $procesVerbal,
        ProcesVerbalExportService $exportService
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        return $exportService->exportToPdf($procesVerbal);
    }

    #[Route('/export/word/{id}', name: 'proces_verbal_export_word', methods: ['GET'])]
    public function exportWord(
        ProcesVerbal $procesVerbal,
        ProcesVerbalExportService $exportService
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        return $exportService->exportToWord($procesVerbal);
    }

    #[Route('/export/print/{id}', name: 'proces_verbal_export_print', methods: ['GET'])]
    public function exportPrint(
        ProcesVerbal $procesVerbal,
        ProcesVerbalExportService $exportService
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        $html = $exportService->exportToHtml($procesVerbal);
        
        return new Response($html, 200, [
            'Content-Type' => 'text/html'
        ]);
    }

    #[Route('/share/{id}', name: 'proces_verbal_share', methods: ['GET', 'POST'])]
    public function share(
        Request $request,
        ProcesVerbal $procesVerbal,
        ProcesVerbalShareService $shareService
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        $participants = $shareService->getEligibleParticipants($procesVerbal);
        
        $form = $this->createForm(ProcesVerbalShareType::class, null, [
            'participants' => $participants
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $selectedParticipants = $data['participants'] ?? [];
            $additionalEmails = $data['additionalEmails'] ?? '';
            
            $success = false;
            $emailsSent = 0;

            // Envoyer aux participants sélectionnés
            if (!empty($selectedParticipants)) {
                $users = [];
                foreach ($participants as $participant) {
                    if (in_array($participant['email'], $selectedParticipants)) {
                        $users[] = $participant['user'];
                    }
                }
                
                if ($shareService->sendToUsers($procesVerbal, $users)) {
                    $emailsSent += count($users);
                    $success = true;
                }
            }

            // Envoyer aux emails supplémentaires
            if (!empty($additionalEmails)) {
                $emails = array_filter(array_map('trim', explode("\n", $additionalEmails)));
                $validEmails = array_filter($emails, function($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                });
                
                if (!empty($validEmails)) {
                    if ($shareService->sendToEmails($procesVerbal, $validEmails)) {
                        $emailsSent += count($validEmails);
                        $success = true;
                    }
                }
            }

            if ($success) {
                $this->addFlash('success', "Procès-verbal envoyé avec succès à {$emailsSent} destinataire(s)");
                return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'envoi du procès-verbal');
            }
        }

        return $this->render('proces_verbal/share.html.twig', [
            'form' => $form->createView(),
            'procesVerbal' => $procesVerbal,
            'event' => $procesVerbal->getEvent(),
            'participants' => $participants
        ]);
    }

    #[Route('/share-participants/{id}', name: 'proces_verbal_share_all', methods: ['POST'])]
    public function shareWithAllParticipants(
        ProcesVerbal $procesVerbal,
        ProcesVerbalShareService $shareService
    ): Response {
        // Vérifier les droits d'accès
        if ($procesVerbal->getRedacteur() !== $this->getUser() && 
            $procesVerbal->getEvent()->getOrganizer() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas accès à ce procès-verbal');
            return $this->redirectToRoute('event_list');
        }

        if ($shareService->shareWithParticipants($procesVerbal)) {
            $this->addFlash('success', 'Procès-verbal envoyé avec succès à tous les participants');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'envoi du procès-verbal ou aucun participant trouvé');
        }

        return $this->redirectToRoute('proces_verbal_show', ['id' => $procesVerbal->getId()]);
    }
}