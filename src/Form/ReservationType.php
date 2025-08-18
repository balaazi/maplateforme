<?php

namespace App\Form;

use App\Entity\Salle;
use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => function(Salle $salle) {
                    return sprintf('%s (%d personnes - %s)', 
                        $salle->getNom(), 
                        $salle->getCapacite(),
                        $salle->getTypeLabel()
                    );
                },
                'placeholder' => 'Choisissez une salle',
                'label' => 'Salle à réserver',
                'attr' => ['class' => 'form-select'],
                'help' => 'Sélectionnez la salle que vous souhaitez réserver'
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Date et heure de début de votre réservation'
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Date et heure de fin de votre réservation'
            ])
            ->add('motif', TextType::class, [
                'label' => 'Motif de la réservation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Réunion équipe, Formation, Entretien...'
                ],
                'help' => 'Décrivez brièvement l\'objet de votre réservation'
            ])
            ->add('reservePar', TextType::class, [
                'label' => 'Réservé par',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom et prénom du responsable'
                ],
                'help' => 'Nom de la personne responsable de la réservation'
            ])
            ->add('nombreParticipants', NumberType::class, [
                'label' => 'Nombre de participants',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 10'
                ],
                'help' => 'Nombre approximatif de personnes qui assisteront'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
} 