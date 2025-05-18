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
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Form\Type\VichFileType;

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
                'widget' => 'single_text',  
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'datetime-local', 
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
            ->add('etherpadUrl', UrlType::class, [
            'label' => 'Lien Etherpad (collaboratif)',
             'required' => false,
                     'attr' => [
            'placeholder' => 'https://etherpad.example.com/p/ton-pad'
        ]

            ])
            ->add('imageFile', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer',
                'asset_helper' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'mimeTypesMessage' => 'Merci de choisir un fichier PDF ou Word valide.',
                    ])
                ]
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
