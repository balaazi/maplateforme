<?php
// src/Form/InvitationType.php

namespace App\Form;

use App\Entity\Invitation;
use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class InvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('receiver', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'placeholder' => 'Sélectionnez un utilisateur',
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez un événement',
                'label' => 'Événement concerné'
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'label' => 'Message (optionnel)',
            ])
            ->add('response', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'pending',
                    'Acceptée' => 'accepted',
                    'Refusée' => 'declined',
                ],
                'required' => false,
                'label' => 'Réponse à l\'invitation',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Invitation::class,
        ]);
    }
}
