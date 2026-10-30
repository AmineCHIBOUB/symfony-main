<?php

namespace App\Security\Voter;

use App\Entity\Music;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Seul l'auteur d'un morceau (ou un administrateur) peut le modifier ou le supprimer.
 *
 * @extends Voter<string, Music>
 */
final class MusicVoter extends Voter
{
    public const EDIT = 'MUSIC_EDIT';
    public const DELETE = 'MUSIC_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Music;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $user->isAdmin() || $subject->getOwner() === $user;
    }
}
