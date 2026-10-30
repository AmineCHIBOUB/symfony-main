<?php

namespace App\Tests\Security;

use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;
use App\Security\Voter\MusicVoter;
use App\Security\Voter\PlaylistVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoterTest extends TestCase
{
    private User $proprietaire;
    private User $autre;
    private User $admin;

    protected function setUp(): void
    {
        $this->proprietaire = (new User())->setEmail('proprio@test.fr');
        $this->autre = (new User())->setEmail('autre@test.fr');
        $this->admin = (new User())->setEmail('admin@test.fr')->setRoles(['ROLE_ADMIN']);
    }

    public function testSeulLeProprietaireOuLAdminModifieUnMorceau(): void
    {
        $voter = new MusicVoter();
        $music = (new Music())->setOwner($this->proprietaire);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->proprietaire), $music, [MusicVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->admin), $music, [MusicVoter::DELETE]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($this->autre), $music, [MusicVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $music, [MusicVoter::DELETE]));
    }

    public function testPlaylistPubliqueVisibleParTousMaisModifiableParSonProprietaire(): void
    {
        $voter = new PlaylistVoter();
        $playlist = (new Playlist())->setOwner($this->proprietaire)->setIsPublic(true);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new NullToken(), $playlist, [PlaylistVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($this->autre), $playlist, [PlaylistVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->proprietaire), $playlist, [PlaylistVoter::EDIT]));
    }

    public function testPlaylistPriveeInvisiblePourLesAutres(): void
    {
        $voter = new PlaylistVoter();
        $playlist = (new Playlist())->setOwner($this->proprietaire);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $playlist, [PlaylistVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token($this->autre), $playlist, [PlaylistVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->proprietaire), $playlist, [PlaylistVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token($this->admin), $playlist, [PlaylistVoter::VIEW]));
    }

    public function testAttributInconnuIgnore(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, (new MusicVoter())->vote(new NullToken(), new Music(), ['AUTRE']));
    }

    private function token(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }
}
