<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Form\CreationCompteType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    #[Route('', name: 'admin_utilisateur_liste', methods: ['GET'])]
    public function liste(UtilisateurRepository $utilisateurs): Response
    {
        return $this->render('admin/utilisateur/liste.html.twig', [
            'utilisateurs' => $utilisateurs->findTousParDateInscription(),
        ]);
    }

    #[Route('/nouveau', name: 'admin_utilisateur_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(CreationCompteType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasse = $form->get('motDePasseClair')->getData();
            $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, $motDePasse));

            if ($form->get('role')->getData() === 'admin') {
                $utilisateur->setRoles(['ROLE_ADMIN']);
            }

            $em->persist($utilisateur);
            $em->flush();
            $this->addFlash('succes', 'Compte de '.$utilisateur->getPseudo().' créé.');

            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        return $this->render('admin/utilisateur/nouveau.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/role-admin', name: 'admin_utilisateur_role_admin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function basculerRoleAdmin(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('role-admin-'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        // Un admin ne peut pas se retirer ses propres droits (évite de se bloquer hors du Back-Office).
        if ($utilisateur === $this->getUser()) {
            $this->addFlash('erreur', 'Tu ne peux pas modifier ton propre rôle.');

            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        if ($utilisateur->isAdmin()) {
            $utilisateur->setRoles([]);
            $this->addFlash('succes', sprintf('%s n\'est plus administrateur.', $utilisateur->getPseudo()));
        } else {
            $utilisateur->setRoles(['ROLE_ADMIN']);
            $this->addFlash('succes', sprintf('%s est maintenant administrateur.', $utilisateur->getPseudo()));
        }

        $em->flush();

        return $this->redirectToRoute('admin_utilisateur_liste');
    }

    #[Route('/{id}/supprimer', name: 'admin_utilisateur_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimer(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-'.$utilisateur->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        if ($utilisateur === $this->getUser()) {
            $this->addFlash('erreur', 'Tu ne peux pas supprimer ton propre compte depuis le Back-Office.');

            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        $pseudo = $utilisateur->getPseudo();
        $em->remove($utilisateur);
        $em->flush();

        $this->addFlash('succes', sprintf('Le compte de %s a été supprimé.', $pseudo));

        return $this->redirectToRoute('admin_utilisateur_liste');
    }
}
