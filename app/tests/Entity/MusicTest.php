<?php

namespace App\Tests\Entity;

use App\Entity\Music;
use PHPUnit\Framework\TestCase;

final class MusicTest extends TestCase
{
    /**
     * @dataProvider liensYoutube
     */
    public function testExtraitIdentifiantYoutube(string $url, ?string $attendu): void
    {
        $music = (new Music())->setUrl($url);

        self::assertSame($attendu, $music->getYoutubeId());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function liensYoutube(): iterable
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'];
        yield 'watch avec paramètres' => ['https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ&t=42', 'dQw4w9WgXcQ'];
        yield 'lien court' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'];
        yield 'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'];
        yield 'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'];
        yield 'autre site' => ['https://open.spotify.com/track/abc', null];
    }

    public function testVignetteSeulementPourYoutube(): void
    {
        $youtube = (new Music())->setUrl('https://youtu.be/dQw4w9WgXcQ');
        $autre = (new Music())->setUrl('https://soundcloud.com/x/y');

        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $youtube->getThumbnailUrl());
        self::assertNull($autre->getThumbnailUrl());
    }

    public function testDatesInitialiseesALaCreationPuisMisesAJour(): void
    {
        $music = new Music();
        $music->initDates();
        $creation = $music->getCreatedAt();

        self::assertNotNull($creation);
        self::assertNotNull($music->getUpdatedAt());

        $music->initDates();
        self::assertSame($creation, $music->getCreatedAt(), 'La date de création ne doit pas changer.');
    }

    public function testAffichageTitreEtArtiste(): void
    {
        self::assertSame('Hello - Adele', (string) (new Music())->setName('Hello')->setAuteur('Adele'));
        self::assertSame('Hello', (string) (new Music())->setName('Hello'));
    }
}
