<?php

namespace App\Controller\Admin;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use App\Repository\ExerciceRepository;
use App\Service\CatalogueExercices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/exercices')]
#[IsGranted('ROLE_ADMIN')]
class ExerciceController extends AbstractController
{
    // Liste des exercices du catalogue
    #[Route('', name: 'admin_exercice_liste', methods: ['GET'])]
    public function liste(ExerciceRepository $exercices): Response
    {
        return $this->render('admin/exercice/liste.html.twig', [
            'exercices' => $exercices->findCatalogue(),
        ]);
    }

    // Création d'un exercice (on s'assure d'abord que matériels et groupes existent)
    #[Route('/nouveau', name: 'admin_exercice_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, CatalogueExercices $catalogue): Response
    {
        $catalogue->initialiserReferentiel();

        return $this->formulaire(new Exercice(), $request, $catalogue, 'Exercice ajouté au catalogue.');
    }

    // Modification d'un exercice
    #[Route('/{id}/modifier', name: 'admin_exercice_modifier', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function modifier(Exercice $exercice, Request $request, CatalogueExercices $catalogue): Response
    {
        return $this->formulaire($exercice, $request, $catalogue, 'Exercice modifié.');
    }

    // Suppression d'un exercice, protégée par un jeton CSRF
    #[Route('/{id}/supprimer', name: 'admin_exercice_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimer(Exercice $exercice, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-exercice-'.$exercice->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('admin_exercice_liste');
        }

        // Un exercice déjà utilisé reste au catalogue, pour ne pas casser l'historique des membres
        if (count($exercice->getSeries()) > 0 || count($exercice->getExerciceProgrammes()) > 0) {
            $this->addFlash('erreur', '« '.$exercice->getNom().' » est utilisé dans des séances ou des programmes, il ne peut pas être supprimé.');

            return $this->redirectToRoute('admin_exercice_liste');
        }

        $em->remove($exercice);
        $em->flush();
        $this->addFlash('succes', '« '.$exercice->getNom().' » a été supprimé du catalogue.');

        return $this->redirectToRoute('admin_exercice_liste');
    }

    // Affiche le formulaire, puis enregistre l'exercice s'il est valide
    private function formulaire(Exercice $exercice, Request $request, CatalogueExercices $catalogue, string $message): Response
    {
        $form = $this->createForm(ExerciceType::class, $exercice);

        // Pré-coche les muscles déjà associés à l'exercice
        if ($request->isMethod('GET')) {
            $principaux = [];
            $secondaires = [];
            foreach ($exercice->getCiblers() as $cibler) {
                if ($cibler->isEstPrincipal()) {
                    $principaux[] = $cibler->getGroupeMusculaire();
                } else {
                    $secondaires[] = $cibler->getGroupeMusculaire();
                }
            }
            $form->get('musclesPrincipaux')->setData($principaux);
            $form->get('musclesSecondaires')->setData($secondaires);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $catalogue->enregistrer(
                $exercice,
                $form->get('musclesPrincipaux')->getData(),
                $form->get('musclesSecondaires')->getData(),
            );
            $this->addFlash('succes', $message);

            return $this->redirectToRoute('admin_exercice_liste');
        }

        return $this->render('admin/exercice/formulaire.html.twig', [
            'form' => $form,
            'exercice' => $exercice,
        ]);
    }
}
