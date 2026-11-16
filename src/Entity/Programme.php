<?php

namespace App\Entity;

use App\Repository\ProgrammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgrammeRepository::class)]
class Programme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $titre = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $objectif = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $niveau = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?bool $estPublic = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateModification = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    /**
     * @var Collection<int, Utilisateur>
     */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    #[ORM\JoinTable(name: 'favori')]
    private Collection $favorisUtilisateurs;

    /**
     * @var Collection<int, JourProgramme>
     */
    #[ORM\OneToMany(targetEntity: JourProgramme::class, mappedBy: 'programme', orphanRemoval: true)]
    private Collection $jourProgrammes;

    /**
     * @var Collection<int, Noter>
     */
    #[ORM\OneToMany(targetEntity: Noter::class, mappedBy: 'programme', orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'programme', orphanRemoval: true)]
    private Collection $commentaires;

    public function __construct()
    {
        $this->favorisUtilisateurs = new ArrayCollection();
        $this->jourProgrammes = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getObjectif(): ?string
    {
        return $this->objectif;
    }

    public function setObjectif(?string $objectif): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function isEstPublic(): ?bool
    {
        return $this->estPublic;
    }

    public function setEstPublic(bool $estPublic): static
    {
        $this->estPublic = $estPublic;

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

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;

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

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getFavorisUtilisateurs(): Collection
    {
        return $this->favorisUtilisateurs;
    }

    public function addFavorisUtilisateur(Utilisateur $favorisUtilisateur): static
    {
        if (!$this->favorisUtilisateurs->contains($favorisUtilisateur)) {
            $this->favorisUtilisateurs->add($favorisUtilisateur);
        }

        return $this;
    }

    public function removeFavorisUtilisateur(Utilisateur $favorisUtilisateur): static
    {
        $this->favorisUtilisateurs->removeElement($favorisUtilisateur);

        return $this;
    }

    /**
     * @return Collection<int, JourProgramme>
     */
    public function getJourProgrammes(): Collection
    {
        return $this->jourProgrammes;
    }

    public function addJourProgramme(JourProgramme $jourProgramme): static
    {
        if (!$this->jourProgrammes->contains($jourProgramme)) {
            $this->jourProgrammes->add($jourProgramme);
            $jourProgramme->setProgramme($this);
        }

        return $this;
    }

    public function removeJourProgramme(JourProgramme $jourProgramme): static
    {
        if ($this->jourProgrammes->removeElement($jourProgramme)) {
            if ($jourProgramme->getProgramme() === $this) {
                $jourProgramme->setProgramme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Noter>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Noter $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setProgramme($this);
        }

        return $this;
    }

    public function removeNote(Noter $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getProgramme() === $this) {
                $note->setProgramme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setProgramme($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getProgramme() === $this) {
                $commentaire->setProgramme(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}
