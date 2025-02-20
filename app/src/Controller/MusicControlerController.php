<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\MusicRepository;

final class MusicControlerController extends AbstractController
{
    #[Route('/', name: 'app_music_controler')]
    public function index(
        MusicRepository $musicRepo
    ): Response
    {
        $musics=$musicRepo->findAll();
        return $this->render('music_controler/index.html.twig', [
            'musicLists' => $musics,
        ]);
    }
}
