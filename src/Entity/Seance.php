<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateSeance = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 600)]
    private ?int $dureeMin = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $ressenti = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $notes = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'seances')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?JourProgramme $jourProgramme = null;

    /**
     * @var Collection<int, Serie>
     */
    #[ORM\OneToMany(targetEntity: Serie::class, mappedBy: 'seance', orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['numeroSerie' => 'ASC'])]
    #[Assert\Valid]
    private Collection $series;

    public function __construct()
    {
        $this->series = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSeance(): ?\DateTimeImmutable
    {
        return $this->dateSeance;
    }

    public function setDateSeance(\DateTimeImmutable $dateSeance): static
    {
        $this->dateSeance = $dateSeance;

        return $this;
    }

    public function getDureeMin(): ?int
    {
        return $this->dureeMin;
    }

    public function setDureeMin(?int $dureeMin): static
    {
        $this->dureeMin = $dureeMin;

        return $this;
    }

    public function getRessenti(): ?string
    {
        return $this->ressenti;
    }

    public function setRessenti(?string $ressenti): static
    {
        $this->ressenti = $ressenti;

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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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
            $series->setSeance($this);
        }

        return $this;
    }

    public function removeSeries(Serie $series): static
    {
        if ($this->series->removeElement($series)) {
            if ($series->getSeance() === $this) {
                $series->setSeance(null);
            }
        }

        return $this;
    }

    // Numérote les séries de chaque exercice dans l'ordre de saisie : 1, 2, 3...
    public function numeroterSeries(): void
    {
        $compteurs = [];
        foreach ($this->series as $serie) {
            $cle = 0;
            if ($serie->getExercice() !== null) {
                $cle = $serie->getExercice()->getId();
            }

            if (!isset($compteurs[$cle])) {
                $compteurs[$cle] = 0;
            }
            $compteurs[$cle]++;
            $serie->setNumeroSerie($compteurs[$cle]);
        }
    }

    // Regroupe les séries par exercice, pour l'affichage
    public function getSeriesParExercice(): array
    {
        $groupes = [];
        foreach ($this->series as $serie) {
            $id = $serie->getExercice()->getId();
            if (!isset($groupes[$id])) {
                $groupes[$id] = ['exercice' => $serie->getExercice(), 'series' => []];
            }
            $groupes[$id]['series'][] = $serie;
        }

        return array_values($groupes);
    }

    // Volume de la séance : somme de répétitions x charge
    public function getVolumeTotal(): float
    {
        $volume = 0;
        foreach ($this->series as $serie) {
            $volume = $volume + $serie->getRepetitions() * $serie->getChargeKg();
        }

        return $volume;
    }

    public function __toString(): string
    {
        return $this->dateSeance ? $this->dateSeance->format('d/m/Y') : '';
    }
}
