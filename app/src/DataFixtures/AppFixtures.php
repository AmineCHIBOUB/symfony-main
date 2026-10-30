<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de démonstration : 2 comptes, 6 catégories, 18 morceaux, des favoris et des playlists.
 * Les mots de passe sont des mots de passe de démo, à ne jamais utiliser en production.
 */
class AppFixtures extends Fixture
{
    public const DEMO_PASSWORD = 'demo1234';

    private const CATEGORIES = [
        'Pop' => '#e83e8c',
        'Rock' => '#dc3545',
        'Rap' => '#fd7e14',
        'Électro' => '#0dcaf0',
        'Funk & Soul' => '#ffc107',
        'Variété française' => '#20c997',
    ];

    // [titre, artiste, identifiant YouTube, catégorie]
    private const MUSICS = [
        ['Bohemian Rhapsody', 'Queen', 'fJ9rUzIMcZQ', 'Rock'],
        ['Smells Like Teen Spirit', 'Nirvana', 'hTWKbfoikeg', 'Rock'],
        ['Back In Black', 'AC/DC', 'pAgnJDJN4VA', 'Rock'],
        ['Shape of You', 'Ed Sheeran', 'JGwWNGJdvx8', 'Pop'],
        ['Hello', 'Adele', 'YQHsXMglC9A', 'Pop'],
        ['Never Gonna Give You Up', 'Rick Astley', 'dQw4w9WgXcQ', 'Pop'],
        ['Blinding Lights', 'The Weeknd', '4NRXx6U8ABQ', 'Pop'],
        ['Lose Yourself', 'Eminem', '_Yhyp-_hX2s', 'Rap'],
        ['See You Again', 'Wiz Khalifa ft. Charlie Puth', 'RgKAFK5djSk', 'Rap'],
        ['Gangnam Style', 'PSY', '9bZkp7q19f0', 'Rap'],
        ['Faded', 'Alan Walker', '60ItHLz5WEA', 'Électro'],
        ['Get Lucky', 'Daft Punk', '5NV6Rdv1a3I', 'Électro'],
        ['Titanium', 'David Guetta ft. Sia', 'JRfuAukYTKg', 'Électro'],
        ['Uptown Funk', 'Mark Ronson ft. Bruno Mars', 'OPf0YbXqDm0', 'Funk & Soul'],
        ['Despacito', 'Luis Fonsi ft. Daddy Yankee', 'kJQP7kiw5Fk', 'Funk & Soul'],
        ['Papaoutai', 'Stromae', 'oiKj0Z_Xnjc', 'Variété française'],
        ['Alors on danse', 'Stromae', 'VHoT4N43jK8', 'Variété française'],
        ['Djadja', 'Aya Nakamura', 'iPGgnzc34tY', 'Variété française'],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser('admin@youmusic.local', 'Admin', ['ROLE_ADMIN']);
        $user = $this->createUser('user@youmusic.local', 'Mélomane');
        $manager->persist($admin);
        $manager->persist($user);

        $categories = [];
        foreach (self::CATEGORIES as $name => $color) {
            $categories[$name] = (new Category())->setName($name)->setColor($color);
            $manager->persist($categories[$name]);
        }

        $musics = [];
        foreach (self::MUSICS as $i => [$name, $artist, $youtubeId, $category]) {
            $music = (new Music())
                ->setName($name)
                ->setAuteur($artist)
                ->setUrl('https://www.youtube.com/watch?v='.$youtubeId)
                ->setCategory($categories[$category])
                ->setOwner(0 === $i % 3 ? $user : $admin);
            $manager->persist($music);
            $musics[$name] = $music;
        }

        foreach (['Bohemian Rhapsody', 'Get Lucky', 'Papaoutai', 'Uptown Funk'] as $name) {
            $user->addFavorite($musics[$name]);
        }
        foreach (['Bohemian Rhapsody', 'Blinding Lights'] as $name) {
            $admin->addFavorite($musics[$name]);
        }

        $this->createPlaylist($manager, $user, 'Soirée entre amis', 'De quoi faire danser tout le monde.', true,
            [$musics['Uptown Funk'], $musics['Get Lucky'], $musics['Alors on danse'], $musics['Despacito']]);
        $this->createPlaylist($manager, $user, 'Classiques du rock', null, true,
            [$musics['Bohemian Rhapsody'], $musics['Smells Like Teen Spirit'], $musics['Back In Black']]);
        $this->createPlaylist($manager, $user, 'Ma playlist privée', 'Visible uniquement par moi.', false,
            [$musics['Hello'], $musics['Faded']]);

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, string $username, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setUsername($username)->setRoles($roles);

        return $user->setPassword($this->hasher->hashPassword($user, self::DEMO_PASSWORD));
    }

    /**
     * @param Music[] $musics
     */
    private function createPlaylist(ObjectManager $manager, User $owner, string $name, ?string $description, bool $public, array $musics): void
    {
        $playlist = (new Playlist())
            ->setName($name)
            ->setDescription($description)
            ->setIsPublic($public);
        $owner->addPlaylist($playlist);

        foreach ($musics as $music) {
            $playlist->addMusic($music);
        }

        $manager->persist($playlist);
    }
}
