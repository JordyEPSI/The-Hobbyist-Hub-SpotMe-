<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numeroSerie = null;

    #[ORM\Column]
    private ?int $repetitions = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $chargeKg = null;

    #[ORM\Column(nullable: true)]
    private ?int $rpe = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Seance $seance = null;

    #[ORM\ManyToOne(inversedBy: 'series')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroSerie(): ?int
    {
        return $this->numeroSerie;
    }

    public function setNumeroSerie(int $numeroSerie): static
    {
        $this->numeroSerie = $numeroSerie;

        return $this;
    }

    public function getRepetitions(): ?int
    {
        return $this->repetitions;
    }

    public function setRepetitions(int $repetitions): static
    {
        $this->repetitions = $repetitions;

        return $this;
    }

    public function getChargeKg(): ?string
    {
        return $this->chargeKg;
    }

    public function setChargeKg(string $chargeKg): static
    {
        $this->chargeKg = $chargeKg;

        return $this;
    }

    public function getRpe(): ?int
    {
        return $this->rpe;
    }

    public function setRpe(?int $rpe): static
    {
        $this->rpe = $rpe;

        return $this;
    }

    public function getSeance(): ?Seance
    {
        return $this->seance;
    }

    public function setSeance(?Seance $seance): static
    {
        $this->seance = $seance;

        return $this;
    }

    public function getExercice(): ?Exercice
    {
        return $this->exercice;
    }

    public function setExercice(?Exercice $exercice): static
    {
        $this->exercice = $exercice;

        return $this;
    }
}
