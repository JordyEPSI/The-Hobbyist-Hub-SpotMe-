<?php

namespace App\Entity;

use App\Repository\GroupeMusculaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupeMusculaireRepository::class)]
class GroupeMusculaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $slug = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $zoneCorps = null;

    /**
     * @var Collection<int, Cibler>
     */
    #[ORM\OneToMany(targetEntity: Cibler::class, mappedBy: 'groupeMusculaire', orphanRemoval: true)]
    private Collection $ciblers;

    public function __construct()
    {
        $this->ciblers = new ArrayCollection();
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

    public function getZoneCorps(): ?string
    {
        return $this->zoneCorps;
    }

    public function setZoneCorps(?string $zoneCorps): static
    {
        $this->zoneCorps = $zoneCorps;

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
            $cibler->setGroupeMusculaire($this);
        }

        return $this;
    }

    public function removeCibler(Cibler $cibler): static
    {
        if ($this->ciblers->removeElement($cibler)) {
            if ($cibler->getGroupeMusculaire() === $this) {
                $cibler->setGroupeMusculaire(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
