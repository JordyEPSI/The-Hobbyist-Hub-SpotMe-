<?php

namespace App\Security\Voter;

use App\Entity\Seance;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Une séance n'est accessible qu'à son auteur
class SeanceVoter extends Voter
{
    public const GERER = 'SEANCE_GERER';

    // Ce voter ne répond qu'à SEANCE_GERER sur une séance
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::GERER && $subject instanceof Seance;
    }

    // Autorisé seulement si l'utilisateur connecté est l'auteur de la séance
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $subject->getUtilisateur() === $token->getUser();
    }
}
