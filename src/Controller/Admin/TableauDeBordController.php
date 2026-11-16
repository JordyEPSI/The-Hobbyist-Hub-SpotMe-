<?php

namespace App\Controller\Admin;

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
    public function index(UtilisateurRepository $utilisateurs): Response
    {
        return $this->render('admin/tableau_de_bord.html.twig', [
            'nb_utilisateurs' => $utilisateurs->count([]),
            'nb_admins' => $utilisateurs->compterAdmins(),
        ]);
    }
}
