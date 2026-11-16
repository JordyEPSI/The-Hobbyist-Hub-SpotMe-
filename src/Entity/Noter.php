<?php

namespace App\Entity;

use App\Repository\NoterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NoterRepository::class)]
#[UniqueEntity(fields: ['utilisateur', 'programme'], message: 'Tu as déjà noté ce programme.')]
class Noter
{
    // Association NOTER : identifiée par le couple (utilisateur, programme)
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Programme $programme = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $note = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $dateNote = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDateNote(): ?\DateTimeImmutable
    {
        return $this->dateNote;
    }

    public function setDateNote(\DateTimeImmutable $dateNote): static
    {
        $this->dateNote = $dateNote;

        return $this;
    }
}
