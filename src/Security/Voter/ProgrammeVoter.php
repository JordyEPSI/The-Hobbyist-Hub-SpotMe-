<?php

namespace App\Security\Voter;

use App\Entity\Programme;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Droits sur un programme : le voir, le modifier, le supprimer
class ProgrammeVoter extends Voter
{
    public const VOIR = 'PROGRAMME_VOIR';
    public const MODIFIER = 'PROGRAMME_MODIFIER';
    public const SUPPRIMER = 'PROGRAMME_SUPPRIMER';

    // Ce voter ne répond qu'à ses trois droits, sur un programme
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Programme) {
            return false;
        }

        return $attribute === self::VOIR || $attribute === self::MODIFIER || $attribute === self::SUPPRIMER;
    }

    // Retourne true si l'utilisateur connecté a le droit demandé
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Tout le monde peut voir un programme public, même sans compte
        if ($attribute === self::VOIR && $subject->isEstPublic()) {
            return true;
        }

        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        $estAuteur = $subject->getUtilisateur() === $utilisateur;

        if ($attribute === self::MODIFIER) {
            return $estAuteur;
        }

        // Voir un programme privé ou supprimer : l'auteur, ou un admin (modération)
        return $estAuteur || $utilisateur->isAdmin();
    }
}
