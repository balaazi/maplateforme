<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcesVerbalShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $participants = $options['participants'] ?? [];
        
        $participantChoices = [];
        foreach ($participants as $participant) {
            $participantChoices[$participant['name'] . ' (' . $participant['email'] . ')'] = $participant['email'];
        }
        
        $builder
            ->add('participants', ChoiceType::class, [
                'label' => 'Participants à notifier',
                'choices' => $participantChoices,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'participants-list'
                ],
                'help' => 'Sélectionnez les participants qui recevront le procès-verbal par email'
            ])
            ->add('additionalEmails', TextareaType::class, [
                'label' => 'Emails supplémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'email1@example.com' . "\n" . 'email2@example.com' . "\n" . '...'
                ],
                'help' => 'Ajoutez des adresses email supplémentaires (une par ligne)'
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer le procès-verbal',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'participants' => [],
        ]);
    }
}