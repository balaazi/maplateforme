<?php
namespace App\Form;

use App\Entity\Event;
use App\Entity\Salle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EventFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('salle', EntityType::class, [
                'class' => Salle::class,
                'choice_label' => 'nom',
                'label' => 'Salle',
                'required' => true,
                'placeholder' => 'Choisir une salle...',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Sélectionnez une salle pour votre événement'
            ])
            ->add('dateHeure', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'input' => 'datetime',
                'model_timezone' => 'Europe/Paris',
                'view_timezone' => 'Europe/Paris',
                'with_seconds' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'input_format' => 'Y-m-d H:i',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
