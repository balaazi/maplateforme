<?php

namespace App\Form;

use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RespondInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('response', ChoiceType::class, [
            'choices' => [
                'Accepter' => 'accepted',
                'Refuser' => 'declined',
                'Marquer comme expiré' => 'expired',
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'label' => 'Votre réponse',
            'attr' => [
                'class' => 'response-choices'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invitation::class,
        ]);
    }
}
