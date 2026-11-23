<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

// Création d'un compte par un administrateur : l'inscription, plus le choix du rôle
class CreationCompteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('role', ChoiceType::class, [
            'label' => 'Rôle',
            'mapped' => false,
            'expanded' => true,
            'choices' => [
                'Membre' => 'membre',
                'Administrateur' => 'admin',
            ],
            'data' => 'membre',
        ]);
    }

    public function getParent(): string
    {
        return InscriptionType::class;
    }
}
