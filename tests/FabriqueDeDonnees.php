<?php

namespace App\Tests;

use App\Entity\Cibler;
use App\Entity\Commentaire;
use App\Entity\Exercice;
use App\Entity\ExerciceProgramme;
use App\Entity\GroupeMusculaire;
use App\Entity\JourProgramme;
use App\Entity\Materiel;
use App\Entity\Mensuration;
use App\Entity\Noter;
use App\Entity\Programme;
use App\Entity\Seance;
use App\Entity\Serie;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait FabriqueDeDonnees
{
    protected EntityManagerInterface $em;

    protected function viderLaBase(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        foreach ([Serie::class, Seance::class, Noter::class, Commentaire::class, ExerciceProgramme::class, JourProgramme::class,
            Programme::class, Mensuration::class, Cibler::class, Exercice::class, Materiel::class, GroupeMusculaire::class,
            Utilisateur::class] as $classe) {
            $this->em->createQuery('DELETE FROM '.$classe)->execute();
        }
    }

    protected function creerUtilisateur(string $pseudo, bool $admin = false): Utilisateur
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $utilisateur = (new Utilisateur())->setEmail($pseudo.'@exemple.fr')->setPseudo($pseudo);
        $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, 'motdepasse123'));
        if ($admin) {
            $utilisateur->setRoles(['ROLE_ADMIN']);
        }

        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    protected function creerMateriel(string $nom = 'Barre', string $slug = 'barre'): Materiel
    {
        $materiel = $this->em->getRepository(Materiel::class)->findOneBy(['slug' => $slug])
            ?? (new Materiel())->setNom($nom)->setSlug($slug);
        $this->em->persist($materiel);
        $this->em->flush();

        return $materiel;
    }

    protected function creerGroupe(string $nom = 'Pectoraux', string $slug = 'pectoraux'): GroupeMusculaire
    {
        $groupe = $this->em->getRepository(GroupeMusculaire::class)->findOneBy(['slug' => $slug])
            ?? (new GroupeMusculaire())->setNom($nom)->setSlug($slug);
        $this->em->persist($groupe);
        $this->em->flush();

        return $groupe;
    }

    protected function creerExercice(string $nom, string $slug, ?GroupeMusculaire $principal = null): Exercice
    {
        $exercice = (new Exercice())->setNom($nom)->setSlug($slug)
            ->setDateCreation(new \DateTimeImmutable())
            ->setMateriel($this->creerMateriel());
        $this->em->persist($exercice);
        $this->em->flush();

        if ($principal) {
            $cibler = (new Cibler())->setGroupeMusculaire($principal)->setEstPrincipal(true);
            $exercice->addCibler($cibler);
            $this->em->persist($cibler);
            $this->em->flush();
        }

        return $exercice;
    }

    protected function creerSeance(Utilisateur $utilisateur, Exercice $exercice, string $date, array $series): Seance
    {
        $seance = (new Seance())->setUtilisateur($utilisateur)->setDateSeance(new \DateTimeImmutable($date));
        foreach ($series as $i => [$repetitions, $charge]) {
            $seance->addSeries((new Serie())->setExercice($exercice)->setNumeroSerie($i + 1)
                ->setRepetitions($repetitions)->setChargeKg($charge));
        }
        $this->em->persist($seance);
        $this->em->flush();

        return $seance;
    }

    protected function creerProgramme(Utilisateur $auteur, string $titre, string $slug, bool $public): Programme
    {
        $programme = (new Programme())->setTitre($titre)->setSlug($slug)->setEstPublic($public)
            ->setDateCreation(new \DateTimeImmutable())->setUtilisateur($auteur);
        $this->em->persist($programme);
        $this->em->flush();

        return $programme;
    }
}
