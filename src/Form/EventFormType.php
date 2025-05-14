<?php
namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('lieu', TextType::class, ['label' => 'Lieu'])
            ->add('dateHeure', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',  // Utiliser un champ unique pour le datetime
                  // Assurez-vous que le format est correct
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'datetime-local', // Cela force le champ à utiliser datetime-local
                ],
            ])
            ->add('duree', IntegerType::class, ['label' => 'Durée (minutes)'])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Réunion' => 'Réunion',
                    'Formation' => 'Formation',
                    'Séminaire' => 'Séminaire',
                    'Atelier' => 'Atelier',
                    'Conférence' => 'Conférence',
                ],
            ])
            ->add('googleDriveUrl', TextType::class, [
        'required' => false,
        'label' => 'Lien du dossier Google Drive',
    ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
