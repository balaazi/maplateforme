<?php

namespace App\Form;

use App\Entity\Departement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepartementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('code', TextType::class, [
                'label' => 'Code',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('responsable', TextType::class, [
                'label' => 'Responsable',
                'required' => false,
            ])
            ->add('emailContact', EmailType::class, [
                'label' => 'Email de contact',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => false,
            ]) 
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Departement::class,
        ]);
    }
}