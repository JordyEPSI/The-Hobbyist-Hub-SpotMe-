<?php

namespace App\Service;

use App\Entity\ExerciceProgramme;
use App\Entity\JourProgramme;
use App\Entity\Programme;
use App\Repository\ExerciceRepository;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GestionProgrammes
{
    public const MAX_JOURS = 7;
    public const MAX_EXERCICES_PAR_JOUR = 15;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProgrammeRepository $programmes,
        private readonly ExerciceRepository $exercices,
        private readonly SluggerInterface $slugger,
        private readonly ValidatorInterface $validator,
    ) {
    }

    // Enregistre les informations d'un programme (titre, niveau...), avec un slug calculé à la création
    public function enregistrer(Programme $programme): void
    {
        if ($programme->getId() === null) {
            $programme->setSlug($this->slugUnique($programme->getTitre()));
            $programme->setDateCreation(new \DateTimeImmutable());
        } else {
            $programme->setDateModification(new \DateTime());
        }

        $this->em->persist($programme);
        $this->em->flush();
    }

    // Remplace les jours et exercices du programme par ceux envoyés par la page de construction.
    // Retourne la liste des erreurs : si elle n'est pas vide, rien n'est enregistré.
    public function enregistrerStructure(Programme $programme, array $jours): array
    {
        if (count($jours) > self::MAX_JOURS) {
            return ['Un programme ne peut pas dépasser '.self::MAX_JOURS.' jours.'];
        }

        // 1. On construit les nouveaux jours en mémoire et on les vérifie
        $nouveauxJours = [];
        $erreurs = [];
        $positionJour = 0;

        foreach ($jours as $donneesJour) {
            // Données envoyées à la main et mal formées : on refuse
            if (!is_array($donneesJour)) {
                return ['Données du programme invalides.'];
            }

            $jour = new JourProgramme();
            $jour->setNom($this->texte($donneesJour, 'nom'));
            $jour->setPosition($positionJour);
            $positionJour++;
            $erreurs = array_merge($erreurs, $this->verifier($jour, 'Jour '.$positionJour));

            $lignes = $donneesJour['exercices'] ?? [];
            if (!is_array($lignes)) {
                return ['Données du programme invalides.'];
            }
            if (count($lignes) > self::MAX_EXERCICES_PAR_JOUR) {
                $erreurs[] = 'Jour '.$positionJour.' : '.self::MAX_EXERCICES_PAR_JOUR.' exercices maximum.';
                continue;
            }

            $positionLigne = 0;
            foreach ($lignes as $donneesLigne) {
                if (!is_array($donneesLigne)) {
                    return ['Données du programme invalides.'];
                }

                $exercice = $this->exercices->find($this->entier($donneesLigne, 'exercice'));
                if ($exercice === null) {
                    $erreurs[] = 'Jour '.$positionJour.' : exercice introuvable.';
                    continue;
                }

                $ligne = new ExerciceProgramme();
                $ligne->setExercice($exercice);
                $ligne->setPosition($positionLigne);
                $ligne->setSeriesCibles($this->entier($donneesLigne, 'series'));
                $ligne->setRepsCibles($this->texte($donneesLigne, 'reps'));
                $ligne->setReposSecondes($this->entier($donneesLigne, 'repos'));
                $positionLigne++;

                $erreurs = array_merge($erreurs, $this->verifier($ligne, $exercice->getNom()));
                $jour->addExerciceProgramme($ligne);
            }

            $nouveauxJours[] = $jour;
        }

        if (count($erreurs) > 0) {
            return $erreurs;
        }

        // 2. Tout est valide : on supprime les anciens jours puis on ajoute les nouveaux
        foreach ($programme->getJourProgrammes()->toArray() as $ancienJour) {
            $programme->removeJourProgramme($ancienJour);
        }
        foreach ($nouveauxJours as $jour) {
            $programme->addJourProgramme($jour);
            $this->em->persist($jour);
            foreach ($jour->getExerciceProgrammes() as $ligne) {
                $this->em->persist($ligne);
            }
        }

        $programme->setDateModification(new \DateTime());
        $this->em->flush();

        return [];
    }

    // Retourne le texte envoyé pour cette clé, ou '' s'il manque ou n'est pas un texte.
    // Sécurité : une requête trafiquée peut envoyer un tableau à la place d'un texte.
    private function texte(array $donnees, string $cle): string
    {
        if (!isset($donnees[$cle]) || !is_string($donnees[$cle])) {
            return '';
        }

        return trim($donnees[$cle]);
    }

    // Retourne le nombre entier envoyé pour cette clé, ou 0 s'il manque ou n'est pas un nombre
    private function entier(array $donnees, string $cle): int
    {
        if (!isset($donnees[$cle]) || !is_numeric($donnees[$cle])) {
            return 0;
        }

        return (int) $donnees[$cle];
    }

    // Valide une entité avec ses contraintes #[Assert] et retourne les messages d'erreur
    private function verifier(object $entite, string $contexte): array
    {
        $messages = [];
        foreach ($this->validator->validate($entite) as $violation) {
            $messages[] = $contexte.' : '.$violation->getMessage();
        }

        return $messages;
    }

    // Retourne un slug libre à partir du titre : full-body, puis full-body-2...
    private function slugUnique(string $titre): string
    {
        $base = $this->slugger->slug($titre)->lower()->truncate(110)->toString();
        $slug = $base;
        $numero = 2;

        // Titre sans lettre ni chiffre (ex : « !!! ») : le slug serait vide
        if ($slug === '') {
            $slug = 'programme';
            $base = 'programme';
        }

        while ($this->programmes->findOneBy(['slug' => $slug]) !== null) {
            $slug = $base.'-'.$numero;
            $numero++;
        }

        return $slug;
    }
}
