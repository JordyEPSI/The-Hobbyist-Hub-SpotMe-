<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Noter;
use App\Entity\Programme;
use App\Entity\Utilisateur;
use App\Form\CommentaireType;
use App\Form\NoteType;
use App\Repository\NoterRepository;
use App\Repository\ProgrammeRepository;
use App\Security\Voter\ProgrammeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/programmes')]
class ProgrammeController extends AbstractController
{
    // Liste des programmes publics
    #[Route('', name: 'app_programme_liste', methods: ['GET'])]
    public function liste(ProgrammeRepository $programmes): Response
    {
        return $this->render('programme/liste.html.twig', [
            'programmes' => $programmes->findPublics(),
        ]);
    }

    // Fiche d'un programme, avec les formulaires de commentaire et de note pour les membres
    #[Route('/{slug}', name: 'app_programme_fiche', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function fiche(string $slug, ProgrammeRepository $programmes, NoterRepository $notes): Response
    {
        $programme = $programmes->findUnAvecDetails($slug);
        if ($programme === null) {
            throw $this->createNotFoundException('Programme introuvable.');
        }
        $this->denyAccessUnlessGranted(ProgrammeVoter::VOIR, $programme);

        $formCommentaire = null;
        $formNote = null;
        $utilisateur = $this->getUser();

        if ($utilisateur instanceof Utilisateur) {
            $formCommentaire = $this->createForm(CommentaireType::class, new Commentaire(), [
                'action' => $this->generateUrl('app_programme_commenter', ['slug' => $slug]),
            ]);

            // On ne note pas son propre programme
            if ($programme->getUtilisateur() !== $utilisateur) {
                $maNote = $notes->findOneBy(['utilisateur' => $utilisateur, 'programme' => $programme]);
                $formNote = $this->createForm(NoteType::class, $maNote ?? new Noter(), [
                    'action' => $this->generateUrl('app_programme_noter', ['slug' => $slug]),
                ]);
            }
        }

        return $this->render('programme/fiche.html.twig', [
            'programme' => $programme,
            'form_commentaire' => $formCommentaire,
            'form_note' => $formNote,
        ]);
    }

    // Ajoute un commentaire sur un programme visible par le membre
    #[Route('/{slug:programme}/commenter', name: 'app_programme_commenter', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[IsGranted('ROLE_USER')]
    public function commenter(Programme $programme, #[CurrentUser] Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProgrammeVoter::VOIR, $programme);

        // Champs remplis avant la validation, qui les vérifie aussi
        $commentaire = new Commentaire();
        $commentaire->setProgramme($programme);
        $commentaire->setUtilisateur($utilisateur);
        $commentaire->setDateCreation(new \DateTimeImmutable());
        $commentaire->setEstMasque(false);

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($commentaire);
            $em->flush();
            $this->addFlash('succes', 'Commentaire publié.');
        } else {
            $this->addFlash('erreur', 'Ton commentaire est vide ou trop long (2000 caractères maximum).');
        }

        return $this->redirectToRoute('app_programme_fiche', ['slug' => $programme->getSlug()]);
    }

    // Donne ou modifie sa note (1 à 5) sur le programme d'un autre membre
    #[Route('/{slug:programme}/noter', name: 'app_programme_noter', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[IsGranted('ROLE_USER')]
    public function noter(Programme $programme, #[CurrentUser] Utilisateur $utilisateur, Request $request, NoterRepository $notes, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(ProgrammeVoter::VOIR, $programme);
        $retour = $this->redirectToRoute('app_programme_fiche', ['slug' => $programme->getSlug()]);

        if ($programme->getUtilisateur() === $utilisateur) {
            $this->addFlash('erreur', 'Tu ne peux pas noter ton propre programme.');

            return $retour;
        }

        // Une seule note par membre : on reprend l'ancienne s'il en a déjà mis une
        $note = $notes->findOneBy(['utilisateur' => $utilisateur, 'programme' => $programme]);
        if ($note === null) {
            $note = new Noter();
            $note->setUtilisateur($utilisateur);
            $note->setProgramme($programme);
        }
        $note->setDateNote(new \DateTimeImmutable());

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($note);
            $em->flush();
            $this->addFlash('succes', 'Merci pour ta note !');
        } else {
            $this->addFlash('erreur', 'Choisis une note entre 1 et 5.');
        }

        return $retour;
    }
}
