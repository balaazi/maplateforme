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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class SalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la salle',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Salle de réunion A']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de salle',
                'choices' => [
                    'Salle de réunion' => 'reunion',
                    'Salle de conférence' => 'conference',
                    'Salle de formation' => 'formation',
                    'Bureau' => 'bureau',
                    'Amphithéâtre' => 'amphitheatre',
                    'Atelier' => 'workshop'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('capacite', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('superficie', NumberType::class, [
                'label' => 'Superficie (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Bâtiment A, Étage 2']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Description détaillée de la salle...']
            ])
            ->add('equipements', ChoiceType::class, [
                'label' => 'Équipements disponibles',
                'choices' => [
                    'Projecteur' => 'projecteur',
                    'Écran' => 'ecran',
                    'Tableau blanc' => 'tableau_blanc',
                    'Système audio' => 'audio',
                    'Visioconférence' => 'visio',
                    'WiFi' => 'wifi',
                    'Prise électrique' => 'prise',
                    'Climatisation' => 'clim',
                    'Paperboard' => 'paperboard',
                    'Ordinateur fixe' => 'ordinateur'
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => ['class' => 'equipements-checkboxes']
            ])
            ->add('tarif', MoneyType::class, [
                'label' => 'Tarif horaire (€)',
                'required' => false,
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 1,
                    'Normale' => 2,
                    'Haute' => 3
                ],
                'attr' => ['class' => 'form-select']
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
            ->add('accessibilite', CheckboxType::class, [
                'label' => 'Accessible PMR',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Salle active',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de la salle',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF)',
                    ])
                ],
                'attr' => ['class' => 'form-control', 'accept' => 'image/*']
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