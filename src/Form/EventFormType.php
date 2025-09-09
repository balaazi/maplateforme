<?php
namespace App\Form;

use App\Entity\Event;
use App\Entity\Salle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Service\SalleDisponibiliteService;
use Doctrine\ORM\EntityManagerInterface;

class EventFormType extends AbstractType
{
    private $entityManager;
    private $salleDisponibiliteService;

    public function __construct(EntityManagerInterface $entityManager, SalleDisponibiliteService $salleDisponibiliteService)
    {
        $this->entityManager = $entityManager;
        $this->salleDisponibiliteService = $salleDisponibiliteService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateHeure = $options['dateHeure'] ?? null;
        $duree = $options['duree'] ?? 60;
        
        // Récupérer toutes les salles actives
        $salles = $this->entityManager->getRepository(\App\Entity\Salle::class)->findActiveSalles();
        $sallesDisponibles = [];
        
        if ($dateHeure instanceof \DateTimeInterface) {
            // Calculer la fin de l'événement
            $fin = new \DateTime($dateHeure->format('Y-m-d H:i:s'));
            $fin->add(new \DateInterval('PT' . $duree . 'M'));
            
            // Filtrer les salles disponibles
            foreach ($salles as $salle) {
                // Vérifier que la salle n'est pas désactivée
                if (!$salle->isDisponible()) {
                    continue;
                }
                
                // Vérifier que la salle n'est pas actuellement occupée
                $maintenant = new \DateTime();
                $reservationActuelle = $this->entityManager->getRepository(\App\Entity\Reservation::class)->findReservationActuelle($salle, $maintenant);
                if ($reservationActuelle) {
                    continue;
                }
                
                // Vérifier qu'il n'y a pas de réservation prochaine
                $prochaineReservation = $this->entityManager->getRepository(\App\Entity\Reservation::class)->findProchaineReservation($salle, $maintenant);
                if ($prochaineReservation) {
                    continue;
                }
                
                // Vérifier la disponibilité pour le créneau spécifique
                if ($this->salleDisponibiliteService->estDisponible($salle, $dateHeure, $fin)) {
                    // Vérification du délai tampon (1 seconde) avant la prochaine réservation
                    $prochaineReservation = $this->entityManager->getRepository(\App\Entity\Reservation::class)->findProchaineReservation($salle, $fin);
                    if ($prochaineReservation) {
                        $diff = $fin->diff($prochaineReservation->getDateDebut());
                        $diffInSeconds = ($diff->days * 24 * 60 * 60) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
                        if ($diffInSeconds <= 1 && !$diff->invert) {
                            // Il y a une réservation qui commence dans la seconde qui suit la fin de l'événement, on exclut la salle
                            continue;
                        }
                    }
                    $sallesDisponibles[] = $salle;
                }
            }
        } else {
            // Si pas de date/heure spécifiée, proposer uniquement les salles réellement disponibles
            $sallesDisponibles = [];
            $maintenant = new \DateTime();
            foreach ($salles as $salle) {
                // Vérifier que la salle n'est pas désactivée
                if (!$salle->isDisponible()) {
                    continue;
                }
                
                // Vérifier que la salle n'est pas actuellement occupée
                $reservationActuelle = $this->entityManager->getRepository(\App\Entity\Reservation::class)->findReservationActuelle($salle, $maintenant);
                if ($reservationActuelle) {
                    continue;
                }
                
                // Vérifier qu'il n'y a pas de réservation prochaine
                $prochaineReservation = $this->entityManager->getRepository(\App\Entity\Reservation::class)->findProchaineReservation($salle, $maintenant);
                if ($prochaineReservation) {
                    continue;
                }
                
                // Si toutes les vérifications passent, la salle est disponible
                $sallesDisponibles[] = $salle;
            }
        }
        
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('salle', EntityType::class, [
                'class' => \App\Entity\Salle::class,
                'choices' => $sallesDisponibles,
                'choice_label' => 'nom',
                'label' => 'Salle',
                'required' => false,
                'placeholder' => 'Choisir une salle...',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => $dateHeure ? 'Sélectionnez une salle disponible pour votre événement' : 'Sélectionnez d\'abord une date et heure pour voir les salles disponibles'
            ])

            ->add('dateHeure', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'input' => 'datetime',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
                'with_seconds' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'input_format' => 'Y-m-d H:i',
            ])
            ->add('duree', IntegerType::class, ['label' => 'Durée (minutes)'])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Réunion' => 'Réunion',
                    'Formation' => 'Formation',
                    'Séminaire' => 'Séminaire',
                ],
                'placeholder' => 'Choisir une catégorie...',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])

            ->add('imageFile', FileType::class, [
                'label' => 'Documents (PDF, Word, Images, etc.)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '10M',
                            'mimeTypes' => [
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif'
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, Word, Image).',
                        ])
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.gif',
                    'multiple' => 'multiple'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'dateHeure' => null,
            'duree' => 60,
        ]);
    }
}
