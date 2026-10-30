<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;

final class UtilisateurTest extends AppWebTestCase
{
    public function testInscriptionPuisConnexionAutomatique(): void
    {
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm("S'inscrire", [
            'registration_form[username]' => 'Nouveau',
            'registration_form[email]' => 'nouveau@test.fr',
            'registration_form[plainPassword][first]' => 'motdepasse123',
            'registration_form[plainPassword][second]' => 'motdepasse123',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.navbar', 'Nouveau');

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'nouveau@test.fr']);
        self::assertNotNull($user);
        self::assertNotSame('motdepasse123', $user->getPassword(), 'Le mot de passe doit être haché.');
    }

    public function testInscriptionRefuseUnEmailDejaUtilise(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm("S'inscrire", [
            'registration_form[email]' => 'user@youmusic.local',
            'registration_form[plainPassword][first]' => 'motdepasse123',
            'registration_form[plainPassword][second]' => 'motdepasse123',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Un compte existe déjà');
    }

    public function testConnexionParFormulaire(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'user@youmusic.local',
            '_password' => AppFixtures::DEMO_PASSWORD,
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.navbar', 'Mélomane');
    }

    public function testConnexionAvecMauvaisMotDePasse(): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', [
            '_username' => 'user@youmusic.local',
            '_password' => 'mauvais',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }

    public function testAjouterUnMorceau(): void
    {
        $this->connecter('user@youmusic.local');
        $this->client->request('GET', '/music/new');

        $this->client->submitForm('Ajouter au catalogue', [
            'music[name]' => 'Thriller',
            'music[auteur]' => 'Michael Jackson',
            'music[url]' => 'https://www.youtube.com/watch?v=sOnqjkJTMaA',
        ]);

        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Thriller']);
        self::assertNotNull($music);
        self::assertSame('user@youmusic.local', $music->getOwner()->getEmail());
        self::assertResponseRedirects('/music/'.$music->getId());
    }

    public function testFormulaireMorceauInvalide(): void
    {
        $this->connecter('user@youmusic.local');
        $this->client->request('GET', '/music/new');
        $this->client->submitForm('Ajouter au catalogue', [
            'music[name]' => '',
            'music[url]' => 'pas-une-url',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Le titre est obligatoire.');
    }

    public function testOnNePeutPasModifierLeMorceauDUnAutre(): void
    {
        // « Smells Like Teen Spirit » appartient à l'administrateur
        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Smells Like Teen Spirit']);
        $this->connecter('user@youmusic.local');

        $this->client->request('GET', '/music/'.$music->getId().'/edit');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/music/'.$music->getId().'/delete');
        self::assertResponseStatusCodeSame(403);
    }

    public function testModifierEtSupprimerSonPropreMorceau(): void
    {
        // « Bohemian Rhapsody » appartient à l'utilisateur de démo
        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Bohemian Rhapsody']);
        $id = $music->getId();
        $this->connecter('user@youmusic.local');

        $this->client->request('GET', '/music/'.$id.'/edit');
        $this->client->submitForm('Enregistrer', ['music[auteur]' => 'Queen (live)']);
        self::assertResponseRedirects('/music/'.$id);

        $this->client->request('GET', '/music/'.$id);
        $this->client->submitForm('Supprimer');
        self::assertResponseRedirects('/music');

        $this->em->clear();
        self::assertNull($this->em->getRepository(Music::class)->find($id));
        // le morceau a disparu des playlists sans erreur de clé étrangère
        $playlist = $this->em->getRepository(Playlist::class)->findOneBy(['name' => 'Classiques du rock']);
        self::assertCount(2, $playlist->getMusics());
    }

    public function testAjouterEtRetirerUnFavori(): void
    {
        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Hello']);
        $this->connecter('user@youmusic.local');

        $crawler = $this->client->request('GET', '/music/'.$music->getId());
        $this->client->submit($crawler->filter('button[title="Ajouter aux favoris"]')->form());
        $crawler = $this->client->followRedirect();
        self::assertSelectorExists('button[title="Retirer des favoris"]');

        $this->client->submit($crawler->filter('button[title="Retirer des favoris"]')->form());
        $this->client->followRedirect();
        self::assertSelectorExists('button[title="Ajouter aux favoris"]');
    }

    public function testCreerUnePlaylistEtYAjouterUnMorceau(): void
    {
        $this->connecter('user@youmusic.local');
        $this->client->request('GET', '/playlists/new');
        $this->client->submitForm('Créer la playlist', [
            'playlist[name]' => 'Sport',
            'playlist[isPublic]' => true,
        ]);

        $playlist = $this->em->getRepository(Playlist::class)->findOneBy(['name' => 'Sport']);
        self::assertNotNull($playlist);

        $music = $this->em->getRepository(Music::class)->findOneBy(['name' => 'Titanium']);
        $this->client->request('GET', '/music/'.$music->getId());
        $this->client->submitForm('Ajouter', ['playlist' => $playlist->getId()]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Ajouté à « Sport »');

        $this->client->request('GET', '/playlists/'.$playlist->getId());
        self::assertSelectorTextContains('.list-group', 'Titanium');
    }

    public function testEspacePersonnel(): void
    {
        $this->connecter('user@youmusic.local');
        $this->client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mélomane');
        self::assertSelectorTextContains('#favoris', 'Papaoutai');
    }

    public function testAdministrationReserveeAuxAdmins(): void
    {
        $this->connecter('user@youmusic.local');
        $this->client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);

        $this->connecter('admin@youmusic.local');
        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Morceaux par catégorie');
    }
}
