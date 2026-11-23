<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use App\Repository\CommentaireRepository;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Modération de la communauté : programmes et commentaires
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class ModerationController extends AbstractController
{
    // Tous les programmes, publics et privés (la suppression passe par MesProgrammesController)
    #[Route('/programmes', name: 'admin_programme_liste', methods: ['GET'])]
    public function programmes(ProgrammeRepository $programmes): Response
    {
        return $this->render('admin/moderation/programmes.html.twig', [
            'programmes' => $programmes->findTousAvecAuteur(),
        ]);
    }

    // Tous les commentaires, du plus récent au plus ancien
    #[Route('/commentaires', name: 'admin_commentaire_liste', methods: ['GET'])]
    public function commentaires(CommentaireRepository $commentaires): Response
    {
        return $this->render('admin/moderation/commentaires.html.twig', [
            'commentaires' => $commentaires->findTousAvecDetails(),
        ]);
    }

    // Masque un commentaire visible, ou réaffiche un commentaire masqué
    #[Route('/commentaires/{id}/masquer', name: 'admin_commentaire_masquer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function masquer(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('masquer-'.$commentaire->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('admin_commentaire_liste');
        }

        if ($commentaire->isEstMasque()) {
            $commentaire->setEstMasque(false);
            $this->addFlash('succes', 'Commentaire de nouveau visible.');
        } else {
            $commentaire->setEstMasque(true);
            $this->addFlash('succes', 'Commentaire masqué.');
        }
        $em->flush();

        return $this->redirectToRoute('admin_commentaire_liste');
    }
}
