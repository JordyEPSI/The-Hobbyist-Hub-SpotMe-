<?php

namespace App\Entity;

use App\Repository\ExerciceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciceRepository::class)]
class Exercice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 120)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $difficulte = null;

    #[ORM\Column(nullable: true)]
    private ?int $idWger = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    // UTILISER (1,1) : un exercice utilise un seul matériel
    #[ORM\ManyToOne(inversedBy: 'exercices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Materiel $materiel = null;

    /**
     * @var Collection<int, Cibler>
     */
    #[ORM\OneToMany(targetEntity: Cibler::class, mappedBy: 'exercice', orphanRemoval: true)]
    private Collection $ciblers;

    /**
     * @var Collection<int, ExerciceProgramme>
     */
    #[ORM\OneToMany(targetEntity: ExerciceProgramme::class, mappedBy: 'exercice')]
    private Collection $exerciceProgrammes;

    /**
     * @var Collection<int, Serie>
     */
    #[ORM\OneToMany(targetEntity: Serie::class, mappedBy: 'exercice')]
    private Collection $series;

    public function __construct()
    {
        $this->ciblers = new ArrayCollection();
        $this->exerciceProgrammes = new ArrayCollection();
        $this->series = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(?string $difficulte): static
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getIdWger(): ?int
    {
        return $this->idWger;
    }

    public function setIdWger(?int $idWger): static
    {
        $this->idWger = $idWger;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getMateriel(): ?Materiel
    {
        return $this->materiel;
    }

    public function setMateriel(?Materiel $materiel): static
    {
        $this->materiel = $materiel;

        return $this;
    }

    /**
     * @return Collection<int, Cibler>
     */
    public function getCiblers(): Collection
    {
        return $this->ciblers;
    }

    public function addCibler(Cibler $cibler): static
    {
        if (!$this->ciblers->contains($cibler)) {
            $this->ciblers->add($cibler);
            $cibler->setExercice($this);
        }

        return $this;
    }

    public function removeCibler(Cibler $cibler): static
    {
        if ($this->ciblers->removeElement($cibler)) {
            if ($cibler->getExercice() === $this) {
                $cibler->setExercice(null);
            }
        }

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
            $exerciceProgramme->setExercice($this);
        }

        return $this;
    }

    public function removeExerciceProgramme(ExerciceProgramme $exerciceProgramme): static
    {
        if ($this->exerciceProgrammes->removeElement($exerciceProgramme)) {
            if ($exerciceProgramme->getExercice() === $this) {
                $exerciceProgramme->setExercice(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Serie>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Serie $series): static
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->setExercice($this);
        }

        return $this;
    }

    public function removeSeries(Serie $series): static
    {
        if ($this->series->removeElement($series)) {
            if ($series->getExercice() === $this) {
                $series->setExercice(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
