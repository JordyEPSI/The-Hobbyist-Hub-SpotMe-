<?php

namespace App\Controller\Admin;

use App\Repository\CommentaireRepository;
use App\Repository\ExerciceRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\SeanceRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class TableauDeBordController extends AbstractController
{
    #[Route('', name: 'admin_tableau_de_bord')]
    public function index(
        UtilisateurRepository $utilisateurs,
        ExerciceRepository $exercices,
        ProgrammeRepository $programmes,
        SeanceRepository $seances,
        CommentaireRepository $commentaires,
    ): Response {
        return $this->render('admin/tableau_de_bord.html.twig', [
            'nb_utilisateurs' => $utilisateurs->count([]),
            'nb_admins' => $utilisateurs->compterAdmins(),
            'nb_exercices' => $exercices->count([]),
            'nb_programmes' => $programmes->count([]),
            'nb_seances' => $seances->count([]),
            'nb_commentaires' => $commentaires->count([]),
        ]);
    }
}
