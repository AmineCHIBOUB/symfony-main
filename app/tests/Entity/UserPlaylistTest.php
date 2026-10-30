<?php

namespace App\Tests\Entity;

use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserPlaylistTest extends TestCase
{
    public function testToutUtilisateurALeRoleUser(): void
    {
        $user = new User();
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertFalse($user->isAdmin());

        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertTrue($user->isAdmin());
    }

    public function testNomAfficheUtiliseLePseudoSinonLEmail(): void
    {
        $user = (new User())->setEmail('jean.dupont@exemple.fr');
        self::assertSame('jean.dupont', $user->getDisplayName());

        $user->setUsername('Jeannot');
        self::assertSame('Jeannot', $user->getDisplayName());
    }

    public function testFavorisSansDoublon(): void
    {
        $user = new User();
        $music = new Music();

        $user->addFavorite($music)->addFavorite($music);
        self::assertCount(1, $user->getFavorites());
        self::assertTrue($user->hasFavorite($music));

        $user->removeFavorite($music);
        self::assertFalse($user->hasFavorite($music));
    }

    public function testPlaylistSansDoublonEtProprietaire(): void
    {
        $user = new User();
        $playlist = new Playlist();
        $music = new Music();

        $user->addPlaylist($playlist);
        $playlist->addMusic($music)->addMusic($music);

        self::assertSame($user, $playlist->getOwner());
        self::assertCount(1, $playlist->getMusics());
        self::assertFalse($playlist->isPublic(), 'Une playlist est privée par défaut.');

        $playlist->removeMusic($music);
        self::assertFalse($playlist->hasMusic($music));
    }
}
