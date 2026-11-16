<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecuriteController extends AbstractController
{
    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('securite/connexion.html.twig', [
            'dernier_email' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion', methods: ['POST'])]
    public function deconnexion(): never
    {
        // Intercepté par le pare-feu (voir config/packages/security.yaml).
        throw new \LogicException('Cette méthode est interceptée par la clé logout du pare-feu.');
    }

    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        $utilisateur = new Utilisateur();
        $form = $this->createForm(InscriptionType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasseClair = $form->get('motDePasseClair')->getData();
            $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, $motDePasseClair));

            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('succes', 'Compte créé ! Tu peux maintenant te connecter.');

            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('securite/inscription.html.twig', [
            'form' => $form,
        ]);
    }
}
