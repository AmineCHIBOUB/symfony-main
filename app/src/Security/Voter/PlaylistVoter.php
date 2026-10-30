<?php

namespace App\Security\Voter;

use App\Entity\Playlist;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * VIEW : playlist publique, ou privée pour son propriétaire.
 * EDIT : propriétaire ou administrateur.
 *
 * @extends Voter<string, Playlist>
 */
final class PlaylistVoter extends Voter
{
    public const VIEW = 'PLAYLIST_VIEW';
    public const EDIT = 'PLAYLIST_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof Playlist;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::VIEW === $attribute && $subject->isPublic()) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $user->isAdmin() || $subject->getOwner() === $user;
    }
}
