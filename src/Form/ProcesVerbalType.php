<?php

namespace App\Form;

use App\Entity\ProcesVerbal;
use App\Entity\ActionPV;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcesVerbalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $participants = $options['participants'] ?? [];
        
        $participantChoices = [];
        foreach ($participants as $participant) {
            $participantChoices[$participant['name'] . ' (' . $participant['email'] . ')'] = $participant['email'];
        }
        
        $builder
            ->add('dateHeure', DateTimeType::class, [
                'label' => 'Date et heure de la réunion',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Date et heure de la réunion pour laquelle ce PV est rédigé'
            ])
            ->add('participants', ChoiceType::class, [
                'label' => 'Liste des participants',
                'choices' => $participantChoices,
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'participants-list'
                ],
                'help' => 'Sélectionnez les participants en cochant les cases correspondantes. Vous pouvez sélectionner tous les utilisateurs de la plateforme. Utilisez le bouton "Tout sélectionner" pour une sélection en masse.'
            ])
            ->add('pointsAbordes', TextareaType::class, [
                'label' => 'Points abordés',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Décrivez les différents points discutés lors de la réunion...'
                ],
                'help' => 'Résumé des sujets traités et discussions principales'
            ])
            ->add('decisionsPrises', TextareaType::class, [
                'label' => 'Décisions prises',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Listez les décisions importantes prises lors de la réunion...'
                ],
                'help' => 'Décisions validées et approuvées par les participants'
            ])
            ->add('actions', CollectionType::class, [
                'entry_type' => ActionPVType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Actions à réaliser',
                'attr' => [
                    'class' => 'actions-collection'
                ],
                'help' => 'Actions définies avec responsables et délais'
            ])
            ->add('finalise', CheckboxType::class, [
                'label' => 'Marquer comme finalisé',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Un PV finalisé ne peut plus être modifié'
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer le procès-verbal',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProcesVerbal::class,
            'participants' => [],
        ]);
    }
}