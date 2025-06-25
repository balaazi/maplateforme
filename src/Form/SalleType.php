<?php

namespace App\Form;

use App\Entity\Salle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la salle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Salle de réunion A']
            ])
            ->add('capacite', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('debutReservation', DateTimeType::class, [
                'label' => 'Heure d\'ouverture',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Définit l\'heure d\'ouverture quotidienne de la salle'
            ])
            ->add('finReservation', DateTimeType::class, [
                'label' => 'Heure de fermeture',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Définit l\'heure de fermeture quotidienne de la salle'
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Salle active',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salle::class,
        ]);
    }
} 