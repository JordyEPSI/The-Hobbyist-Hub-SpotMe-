<?php

namespace App\Controller;

use App\Repository\ExerciceRepository;
use App\Repository\GroupeMusculaireRepository;
use App\Repository\MaterielRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exercices')]
class ExerciceController extends AbstractController
{
    // Page catalogue, avec recherche par nom et filtres par muscle et matériel
    #[Route('', name: 'app_exercice_liste', methods: ['GET'])]
    public function liste(Request $request, ExerciceRepository $exercices, GroupeMusculaireRepository $groupes, MaterielRepository $materiels): Response
    {
        $recherche = trim($request->query->getString('q'));
        $groupe = $request->query->getString('groupe');
        $materiel = $request->query->getString('materiel');

        return $this->render('exercice/liste.html.twig', [
            'exercices' => $exercices->findCatalogue($recherche, $groupe, $materiel),
            'groupes' => $groupes->findBy([], ['nom' => 'ASC']),
            'materiels' => $materiels->findBy([], ['nom' => 'ASC']),
            'recherche' => $recherche,
            'groupe' => $groupe,
            'materiel' => $materiel,
        ]);
    }

    // Fiche d'un exercice, retrouvé grâce à son slug dans l'adresse
    #[Route('/{slug}', name: 'app_exercice_fiche', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function fiche(string $slug, ExerciceRepository $exercices): Response
    {
        $exercice = $exercices->findUnAvecDetails($slug);
        if ($exercice === null) {
            throw $this->createNotFoundException('Exercice introuvable.');
        }

        return $this->render('exercice/fiche.html.twig', [
            'exercice' => $exercice,
        ]);
    }
}
