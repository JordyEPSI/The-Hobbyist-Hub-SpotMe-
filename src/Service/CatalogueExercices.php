<?php

namespace App\Service;

use App\Entity\Cibler;
use App\Entity\Exercice;
use App\Entity\GroupeMusculaire;
use App\Entity\Materiel;
use App\Repository\ExerciceRepository;
use App\Repository\GroupeMusculaireRepository;
use App\Repository\MaterielRepository;
use App\Wger\CorrespondanceWger;
use App\Wger\ExerciceWger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CatalogueExercices
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ExerciceRepository $exercices,
        private readonly MaterielRepository $materiels,
        private readonly GroupeMusculaireRepository $groupes,
        private readonly SluggerInterface $slugger,
        private readonly ImagesExercices $images,
    ) {
    }

    // Crée les matériels et groupes musculaires manquants, renvoie le nombre créé
    public function initialiserReferentiel(): int
    {
        $crees = 0;

        foreach (CorrespondanceWger::MATERIELS as $materiel) {
            if ($this->materiels->findOneBy(['slug' => $materiel[0]]) === null) {
                $this->materiel($materiel[0]);
                $crees++;
            }
        }

        foreach (CorrespondanceWger::GROUPES as $slug => $groupe) {
            if ($this->groupes->findOneBy(['slug' => $slug]) === null) {
                $this->groupe($slug);
                $crees++;
            }
        }

        return $crees;
    }

    // Enregistre un exercice (nouveau ou modifié) avec ses muscles principaux et secondaires
    public function enregistrer(Exercice $exercice, iterable $principaux = [], iterable $secondaires = []): void
    {
        $exercice->setSlug($this->slugUnique($exercice));
        if ($exercice->getDateCreation() === null) {
            $exercice->setDateCreation(new \DateTimeImmutable());
        }

        // L'exercice doit avoir un id avant de créer ses liens CIBLER (clé composite)
        $this->em->persist($exercice);
        $this->em->flush();

        $this->modifierMuscles($exercice, $principaux, $secondaires);
        $this->em->flush();
    }

    // Ajoute un exercice wger au catalogue. Retourne null s'il y est déjà
    public function importer(ExerciceWger $exerciceWger): ?Exercice
    {
        if ($this->exercices->findOneBy(['idWger' => $exerciceWger->idWger]) !== null) {
            return null;
        }

        // On recopie les informations de wger dans un nouvel exercice
        $exercice = new Exercice();
        $exercice->setNom($exerciceWger->nom);
        $exercice->setDescription($exerciceWger->description);
        $exercice->setImage($exerciceWger->image);
        $exercice->setIdWger($exerciceWger->idWger);
        $exercice->setMateriel($this->materiel($exerciceWger->materiel));

        // On retrouve (ou crée) nos groupes musculaires à partir des slugs
        $principaux = [];
        foreach ($exerciceWger->musclesPrincipaux as $slug) {
            $principaux[] = $this->groupe($slug);
        }

        $secondaires = [];
        foreach ($exerciceWger->musclesSecondaires as $slug) {
            $secondaires[] = $this->groupe($slug);
        }

        $this->enregistrer($exercice, $principaux, $secondaires);

        // Éco-conception : l'image est copiée en local, plus légère (WebP).
        // Le slug est connu seulement après enregistrer(), d'où ce deuxième flush.
        if ($this->images->copierEnLocal($exercice)) {
            $this->em->flush();
        }

        return $exercice;
    }

    // Met à jour les liens CIBLER entre l'exercice et ses groupes musculaires
    private function modifierMuscles(Exercice $exercice, iterable $principaux, iterable $secondaires): void
    {
        // id du groupe => [groupe, est principal]
        $voulus = [];
        foreach ($secondaires as $groupe) {
            $voulus[$groupe->getId()] = [$groupe, false];
        }
        foreach ($principaux as $groupe) {
            $voulus[$groupe->getId()] = [$groupe, true];
        }

        // On met à jour les liens existants au lieu de tout recréer :
        // Doctrine insère avant de supprimer, ce qui créerait un doublon de clé
        foreach ($exercice->getCiblers()->toArray() as $cibler) {
            $id = $cibler->getGroupeMusculaire()->getId();
            if (isset($voulus[$id])) {
                $cibler->setEstPrincipal($voulus[$id][1]);
                unset($voulus[$id]);
            } else {
                $exercice->removeCibler($cibler);
            }
        }

        // Il ne reste dans $voulus que les nouveaux muscles : on crée leurs liens
        foreach ($voulus as $voulu) {
            $cibler = new Cibler();
            $cibler->setGroupeMusculaire($voulu[0]);
            $cibler->setEstPrincipal($voulu[1]);
            $exercice->addCibler($cibler);
            $this->em->persist($cibler);
        }
    }

    // Retourne un slug libre à partir du nom : squat, puis squat-2, squat-3...
    private function slugUnique(Exercice $exercice): string
    {
        $base = $this->slugger->slug($exercice->getNom())->lower()->truncate(110)->toString();
        $slug = $base;
        $numero = 2;

        $existant = $this->exercices->findOneBy(['slug' => $slug]);
        while ($existant !== null && $existant !== $exercice) {
            $slug = $base.'-'.$numero;
            $numero++;
            $existant = $this->exercices->findOneBy(['slug' => $slug]);
        }

        return $slug;
    }

    // Retourne le matériel correspondant au slug, et le crée s'il n'existe pas
    private function materiel(string $slug): Materiel
    {
        $materiel = $this->materiels->findOneBy(['slug' => $slug]);
        if ($materiel === null) {
            $materiel = new Materiel();
            $materiel->setSlug($slug);
            $materiel->setNom(CorrespondanceWger::nomMateriel($slug));
            $this->em->persist($materiel);
            $this->em->flush();
        }

        return $materiel;
    }

    // Retourne le groupe musculaire correspondant au slug, et le crée s'il n'existe pas
    private function groupe(string $slug): GroupeMusculaire
    {
        $groupe = $this->groupes->findOneBy(['slug' => $slug]);
        if ($groupe === null) {
            $groupe = new GroupeMusculaire();
            $groupe->setSlug($slug);
            $groupe->setNom(CorrespondanceWger::GROUPES[$slug][0]);
            $groupe->setZoneCorps(CorrespondanceWger::GROUPES[$slug][1]);
            $this->em->persist($groupe);
            $this->em->flush();
        }

        return $groupe;
    }
}
