<?php

namespace App\Form;

use App\Entity\Programme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Informations générales d'un programme (les jours et exercices se placent ensuite en glisser-déposer)
class ProgrammeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, ['label' => 'Titre'])
            ->add('objectif', ChoiceType::class, [
                'label' => 'Objectif',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choices' => [
                    'Prise de masse' => 'Prise de masse',
                    'Force' => 'Force',
                    'Perte de poids' => 'Perte de poids',
                    'Remise en forme' => 'Remise en forme',
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau',
                'required' => false,
                'placeholder' => 'Non précisé',
                'choices' => [
                    'Débutant' => 'debutant',
                    'Intermédiaire' => 'intermediaire',
                    'Avancé' => 'avance',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('estPublic', CheckboxType::class, [
                'label' => 'Programme public (visible par toute la communauté)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Programme::class,
        ]);
    }
}
