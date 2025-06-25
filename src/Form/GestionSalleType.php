<?php

namespace App\Form;

use App\Entity\Salle;
use App\Entity\GestionSalle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class GestionSalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => function(Salle $salle) {
                    return $salle->getNom() . ' (Capacité: ' . $salle->getCapacite() . ' personnes)';
                },
                'placeholder' => 'Sélectionnez une salle',
                'label' => 'Salle',
                'attr' => ['class' => 'form-select']
            ])
            ->add('responsable', TextType::class, [
                'label' => 'Responsable',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du responsable']
            ])
            ->add('dateGestion', DateTimeType::class, [
                'label' => 'Date de gestion',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => 'en_cours',
                    'Terminé' => 'termine',
                    'En attente' => 'en_attente',
                    'Annulé' => 'annule'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Ajoutez un commentaire (optionnel)'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GestionSalle::class,
        ]);
    }
} 