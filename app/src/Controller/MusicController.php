<?php

namespace App\Controller;

use App\Entity\Music;
use App\Entity\Playlist;
use App\Entity\User;
use App\Form\MusicType;
use App\Repository\CategoryRepository;
use App\Repository\MusicRepository;
use App\Security\Voter\MusicVoter;
use App\Security\Voter\PlaylistVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/music')]
final class MusicController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'app_music', methods: ['GET'])]
    public function index(Request $request, MusicRepository $musicRepo, CategoryRepository $categoryRepo): Response
    {
        $q = $request->query->getString('q');
        $tri = $request->query->getString('tri', 'recent');
        $page = max(1, $request->query->getInt('page', 1));
        $category = $request->query->getInt('categorie')
            ? $categoryRepo->find($request->query->getInt('categorie'))
            : null;

        $musics = $musicRepo->search($q, $category, $tri, $page);

        return $this->render('music/index.html.twig', [
            'musics' => $musics,
            'categories' => $categoryRepo->findBy([], ['name' => 'ASC']),
            'q' => $q,
            'tri' => $tri,
            'currentCategory' => $category,
            'page' => $page,
            'pages' => max(1, (int) ceil(count($musics) / MusicRepository::PAR_PAGE)),
        ]);
    }

    #[Route('/new', name: 'app_music_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $music = (new Music())->setOwner($this->getUser());
        $form = $this->createForm(MusicType::class, $music);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($music);
            $this->em->flush();
            $this->addFlash('success', sprintf('« %s » a été ajouté au catalogue.', $music->getName()));

            return $this->redirectToRoute('app_music_show', ['id' => $music->getId()]);
        }

        return $this->render('music/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_music_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[MapEntity(id: 'id')] Music $music, MusicRepository $musicRepo): Response
    {
        return $this->render('music/show.html.twig', [
            'music' => $music,
            'similar' => $musicRepo->findSimilar($music),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_music_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted(MusicVoter::EDIT, subject: 'music')]
    public function edit(#[MapEntity(id: 'id')] Music $music, Request $request): Response
    {
        $form = $this->createForm(MusicType::class, $music);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Morceau mis à jour.');

            return $this->redirectToRoute('app_music_show', ['id' => $music->getId()]);
        }

        return $this->render('music/edit.html.twig', ['form' => $form, 'music' => $music]);
    }

    #[Route('/{id}/delete', name: 'app_music_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(MusicVoter::DELETE, subject: 'music')]
    public function delete(#[MapEntity(id: 'id')] Music $music, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete-music-'.$music->getId(), $request->getPayload()->getString('_token'))) {
            // les liens playlists et favoris sont supprimés par la base (ON DELETE CASCADE)
            $this->em->remove($music);
            $this->em->flush();
            $this->addFlash('success', 'Morceau supprimé.');
        }

        return $this->redirectToRoute('app_music');
    }

    #[Route('/{id}/favorite', name: 'app_music_favorite', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function favorite(#[MapEntity(id: 'id')] Music $music, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('favorite-'.$music->getId(), $request->getPayload()->getString('_token'))) {
            if ($user->hasFavorite($music)) {
                $user->removeFavorite($music);
                $this->addFlash('info', 'Retiré de vos favoris.');
            } else {
                $user->addFavorite($music);
                $this->addFlash('success', 'Ajouté à vos favoris.');
            }
            $this->em->flush();
        }

        return $this->redirectBack($request, 'app_music_show', ['id' => $music->getId()]);
    }

    #[Route('/{id}/playlist', name: 'app_music_add_to_playlist', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addToPlaylist(#[MapEntity(id: 'id')] Music $music, Request $request): Response
    {
        $playlist = $this->em->getRepository(Playlist::class)->find($request->getPayload()->getInt('playlist'));

        if (!$playlist || !$this->isCsrfTokenValid('add-playlist-'.$music->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Choisissez une playlist.');
        } else {
            $this->denyAccessUnlessGranted(PlaylistVoter::EDIT, $playlist);
            if ($playlist->hasMusic($music)) {
                $this->addFlash('info', sprintf('Déjà présent dans « %s ».', $playlist->getName()));
            } else {
                $playlist->addMusic($music);
                $this->em->flush();
                $this->addFlash('success', sprintf('Ajouté à « %s ».', $playlist->getName()));
            }
        }

        return $this->redirectToRoute('app_music_show', ['id' => $music->getId()]);
    }

    /**
     * Revient à la page d'origine (chemin relatif uniquement, pour éviter les redirections externes).
     *
     * @param array<string, mixed> $params
     */
    private function redirectBack(Request $request, string $route, array $params = []): RedirectResponse
    {
        $back = $request->getPayload()->getString('_back');

        if (str_starts_with($back, '/') && !str_starts_with($back, '//')) {
            return $this->redirect($back);
        }

        return $this->redirectToRoute($route, $params);
    }
}
