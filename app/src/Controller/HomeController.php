<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\MusicRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        MusicRepository $musicRepo,
        CategoryRepository $categoryRepo,
        PlaylistRepository $playlistRepo,
        UserRepository $userRepo,
    ): Response {
        return $this->render('home/index.html.twig', [
            'latest' => $musicRepo->findLatest(8),
            'mostLiked' => $musicRepo->findMostLiked(5),
            'categories' => $categoryRepo->findAllWithCount(),
            'playlists' => $playlistRepo->findPublic(4),
            'stats' => [
                'musics' => $musicRepo->count([]),
                'users' => $userRepo->count([]),
                'playlists' => $playlistRepo->count(['isPublic' => true]),
            ],
        ]);
    }
}
