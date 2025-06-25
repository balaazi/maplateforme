<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Entity\Reservation;
use App\Entity\GestionSalle;
use App\Form\SalleType;
use App\Service\SalleDisponibiliteService;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;



#[Route('/gestion-salles')]
#[IsGranted('ROLE_ORGANISATEUR')]
class SalleController extends AbstractController
{
    #[Route('/', name: 'app_salle_index')]
    public function index(EntityManagerInterface $entityManager, SalleDisponibiliteService $disponibiliteService): Response
    {
        // Récupération de la liste des salles avec leur statut
        $salles = $entityManager->getRepository(Salle::class)->findAll();
        $sallesAvecStatut = [];

        foreach ($salles as $salle) {
            $statut = $disponibiliteService->getStatutActuel($salle);
            $sallesAvecStatut[] = [
                'salle' => $salle,
                'statut' => $statut
            ];
        }

        return $this->render('salle/index.html.twig', [
            'salles_avec_statut' => $sallesAvecStatut,
        ]);
    }

    #[Route('/new', name: 'app_salle_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salle = new Salle();
        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salle);
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été créée avec succès !');
            return $this->redirectToRoute('app_salle_index');
        }

        return $this->render('salle/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/test', name: 'app_salle_test')]
    public function test(EntityManagerInterface $entityManager): Response
    {
        // Création d'une nouvelle salle pour test
        $salle = new Salle();
        $salle->setNom('Salle de test');
        $salle->setCapacite(50);
        $salle->setDisponible(true);
        $salle->setDebutReservation(new \DateTime('now'));
        $salle->setFinReservation(new \DateTime('tomorrow'));

        // Sauvegarde dans la base de données
        $entityManager->persist($salle);
        $entityManager->flush();

        return $this->render('salle/test.html.twig', [
            'salle' => $salle,
        ]);
    }

    #[Route('/disponibilite/{id}', name: 'app_salle_disponibilite')]
    public function disponibilite(Salle $salle, SalleDisponibiliteService $disponibiliteService, ReservationRepository $reservationRepository): Response
    {
        $aujourd_hui = new \DateTime();
        $creneauxLibres = $disponibiliteService->getCreneauxLibres($salle, $aujourd_hui);
        $statutActuel = $disponibiliteService->getStatutActuel($salle);
        
        // Récupération des réservations du jour
        $reservationsJour = $reservationRepository->findReservationsPourJour($salle, $aujourd_hui);
        
        // Réservation actuelle et prochaine réservation
        $maintenant = new \DateTime();
        $reservationActuelle = $reservationRepository->findReservationActuelle($salle, $maintenant);
        $prochaineReservation = $reservationRepository->findProchaineReservation($salle, $maintenant);

        return $this->render('salle/disponibilite.html.twig', [
            'salle' => $salle,
            'statutActuel' => $statutActuel,
            'creneauxLibres' => $creneauxLibres,
            'reservationsJour' => $reservationsJour,
            'reservationActuelle' => $reservationActuelle,
            'prochaineReservation' => $prochaineReservation,
        ]);
    }

    #[Route('/reserver/{id}', name: 'salle_reserver', methods: ['POST'])]
    public function reserver(Salle $salle, Request $request, EntityManagerInterface $entityManager, SalleDisponibiliteService $disponibiliteService): Response
    {
        try {
            // Récupération des données du formulaire
            $dateDebutStr = $request->request->get('dateDebut');
            $dateFinStr = $request->request->get('dateFin');
            $motif = trim($request->request->get('motif'));
            $reservePar = trim($request->request->get('reservePar'));

            // Validation des données
            if (empty($dateDebutStr) || empty($dateFinStr) || empty($motif) || empty($reservePar)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_salle_disponibilite', ['id' => $salle->getId()]);
            }

            // Conversion des dates
            $dateDebut = new \DateTime($dateDebutStr);
            $dateFin = new \DateTime($dateFinStr);

            // Vérification que la date de fin est après la date de début
            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->redirectToRoute('app_salle_disponibilite', ['id' => $salle->getId()]);
            }

            // Vérification de disponibilité avec diagnostic détaillé
            if (!$disponibiliteService->estDisponible($salle, $dateDebut, $dateFin)) {
                // Diagnostic détaillé pour identifier le problème
                $diagnostic = [];
                
                if (!$salle->isDisponible()) {
                    $diagnostic[] = "❌ La salle est désactivée";
                }
                
                // Vérification des heures d'ouverture
                $heureOuverture = $salle->getDebutReservation();
                $heureFermeture = $salle->getFinReservation();
                if ($heureOuverture && $heureFermeture) {
                    $debutHeure = $dateDebut->format('H:i');
                    $finHeure = $dateFin->format('H:i');
                    $ouvertureHeure = $heureOuverture->format('H:i');
                    $fermetureHeure = $heureFermeture->format('H:i');
                    
                    $diagnostic[] = "🕒 Votre créneau : {$debutHeure} - {$finHeure}";
                    $diagnostic[] = "🏢 Heures d'ouverture : {$ouvertureHeure} - {$fermetureHeure}";
                    
                    if ($debutHeure < $ouvertureHeure || $finHeure > $fermetureHeure) {
                        $diagnostic[] = "❌ Hors des heures d'ouverture de la salle";
                    }
                }
                
                // Vérification des conflits
                $reservationRepository = $entityManager->getRepository(Reservation::class);
                $reservationsConflictuelles = $reservationRepository->findReservationsConflictuelles(
                    $salle, $dateDebut, $dateFin
                );
                
                if (!empty($reservationsConflictuelles)) {
                    $diagnostic[] = "❌ Conflit avec " . count($reservationsConflictuelles) . " réservation(s) existante(s)";
                    foreach ($reservationsConflictuelles as $reservation) {
                        $diagnostic[] = "   • {$reservation->getDateDebut()->format('d/m/Y H:i')} - {$reservation->getDateFin()->format('d/m/Y H:i')} : {$reservation->getMotif()} ({$reservation->getReservePar()})";
                    }
                }
                
                $message = "La salle n'est pas disponible pour cette période.<br><br><strong>Diagnostic :</strong><br>" . implode('<br>', $diagnostic);
                $this->addFlash('error', $message);
                return $this->redirectToRoute('app_salle_disponibilite', ['id' => $salle->getId()]);
            }

            // Création de la réservation
            $reservation = new Reservation();
            $reservation->setSalle($salle);
            $reservation->setDateDebut($dateDebut);
            $reservation->setDateFin($dateFin);
            $reservation->setMotif($motif);
            $reservation->setReservePar($reservePar);
            $reservation->setStatut('confirmee');
            $reservation->setDateCreation(new \DateTime());

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Réservation créée avec succès ! Salle "%s" réservée par %s de %s à %s pour : %s',
                $salle->getNom(),
                $reservePar,
                $dateDebut->format('d/m/Y H:i'),
                $dateFin->format('d/m/Y H:i'),
                $motif
            ));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de la réservation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_salle_disponibilite', ['id' => $salle->getId()]);
    }

    #[Route('/edit/{id}', name: 'app_salle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalleType::class, $salle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La salle a été modifiée avec succès !');
            return $this->redirectToRoute('app_salle_index');
        }

        return $this->render('salle/edit.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_salle_delete', methods: ['POST'])]
    public function delete(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$salle->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_salle_index');
        }

        try {
            // Vérifier s'il y a des dépendances
            $gestionSalles = $entityManager->getRepository(GestionSalle::class)->findBy(['salle' => $salle]);
            $reservations = $entityManager->getRepository(Reservation::class)->findBy(['salle' => $salle]);

            if (!empty($gestionSalles) || !empty($reservations)) {
                $message = 'Impossible de supprimer la salle "' . $salle->getNom() . '" car elle est utilisée dans :';
                if (!empty($gestionSalles)) {
                    $message .= '<br>• ' . count($gestionSalles) . ' attribution(s) de salle';
                }
                if (!empty($reservations)) {
                    $message .= '<br>• ' . count($reservations) . ' réservation(s)';
                }
                $message .= '<br><br>Veuillez d\'abord supprimer ces références ou désactiver la salle.';
                
                $this->addFlash('error', $message);
                return $this->redirectToRoute('app_salle_index');
            }

            // Si aucune dépendance, procéder à la suppression
            $entityManager->remove($salle);
            $entityManager->flush();

            $this->addFlash('success', 'La salle "' . $salle->getNom() . '" a été supprimée avec succès !');

        } catch (\Exception $e) {
            // Gestion d'autres erreurs possibles
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_salle_index');
    }

    #[Route('/disable/{id}', name: 'app_salle_disable', methods: ['POST'])]
    public function disable(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('disable'.$salle->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_salle_index');
        }

        $salle->setDisponible(false);
        $entityManager->flush();

        $this->addFlash('success', 'La salle "' . $salle->getNom() . '" a été désactivée avec succès !');
        return $this->redirectToRoute('app_salle_index');
    }

    #[Route('/enable/{id}', name: 'app_salle_enable', methods: ['POST'])]
    public function enable(Request $request, Salle $salle, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('enable'.$salle->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_salle_index');
        }

        $salle->setDisponible(true);
        $entityManager->flush();

        $this->addFlash('success', 'La salle "' . $salle->getNom() . '" a été activée avec succès !');
        return $this->redirectToRoute('app_salle_index');
    }

    #[Route('/calendrier', name: 'app_salle_calendar')]
    public function calendar(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ): Response {
        // Statistiques pour le calendrier
        $totalReservations = $reservationRepository->count([]);
        $sallesActives = $entityManager->getRepository(Salle::class)->count(['disponible' => true]);
        $reservationsAujourdhui = $reservationRepository->getReservationsAujourdhui();
        $tauxOccupation = 75; // À calculer avec le service

        return $this->render('salle/calendar.html.twig', [
            'totalReservations' => $totalReservations,
            'sallesActives' => $sallesActives,
            'reservationsAujourdhui' => count($reservationsAujourdhui),
            'tauxOccupation' => $tauxOccupation,
        ]);
    }
} 