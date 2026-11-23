<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Entity\Serie;
use App\Entity\Utilisateur;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use App\Security\Voter\SeanceVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/seances')]
#[IsGranted('ROLE_USER')]
class SeanceController extends AbstractController
{
    // Journal : les séances du membre connecté, de la plus récente à la plus ancienne
    #[Route('', name: 'app_seance_liste', methods: ['GET'])]
    public function liste(#[CurrentUser] Utilisateur $utilisateur, SeanceRepository $seances): Response
    {
        return $this->render('seance/liste.html.twig', [
            'seances' => $seances->findJournal($utilisateur),
        ]);
    }

    // Nouvelle séance, datée d'aujourd'hui, avec une première ligne de série vide
    #[Route('/nouvelle', name: 'app_seance_nouvelle', methods: ['GET', 'POST'])]
    public function nouvelle(#[CurrentUser] Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        $seance = new Seance();
        $seance->setUtilisateur($utilisateur);
        $seance->setDateSeance(new \DateTimeImmutable('today'));
        $seance->addSeries(new Serie());

        return $this->formulaire($seance, $request, $em, 'Séance enregistrée, bien joué !');
    }

    // Détail d'une séance (le voter vérifie que c'est bien celle du membre)
    #[Route('/{id}', name: 'app_seance_fiche', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(SeanceVoter::GERER, 'seance')]
    public function fiche(Seance $seance): Response
    {
        return $this->render('seance/fiche.html.twig', [
            'seance' => $seance,
        ]);
    }

    // Modification d'une séance
    #[Route('/{id}/modifier', name: 'app_seance_modifier', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(SeanceVoter::GERER, 'seance')]
    public function modifier(Seance $seance, Request $request, EntityManagerInterface $em): Response
    {
        return $this->formulaire($seance, $request, $em, 'Séance modifiée.');
    }

    // Suppression d'une séance, protégée par un jeton CSRF
    #[Route('/{id}/supprimer', name: 'app_seance_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted(SeanceVoter::GERER, 'seance')]
    public function supprimer(Seance $seance, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-seance-'.$seance->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('app_seance_fiche', ['id' => $seance->getId()]);
        }

        $em->remove($seance);
        $em->flush();
        $this->addFlash('succes', 'Séance supprimée.');

        return $this->redirectToRoute('app_seance_liste');
    }

    // Affiche le formulaire, puis enregistre la séance s'il est valide
    private function formulaire(Seance $seance, Request $request, EntityManagerInterface $em, string $message): Response
    {
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($seance);
            $em->flush();
            $this->addFlash('succes', $message);

            return $this->redirectToRoute('app_seance_fiche', ['id' => $seance->getId()]);
        }

        return $this->render('seance/formulaire.html.twig', [
            'form' => $form,
            'seance' => $seance,
        ]);
    }
}
