<?php

namespace App\Form;

use App\Entity\Exercice;
use App\Entity\Serie;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SerieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('exercice', EntityType::class, [
                'class' => Exercice::class,
                'label' => 'Exercice',
                'placeholder' => 'Choisis un exercice',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('e')->orderBy('e.nom', 'ASC');
                },
            ])
            ->add('repetitions', IntegerType::class, [
                'label' => 'Répétitions',
                'attr' => ['min' => 1, 'max' => 1000],
            ])
            ->add('chargeKg', NumberType::class, [
                'label' => 'Charge (kg)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'max' => 1000, 'step' => '0.5'],
            ])
            ->add('rpe', IntegerType::class, [
                'label' => 'RPE',
                'required' => false,
                'help' => 'Effort ressenti de 1 à 10',
                'attr' => ['min' => 1, 'max' => 10],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Serie::class,
        ]);
    }
}
