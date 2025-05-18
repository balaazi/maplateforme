<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('notifyByEmail', CheckboxType::class, [
                'label' => 'Recevoir des emails',
                'required' => false,
            ])
            ->add('notifyBySms', CheckboxType::class, [
                'label' => 'Recevoir des SMS',
                'required' => false,
            ])
            ->add('reminderFrequency', ChoiceType::class, [
                'choices' => [
                    '1 heure' => 1,
                    '6 heures' => 6,
                    '24 heures' => 24,
                    '48 heures' => 48,
                ],
                'label' => 'Fréquence des rappels',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
