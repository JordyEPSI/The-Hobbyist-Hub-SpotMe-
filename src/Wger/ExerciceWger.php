<?php

namespace App\Wger;

// Exercice reçu de l'API wger, avec les slugs de nos matériels et groupes musculaires
final class ExerciceWger
{
    public function __construct(
        public readonly int $idWger,
        public readonly string $nom,
        public readonly bool $estEnFrancais,
        public readonly ?string $description,
        public readonly ?string $image,
        public readonly string $materiel,
        public readonly array $musclesPrincipaux,
        public readonly array $musclesSecondaires,
    ) {
    }

    // Retourne une copie de l'exercice avec un autre nom
    public function avecNom(string $nom): self
    {
        return new self($this->idWger, $nom, true, $this->description, $this->image,
            $this->materiel, $this->musclesPrincipaux, $this->musclesSecondaires);
    }
}
