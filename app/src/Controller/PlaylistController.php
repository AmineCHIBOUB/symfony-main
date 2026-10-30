<?php

namespace App\Controller;

use App\Entity\Music;
use App\Entity\Playlist;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use App\Security\Voter\PlaylistVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/playlists')]
final class PlaylistController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_playlist_index', methods: ['GET'])]
    public function index(PlaylistRepository $playlistRepo): Response
    {
        return $this->render('playlist/index.html.twig', [
            'publicPlaylists' => $playlistRepo->findPublic(),
        ]);
    }

    #[Route('/new', name: 'app_playlist_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $playlist = (new Playlist())->setOwner($this->getUser());
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($playlist);
            $this->em->flush();
            $this->addFlash('success', 'Playlist créée. Ajoutez-y des morceaux depuis le catalogue.');

            return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()]);
        }

        return $this->render('playlist/form.html.twig', ['form' => $form, 'playlist' => null]);
    }

    #[Route('/{id}', name: 'app_playlist_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(PlaylistVoter::VIEW, subject: 'playlist')]
    public function show(#[MapEntity(id: 'id')] Playlist $playlist): Response
    {
        return $this->render('playlist/show.html.twig', ['playlist' => $playlist]);
    }

    #[Route('/{id}/edit', name: 'app_playlist_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted(PlaylistVoter::EDIT, subject: 'playlist')]
    public function edit(#[MapEntity(id: 'id')] Playlist $playlist, Request $request): Response
    {
        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Playlist mise à jour.');

            return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()]);
        }

        return $this->render('playlist/form.html.twig', ['form' => $form, 'playlist' => $playlist]);
    }

    #[Route('/{id}/delete', name: 'app_playlist_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(PlaylistVoter::EDIT, subject: 'playlist')]
    public function delete(#[MapEntity(id: 'id')] Playlist $playlist, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete-playlist-'.$playlist->getId(), $request->getPayload()->getString('_token'))) {
            $this->em->remove($playlist);
            $this->em->flush();
            $this->addFlash('success', 'Playlist supprimée.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}/remove/{music}', name: 'app_playlist_remove_music', requirements: ['id' => '\d+', 'music' => '\d+'], methods: ['POST'])]
    #[IsGranted(PlaylistVoter::EDIT, subject: 'playlist')]
    public function removeMusic(
        #[MapEntity(id: 'id')] Playlist $playlist,
        #[MapEntity(id: 'music')] Music $music,
        Request $request,
    ): Response {
        if ($this->isCsrfTokenValid('remove-'.$playlist->getId().'-'.$music->getId(), $request->getPayload()->getString('_token'))) {
            $playlist->removeMusic($music);
            $this->em->flush();
            $this->addFlash('info', sprintf('« %s » retiré de la playlist.', $music->getName()));
        }

        return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()]);
    }
}
