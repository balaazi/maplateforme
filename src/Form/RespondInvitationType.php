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
        $builder
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Accepter' => InvitationStatus::ACCEPTED,
                    'Refuser' => InvitationStatus::DECLINED,
                ],
                'expanded' => true, // boutons radios
                'multiple' => false,
                'label' => 'Votre réponse',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invitation::class,
        ]);
    }
}
