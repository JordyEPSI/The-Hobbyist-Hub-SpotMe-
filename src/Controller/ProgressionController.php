<?php

namespace App\Controller;

use App\Entity\Exercice;
use App\Entity\Utilisateur;
use App\Repository\ExerciceRepository;
use App\Repository\SerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/progression')]
#[IsGranted('ROLE_USER')]
class ProgressionController extends AbstractController
{
    // Page Progression : liste des exercices déjà pratiqués par le membre
    #[Route('', name: 'app_progression', methods: ['GET'])]
    public function index(#[CurrentUser] Utilisateur $utilisateur, ExerciceRepository $exercices): Response
    {
        return $this->render('progression/index.html.twig', [
            'exercices' => $exercices->findPratiquesPar($utilisateur),
        ]);
    }

    // Données des graphiques (Chart.js), uniquement les séries du membre connecté
    #[Route('/donnees/{id}', name: 'app_progression_donnees', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function donnees(#[CurrentUser] Utilisateur $utilisateur, Exercice $exercice, SerieRepository $series): JsonResponse
    {
        return $this->json([
            'exercice' => $exercice->getNom(),
            'points' => $series->findProgression($utilisateur, $exercice),
        ]);
    }
}
