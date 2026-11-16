<?php

namespace App\Entity;

use App\Repository\CiblerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CiblerRepository::class)]
#[UniqueEntity(fields: ['exercice', 'groupeMusculaire'], message: 'Ce muscle est déjà associé à cet exercice.')]
class Cibler
{
    // Association CIBLER : identifiée par le couple (exercice, groupe musculaire)
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ciblers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'ciblers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GroupeMusculaire $groupeMusculaire = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?bool $estPrincipal = null;

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): static
    {
        $this->exercice = $exercice;

        return $this;
    }

    public function getGroupeMusculaire(): ?GroupeMusculaire
    {
        return $this->groupeMusculaire;
    }

    public function setGroupeMusculaire(?GroupeMusculaire $groupeMusculaire): static
    {
        $this->groupeMusculaire = $groupeMusculaire;

        return $this;
    }

    public function isEstPrincipal(): ?bool
    {
        return $this->estPrincipal;
    }

    public function setEstPrincipal(bool $estPrincipal): static
    {
        $this->estPrincipal = $estPrincipal;

        return $this;
    }
}
