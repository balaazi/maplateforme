<?php
// src/Form/ProfileType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'disabled' => true, // Empêche la modification
            ])            
            ->add('telephone', TelType::class, ['label' => 'Téléphone'])
            ->add('dateNaissance', DateType::class, [
                'required' => false,
                'widget' => 'single_text',  // Utilisation du widget "single_text" pour une date unique
                'attr' => ['class' => 'form-control'], // Style ou classe CSS personnalisée
            ])
            ->add('specialite', TextType::class, ['label' => 'Spécialité'])
            ->add('departement', TextType::class, ['label' => 'Département'])
            ->add('societe', TextType::class, [
                'required' => false,
                'label' => 'Société'
            ])
            ->add('photoFile', FileType::class, [
                'required' => false,
                'mapped' => false,  // ne pas mapper directement à l'entité
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '2M',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
