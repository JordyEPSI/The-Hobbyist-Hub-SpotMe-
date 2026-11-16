<?php

namespace App\Entity;

use App\Repository\ExerciceProgrammeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExerciceProgrammeRepository::class)]
class ExerciceProgramme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $position = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $seriesCibles = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $repsCibles = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 900)]
    private ?int $reposSecondes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'exerciceProgrammes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?JourProgramme $jourProgramme = null;

    #[ORM\ManyToOne(inversedBy: 'exerciceProgrammes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercice $exercice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getSeriesCibles(): ?int
    {
        return $this->seriesCibles;
    }

    public function setSeriesCibles(int $seriesCibles): static
    {
        $this->seriesCibles = $seriesCibles;

        return $this;
    }

    public function getRepsCibles(): ?string
    {
        return $this->repsCibles;
    }

    public function setRepsCibles(string $repsCibles): static
    {
        $this->repsCibles = $repsCibles;

        return $this;
    }

    public function getReposSecondes(): ?int
    {
        return $this->reposSecondes;
    }

    public function setReposSecondes(int $reposSecondes): static
    {
        $this->reposSecondes = $reposSecondes;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getJourProgramme(): ?JourProgramme
    {
        return $this->jourProgramme;
    }

    public function setJourProgramme(?JourProgramme $jourProgramme): static
    {
        $this->jourProgramme = $jourProgramme;

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
