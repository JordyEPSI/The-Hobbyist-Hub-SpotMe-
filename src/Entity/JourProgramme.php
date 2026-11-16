<?php

namespace App\Entity;

use App\Repository\JourProgrammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JourProgrammeRepository::class)]
class JourProgramme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'jourProgrammes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Programme $programme = null;

    /**
     * @var Collection<int, ExerciceProgramme>
     */
    #[ORM\OneToMany(targetEntity: ExerciceProgramme::class, mappedBy: 'jourProgramme', orphanRemoval: true)]
    private Collection $exerciceProgrammes;

    /**
     * @var Collection<int, Seance>
     */
    #[ORM\OneToMany(targetEntity: Seance::class, mappedBy: 'jourProgramme')]
    private Collection $seances;

    public function __construct()
    {
        $this->exerciceProgrammes = new ArrayCollection();
        $this->seances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
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

    public function getProgramme(): ?Programme
    {
        return $this->programme;
    }

    public function setProgramme(?Programme $programme): static
    {
        $this->programme = $programme;

        return $this;
    }

    /**
     * @return Collection<int, ExerciceProgramme>
     */
    public function getExerciceProgrammes(): Collection
    {
        return $this->exerciceProgrammes;
    }

    public function addExerciceProgramme(ExerciceProgramme $exerciceProgramme): static
    {
        if (!$this->exerciceProgrammes->contains($exerciceProgramme)) {
            $this->exerciceProgrammes->add($exerciceProgramme);
            $exerciceProgramme->setJourProgramme($this);
        }

        return $this;
    }

    public function removeExerciceProgramme(ExerciceProgramme $exerciceProgramme): static
    {
        if ($this->exerciceProgrammes->removeElement($exerciceProgramme)) {
            if ($exerciceProgramme->getJourProgramme() === $this) {
                $exerciceProgramme->setJourProgramme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Seance>
     */
    public function getSeances(): Collection
    {
        return $this->seances;
    }

    public function addSeance(Seance $seance): static
    {
        if (!$this->seances->contains($seance)) {
            $this->seances->add($seance);
            $seance->setJourProgramme($this);
        }

        return $this;
    }

    public function removeSeance(Seance $seance): static
    {
        if ($this->seances->removeElement($seance)) {
            if ($seance->getJourProgramme() === $this) {
                $seance->setJourProgramme(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
