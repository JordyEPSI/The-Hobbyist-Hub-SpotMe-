<?php

namespace App\Form;

use App\Entity\Exercice;
use App\Entity\GroupeMusculaire;
use App\Entity\Materiel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExerciceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 6],
            ])
            ->add('materiel', EntityType::class, [
                'class' => Materiel::class,
                'label' => 'Matériel',
                'placeholder' => 'Choisis un matériel',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('m')->orderBy('m.nom', 'ASC');
                },
            ])
            ->add('difficulte', ChoiceType::class, [
                'label' => 'Difficulté',
                'required' => false,
                'placeholder' => 'Non précisée',
                'choices' => [
                    'Débutant' => 'debutant',
                    'Intermédiaire' => 'intermediaire',
                    'Avancé' => 'avance',
                ],
            ])
            // Adresse https, ou image déjà copiée sur le serveur (/images/exercices/...)
            ->add('image', TextType::class, [
                'label' => 'Image (adresse https)',
                'required' => false,
                'constraints' => [new Assert\Regex(
                    pattern: '#^(https://|/images/exercices/)[^\s"<>]+$#',
                    message: 'Donne une adresse qui commence par https://',
                )],
            ])
            // Non liés à l'entité : le service CatalogueExercices crée les liens CIBLER
            ->add('musclesPrincipaux', EntityType::class, $this->optionsMuscles('Muscles principaux', true))
            ->add('musclesSecondaires', EntityType::class, $this->optionsMuscles('Muscles secondaires', false));
    }

    private function optionsMuscles(string $label, bool $obligatoire): array
    {
        $options = [
            'class' => GroupeMusculaire::class,
            'label' => $label,
            'mapped' => false,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'query_builder' => function (EntityRepository $repository) {
                return $repository->createQueryBuilder('g')->orderBy('g.nom', 'ASC');
            },
        ];

        if ($obligatoire) {
            $options['constraints'] = [new Assert\Count(min: 1, minMessage: 'Choisis au moins un muscle principal.')];
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercice::class,
        ]);
    }
}
