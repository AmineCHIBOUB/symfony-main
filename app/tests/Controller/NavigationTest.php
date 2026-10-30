<?php

namespace App\Tests\Controller;

use App\Entity\Music;
use App\Entity\Playlist;

final class NavigationTest extends AppWebTestCase
{
    public function testAccueilAfficheLesStatistiquesEtLesDerniersAjouts(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Partagez la musique');
        self::assertSelectorTextContains('body', 'Derniers ajouts');
        self::assertSelectorTextContains('body', 'Soirée entre amis');
    }

    public function testCatalogueRechercheEtFiltre(): void
    {
        $crawler = $this->client->request('GET', '/music');
        self::assertResponseIsSuccessful();
        self::assertCount(12, $crawler->filter('.ym-card'), '12 morceaux par page');

        $crawler = $this->client->request('GET', '/music?q=stromae');
        self::assertCount(2, $crawler->filter('.ym-card'));

        $rock = $this->em->getRepository(\App\Entity\Category::class)->findOneBy(['name' => 'Rock']);
        $crawler = $this->client->request('GET', '/music?categorie='.$rock->getId().'&tri=titre');
        self::assertCount(3, $crawler->filter('.ym-card'));
        self::assertSame('Back In Black', trim($crawler->filter('.ym-card h3')->first()->text()));
    }

    public function testCataloguePagine(): void
    {
        $crawler = $this->client->request('GET', '/music?page=2');

        self::assertResponseIsSuccessful();
        self::assertCount(6, $crawler->filter('.ym-card'));
    }

    public function testTriParPopularite(): void
    {
        $crawler = $this->client->request('GET', '/music?tri=populaire');

        self::assertResponseIsSuccessful();
        self::assertSame('Bohemian Rhapsody', trim($crawler->filter('.ym-card h3')->first()->text()));
    }

    public function testFicheMorceauAvecLecteurYoutube(): void
    {
        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Get Lucky']);
        $this->client->request('GET', '/music/'.$music->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('iframe[src*="youtube-nocookie.com/embed/5NV6Rdv1a3I"]');
        self::assertSelectorTextContains('body', 'Dans la même catégorie');
    }

    public function testMorceauInexistant(): void
    {
        $this->client->request('GET', '/music/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPlaylistPubliqueVisibleEtPriveeProtegee(): void
    {
        $repo = $this->em->getRepository(Playlist::class);

        $this->client->request('GET', '/playlists/'.$repo->findOneBy(['name' => 'Classiques du rock'])->getId());
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/playlists/'.$repo->findOneBy(['name' => 'Ma playlist privée'])->getId());
        self::assertResponseRedirects('/login');
    }

    public function testPagesProtegeesRedirigentVersLaConnexion(): void
    {
        foreach (['/music/new', '/profile', '/playlists/new', '/admin'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseRedirects('/login', null, $url);
        }
    }
}
