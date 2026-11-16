<?php

namespace App\Entity;

use App\Repository\MensurationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MensurationRepository::class)]
class Mensuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateMesure = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $poidsKg = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1, nullable: true)]
    private ?string $masseGrassePct = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 1, nullable: true)]
    private ?string $tourTailleCm = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1, nullable: true)]
    private ?string $tourBrasCm = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateMesure(): ?\DateTimeImmutable
    {
        return $this->dateMesure;
    }

    public function setDateMesure(\DateTimeImmutable $dateMesure): static
    {
        $this->dateMesure = $dateMesure;

        return $this;
    }

    public function getPoidsKg(): ?string
    {
        return $this->poidsKg;
    }

    public function setPoidsKg(string $poidsKg): static
    {
        $this->poidsKg = $poidsKg;

        return $this;
    }

    public function getMasseGrassePct(): ?string
    {
        return $this->masseGrassePct;
    }

    public function setMasseGrassePct(?string $masseGrassePct): static
    {
        $this->masseGrassePct = $masseGrassePct;

        return $this;
    }

    public function getTourTailleCm(): ?string
    {
        return $this->tourTailleCm;
    }

    public function setTourTailleCm(?string $tourTailleCm): static
    {
        $this->tourTailleCm = $tourTailleCm;

        return $this;
    }

    public function getTourBrasCm(): ?string
    {
        return $this->tourBrasCm;
    }

    public function setTourBrasCm(?string $tourBrasCm): static
    {
        $this->tourBrasCm = $tourBrasCm;

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
}
