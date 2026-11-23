<?php

namespace App\Form;

use App\Entity\Seance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateSeance', DateType::class, [
                'label' => 'Date',
                'input' => 'datetime_immutable',
                'widget' => 'single_text',
            ])
            ->add('dureeMin', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'required' => false,
                'attr' => ['min' => 1, 'max' => 600],
            ])
            ->add('ressenti', ChoiceType::class, [
                'label' => 'Ressenti',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choices' => [
                    'Facile' => 'facile',
                    'Correcte' => 'correcte',
                    'Difficile' => 'difficile',
                    'Épuisante' => 'epuisante',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            // Lignes ajoutées et retirées en JavaScript (assets/controllers/collection_controller.js)
            ->add('series', CollectionType::class, [
                'label' => false,
                'entry_type' => SerieType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                // Sécurité : sans maximum, une requête trafiquée pourrait envoyer des milliers de séries
                'constraints' => [new Assert\Count(
                    min: 1,
                    max: 50,
                    minMessage: 'Ajoute au moins une série.',
                    maxMessage: '50 séries maximum par séance.',
                )],
            ]);

        // Le numéro de série est obligatoire : on le calcule avant la validation (priorité 10 > 0)
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $event->getData()->numeroterSeries();
        }, 10);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
        ]);
    }
}
