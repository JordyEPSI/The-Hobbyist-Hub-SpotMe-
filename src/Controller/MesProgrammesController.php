<?php

namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Utilisateur;
use App\Form\ProgrammeType;
use App\Repository\ExerciceRepository;
use App\Repository\ProgrammeRepository;
use App\Security\Voter\ProgrammeVoter;
use App\Service\GestionProgrammes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Création et modification des programmes du membre connecté
#[Route('/mes-programmes')]
#[IsGranted('ROLE_USER')]
class MesProgrammesController extends AbstractController
{
    // Liste de mes programmes, publics et privés
    #[Route('', name: 'app_mes_programmes', methods: ['GET'])]
    public function liste(#[CurrentUser] Utilisateur $utilisateur, ProgrammeRepository $programmes): Response
    {
        return $this->render('mes_programmes/liste.html.twig', [
            'programmes' => $programmes->findBy(['utilisateur' => $utilisateur], ['dateCreation' => 'DESC']),
        ]);
    }

    // Étape 1 de la création : titre, objectif, niveau, public ou privé
    #[Route('/nouveau', name: 'app_programme_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(#[CurrentUser] Utilisateur $utilisateur, Request $request, GestionProgrammes $gestion): Response
    {
        $programme = new Programme();
        $programme->setUtilisateur($utilisateur);
        $programme->setEstPublic(true);

        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gestion->enregistrer($programme);
            $this->addFlash('succes', 'Programme créé ! Glisse maintenant tes exercices dans les jours.');

            // Étape 2 : la page de construction en glisser-déposer
            return $this->redirectToRoute('app_programme_construire', ['slug' => $programme->getSlug()]);
        }

        return $this->render('mes_programmes/formulaire.html.twig', [
            'form' => $form,
            'programme' => $programme,
        ]);
    }

    // Modification des informations générales d'un programme
    #[Route('/{slug:programme}/modifier', name: 'app_programme_modifier', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[IsGranted(ProgrammeVoter::MODIFIER, 'programme')]
    public function modifier(Programme $programme, Request $request, GestionProgrammes $gestion): Response
    {
        $form = $this->createForm(ProgrammeType::class, $programme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gestion->enregistrer($programme);
            $this->addFlash('succes', 'Programme modifié.');

            return $this->redirectToRoute('app_programme_fiche', ['slug' => $programme->getSlug()]);
        }

        return $this->render('mes_programmes/formulaire.html.twig', [
            'form' => $form,
            'programme' => $programme,
        ]);
    }

    // Page de construction : on glisse les exercices du catalogue dans les jours du programme
    #[Route('/{slug:programme}/construire', name: 'app_programme_construire', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[IsGranted(ProgrammeVoter::MODIFIER, 'programme')]
    public function construire(Programme $programme, Request $request, ExerciceRepository $exercices, GestionProgrammes $gestion): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('construire-'.$programme->getId(), $request->getPayload()->getString('_token'))) {
                $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

                return $this->redirectToRoute('app_programme_construire', ['slug' => $programme->getSlug()]);
            }

            // Le JavaScript envoie les jours sous la forme jours[0][nom], jours[0][exercices][0][series]...
            $erreurs = $gestion->enregistrerStructure($programme, $request->getPayload()->all('jours'));

            if (count($erreurs) === 0) {
                $this->addFlash('succes', 'Programme enregistré.');

                return $this->redirectToRoute('app_programme_fiche', ['slug' => $programme->getSlug()]);
            }

            foreach ($erreurs as $erreur) {
                $this->addFlash('erreur', $erreur);
            }

            return $this->redirectToRoute('app_programme_construire', ['slug' => $programme->getSlug()]);
        }

        return $this->render('mes_programmes/construire.html.twig', [
            'programme' => $programme,
            'exercices' => $exercices->findCatalogue(),
            'max_jours' => GestionProgrammes::MAX_JOURS,
        ]);
    }

    // Suppression d'un programme par son auteur ou un admin, protégée par un jeton CSRF
    #[Route('/{slug:programme}/supprimer', name: 'app_programme_supprimer', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[IsGranted(ProgrammeVoter::SUPPRIMER, 'programme')]
    public function supprimer(Programme $programme, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-programme-'.$programme->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('app_programme_fiche', ['slug' => $programme->getSlug()]);
        }

        $em->remove($programme);
        $em->flush();
        $this->addFlash('succes', 'Programme supprimé.');

        // Un admin qui modère revient au Back-Office, l'auteur à sa liste
        if ($programme->getUtilisateur() !== $this->getUser()) {
            return $this->redirectToRoute('admin_programme_liste');
        }

        return $this->redirectToRoute('app_mes_programmes');
    }
}
