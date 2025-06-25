<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Entity\Reservation;
use App\Repository\SalleRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/statistiques-salles')]
#[IsGranted('ROLE_ORGANISATEUR')]
class SalleStatsController extends AbstractController
{
    #[Route('/', name: 'app_salle_stats')]
    public function index(
        EntityManagerInterface $entityManager,
        SalleRepository $salleRepository,
        ReservationRepository $reservationRepository
    ): Response {
        // Statistiques générales
        $totalSalles = $salleRepository->count([]);
        $sallesActives = $salleRepository->count(['disponible' => true]);
        $totalReservations = $reservationRepository->count([]);
        $reservationsAujourdhui = $reservationRepository->getReservationsAujourdhui();

        // Taux d'occupation
        $tauxOccupation = $this->calculerTauxOccupation($reservationRepository);

        // Salles les plus utilisées
        $sallesPlusUtilisees = $reservationRepository->getSallesPlusUtilisees(5);

        // Répartition par type de salle
        $repartitionParType = $salleRepository->getRepartitionParType();

        // Évolution des réservations (30 derniers jours)
        $evolutionReservations = $reservationRepository->getEvolutionReservations(30);

        return $this->render('salle_stats/index.html.twig', [
            'totalSalles' => $totalSalles,
            'sallesActives' => $sallesActives,
            'totalReservations' => $totalReservations,
            'reservationsAujourdhui' => count($reservationsAujourdhui),
            'tauxOccupation' => $tauxOccupation,
            'sallesPlusUtilisees' => $sallesPlusUtilisees,
            'repartitionParType' => $repartitionParType,
            'evolutionReservations' => $evolutionReservations,
        ]);
    }

    #[Route('/salle/{id}/stats', name: 'app_salle_stats_detail')]
    public function statsDetail(
        Salle $salle,
        ReservationRepository $reservationRepository
    ): Response {
        // Statistiques de la salle spécifique
        $totalReservations = $reservationRepository->count(['salle' => $salle]);
        $reservationsMois = $reservationRepository->getReservationsMois($salle);
        $tauxOccupationSalle = $this->calculerTauxOccupationSalle($salle, $reservationRepository);
        $dureesMoyennes = $reservationRepository->getDureesMoyennes($salle);
        $utilisateursFrequents = $reservationRepository->getUtilisateursFrequents($salle, 10);

        return $this->render('salle_stats/detail.html.twig', [
            'salle' => $salle,
            'totalReservations' => $totalReservations,
            'reservationsMois' => $reservationsMois,
            'tauxOccupation' => $tauxOccupationSalle,
            'dureesMoyennes' => $dureesMoyennes,
            'utilisateursFrequents' => $utilisateursFrequents,
        ]);
    }

    #[Route('/api/occupation-temps-reel', name: 'app_api_occupation_temps_reel')]
    public function occupationTempsReel(
        SalleRepository $salleRepository,
        ReservationRepository $reservationRepository
    ): JsonResponse {
        $salles = $salleRepository->findBy(['disponible' => true]);
        $occupation = [];

        foreach ($salles as $salle) {
            $reservationActuelle = $reservationRepository->findReservationActuelle($salle, new \DateTime());
            $prochaineReservation = $reservationRepository->findProchaineReservation($salle, new \DateTime());

            $occupation[] = [
                'id' => $salle->getId(),
                'nom' => $salle->getNom(),
                'capacite' => $salle->getCapacite(),
                'occupe' => $reservationActuelle !== null,
                'occupant' => $reservationActuelle?->getReservePar(),
                'finOccupation' => $reservationActuelle?->getDateFin()?->format('H:i'),
                'prochaineReservation' => $prochaineReservation?->getDateDebut()?->format('H:i'),
                'prochainOccupant' => $prochaineReservation?->getReservePar(),
            ];
        }

        return new JsonResponse($occupation);
    }

    #[Route('/export/excel', name: 'app_salle_stats_export')]
    public function exportExcel(
        ReservationRepository $reservationRepository,
        Request $request
    ): Response {
        $debut = $request->query->get('debut', (new \DateTime('-30 days'))->format('Y-m-d'));
        $fin = $request->query->get('fin', (new \DateTime())->format('Y-m-d'));

        $reservations = $reservationRepository->findByPeriode(
            new \DateTime($debut),
            new \DateTime($fin)
        );

        // Ici vous pourriez utiliser PhpSpreadsheet pour générer un Excel
        // Pour l'instant, on retourne un CSV simple
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="reservations_'.$debut.'_'.$fin.'.csv"');

        $csv = "Salle,Date début,Date fin,Réservé par,Motif,Statut,Durée (h)\n";
        
        foreach ($reservations as $reservation) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%.1f\n",
                $reservation->getSalle()->getNom(),
                $reservation->getDateDebut()->format('d/m/Y H:i'),
                $reservation->getDateFin()->format('d/m/Y H:i'),
                $reservation->getReservePar(),
                $reservation->getMotif(),
                $reservation->getStatutLabel(),
                $reservation->getDureeEnHeures()
            );
        }

        $response->setContent($csv);
        return $response;
    }

    private function calculerTauxOccupation(ReservationRepository $reservationRepository): float
    {
        // Calcul du taux d'occupation global sur les 30 derniers jours
        $debut = new \DateTime('-30 days');
        $fin = new \DateTime();
        
        $totalHeuresPossibles = $this->calculerHeuresPossibles($debut, $fin);
        $totalHeuresReservees = $reservationRepository->getTotalHeuresReservees($debut, $fin);

        return $totalHeuresPossibles > 0 ? ($totalHeuresReservees / $totalHeuresPossibles) * 100 : 0;
    }

    private function calculerTauxOccupationSalle(Salle $salle, ReservationRepository $reservationRepository): float
    {
        $debut = new \DateTime('-30 days');
        $fin = new \DateTime();
        
        $heuresPossibles = $this->calculerHeuresPossiblesSalle($salle, $debut, $fin);
        $heuresReservees = $reservationRepository->getTotalHeuresReserveesSalle($salle, $debut, $fin);

        return $heuresPossibles > 0 ? ($heuresReservees / $heuresPossibles) * 100 : 0;
    }

    private function calculerHeuresPossibles(\DateTime $debut, \DateTime $fin): float
    {
        // Calcul basé sur 10 heures par jour (8h-18h) x nombre de jours ouvrables
        $interval = $debut->diff($fin);
        $jours = $interval->days;
        return $jours * 10; // 10 heures par jour
    }

    private function calculerHeuresPossiblesSalle(Salle $salle, \DateTime $debut, \DateTime $fin): float
    {
        // Calcul spécifique à une salle
        $ouverture = $salle->getDebutReservation();
        $fermeture = $salle->getFinReservation();
        
        if (!$ouverture || !$fermeture) {
            return $this->calculerHeuresPossibles($debut, $fin);
        }

        $heuresParJour = $ouverture->diff($fermeture)->h;
        $interval = $debut->diff($fin);
        
        return $interval->days * $heuresParJour;
    }
} 