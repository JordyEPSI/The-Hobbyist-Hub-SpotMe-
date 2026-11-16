<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateSeance = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeMin = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $ressenti = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'seances')]
    private ?JourProgramme $jourProgramme = null;

    /**
     * @var Collection<int, Serie>
     */
    #[ORM\OneToMany(targetEntity: Serie::class, mappedBy: 'seance', orphanRemoval: true)]
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

    public function __toString(): string
    {
        return $this->dateSeance ? $this->dateSeance->format('d/m/Y') : '';
    }
}
